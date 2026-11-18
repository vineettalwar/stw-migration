<?php
$mode = $args[0] ?? '';
$shared = ABSPATH . 'stw-tests/.runtime/roundtrip.';
if ( $mode === 'stage' ) {
	wp_mkdir_p( dirname( $shared ) );
	if ( ! copy( ai1wm_backup_path( array( 'archive' => $args[1] ?? '' ) ), $shared . 'stwm' ) ) {
		throw new RuntimeException( 'Could not stage native archive.' );
	}
	@unlink( $shared . 'wpress' );
	Stw_Archive::to_legacy( $shared . 'stwm', $shared . 'wpress' );
} elseif ( $mode === 'receive' ) {
	$extension = $args[1] ?? 'stwm';
	if ( ! in_array( $extension, array( 'stwm', 'wpress' ), true ) ) { throw new RuntimeException( 'Invalid test format.' ); }
	wp_mkdir_p( AI1WM_BACKUPS_PATH );
	if ( ! copy( $shared . $extension, ai1wm_backup_path( array( 'archive' => 'roundtrip.' . $extension ) ) ) ) {
		throw new RuntimeException( 'Could not copy destination archive.' );
	}
} else {
	throw new RuntimeException( 'Unknown transfer mode.' );
}
echo "Archive transfer complete\n";
