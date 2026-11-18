<?php
/** Explicit loader for STW's archive, job, and transfer security boundaries. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once __DIR__ . '/lib/stw/archive-security.php';
require_once __DIR__ . '/lib/stw/jobs.php';
require_once __DIR__ . '/lib/stw/transfers.php';
add_action( 'admin_post_stw_download', 'stw_download_archive' );
