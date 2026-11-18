<?php
/** Native format invariants. Run: wp eval-file stw-tests/native-archive.php */
$GLOBALS['stw_format_checks'] = 0;
$root = sys_get_temp_dir() . '/stw-format-' . bin2hex( random_bytes( 8 ) );
mkdir( $root, 0700 );
function stw_format_assert( $ok, $message ) {
	$GLOBALS['stw_format_checks'] = ( $GLOBALS['stw_format_checks'] ?? 0 ) + 1;
	if ( ! $ok ) { throw new RuntimeException( $message ); }
}
function stw_format_reject( $callback, $message ) {
	try { $callback(); } catch ( Ai1wm_Archive_Exception $error ) { stw_format_assert( true, $message ); return; }
	throw new RuntimeException( $message );
}
function stw_mutate_zip( $original, $copy, $callback ) {
	copy( $original, $copy );
	$zip = new ZipArchive();
	if ( $zip->open( $copy ) !== true ) { throw new RuntimeException( 'Test ZIP could not open.' ); }
	$manifest = json_decode( $zip->getFromName( 'manifest.json' ), true );
	$callback( $zip, $manifest );
	$zip->addFromString( 'manifest.json', wp_json_encode( $manifest ) );
	$zip->close();
}
try {
	$files = array( 'package.json' => '{"Plugin":{"Version":"6.77"},"WordPress":{"Content":"/content"},"SiteURL":"https://source.test","HomeURL":"https://source.test"}', 'data.txt' => 'original', 'empty.txt' => '', 'uploads/Grüße.txt' => str_repeat( 'Content 日本語 ', 100000 ) );
	$compressor = new Ai1wm_Compressor( $root . '/source.wpress' );
	foreach ( $files as $name => $content ) {
		file_put_contents( $root . '/input', $content );
		$compressor->add_file( $root . '/input', $name );
	}
	$compressor->close( true );
	Stw_Archive::create( $root . '/source.wpress', $root . '/native.stwm' );
	stw_format_assert( file_get_contents( $root . '/native.stwm', false, null, 0, 2 ) === 'PK', 'Native format has ZIP magic' );
	$zip = new ZipArchive();
	$zip->open( $root . '/native.stwm' );
	$manifest = json_decode( $zip->getFromName( 'manifest.json' ), true );
	stw_format_assert( $manifest['format'] === 'stw-migration' && $manifest['version'] === 1, 'Versioned STW manifest' );
	stw_format_assert( count( $manifest['entries'] ) === count( $files ), 'Manifest inventory matches content' );
	stw_format_assert( $zip->getFromName( 'site/data.txt' ) === 'original', 'Content is a directly inspectable ZIP entry' );
	$zip->close();
	Stw_Archive::to_legacy( $root . '/native.stwm', $root . '/restored.wpress' );
	$extractor = new Ai1wm_Extractor( $root . '/restored.wpress' );
	stw_format_assert( $extractor->is_valid() && $extractor->get_total_files_count() === count( $files ), 'Adapter produces a valid legacy stream' );
	mkdir( $root . '/output' );
	$extractor->set_file_pointer( 0 );
	while ( $extractor->has_not_reached_eof() ) { $extractor->extract_one_file_to( $root . '/output' ); }
	foreach ( $files as $name => $content ) { stw_format_assert( file_get_contents( $root . '/output/' . $name ) === $content, 'Native round-trip preserves ' . $name ); }
	$extractor->close();
	$mutations = array(
		'checksum' => function ( $zip, &$manifest ) { $zip->addFromString( 'site/data.txt', 'modified' ); $zip->setCompressionName( 'site/data.txt', ZipArchive::CM_STORE ); },
		'version' => function ( $zip, &$manifest ) { $manifest['version'] = 999; },
		'traversal' => function ( $zip, &$manifest ) { $manifest['entries'][0]['path'] = 'site/../../outside'; },
		'unlisted' => function ( $zip, &$manifest ) { $zip->addFromString( 'unlisted.php', 'not permitted' ); },
		'missing' => function ( $zip, &$manifest ) { $zip->deleteName( 'site/data.txt' ); },
		'symlink' => function ( $zip, &$manifest ) { $zip->setExternalAttributesName( 'site/data.txt', ZipArchive::OPSYS_UNIX, 0120777 << 16 ); },
		'negative-size' => function ( $zip, &$manifest ) { $manifest['entries'][0]['size'] = -1; },
		'compression' => function ( $zip, &$manifest ) { $zip->setCompressionName( 'site/data.txt', ZipArchive::CM_DEFLATE ); },
		'invalid-hash' => function ( $zip, &$manifest ) { $manifest['entries'][0]['sha256'] = 'not-a-hash'; },
	);
	foreach ( $mutations as $name => $mutation ) {
		stw_mutate_zip( $root . '/native.stwm', $root . '/' . $name . '.stwm', $mutation );
		stw_format_reject( function () use ( $root, $name ) { Stw_Archive::to_legacy( $root . '/' . $name . '.stwm', $root . '/rejected.wpress' ); }, 'Reject ' . $name );
		stw_format_assert( ! file_exists( $root . '/rejected.wpress' ), 'Failed conversion leaves no usable stream' );
	}
	stw_format_assert( stw_is_archive_name( 'backup.stwm' ) && stw_is_archive_name( 'legacy.wpress' ) && ! stw_is_archive_name( '../backup.stwm' ), 'Both extensions share the same strict filename policy' );
	file_put_contents( $root . '/existing.wpress', 'keep existing data' );
	stw_format_reject( function () use ( $root ) { Stw_Archive::to_legacy( $root . '/native.stwm', $root . '/existing.wpress' ); }, 'Conversion cannot overwrite an existing file' );
	stw_format_assert( file_get_contents( $root . '/existing.wpress' ) === 'keep existing data', 'Rejected conversion preserves existing data' );
	stw_format_reject( function () use ( $root ) { Stw_Archive::create( $root . '/source.wpress', $root . '/native.stwm' ); }, 'Packaging cannot overwrite an existing archive' );
	stw_format_reject( function () use ( $root ) { Stw_Archive::to_legacy( $root . '/source.wpress', $root . '/not-native.wpress' ); }, 'A renamed WPress file is not a native STW container' );
	$params = array( 'storage' => 'format-test-' . bin2hex( random_bytes( 8 ) ) );
	$lock = stw_lock_archive_stage( $params, 'import' );
	$probe_code = 'define("WP_ADMIN", true); require "/var/www/html/wp-load.php"; global $wpdb; $result=$wpdb->get_var($wpdb->prepare("SELECT GET_LOCK(%s,0)",$argv[1])); exit((string)$result==="0"?0:1);';
	$probe = proc_open( array( PHP_BINARY, '-d', 'register_argc_argv=1', '-r', $probe_code, $lock ), array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes );
	if ( ! is_resource( $probe ) ) { throw new RuntimeException( 'Could not start the independent lock probe.' ); }
	fclose( $pipes[0] );
	$probe_output = stream_get_contents( $pipes[1] ); fclose( $pipes[1] );
	$probe_error = stream_get_contents( $pipes[2] ); fclose( $pipes[2] );
	$probe_status = proc_close( $probe );
	stw_format_assert( $probe_status === 0, 'A separate worker cannot enter the same native stage (exit ' . $probe_status . ': ' . $probe_error . ')' );
	stw_release_database_lock( $lock );
	$lock = stw_lock_archive_stage( $params, 'import' );
	stw_format_assert( is_string( $lock ), 'Stage can resume after the worker releases its lock' );
	stw_release_database_lock( $lock );
	Ai1wm_Directory::delete( ai1wm_storage_path( $params ) );
	printf( "PASS: %d native archive assertions\n", $GLOBALS['stw_format_checks'] );
} finally {
	Ai1wm_Directory::delete( $root );
}
