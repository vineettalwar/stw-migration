<?php
/** STW-owned navigation and presentation; the legacy engine remains an internal adapter. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Stw_Admin {
	private $engine;
	private static $screens = array();

	public function __construct( Ai1wm_Main_Controller $engine ) {
		$this->engine = $engine;
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( 'Stw_Settings', 'register' ) );
		add_action( 'admin_menu', array( $this, 'redirect_legacy_routes' ), 0 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_engine' ), 6 );
		add_action( 'admin_head', array( $this, 'render_icon_font' ) );
	}

	public static function is_screen( $hook ) {
		return isset( self::$screens[ $hook ] );
	}

	public function register_menu() {
		if ( is_multisite() ) {
			return;
		}
		$hook                   = add_menu_page( 'STW Migration', 'STW Migration', 'manage_options', 'stw_backup', 'Ai1wm_Export_Controller::index', 'dashicons-migrate', 76 );
		self::$screens[ $hook ] = 'backup';
		$pages                  = array(
			'stw_backup'   => array( 'Create backup', 'Ai1wm_Export_Controller::index', 'backup' ),
			'stw_restore'  => array( 'Restore site', 'Ai1wm_Import_Controller::index', 'restore' ),
			'stw_library'  => array( 'Backup library', 'Ai1wm_Backups_Controller::index', 'library' ),
			'stw_settings' => array( 'Settings', 'Stw_Settings::render', 'settings' ),
		);
		foreach ( $pages as $slug => $page ) {
			$hook                   = add_submenu_page( 'stw_backup', $page[0], $page[0], 'manage_options', $slug, $page[1] );
			self::$screens[ $hook ] = $page[2];
		}
	}

	public function redirect_legacy_routes() {
		$routes = array(
			'ai1wm_export'  => 'stw_backup',
			'ai1wm_import'  => 'stw_restore',
			'ai1wm_backups' => 'stw_library',
		);
		$page   = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( ! wp_doing_ajax() && isset( $routes[ $page ] ) && current_user_can( 'manage_options' ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . $routes[ $page ] ) );
			exit;
		}
	}

	public function enqueue_engine( $hook ) {
		$screen = self::$screens[ $hook ] ?? '';
		if ( 'backup' === $screen ) {
			$this->engine->enqueue_export_scripts_and_styles( 'toplevel_page_ai1wm_export' );
		} elseif ( 'restore' === $screen ) {
			$this->engine->enqueue_import_scripts_and_styles( 'stw-migration_page_ai1wm_import' );
		} elseif ( 'library' === $screen ) {
			$this->engine->enqueue_backups_scripts_and_styles( 'stw-migration_page_ai1wm_backups' );
		}
	}

	public function render_icon_font() {
		$screen = get_current_screen();
		if ( $screen && self::is_screen( $screen->id ) ) {
			Ai1wm_Template::render( 'main/admin-head', array( 'version' => get_bloginfo( 'version' ) ) );
		}
	}
}
