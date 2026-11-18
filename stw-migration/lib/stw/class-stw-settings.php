<?php
/** STW backup preferences. GPL-3.0-or-later. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Stw_Settings {
	const OPTION = 'stw_migration_settings';

	public static function fields() {
		return array(
			'no_spam_comments'  => __( 'Exclude spam comments', 'stw-migration' ),
			'no_post_revisions' => __( 'Exclude post revisions', 'stw-migration' ),
			'no_cache'          => __( 'Exclude cache files', 'stw-migration' ),
		);
	}

	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$clean = array();
		foreach ( self::fields() as $key => $label ) {
			$clean[ $key ] = isset( $input[ $key ] ) && in_array( $input[ $key ], array( true, 1, '1', 'on' ), true );
		}
		return $clean;
	}

	public static function get() {
		return self::sanitize( get_option( self::OPTION, array() ) );
	}

	public static function register() {
		register_setting(
			'stw_migration',
			self::OPTION,
			array(
				'type'              => 'array',
				'default'           => self::sanitize( array() ),
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You cannot manage migration settings.', 'stw-migration' ), '', array( 'response' => 403 ) );
		}
		$settings = self::get();
		include AI1WM_TEMPLATES_PATH . '/common/stw-header.php';
		?>
		<section class="stw-settings-panel">
			<h2><?php esc_html_e( 'Backup defaults', 'stw-migration' ); ?></h2>
			<p><?php esc_html_e( 'Default selections for Create backup. You can change them for each backup.', 'stw-migration' ); ?></p>
			<?php settings_errors(); ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php settings_fields( 'stw_migration' ); ?>
				<input type="hidden" name="<?php echo esc_attr( self::OPTION ); ?>[submitted]" value="1" />
				<fieldset>
					<legend class="screen-reader-text"><?php esc_html_e( 'Default backup exclusions', 'stw-migration' ); ?></legend>
					<?php foreach ( self::fields() as $key => $label ) : ?>
						<label class="stw-setting-row">
							<input type="checkbox" name="<?php echo esc_attr( self::OPTION . '[' . $key . ']' ); ?>" value="1" <?php checked( $settings[ $key ] ); ?> />
							<?php echo esc_html( $label ); ?>
						</label>
					<?php endforeach; ?>
				</fieldset>
				<button type="submit" class="stw-primary"><?php esc_html_e( 'Save settings', 'stw-migration' ); ?></button>
			</form>
		</section>
		<?php
		include AI1WM_TEMPLATES_PATH . '/common/stw-footer.php';
	}
}
