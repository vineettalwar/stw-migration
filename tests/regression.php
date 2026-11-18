<?php
/** Run through wp-env: wp eval-file stw-tests/regression.php --user=admin */
$GLOBALS['stw_passed'] = 0;
$original_secret = get_option( AI1WM_SECRET_KEY );
function stw_test( $condition, $message ) {
	global $stw_passed;
	if ( ! $condition ) {
		throw new RuntimeException( 'FAIL: ' . $message );
	}
	$stw_passed++;
}
function stw_rejects( $callback, $message ) {
	try { $callback(); } catch ( Throwable $error ) { stw_test( true, $message ); return; }
	stw_test( false, $message );
}
$root = sys_get_temp_dir() . '/stw-regression-' . bin2hex( random_bytes( 8 ) );
mkdir( $root, 0700 );
mkdir( $root . '/output', 0700 );
function stw_archive_fixture( $root, $name, $size, $path, $content, $header = null ) {
	$file = $root . '/fixture.wpress';
	file_put_contents( $file, ( $header ?? pack( 'a255a14a12a4096', $name, $size, time(), $path ) ) . $content . pack( 'a4377', '' ) );
	return new Ai1wm_Extractor( $file );
}
try {
	foreach ( array( '..', '.', '../outside', 'a/b', 'a\\b', array( 'bad' ), "bad\0name" ) as $storage ) {
		stw_rejects( function () use ( $storage ) { ai1wm_storage_path( array( 'storage' => $storage ) ); }, 'invalid storage rejected' );
	}
	foreach ( array( '../bad.wpress', '/bad.wpress', 'a\\bad.wpress', 'bad.php', array(), "a\0.wpress" ) as $archive ) {
		stw_rejects( function () use ( $archive ) { ai1wm_backup_path( array( 'archive' => $archive ) ); }, 'invalid archive path rejected' );
	}
	foreach ( array( null, false, array(), '' ) as $secret ) {
		stw_rejects( function () use ( $secret ) { ai1wm_verify_secret_key( $secret ); }, 'invalid secret rejected' );
	}
	update_option( AI1WM_SECRET_KEY, str_repeat( 'a', 64 ) );
	stw_test( ai1wm_verify_secret_key( str_repeat( 'a', 64 ) ), 'valid secret accepted' );
	foreach ( array( '../escape', '/absolute', 'C:/windows', 'sub/../escape', 'sub\\escape' ) as $path ) {
		$archive = stw_archive_fixture( $root, 'file.txt', 1, $path, 'x' );
		stw_rejects( function () use ( $archive, $root ) { $archive->extract_one_file_to( $root . '/output' ); }, 'traversal rejected' );
		$archive->close();
	}
	foreach ( array( '-1', 'x', '99999999999999' ) as $size ) {
		$archive = stw_archive_fixture( $root, 'file.txt', $size, '.', 'x' );
		stw_rejects( function () use ( $archive ) { $archive->get_total_files_count(); }, 'malformed size rejected' );
		$archive->close();
	}
	$archive = stw_archive_fixture( $root, 'file.txt', 1, '.', '', 'short' );
	stw_rejects( function () use ( $archive ) { $archive->get_total_files_count(); }, 'short header rejected' );
	$archive->close();
	$archive = stw_archive_fixture( $root, 'file.txt', 1, '.', 'x' );
	$archive->close();
	file_put_contents( $root . '/fixture.wpress', pack( 'a4377', '' ) . file_get_contents( $root . '/fixture.wpress' ) );
	$archive = new Ai1wm_Extractor( $root . '/fixture.wpress' );
	stw_rejects( function () use ( $archive ) { $archive->get_total_files_count(); }, 'premature footer rejected' );
	$archive->close();
	$archive = stw_archive_fixture( $root, 'empty.txt', 0, '.', '' );
	stw_test( $archive->is_valid(), 'valid footer recognized' );
	stw_test( $archive->get_total_files_count() === 1 && $archive->get_total_files_size() === 0, 'empty-file totals' );
	$archive->set_file_pointer( 0 );
	$archive->extract_one_file_to( $root . '/output' );
	stw_test( filesize( $root . '/output/empty.txt' ) === 0, 'empty file restored' );
	$archive->close();
	$content = str_repeat( 'Streaming fixture. ', 100000 );
	$archive = stw_archive_fixture( $root, 'large.txt', strlen( $content ), '.', $content );
	$archive->extract_one_file_to( $root . '/output' );
	stw_test( hash_file( 'sha256', $root . '/output/large.txt' ) === hash( 'sha256', $content ), 'multi-chunk contents preserved' );
	$archive->close();
	mkdir( $root . '/outside' );
	file_put_contents( $root . '/outside/keep.txt', 'keep' );
	symlink( $root . '/outside', $root . '/output/link' );
	$archive = stw_archive_fixture( $root, 'keep.txt', 1, 'link', 'x' );
	stw_rejects( function () use ( $archive, $root ) { $archive->extract_one_file_to( $root . '/output' ); }, 'symlink extraction rejected' );
	$archive->close();
	// A file that is itself a symlink is skipped, preserving external data privacy.
	symlink( $root . '/outside/keep.txt', $root . '/external-file' );
	$compressor = new Ai1wm_Compressor( $root . '/skip-link.wpress' );
	$compressor->add_file( $root . '/external-file', 'secret.txt' );
	$compressor->close( true );
	$extractor = new Ai1wm_Extractor( $root . '/skip-link.wpress' );
	stw_test( $extractor->get_total_files_count() === 0, 'file symlinks are not exported' );
	$extractor->close();
	Ai1wm_Directory::delete( $root . '/output' );
	stw_test( file_get_contents( $root . '/outside/keep.txt' ) === 'keep', 'recursive deletion does not follow symlink' );
	class STW_Test_Gadget { public $url = 'https://old.test'; public function __wakeup() { $GLOBALS['stw_wakeup'] = true; } }
	$GLOBALS['stw_wakeup'] = false;
	$serialized = serialize( new STW_Test_Gadget() );
	$rewritten = Ai1wm_Database_Utility::replace_serialized_values( array( 'https://old.test' ), array( 'https://new.example' ), $serialized );
	stw_test( ! $GLOBALS['stw_wakeup'], 'database replacements never invoke object wakeup' );
	stw_test( strpos( $rewritten, 'https://new.example' ) !== false && strpos( $rewritten, 'STW_Test_Gadget' ) !== false, 'object identity and URL preserved' );
	$original = array( 'url' => 'https://old.test/ä', 'nested' => serialize( array( 'https://old.test/x' ) ), 'boolean' => false );
	$expected = array( 'url' => 'https://new.example/ä', 'nested' => serialize( array( 'https://new.example/x' ) ), 'boolean' => false );
	$result = Ai1wm_Database_Utility::replace_serialized_values( array( 'https://old.test' ), array( 'https://new.example' ), serialize( $original ) );
	stw_test( $result === serialize( $expected ), 'nested serialized strings and UTF-8 byte lengths preserved' );
	$custom = 'C:6:"Custom":3:{abc}';
	stw_test( stw_rewrite_serialized( $custom, array( 'abc' ), array( 'xyz' ) ) === $custom, 'custom object payload remains opaque' );
	file_put_contents( $root . '/chunk-a', 'abc' );
	file_put_contents( $root . '/chunk-b', 'def' );
	stw_write_upload_chunk( $root . '/chunk-a', $root . '/upload.wpress', '0', '6' );
	stw_write_upload_chunk( $root . '/chunk-a', $root . '/upload.wpress', '0', '6' );
	stw_write_upload_chunk( $root . '/chunk-b', $root . '/upload.wpress', '3', '6' );
	stw_test( file_get_contents( $root . '/upload.wpress' ) === 'abcdef', 'retried chunk is not duplicated' );
	stw_rejects( function () use ( $root ) { stw_write_upload_chunk( $root . '/chunk-b', $root . '/upload.wpress', '0', '6' ); }, 'conflicting retry denied' );
	stw_rejects( function () use ( $root ) { stw_write_upload_chunk( $root . '/chunk-b', $root . '/upload.wpress', '8', '12' ); }, 'out-of-order chunk denied' );
	stw_rejects( function () use ( $root ) { stw_write_upload_chunk( $root . '/chunk-b', $root . '/upload.wpress', '5', '6' ); }, 'oversized chunk denied' );
	file_put_contents( $root . '/partial.wpress', 'ab' );
	stw_write_upload_chunk( $root . '/chunk-a', $root . '/partial.wpress', '0', '3' );
	stw_test( file_get_contents( $root . '/partial.wpress' ) === 'abc', 'partial write safely replaced by retry' );
	foreach ( array( '', '{invalid}', '{}', '{"Plugin":{"Version":"6.77"},"WordPress":{"Content":"/content"},"SiteURL":"file:///etc/passwd","HomeURL":"https://example.test"}' ) as $metadata ) {
		file_put_contents( $root . '/package.json', $metadata );
		stw_rejects( function () use ( $root ) { stw_validate_package( $root . '/package.json' ); }, 'invalid package metadata rejected before restore' );
	}
	$metadata = array( 'Plugin' => array( 'Version' => '6.77' ), 'WordPress' => array( 'Content' => '/content' ), 'SiteURL' => 'https://source.test', 'HomeURL' => 'https://source.test' );
	file_put_contents( $root . '/package.json', wp_json_encode( $metadata ) );
	stw_test( stw_validate_package( $root . '/package.json' ) === $metadata, 'valid metadata accepted' );
	wp_set_current_user( 0 );
	stw_rejects( function () { stw_require_admin_nonce( array() ); }, 'anonymous initiation denied' );
	wp_set_current_user( 1 );
	stw_rejects( function () { stw_require_admin_nonce( array( '_stw_nonce' => 'invalid' ) ); }, 'invalid nonce denied' );
	stw_require_admin_nonce( array( '_stw_nonce' => wp_create_nonce( 'stw_migration' ) ) );
	stw_test( true, 'admin nonce accepted' );
	$initialized = stw_authorize_job( array( 'storage' => 'regression-' . bin2hex( random_bytes( 6 ) ), '_stw_nonce' => wp_create_nonce( 'stw_migration' ) ), 'export' );
	stw_test( ! empty( $initialized['archive'] ), 'browser export initializes archive when storage already supplied' );
	Ai1wm_Directory::delete( ai1wm_storage_path( $initialized ) );
	$params = array( 'storage' => 'regression-' . bin2hex( random_bytes( 6 ) ), 'archive' => 'fixture.wpress', '_stw_nonce' => wp_create_nonce( 'stw_migration' ) );
	stw_authorize_job( $params, 'import' );
	$other = array( 'storage' => 'regression-' . bin2hex( random_bytes( 6 ) ), 'archive' => 'other.wpress', '_stw_nonce' => wp_create_nonce( 'stw_migration' ) );
	stw_rejects( function () use ( $other ) { stw_authorize_job( $other, 'import' ); }, 'concurrent migration job denied' );
	Ai1wm_Directory::delete( ai1wm_storage_path( $other ) );

	wp_set_current_user( 0 );
	stw_test( stw_authorize_job( $params, 'import' ) === $params, 'authorized job can complete after user/session replacement' );
	stw_rejects( function () use ( $params ) { stw_authorize_job( $params, 'export' ); }, 'cross-operation job reuse denied' );
	$params['archive'] = 'other.wpress';
	stw_rejects( function () use ( $params ) { stw_authorize_job( $params, 'import' ); }, 'cross-archive job reuse denied' );
	$params['archive'] = 'fixture.wpress';
	file_put_contents( ai1wm_storage_path( $params ) . '/.stw-job.json', wp_json_encode( array( 'operation' => 'import', 'archive' => 'fixture.wpress', 'expires' => time() - 1 ) ) );
	stw_rejects( function () use ( $params ) { stw_authorize_job( $params, 'import' ); }, 'expired continuation denied' );
	$marker = ai1wm_storage_path( $params ) . '/.stw-job.json';
	file_put_contents( $marker, '{broken-json' );
	stw_rejects( function () use ( $params ) { stw_authorize_job( $params, 'import' ); }, 'malformed job metadata fails closed' );
	unlink( $marker );
	symlink( $root . '/outside/keep.txt', $marker );
	stw_rejects( function () use ( $params ) { stw_authorize_job( $params, 'import' ); }, 'symlinked job metadata rejected' );
	stw_test( file_get_contents( $root . '/outside/keep.txt' ) === 'keep', 'job validation never changes a symlink target' );
	Ai1wm_Directory::delete( ai1wm_storage_path( $params ) );
	wp_set_current_user( 1 );
	printf( "PASS: %d security and integrity assertions\n", $GLOBALS['stw_passed'] );
} finally {
	update_option( AI1WM_SECRET_KEY, $original_secret );
	Ai1wm_Directory::delete( $root );
}
