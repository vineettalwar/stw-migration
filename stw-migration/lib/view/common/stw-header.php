<?php
/** STW Migration workspace. GPL-3.0-or-later. */
$page = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'stw_backup';
$tabs = array( 'stw_backup' => 'Create backup', 'stw_restore' => 'Restore site', 'stw_library' => 'Backup library', 'stw_settings' => 'Settings' );
?>
<div class="stw-workspace">
<header class="stw-topbar">
	<a class="stw-brand" href="<?php echo esc_url( admin_url( 'admin.php?page=stw_backup' ) ); ?>"><span class="stw-mark" aria-hidden="true">STW</span><span>STW <strong>Migration</strong></span></a>
</header>
<nav class="stw-nav" aria-label="Migration workspace">
	<?php foreach ( $tabs as $slug => $label ) : ?>
	<a <?php echo $page === $slug ? 'aria-current="page"' : ''; ?> href="<?php echo esc_url( admin_url( 'admin.php?page=' . $slug ) ); ?>"><?php echo esc_html( $label ); ?></a>
	<?php endforeach; ?>
	<span class="stw-version">BETA · <?php echo esc_html( STW_MIGRATION_VERSION ); ?></span>
</nav>
<div class="stw-intro">
	<h1><?php echo esc_html( array( 'stw_backup' => 'Create a backup', 'stw_restore' => 'Restore a backup', 'stw_library' => 'Backup library', 'stw_settings' => 'Settings' )[ $page ] ?? 'Your migration workspace.' ); ?></h1>
	<p><?php echo esc_html( array( 'stw_backup' => 'Save a portable copy of your WordPress site.', 'stw_restore' => 'Upload a .stwm or legacy .wpress backup to restore its content, media, themes, plugins, and database.', 'stw_library' => 'Download, restore, or delete backups stored on this server.', 'stw_settings' => 'Choose default selections for new backups.' )[ $page ] ?? '' ); ?></p>
</div>
