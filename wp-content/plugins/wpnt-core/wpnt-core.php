<?php
/**
 * Plugin Name: Waypoint Core (wpnt)
 * Plugin URI:  https://github.com/carmourone/waypoint
 * Description: Domain-agnostic coaching and program delivery engine. Tracks participants, sessions, skills, progress, and training plans. Domain packs extend it for specific sports.
 * Version:     0.2.0
 * Author:      Waypoint
 * License:     GPL-2.0-or-later
 * Text Domain: wpnt
 * Network:     true
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPNT_VERSION', '0.2.0' );
define( 'WPNT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPNT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPNT_DB_VERSION', '7' );

require_once WPNT_PLUGIN_DIR . 'includes/class-wpnt-pack.php';
require_once WPNT_PLUGIN_DIR . 'includes/class-wpnt-graph.php';
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
require_once WPNT_PLUGIN_DIR . 'includes/class-wpnt-training-plan.php';
require_once WPNT_PLUGIN_DIR . 'includes/class-wpnt-diary.php';
require_once WPNT_PLUGIN_DIR . 'includes/class-wpnt-rest-api.php';
require_once WPNT_PLUGIN_DIR . 'includes/class-wpnt-buddypress.php';
require_once WPNT_PLUGIN_DIR . 'admin/class-wpnt-admin.php';

register_activation_hook( __FILE__, array( 'WPNT_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPNT_Activator', 'deactivate' ) );

// Provision each new site on Multisite automatically.
add_action( 'wpmu_new_blog', array( 'WPNT_Activator', 'activate_for_site' ) );

add_action( 'plugins_loaded', array( 'WPNT_DB', 'maybe_upgrade' ) );
add_action( 'plugins_loaded', function() { do_action( 'wpnt_packs_init' ); }, 20 );

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
	$form_templates = array(
		'page-templates/template-new-session.php',
		'page-templates/template-new-training-plan.php',
	);
	$all_templates  = array_merge( $form_templates, array(
		'page-templates/template-coach-dashboard.php',
		'page-templates/template-parent-dashboard.php',
		'page-templates/template-athlete-dashboard.php',
	) );

	$is_wpnt_page = is_singular( array( 'wpnt_course', 'wpnt_session', 'wpnt_training_plan', 'wpnt_diary_entry' ) )
		|| is_page_template( $all_templates );

	if ( ! $is_wpnt_page ) {
		return;
	}

	wp_enqueue_style(
		'wpnt-frontend',
		WPNT_PLUGIN_URL . 'assets/css/wpnt-frontend.css',
		array(),
		WPNT_VERSION
	);

	$localize = array(
		'restUrl' => esc_url_raw( rest_url( 'wpnt/v1/' ) ),
		'nonce'   => wp_create_nonce( 'wp_rest' ),
		'userId'  => get_current_user_id(),
		'i18n'    => array(
			'saving' => __( 'Saving…', 'waypoint' ),
			'saved'  => __( 'Saved.', 'waypoint' ),
			'error'  => __( 'Something went wrong. Please try again.', 'waypoint' ),
		),
	);

	wp_enqueue_script(
		'wpnt-frontend',
		WPNT_PLUGIN_URL . 'assets/js/wpnt-frontend.js',
		array( 'jquery' ),
		WPNT_VERSION,
		true
	);
	wp_localize_script( 'wpnt-frontend', 'wpntData', $localize );

	// Form handler — only needed on pages with .wpnt-form elements.
	if ( is_singular( array( 'wpnt_session', 'wpnt_training_plan' ) )
		|| is_page_template( $all_templates )
	) {
		wp_enqueue_script(
			'wpnt-forms',
			WPNT_PLUGIN_URL . 'assets/js/wpnt-forms.js',
			array( 'jquery', 'wpnt-frontend' ),
			WPNT_VERSION,
			true
		);
	}
}
