<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sailing-specific meta fields added on top of the core schema.
 *
 * All meta keys are prefixed _wpnts_ to distinguish them from core _wpnt_ keys.
 * Fields are added via extra meta boxes on the relevant post type screens.
 */
class WPNT_Sailing_Meta {

	private static array $fields = array(
		// session template — recommended conditions
		'wpnts_min_wind'         => '_wpnts_min_wind',
		'wpnts_max_wind'         => '_wpnts_max_wind',
		'wpnts_sea_state'        => '_wpnts_sea_state',
		'wpnts_tmpl_conditions'  => '_wpnts_tmpl_conditions',
		// session — actual conditions on the day
		'wpnts_actual_wind'      => '_wpnts_actual_wind',
		'wpnts_actual_sea_state' => '_wpnts_actual_sea_state',
		'wpnts_conditions'       => '_wpnts_conditions',
	);

	public static function register(): void {
		add_meta_box(
			'wpnts_session_tmpl_sailing',
			__( 'Sailing Conditions', 'wpnt-sailing' ),
			array( __CLASS__, 'session_tmpl_fields' ),
			'wpnt_session_tmpl',
			'side',
			'default'
		);

		add_meta_box(
			'wpnts_session_sailing',
			__( 'Conditions on the Day', 'wpnt-sailing' ),
			array( __CLASS__, 'session_fields' ),
			'wpnt_session',
			'side',
			'default'
		);
	}

	public static function session_tmpl_fields( WP_Post $post ): void {
		wp_nonce_field( 'wpnts_save_meta', 'wpnts_meta_nonce' );
		$min_wind   = get_post_meta( $post->ID, '_wpnts_min_wind', true );
		$max_wind   = get_post_meta( $post->ID, '_wpnts_max_wind', true );
		$sea_state  = get_post_meta( $post->ID, '_wpnts_sea_state', true );
		$conditions = get_post_meta( $post->ID, '_wpnts_tmpl_conditions', true );
		?>
		<p>
			<label><?php esc_html_e( 'Min wind (knots)', 'wpnt-sailing' ); ?><br>
			<input type="number" name="wpnts_min_wind" value="<?php echo esc_attr( $min_wind ); ?>" min="0" max="50" style="width:100%"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Max wind (knots)', 'wpnt-sailing' ); ?><br>
			<input type="number" name="wpnts_max_wind" value="<?php echo esc_attr( $max_wind ); ?>" min="0" max="50" style="width:100%"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Sea state', 'wpnt-sailing' ); ?><br>
			<select name="wpnts_sea_state" style="width:100%">
				<option value=""><?php esc_html_e( '—', 'wpnt-sailing' ); ?></option>
				<?php foreach ( array( 'flat' => 'Flat', 'small_chop' => 'Small chop', 'moderate_chop' => 'Moderate chop', 'rough' => 'Rough' ) as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $sea_state, $val ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Conditions notes', 'wpnt-sailing' ); ?><br>
			<textarea name="wpnts_tmpl_conditions" rows="2" style="width:100%"><?php echo esc_textarea( $conditions ); ?></textarea></label>
		</p>
		<?php
	}

	public static function session_fields( WP_Post $post ): void {
		wp_nonce_field( 'wpnts_save_meta', 'wpnts_meta_nonce' );
		$actual_wind      = get_post_meta( $post->ID, '_wpnts_actual_wind', true );
		$actual_sea_state = get_post_meta( $post->ID, '_wpnts_actual_sea_state', true );
		$conditions       = get_post_meta( $post->ID, '_wpnts_conditions', true );
		?>
		<p>
			<label><?php esc_html_e( 'Wind on the day (knots)', 'wpnt-sailing' ); ?><br>
			<input type="text" name="wpnts_actual_wind" value="<?php echo esc_attr( $actual_wind ); ?>" placeholder="e.g. 8–12" style="width:100%"></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Sea state', 'wpnt-sailing' ); ?><br>
			<select name="wpnts_actual_sea_state" style="width:100%">
				<option value=""><?php esc_html_e( '—', 'wpnt-sailing' ); ?></option>
				<?php foreach ( array( 'flat' => 'Flat', 'small_chop' => 'Small chop', 'moderate_chop' => 'Moderate chop', 'rough' => 'Rough' ) as $val => $label ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $actual_sea_state, $val ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Conditions notes', 'wpnt-sailing' ); ?><br>
			<textarea name="wpnts_conditions" rows="2" style="width:100%"><?php echo esc_textarea( $conditions ); ?></textarea></label>
		</p>
		<?php
	}

	public static function save( int $post_id, WP_Post $post ): void {
		if ( ! isset( $_POST['wpnts_meta_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpnts_meta_nonce'] ) ), 'wpnts_save_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( self::$fields as $field => $meta_key ) {
			if ( ! isset( $_POST[ $field ] ) ) {
				continue;
			}
			$value = sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) );
			if ( $value === '' ) {
				delete_post_meta( $post_id, $meta_key );
			} else {
				update_post_meta( $post_id, $meta_key, $value );
			}
		}
	}
}
