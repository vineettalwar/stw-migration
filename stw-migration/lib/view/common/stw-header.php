<?php
/** STW Migration workspace. GPL-3.0-or-later. */
$page = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'ai1wm_export';
$tabs = array( 'ai1wm_export' => 'Create backup', 'ai1wm_import' => 'Restore site', 'ai1wm_backups' => 'Backup library' );
?>
<div class="stw-workspace">
<header class="stw-topbar">
	<a class="stw-brand" href="<?php echo esc_url( admin_url( 'admin.php?page=ai1wm_export' ) ); ?>"><span class="stw-mark" aria-hidden="true">STW</span><span>STW <strong>Migration</strong></span></a>
	<span class="stw-site"><span aria-hidden="true">●</span> <?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></span>
</header>
<nav class="stw-nav" aria-label="Migration workspace">
	<?php foreach ( $tabs as $slug => $label ) : ?>
	<a <?php echo $page === $slug ? 'aria-current="page"' : ''; ?> href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>"><?php echo esc_html( $label ); ?></a>
	<?php endforeach; ?>
	<span class="stw-version">BETA · <?php echo esc_html( STW_MIGRATION_VERSION ); ?></span>
</nav>
<div class="stw-intro">
	<h1><?php echo esc_html( array( 'ai1wm_export' => 'Create a backup', 'ai1wm_import' => 'Restore a backup', 'ai1wm_backups' => 'Backup library' )[ $page ] ?? 'Your migration workspace.' ); ?></h1>
	<p><?php echo esc_html( array( 'ai1wm_export' => 'Save a portable copy of your WordPress site.', 'ai1wm_import' => 'Upload a .stwm or legacy .wpress backup to restore its content, media, themes, plugins, and database.', 'ai1wm_backups' => 'Download, restore, or delete backups stored on this server.' )[ $page ] ?? '' ); ?></p>
</div>
