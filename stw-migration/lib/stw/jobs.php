<?php
/** STW Migration, GPL-3.0-or-later. Modified 2026-11-18. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Require an administrator and a nonce when starting destructive work. */
function stw_require_admin_nonce( $params ) {
	if (
		! current_user_can( 'manage_options' )
		|| ! isset( $params['_stw_nonce'] )
		|| ! is_string( $params['_stw_nonce'] )
		|| ! wp_verify_nonce( $params['_stw_nonce'], 'stw_migration' )
	) {
		throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Administrator permission and a valid migration nonce are required.' );
	}
}

/** Authorize new jobs as admin; allow bounded completion after imported users replace login sessions. */
function stw_authorize_job( $params, $operation ) {
	if ( $operation === 'export' && ( empty( $params['storage'] ) || empty( $params['archive'] ) ) ) {
		stw_require_admin_nonce( $params );
		$params = Ai1wm_Export_Init::execute( $params );
	}
	if (
		empty( $params['storage'] )
		|| ! is_string( $params['storage'] )
		|| ! preg_match( '/^[a-zA-Z0-9_-]{1,128}$/D', $params['storage'] )
	) {
		throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Invalid migration job.' );
	}
	if ( ! stw_is_archive_name( $params['archive'] ?? null ) ) {
		throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Invalid migration archive.' );
	}
	$directory = stw_assert_safe_path( AI1WM_STORAGE_PATH, $params['storage'] );
	$marker    = stw_assert_safe_path( $directory, '.stw-job.json' );
	$new_job   = false;
	$exists    = is_file( $marker );
	$job       = $exists ? json_decode( file_get_contents( $marker, false, null, 0, 4096 ), true ) : null;
	if (
		$exists
		&& ( ! is_array( $job )
		|| ! isset( $job['operation'], $job['archive'], $job['expires'] )
		|| ! is_string( $job['operation'] )
		|| ! stw_is_archive_name( $job['archive'] )
		|| ! is_int( $job['expires'] ) )
	) {
		throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Migration job state is invalid. Start a new job.' );
	}
	if ( ! $job ) {
		$new_job = true;
		stw_require_admin_nonce( $params );
		if ( $operation === 'import' && ! current_user_can( 'install_plugins' ) ) {
			throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Installing plugins must be allowed to restore site code.' );
		}
		$job       = array(
			'operation' => $operation,
			'archive'   => $params['archive'],
			'expires'   => time() + DAY_IN_SECONDS,
		);
		$directory = ai1wm_storage_path( $params );
		if ( file_put_contents( $marker, wp_json_encode( $job ), LOCK_EX ) === false ) {
			throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Cannot persist migration authorization.' );
		}
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Best-effort permissions inside a private, server-protected directory.
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

/** Serialize whole migration jobs, including the gaps between browser requests. */
function stw_claim_job( $params ) {
	$path = AI1WM_STORAGE_PATH . '/.stw-active-' . substr( hash( 'sha256', home_url( '/' ) ), 0, 20 ) . '.json';
	if ( is_link( $path ) ) {
		throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Cannot use a symlinked migration workspace.' );
	}
	$handle = fopen( $path, 'c+b' );
	if ( ! $handle ) {
		throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Cannot lock migration workspace.' );
	}
	try {
		$mutex = stw_acquire_database_lock( 'workspace:' . home_url( '/' ), 1 );
	} catch ( Throwable $error ) {
		fclose( $handle );
		throw $error;
	}
	if ( ! $mutex ) {
		fclose( $handle );
		throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Another request is updating the migration workspace. Try again.' );
	}
	try {
		$active = json_decode( stream_get_contents( $handle, 2048 ), true );
		if (
			is_array( $active )
			&& isset( $active['storage'], $active['expires'] )
			&& $active['storage'] !== $params['storage']
			&& $active['expires'] > time()
		) {
			$directory = stw_assert_safe_path( AI1WM_STORAGE_PATH, $active['storage'] );
			if ( is_file( $directory . '/.stw-job.json' ) ) {
				throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Another migration is in progress. Finish or stop it before starting a new one.' );
			}
		}
		$contents = wp_json_encode(
			array(
				'storage' => $params['storage'],
				'expires' => time() + DAY_IN_SECONDS,
			)
		);
		rewind( $handle );
		if ( ! ftruncate( $handle, 0 ) || fwrite( $handle, $contents ) !== strlen( $contents ) ) {
			throw new Ai1wm_Not_Valid_Secret_Key_Exception( 'Cannot save migration lock.' );
		}
		fflush( $handle );
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Best-effort permissions inside a private, server-protected directory.
		@chmod( $path, 0600 );
	} finally {
		fclose( $handle );
		stw_release_database_lock( $mutex );
	}
}

/** CLI is already authenticated by filesystem access; still respect active jobs. */
function stw_register_cli_job( $params, $operation ) {
	$directory = ai1wm_storage_path( $params );
	$marker    = stw_assert_safe_path( $directory, '.stw-job.json' );
	try {
		if ( file_put_contents(
			$marker,
			wp_json_encode(
				array(
					'operation' => $operation,
					'archive'   => $params['archive'],
					'expires'   => time() + DAY_IN_SECONDS,
				)
			),
			LOCK_EX
		) === false ) {
			throw new RuntimeException( 'Cannot persist CLI migration job.' );
		}
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Best-effort permissions inside a private, server-protected directory.
		@chmod( $marker, 0600 );
		stw_claim_job( $params );
	} catch ( Throwable $error ) {
		Ai1wm_Directory::delete( $directory );
		throw $error;
	}
}

/** MySQL/MariaDB advisory locks work across PHP workers and container filesystems. */
function stw_acquire_database_lock( $scope, $timeout = 0 ) {
	global $wpdb;
	$database_scope = ( defined( 'DB_NAME' ) ? DB_NAME : '' ) . ':' . $wpdb->prefix . ':' . $scope;
	$name           = 'stw:' . substr( hash( 'sha256', $database_scope ), 0, 60 );
	$result         = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', $name, $timeout ) );
	if ( (string) $result === '1' ) {
		return $name;
	}
	if ( (string) $result === '0' ) {
		return false;
	}
	throw new Ai1wm_Archive_Exception( 'The database must support MySQL/MariaDB advisory locks for safe migration.' );
}

function stw_release_database_lock( $name ) {
	global $wpdb;
	$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
}

/** Guard native archive work against overlapping retries of the same job. */
function stw_lock_archive_stage( $params, $stage ) {
	if ( ! in_array( $stage, array( 'export', 'import' ), true ) ) {
		throw new Ai1wm_Archive_Exception( 'Invalid native archive stage.' );
	}
	$lock = stw_acquire_database_lock( 'native:' . home_url( '/' ) . ':' . $params['storage'] . ':' . $stage );
	if ( ! $lock && defined( 'WP_CLI' ) && WP_CLI ) {
		throw new Ai1wm_Archive_Exception( 'This native archive stage is already running.' );
	}
	return $lock;
}
