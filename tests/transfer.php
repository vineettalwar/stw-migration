<?php
$mode = $args[0] ?? '';
$shared = ABSPATH . 'stw-tests/.runtime/roundtrip.wpress';
if ( $mode === 'stage' ) {
	wp_mkdir_p( dirname( $shared ) );
	$source = ai1wm_backup_path( array( 'archive' => $args[1] ?? '' ) );
	if ( ! copy( $source, $shared ) ) { throw new RuntimeException( 'Could not stage archive.' ); }
} elseif ( $mode === 'receive' ) {
	wp_mkdir_p( AI1WM_BACKUPS_PATH );
	if ( ! copy( $shared, ai1wm_backup_path( array( 'archive' => 'roundtrip.wpress' ) ) ) ) { throw new RuntimeException( 'Could not copy destination archive.' ); }
} else {
	throw new RuntimeException( 'Unknown transfer mode.' );
}
echo "Archive transfer complete\n";
