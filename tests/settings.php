<?php
$previous = get_option( Stw_Settings::OPTION, null );
$assertions = 0;
function stw_settings_assert( $ok, $message ) {
	if ( ! $ok ) { throw new RuntimeException( $message ); }
	$GLOBALS['stw_settings_assertions'] = ( $GLOBALS['stw_settings_assertions'] ?? 0 ) + 1;
}
try {
	$empty = array( 'no_spam_comments' => false, 'no_post_revisions' => false, 'no_cache' => false );
	stw_settings_assert( Stw_Settings::sanitize( null ) === $empty, 'Invalid settings normalize to safe defaults' );
	stw_settings_assert( Stw_Settings::sanitize( array( 'unknown' => 'ignored' ) ) === $empty, 'Unknown settings are discarded' );
	$input = array( 'no_spam_comments' => '1', 'no_post_revisions' => array( 'malformed' ), 'no_cache' => 'false' );
	$expected = array( 'no_spam_comments' => true, 'no_post_revisions' => false, 'no_cache' => false );
	stw_settings_assert( Stw_Settings::sanitize( $input ) === $expected, 'Only explicit checkbox values are accepted' );
	Stw_Settings::register();
	update_option( Stw_Settings::OPTION, $input );
	stw_settings_assert( Stw_Settings::get() === $expected, 'Registered option uses the sanitization callback' );
	ob_start();
	include AI1WM_TEMPLATES_PATH . '/export/advanced-settings.php';
	$html = ob_get_clean();
	stw_settings_assert( preg_match( '/name="options\[no_spam_comments\]"[^>]*checked/', $html ) === 1, 'Saved defaults are selected on the backup form' );
	update_option( Stw_Settings::OPTION, array() );
	stw_settings_assert( Stw_Settings::get() === $empty, 'Unchecking every setting clears previous defaults' );
	stw_settings_assert( get_option( 'ai1wm_secret_key' ) !== false, 'Settings do not change migration authorization' );
	printf( "PASS: %d settings assertions\n", $GLOBALS['stw_settings_assertions'] );
} finally {
	if ( $previous === null ) { delete_option( Stw_Settings::OPTION ); }
	else { update_option( Stw_Settings::OPTION, $previous ); }
}
