<?php
/**
 * Plugin Name: Waypoint Network Training (wpnt)
 * Plugin URI:  https://github.com/carmourone/waypoint
 * Description: Sailing coaching and program delivery engine for Waypoint.
 * Version:     0.1.0
 * Author:      Waypoint
 * License:     GPL-2.0-or-later
 * Text Domain: wpnt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPNT_VERSION', '0.1.0' );
define( 'WPNT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPNT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPNT_DB_VERSION', '2' );

require_once WPNT_PLUGIN_DIR . 'includes/class-wpnt-activator.php';
require_once WPNT_PLUGIN_DIR . 'includes/class-wpnt-db.php';
require_once WPNT_PLUGIN_DIR . 'includes/class-wpnt-post-types.php';
require_once WPNT_PLUGIN_DIR . 'includes/class-wpnt-taxonomies.php';
require_once WPNT_PLUGIN_DIR . 'includes/class-wpnt-roles.php';
require_once WPNT_PLUGIN_DIR . 'includes/class-wpnt-meta-boxes.php';
require_once WPNT_PLUGIN_DIR . 'includes/class-wpnt-attendance.php';
require_once WPNT_PLUGIN_DIR . 'includes/class-wpnt-session-group.php';
require_once WPNT_PLUGIN_DIR . 'includes/class-wpnt-course.php';
require_once WPNT_PLUGIN_DIR . 'includes/class-wpnt-session.php';
require_once WPNT_PLUGIN_DIR . 'includes/class-wpnt-rest-api.php';
require_once WPNT_PLUGIN_DIR . 'includes/class-wpnt-buddypress.php';
require_once WPNT_PLUGIN_DIR . 'admin/class-wpnt-admin.php';

register_activation_hook( __FILE__, array( 'WPNT_Activator', 'activate' ) );
add_action( 'plugins_loaded', array( 'WPNT_DB', 'maybe_upgrade' ) );
register_deactivation_hook( __FILE__, array( 'WPNT_Activator', 'deactivate' ) );

add_action( 'init', array( 'WPNT_Post_Types', 'register' ) );
add_action( 'init', array( 'WPNT_Taxonomies', 'register' ) );
add_action( 'rest_api_init', array( 'WPNT_REST_API', 'register_routes' ) );
add_action( 'add_meta_boxes', array( 'WPNT_Meta_Boxes', 'register' ) );
add_action( 'save_post', array( 'WPNT_Meta_Boxes', 'save' ), 10, 2 );
add_action( 'admin_menu', array( 'WPNT_Admin', 'add_menus' ) );
add_action( 'admin_enqueue_scripts', array( 'WPNT_Admin', 'enqueue_assets' ) );
add_action( 'wp_enqueue_scripts', 'wpnt_enqueue_frontend_assets' );

if ( function_exists( 'buddypress' ) ) {
	add_action( 'bp_init', array( 'WPNT_BuddyPress', 'init' ) );
}

function wpnt_enqueue_frontend_assets(): void {
	if ( is_singular( array( 'wpnt_course', 'wpnt_session', 'wpnt_training_plan' ) )
		|| is_page_template( array(
			'page-templates/template-coach-dashboard.php',
			'page-templates/template-parent-dashboard.php',
			'page-templates/template-sailor-dashboard.php',
			'page-templates/template-club-dashboard.php',
		) )
	) {
		wp_enqueue_style(
			'wpnt-frontend',
			WPNT_PLUGIN_URL . 'assets/css/wpnt-frontend.css',
			array(),
			WPNT_VERSION
		);
		wp_enqueue_script(
			'wpnt-frontend',
			WPNT_PLUGIN_URL . 'assets/js/wpnt-frontend.js',
			array( 'jquery' ),
			WPNT_VERSION,
			true
		);
		wp_localize_script( 'wpnt-frontend', 'wpntData', array(
			'restUrl' => esc_url_raw( rest_url( 'wpnt/v1/' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'userId'  => get_current_user_id(),
		) );
	}
}
