<?php
/** STW Migration workspace. GPL-3.0-or-later. */
$page = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'ai1wm_export';
$tabs = array( 'ai1wm_export' => 'Create backup', 'ai1wm_import' => 'Restore site', 'ai1wm_backups' => 'Backup library' );
?>
<div class="stw-workspace">
<header class="stw-topbar">
	<a class="stw-brand" href="<?php echo esc_url( admin_url( 'admin.php?page=ai1wm_export' ) ); ?>"><span class="stw-mark" aria-hidden="true">s<span>↗</span></span><span>STW <strong>Migration</strong></span></a>
	<span class="stw-site"><span aria-hidden="true">●</span> <?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></span>
</header>
<nav class="stw-nav" aria-label="Migration workspace">
	<?php foreach ( $tabs as $slug => $label ) : ?>
	<a <?php echo $page === $slug ? 'aria-current="page"' : ''; ?> href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>"><?php echo esc_html( $label ); ?></a>
	<?php endforeach; ?>
	<span class="stw-version">BETA · <?php echo esc_html( STW_MIGRATION_VERSION ); ?></span>
</nav>
<div class="stw-intro">
	<p class="stw-eyebrow">YOUR SITE. READY TO MOVE.</p>
	<h1><?php echo esc_html( array( 'ai1wm_export' => 'A fresh start begins with a backup.', 'ai1wm_import' => 'Bring your site home.', 'ai1wm_backups' => 'A little peace of mind, saved.' )[ $page ] ?? 'Your migration workspace.' ); ?></h1>
	<p><?php echo esc_html( array( 'ai1wm_export' => 'Bundle your WordPress site into one portable file. Keep a copy, or take it somewhere new.', 'ai1wm_import' => 'Upload a .wpress backup to restore its content, media, themes, plugins, and database.', 'ai1wm_backups' => 'Download a copy, return to a saved version, or make room for your next backup.' )[ $page ] ?? '' ); ?></p>
</div>
