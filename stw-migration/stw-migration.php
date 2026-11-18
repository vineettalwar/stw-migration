<?php
/**
 * Plugin Name: STW Migration
 * Plugin URI: https://github.com/vineettalwar/stw-migration
 * Description: Migration tool for all your blog data. Import or Export your blog content with a single click.
 * Author: STW
 * Version: 1.2.0-beta.1
 * Text Domain: stw-migration
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Update URI: https://github.com/vineettalwar/stw-migration
 * Domain Path: /languages
 * Requires PHP: 8.1
 * Requires at least: 6.0
 * Network: True
 *
 * Copyright (C) 2014-2018 ServMask Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

// STW Migration fork modified 2026-11-18; original copyright retained above.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Retained upstream internals cannot run alongside another Ai1wm implementation.
$stw_active_plugins = array_merge(
	(array) get_option( 'active_plugins', array() ),
	array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) )
);
$stw_conflict = defined( 'AI1WM_PATH' );
foreach ( $stw_active_plugins as $stw_active_plugin ) {
	if ( basename( $stw_active_plugin ) === 'all-in-one-wp-migration.php' ) {
		$stw_conflict = true;
	}
}
if ( $stw_conflict ) {
	add_action( 'admin_notices', function () {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'STW Migration is paused. Deactivate All-in-One WP Migration before testing this fork.', 'stw-migration' ) . '</p></div>';
	} );
	return;
}
unset( $stw_active_plugins, $stw_active_plugin, $stw_conflict );

// Migration is an admin/CLI tool; skip the engine on public page requests.
if ( ! is_admin() && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	return;
}

// Check SSL Mode
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && ( $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) ) {
	$_SERVER['HTTPS'] = 'on';
}

// Plugin Basename
define( 'AI1WM_PLUGIN_BASENAME', basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ) );

// Plugin Path
define( 'AI1WM_PATH', dirname( __FILE__ ) );

// Plugin Url
define( 'AI1WM_URL', plugins_url( '', AI1WM_PLUGIN_BASENAME ) );

// Plugin Storage Url
define( 'AI1WM_STORAGE_URL', plugins_url( 'storage', AI1WM_PLUGIN_BASENAME ) );

// Plugin Backups Url
define( 'AI1WM_BACKUPS_URL', content_url( 'ai1wm-backups', AI1WM_PLUGIN_BASENAME ) );

// Themes Absolute Path
define( 'AI1WM_THEMES_PATH', get_theme_root() );

// Include constants
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'constants.php';

// Include deprecated
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'deprecated.php';

// Include functions
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'functions.php';

require_once __DIR__ . '/stw-security.php';
require_once __DIR__ . '/lib/stw/class-stw-archive.php';

// Include exceptions
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'exceptions.php';

// Include loader
require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'loader.php';

// =========================================================================
// = All app initialization is done in Ai1wm_Main_Controller __constructor =
// =========================================================================
$main_controller = new Ai1wm_Main_Controller();
require_once __DIR__ . '/lib/stw/class-stw-settings.php';
require_once __DIR__ . '/lib/stw/class-stw-admin.php';
$stw_admin = new Stw_Admin( $main_controller );

// Workspace styles load only on STW screens, after legacy engine styles.
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( ! Stw_Admin::is_screen( $hook ) ) {
		return;
	}
	wp_enqueue_script( 'stw-workspace', plugins_url( 'lib/view/assets/javascript/stw-workspace.js', __FILE__ ), array(), STW_MIGRATION_VERSION, true );
	wp_enqueue_style( 'stw-workspace', plugins_url( 'lib/view/assets/css/stw-workspace.css', __FILE__ ), array(), STW_MIGRATION_VERSION . '.' . filemtime( __DIR__ . '/lib/view/assets/css/stw-workspace.css' ) );
}, 20 );
