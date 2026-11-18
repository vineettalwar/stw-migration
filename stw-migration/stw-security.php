<?php
/** STW Migration hardening, 2026-11-18. GPL-3.0-or-later. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Reject traversal, stream wrappers, and symlinks before writing or deleting. */
function stw_assert_safe_path( $root, $relative ) {
	if ( ! is_string( $relative ) || $relative === '' || preg_match( '/[\\\\\x00-\x1f:]/', $relative ) || $relative[0] === '/' ) {
		throw new Ai1wm_Archive_Exception( 'Unsafe migration path.' );
	}
	$path = rtrim( $root, '/\\' );
	if ( is_link( $path ) ) {
		throw new Ai1wm_Archive_Exception( 'Symlink migration roots are not supported.' );
	}
	foreach ( explode( '/', $relative ) as $part ) {
		if ( $part === '' || $part === '.' || $part === '..' ) {
			throw new Ai1wm_Archive_Exception( 'Unsafe migration path component.' );
		}
		$path .= DIRECTORY_SEPARATOR . $part;
		if ( is_link( $path ) ) {
			throw new Ai1wm_Archive_Exception( 'Migration cannot follow symbolic links.' );
		}
	}
	return $path;
}

function stw_require_admin_nonce( $params ) {
	if ( ! current_user_can( 'manage_options' ) || ! isset( $params['_stw_nonce'] ) || ! is_string( $params['_stw_nonce'] ) || ! wp_verify_nonce( $params['_stw_nonce'], 'stw_migration' ) ) {
		throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Administrator permission and a valid migration nonce are required.' );
	}
}

/** Authorize new jobs as admin; allow bounded completion after imported users replace login sessions. */
function stw_authorize_job( $params, $operation, $priority ) {
	if ( $operation === 'export' && ( empty( $params['storage'] ) || empty( $params['archive'] ) ) ) {
		stw_require_admin_nonce( $params );
		$params = Ai1wm_Export_Init::execute( $params );
	}
	if ( empty( $params['storage'] ) || ! is_string( $params['storage'] ) || ! preg_match( '/^[a-zA-Z0-9_-]{1,128}$/D', $params['storage'] ) ) {
		throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Invalid migration job.' );
	}
	if ( empty( $params['archive'] ) || ! is_string( $params['archive'] ) || basename( $params['archive'] ) !== $params['archive'] || strtolower( pathinfo( $params['archive'], PATHINFO_EXTENSION ) ) !== 'wpress' ) {
		throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Invalid migration archive.' );
	}
	$directory = stw_assert_safe_path( AI1WM_STORAGE_PATH, $params['storage'] );
	$marker = $directory . DIRECTORY_SEPARATOR . '.stw-job.json';
	$new_job = false;
	$job = is_file( $marker ) && ! is_link( $marker ) ? json_decode( file_get_contents( $marker ), true ) : null;
	if ( ! $job ) {
		$new_job = true;
		stw_require_admin_nonce( $params );
		if ( $operation === 'import' && ! current_user_can( 'install_plugins' ) ) {
			throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Installing plugins must be allowed to restore site code.' );
		}
		$job = array( 'operation' => $operation, 'archive' => $params['archive'], 'expires' => time() + DAY_IN_SECONDS );
		$directory = ai1wm_storage_path( $params );
		if ( file_put_contents( $marker, wp_json_encode( $job ), LOCK_EX ) === false ) {
			throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Cannot persist migration authorization.' );
		}
		@chmod( $marker, 0600 );
	}
	if ( $job['operation'] !== $operation || $job['archive'] !== $params['archive'] || $job['expires'] < time() ) {
		throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Migration authorization expired or mismatched.' );
	}
	try {
		stw_claim_job( $params );
	} catch ( Throwable $error ) {
		if ( $new_job ) {
			Ai1wm_Directory::delete( $directory );
		}
		throw $error;
	}
	return $params;
}

/** Rewrite serialized strings without instantiating archive-supplied PHP classes. */
function stw_rewrite_serialized( $input, $from, $to, $depth = 0 ) {
	if ( $depth > 64 ) {
		throw new Ai1wm_Import_Exception( 'Serialized data exceeds the supported nesting depth.' );
	}
	$output = '';
	$length = strlen( $input );
	for ( $offset = 0; $offset < $length; ) {
		if ( preg_match( '/\G(s|O|C|E):([0-9]+):"/', $input, $match, 0, $offset ) ) {
			$type = $match[1];
			$size = (int) $match[2];
			$start = $offset + strlen( $match[0] );
			if ( $size >= $length - $start || $input[ $start + $size ] !== '"' ) {
				throw new Ai1wm_Import_Exception( 'Invalid serialized string length.' );
			}
			$text = substr( $input, $start, $size );
			$offset = $start + $size + 1;
			if ( $type === 's' ) {
				if ( is_serialized( $text ) ) {
					$text = stw_rewrite_serialized( $text, $from, $to, $depth + 1 );
				} else {
					$text = Ai1wm_Database_Utility::replace_values( $from, $to, $text );
				}
				$output .= 's:' . strlen( $text ) . ':"' . $text . '"';
			} else {
				// Class names and enum identities are never rewritten.
				$output .= $match[0] . $text . '"';
				if ( $type === 'C' ) {
					if ( ! preg_match( '/\G:([0-9]+):\{/', $input, $custom, 0, $offset ) ) {
						throw new Ai1wm_Import_Exception( 'Invalid custom serialized object.' );
					}
					$size = (int) $custom[1];
					$start = $offset + strlen( $custom[0] );
					if ( $size >= $length - $start || $input[ $start + $size ] !== '}' ) {
						throw new Ai1wm_Import_Exception( 'Truncated custom serialized object.' );
					}
					$output .= $custom[0] . substr( $input, $start, $size ) . '}';
					$offset = $start + $size + 1;
				}
			}
		} else {
			$output .= $input[ $offset++ ];
		}
	}
	return $output;
}

/** Serve backups through WordPress authorization, streaming with bounded memory. */
add_action( 'admin_post_stw_download', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'You cannot download site backups.', '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'stw_download' );
	try {
		$archive = isset( $_GET['archive'] ) && is_string( $_GET['archive'] ) ? wp_unslash( $_GET['archive'] ) : '';
		$path = ai1wm_backup_path( array( 'archive' => $archive ) );
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
} );

/** Append a chunk exactly once, even if a successful response was lost and retried. */
function stw_write_upload_chunk( $source, $destination, $offset, $total ) {
	if ( ! is_scalar( $offset ) || ! is_scalar( $total ) || ! ctype_digit( (string) $offset ) || ! ctype_digit( (string) $total ) ) {
		throw new Ai1wm_Import_Retry_Exception( 'Invalid upload offset.', 400 );
	}
	$offset = (int) $offset;
	$total = (int) $total;
	$size = filesize( $source );
	if ( $size <= 0 || $total <= 0 || $offset < 0 || $offset > $total || $size > $total - $offset ) {
		throw new Ai1wm_Import_Retry_Exception( 'Upload chunk exceeds declared archive size.', 400 );
	}
	$target = fopen( $destination, 'c+b' );
	if ( ! $target ) {
		throw new Ai1wm_Import_Retry_Exception( 'Cannot write archive.', 400 );
	}
	try {
		if ( ! flock( $target, LOCK_EX ) ) {
			throw new Ai1wm_Import_Retry_Exception( 'Cannot lock upload.', 409 );
		}
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
		flock( $target, LOCK_UN );
		fclose( $target );
	}
}

/** Fail before database replacement if archive metadata is missing or malformed. */
function stw_validate_package( $path ) {
	if ( ! is_file( $path ) || filesize( $path ) > 4 * 1024 * 1024 ) {
		throw new Ai1wm_Import_Exception( 'Missing or oversized archive metadata.' );
	}
	try {
		$package = json_decode( file_get_contents( $path ), true, 64, JSON_THROW_ON_ERROR );
	} catch ( Throwable $error ) {
		throw new Ai1wm_Import_Exception( 'Archive metadata is not valid JSON.' );
	}
	if ( ! is_array( $package ) || ! isset( $package['Plugin']['Version'], $package['WordPress']['Content'] ) || ! is_string( $package['Plugin']['Version'] ) || ! is_string( $package['WordPress']['Content'] ) ) {
		throw new Ai1wm_Import_Exception( 'Archive metadata is missing required fields.' );
	}
	foreach ( array( 'SiteURL', 'HomeURL' ) as $field ) {
		if ( ! isset( $package[ $field ] ) || ! is_string( $package[ $field ] ) || ! in_array( wp_parse_url( $package[ $field ], PHP_URL_SCHEME ), array( 'http', 'https' ), true ) || ! wp_parse_url( $package[ $field ], PHP_URL_HOST ) ) {
			throw new Ai1wm_Import_Exception( 'Archive metadata contains an invalid site URL.' );
		}
	}
	return $package;
}

/** Serialize whole migration jobs, including the gaps between browser requests. */
function stw_claim_job( $params ) {
	$path = AI1WM_STORAGE_PATH . '/.stw-active-' . substr( hash( 'sha256', home_url( '/' ) ), 0, 20 ) . '.json';
	if ( is_link( $path ) || ! ( $handle = fopen( $path, 'c+b' ) ) ) {
		throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Cannot lock migration workspace.' );
	}
	try {
		if ( ! flock( $handle, LOCK_EX ) ) {
			throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Cannot lock migration workspace.' );
		}
		$active = json_decode( stream_get_contents( $handle, 2048 ), true );
		if ( is_array( $active ) && isset( $active['storage'], $active['expires'] ) && $active['storage'] !== $params['storage'] && $active['expires'] > time() ) {
			$directory = stw_assert_safe_path( AI1WM_STORAGE_PATH, $active['storage'] );
			if ( is_file( $directory . '/.stw-job.json' ) ) {
				throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Another migration is in progress. Finish or stop it before starting a new one.' );
			}
		}
		$contents = wp_json_encode( array( 'storage' => $params['storage'], 'expires' => time() + DAY_IN_SECONDS ) );
		rewind( $handle );
		if ( ! ftruncate( $handle, 0 ) || fwrite( $handle, $contents ) !== strlen( $contents ) ) {
			throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Cannot save migration lock.' );
		}
		fflush( $handle );
		@chmod( $path, 0600 );
	} finally {
		flock( $handle, LOCK_UN );
		fclose( $handle );
	}
}

/** CLI is already authenticated by filesystem access; still respect active jobs. */
function stw_register_cli_job( $params, $operation ) {
	$directory = ai1wm_storage_path( $params );
	$marker = $directory . '/.stw-job.json';
	try {
		if ( file_put_contents( $marker, wp_json_encode( array( 'operation' => $operation, 'archive' => $params['archive'], 'expires' => time() + DAY_IN_SECONDS ) ), LOCK_EX ) === false ) {
			throw new RuntimeException( 'Cannot persist CLI migration job.' );
		}
		@chmod( $marker, 0600 );
		stw_claim_job( $params );
	} catch ( Throwable $error ) {
		Ai1wm_Directory::delete( $directory );
		throw $error;
	}
}
