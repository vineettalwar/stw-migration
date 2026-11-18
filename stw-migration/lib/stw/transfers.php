<?php
/** STW Migration, GPL-3.0-or-later. Modified 2026-11-18. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Serve backups through WordPress authorization, streaming with bounded memory. */
function stw_download_archive() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You cannot download site backups.', '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'stw_download' );
	try {
		$archive = isset( $_GET['archive'] ) && is_string( $_GET['archive'] ) ? wp_unslash( $_GET['archive'] ) : '';
		$path    = ai1wm_backup_path( array( 'archive' => $archive ) );
		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			throw new RuntimeException( 'Backup not found.' );
		}
	} catch ( Throwable $error ) {
		wp_die( 'Backup unavailable.', '', array( 'response' => 404 ) );
	}
	while ( ob_get_level() ) {
		ob_end_clean();
	}
	nocache_headers();
	header( 'Content-Type: application/octet-stream' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $archive ) . '"' );
	header( 'Content-Length: ' . filesize( $path ) );
	readfile( $path );
	exit;
}

/** Append a chunk exactly once, even if a successful response was lost and retried. */
function stw_write_upload_chunk( $source, $destination, $offset, $total ) {
	if ( ! is_scalar( $offset ) || ! is_scalar( $total ) || ! ctype_digit( (string) $offset ) || ! ctype_digit( (string) $total ) ) {
		throw new Ai1wm_Import_Retry_Exception( 'Invalid upload offset.', 400 );
	}
	$offset = (int) $offset;
	$total  = (int) $total;
	$size   = filesize( $source );
	if ( $size <= 0 || $total <= 0 || $offset < 0 || $offset > $total || $size > $total - $offset ) {
		throw new Ai1wm_Import_Retry_Exception( 'Upload chunk exceeds declared archive size.', 400 );
	}
	$target = fopen( $destination, 'c+b' );
	if ( ! $target ) {
		throw new Ai1wm_Import_Retry_Exception( 'Cannot write archive.', 400 );
	}
	$mutex = stw_acquire_database_lock( 'upload:' . $destination, 1 );
	if ( ! $mutex ) {
		fclose( $target );
		throw new Ai1wm_Import_Retry_Exception( 'Upload is busy; retry the same chunk.', 409 );
	}
	try {
		$current = fstat( $target )['size'];
		if ( $current < $offset ) {
			throw new Ai1wm_Import_Retry_Exception( 'Upload chunk arrived out of order.', 409 );
		}
		if ( $current >= $offset + $size ) {
			fseek( $target, $offset );
			$hash = hash_init( 'sha256' );
			hash_update_stream( $hash, $target, $size );
			if ( ! hash_equals( hash_file( 'sha256', $source ), hash_final( $hash ) ) ) {
				throw new Ai1wm_Import_Retry_Exception( 'Retried chunk conflicts with uploaded data.', 409 );
			}
			return;
		}
		if ( ! ftruncate( $target, $offset ) || fseek( $target, $offset ) !== 0 ) {
			throw new Ai1wm_Import_Retry_Exception( 'Cannot resume upload.', 400 );
		}
		$input = fopen( $source, 'rb' );
		if ( ! $input ) {
			throw new Ai1wm_Import_Retry_Exception( 'Cannot read upload.', 400 );
		}
		try {
			$written = stream_copy_to_stream( $input, $target );
		} finally {
			fclose( $input );
		}
		if ( $written !== $size ) {
			ftruncate( $target, $offset );
			throw new Ai1wm_Import_Retry_Exception( 'Incomplete chunk write. Check available disk space.', 400 );
		}
	} finally {
		fclose( $target );
		stw_release_database_lock( $mutex );
	}
}
