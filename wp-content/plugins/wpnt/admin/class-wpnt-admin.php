<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNT_Admin {

	public static function add_menus(): void {
		// Top-level Waypoint menu.
		add_menu_page(
			__( 'Waypoint', 'wpnt' ),
			__( 'Waypoint', 'wpnt' ),
			'read',
			'wpnt',
			array( __CLASS__, 'page_dashboard' ),
			'dashicons-anchor',
			3
		);

		add_submenu_page( 'wpnt', __( 'Dashboard', 'wpnt' ), __( 'Dashboard', 'wpnt' ), 'read', 'wpnt', array( __CLASS__, 'page_dashboard' ) );
		add_submenu_page( 'wpnt', __( 'Today', 'wpnt' ), __( 'Today', 'wpnt' ), 'read', 'wpnt-today', array( __CLASS__, 'page_today' ) );
		add_submenu_page( 'wpnt', __( 'Attendance', 'wpnt' ), __( 'Attendance', 'wpnt' ), 'edit_wpnt_sessions', 'wpnt-attendance', array( __CLASS__, 'page_attendance' ) );
		add_submenu_page( 'wpnt', __( 'Sailors', 'wpnt' ), __( 'Sailors', 'wpnt' ), 'read_private_wpnt_sessions', 'wpnt-sailors', array( __CLASS__, 'page_sailors' ) );
		add_submenu_page( 'wpnt', __( 'Training Plans', 'wpnt' ), __( 'Training Plans', 'wpnt' ), 'edit_wpnt_training_plans', 'wpnt-training-plans', array( __CLASS__, 'page_training_plans' ) );
		add_submenu_page( 'wpnt', __( 'Settings', 'wpnt' ), __( 'Settings', 'wpnt' ), 'manage_options', 'wpnt-settings', array( __CLASS__, 'page_settings' ) );
	}

	public static function enqueue_assets( string $hook ): void {
		if ( ! str_contains( $hook, 'wpnt' ) && ! in_array( get_post_type(), array( 'wpnt_course', 'wpnt_session', 'wpnt_training_plan', 'wpnt_skill', 'wpnt_drill', 'wpnt_node', 'wpnt_curriculum', 'wpnt_session_tmpl' ), true ) ) {
			return;
		}

		wp_enqueue_style( 'wpnt-admin', WPNT_PLUGIN_URL . 'assets/css/wpnt-admin.css', array(), WPNT_VERSION );
		wp_enqueue_script( 'wpnt-admin', WPNT_PLUGIN_URL . 'assets/js/wpnt-admin.js', array( 'jquery', 'wp-api' ), WPNT_VERSION, true );
		wp_localize_script( 'wpnt-admin', 'wpntAdmin', array(
			'restUrl' => esc_url_raw( rest_url( 'wpnt/v1/' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'l10n'    => array(
				'saving'  => __( 'Saving…', 'wpnt' ),
				'saved'   => __( 'Saved', 'wpnt' ),
				'error'   => __( 'Error saving. Please try again.', 'wpnt' ),
				'confirm' => __( 'Are you sure?', 'wpnt' ),
			),
		) );
	}

	// -------------------------------------------------------------------------
	// Page callbacks
	// -------------------------------------------------------------------------

	public static function page_dashboard(): void {
		include WPNT_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	public static function page_today(): void {
		include WPNT_PLUGIN_DIR . 'admin/views/today.php';
	}

	public static function page_attendance(): void {
		include WPNT_PLUGIN_DIR . 'admin/views/attendance.php';
	}

	public static function page_sailors(): void {
		include WPNT_PLUGIN_DIR . 'admin/views/sailors.php';
	}

	public static function page_training_plans(): void {
		include WPNT_PLUGIN_DIR . 'admin/views/training-plans.php';
	}

	public static function page_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wpnt' ) );
		}

		if ( isset( $_POST['wpnt_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpnt_settings_nonce'] ) ), 'wpnt_save_settings' ) ) {
			update_option( 'wpnt_club_name', sanitize_text_field( $_POST['wpnt_club_name'] ?? '' ) );
			update_option( 'wpnt_club_location', sanitize_text_field( $_POST['wpnt_club_location'] ?? '' ) );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'wpnt' ) . '</p></div>';
		}

		$club_name     = get_option( 'wpnt_club_name', '' );
		$club_location = get_option( 'wpnt_club_location', '' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Waypoint Settings', 'wpnt' ); ?></h1>
			<form method="post">
				<?php wp_nonce_field( 'wpnt_save_settings', 'wpnt_settings_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="wpnt_club_name"><?php esc_html_e( 'Club Name', 'wpnt' ); ?></label></th>
						<td><input type="text" id="wpnt_club_name" name="wpnt_club_name" value="<?php echo esc_attr( $club_name ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="wpnt_club_location"><?php esc_html_e( 'Club Location', 'wpnt' ); ?></label></th>
						<td><input type="text" id="wpnt_club_location" name="wpnt_club_location" value="<?php echo esc_attr( $club_location ); ?>" class="regular-text"></td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Settings', 'wpnt' ) ); ?>
			</form>
		</div>
		<?php
	}
}
