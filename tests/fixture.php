<?php
$mode = $args[0] ?? 'verify';
$slug = 'stw-migration-roundtrip';
if ( $mode === 'seed' ) {
	$existing = get_page_by_path( $slug, OBJECT, 'post' );
	if ( $existing ) {
		foreach ( get_children( array( 'post_parent' => $existing->ID, 'post_type' => 'attachment' ) ) as $media ) {
			wp_delete_attachment( $media->ID, true );
		}
	}
	$id = wp_insert_post( array( 'ID' => $existing ? $existing->ID : 0, 'post_type' => 'post', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => 'STW round trip — Grüße 日本語', 'post_content' => 'Migration fixture: ' . home_url( '/fixture' ) ), true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	$upload = wp_upload_bits( 'stw-fixture.txt', null, "STW media integrity\nGrüße 日本語\n" );
	if ( $upload['error'] ) { throw new RuntimeException( $upload['error'] ); }
	$attachment = wp_insert_attachment( array( 'post_title' => 'STW fixture media', 'post_mime_type' => 'text/plain', 'post_status' => 'inherit' ), $upload['file'], $id );
	update_option( 'stw_roundtrip_fixture', array( 'url' => home_url( '/fixture' ), 'nested' => array( 'unicode' => 'Grüße 日本語', 'number' => 42, 'flag' => false ), 'attachment' => $attachment, 'hash' => hash_file( 'sha256', $upload['file'] ) ), false );
	echo "Seeded post, media and nested serialized option\n";
} elseif ( $mode === 'destination' ) {
	wp_insert_post( array( 'post_name' => 'stw-destination-only', 'post_title' => 'Must disappear after restore', 'post_status' => 'publish' ) );
	update_option( 'stw_destination_only', 'must disappear' );
	echo "Seeded destination-only data\n";
} else {
	$post = get_page_by_path( $slug, OBJECT, 'post' );
	$option = get_option( 'stw_roundtrip_fixture' );
	$checks = array(
		'Unicode post title' => $post && $post->post_title === 'STW round trip — Grüße 日本語',
		'Content URL rewritten' => $post && $post->post_content === 'Migration fixture: ' . home_url( '/fixture' ),
		'Serialized URL rewritten' => is_array( $option ) && $option['url'] === home_url( '/fixture' ),
		'Nested types preserved' => is_array( $option ) && $option['nested'] === array( 'unicode' => 'Grüße 日本語', 'number' => 42, 'flag' => false ),
		'Media bytes preserved' => is_array( $option ) && is_file( get_attached_file( $option['attachment'] ) ) && hash_file( 'sha256', get_attached_file( $option['attachment'] ) ) === $option['hash'],
		'Destination post removed' => ! get_page_by_path( 'stw-destination-only', OBJECT, 'post' ),
		'Destination option removed' => get_option( 'stw_destination_only', null ) === null,
		'Plugin active after restore' => in_array( 'stw-migration/stw-migration.php', (array) get_option( 'active_plugins' ), true ),
	);
	foreach ( $checks as $name => $ok ) {
		echo ( $ok ? 'PASS ' : 'FAIL ' ) . $name . "\n";
	}
	if ( in_array( false, $checks, true ) ) { throw new RuntimeException( 'Round-trip verification failed.' ); }
	echo 'VERIFIED ' . home_url() . "\n";
}
