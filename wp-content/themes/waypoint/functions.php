<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WAYPOINT_THEME_VERSION', '0.1.0' );
define( 'WAYPOINT_THEME_DIR', get_template_directory() );
define( 'WAYPOINT_THEME_URL', get_template_directory_uri() );

// -------------------------------------------------------------------------
// Theme setup
// -------------------------------------------------------------------------
add_action( 'after_setup_theme', 'waypoint_setup' );

function waypoint_setup(): void {
	load_theme_textdomain( 'waypoint', WAYPOINT_THEME_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'wp-block-styles' );

	register_nav_menus( array(
		'primary'    => __( 'Primary Menu', 'waypoint' ),
		'coach'      => __( 'Coach Menu', 'waypoint' ),
		'athlete'    => __( 'Athlete Menu', 'waypoint' ),
		'parent'     => __( 'Parent Menu', 'waypoint' ),
		'footer'     => __( 'Footer Menu', 'waypoint' ),
	) );
}

// -------------------------------------------------------------------------
// Enqueue scripts and styles
// -------------------------------------------------------------------------
add_action( 'wp_enqueue_scripts', 'waypoint_enqueue_assets' );

function waypoint_enqueue_assets(): void {
	wp_enqueue_style(
		'waypoint-style',
		get_stylesheet_uri(),
		array(),
		WAYPOINT_THEME_VERSION
	);
	wp_enqueue_script(
		'waypoint-main',
		WAYPOINT_THEME_URL . '/assets/js/waypoint.js',
		array( 'jquery' ),
		WAYPOINT_THEME_VERSION,
		true
	);
}

// -------------------------------------------------------------------------
// Sidebars / widget areas
// -------------------------------------------------------------------------
add_action( 'widgets_init', 'waypoint_register_sidebars' );

function waypoint_register_sidebars(): void {
	register_sidebar( array(
		'name'          => __( 'Dashboard Sidebar', 'waypoint' ),
		'id'            => 'dashboard-sidebar',
		'before_widget' => '<div class="card widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="card-title">',
		'after_title'   => '</h3>',
	) );

	register_sidebar( array(
		'name'          => __( 'Course Page Sidebar', 'waypoint' ),
		'id'            => 'course-sidebar',
		'before_widget' => '<div class="card widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h3 class="card-title">',
		'after_title'   => '</h3>',
	) );
}

// -------------------------------------------------------------------------
// Template routing helpers
// -------------------------------------------------------------------------

/**
 * Load a template part with an optional context array injected as local vars.
 */
function waypoint_get_template_part( string $slug, string $name = '', array $args = [] ): void {
	$templates = array();
	if ( $name ) {
		$templates[] = "template-parts/{$slug}-{$name}.php";
	}
	$templates[] = "template-parts/{$slug}.php";

	foreach ( $templates as $template ) {
		$path = WAYPOINT_THEME_DIR . '/' . $template;
		if ( file_exists( $path ) ) {
			if ( ! empty( $args ) ) {
				// Expose args as local variables.
				extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
			}
			include $path;
			return;
		}
	}
}

/**
 * Return the Waypoint role of the current user.
 */
function waypoint_current_role(): string {
	if ( ! is_user_logged_in() ) {
		return 'guest';
	}
	$user  = wp_get_current_user();
	$roles = $user->roles;

	$priority = array( 'administrator', 'wpnt_org_admin', 'wpnt_coach', 'wpnt_asst_coach', 'wpnt_parent', 'wpnt_athlete' );
	foreach ( $priority as $role ) {
		if ( in_array( $role, $roles, true ) ) {
			return $role;
		}
	}
	return 'subscriber';
}

/**
 * Return a human-readable role label.
 */
function waypoint_role_label( string $role = '' ): string {
	$role = $role ?: waypoint_current_role();
	$participant_lbl = class_exists( 'WPNT_Pack' ) ? WPNT_Pack::get_active_label( 'participant_label', __( 'Athlete', 'waypoint' ) ) : __( 'Athlete', 'waypoint' );
	$org_lbl         = class_exists( 'WPNT_Pack' ) ? WPNT_Pack::get_active_label( 'org_label', __( 'Org', 'waypoint' ) ) : __( 'Org', 'waypoint' );
	$labels = array(
		'administrator'  => __( 'Administrator', 'waypoint' ),
		'wpnt_org_admin' => $org_lbl . ' ' . __( 'Admin', 'waypoint' ),
		'wpnt_coach'     => __( 'Coach', 'waypoint' ),
		'wpnt_asst_coach'=> __( 'Assistant Coach', 'waypoint' ),
		'wpnt_parent'    => __( 'Parent', 'waypoint' ),
		'wpnt_athlete'   => $participant_lbl,
	);
	return $labels[ $role ] ?? ucfirst( $role );
}

/**
 * Check if the wpnt plugin is active.
 */
function waypoint_plugin_active(): bool {
	return class_exists( 'WPNT_Course' );
}

// -------------------------------------------------------------------------
// Body classes
// -------------------------------------------------------------------------
add_filter( 'body_class', 'waypoint_body_classes' );

function waypoint_body_classes( array $classes ): array {
	$role     = waypoint_current_role();
	$classes[] = 'waypoint-theme';
	$classes[] = 'role-' . sanitize_html_class( $role );

	if ( is_singular( 'wpnt_course' ) ) {
		$classes[] = 'single-course';
	}
	if ( is_singular( 'wpnt_session' ) ) {
		$classes[] = 'single-session';
	}

	return $classes;
}

// -------------------------------------------------------------------------
// Excerpt
// -------------------------------------------------------------------------
add_filter( 'excerpt_length', fn() => 28 );

// -------------------------------------------------------------------------
// Shortcodes — thin wrappers; real logic lives in wpnt plugin
// -------------------------------------------------------------------------

// [waypoint_coach_dashboard]
add_shortcode( 'waypoint_coach_dashboard', 'waypoint_sc_coach_dashboard' );
function waypoint_sc_coach_dashboard(): string {
	if ( ! is_user_logged_in() ) {
		return '<p class="notice-wp info">' . esc_html__( 'Please log in to view your dashboard.', 'waypoint' ) . '</p>';
	}
	ob_start();
	waypoint_get_template_part( 'content/dashboard', 'coach' );
	return ob_get_clean();
}

// [waypoint_sailor_dashboard]
add_shortcode( 'waypoint_sailor_dashboard', 'waypoint_sc_sailor_dashboard' );
function waypoint_sc_sailor_dashboard(): string {
	if ( ! is_user_logged_in() ) {
		return '<p class="notice-wp info">' . esc_html__( 'Please log in to view your dashboard.', 'waypoint' ) . '</p>';
	}
	ob_start();
	waypoint_get_template_part( 'content/dashboard', 'sailor' );
	return ob_get_clean();
}

// [waypoint_parent_dashboard]
add_shortcode( 'waypoint_parent_dashboard', 'waypoint_sc_parent_dashboard' );
function waypoint_sc_parent_dashboard(): string {
	if ( ! is_user_logged_in() ) {
		return '<p class="notice-wp info">' . esc_html__( 'Please log in to view your dashboard.', 'waypoint' ) . '</p>';
	}
	ob_start();
	waypoint_get_template_part( 'content/dashboard', 'parent' );
	return ob_get_clean();
}

// [waypoint_course_list]
add_shortcode( 'waypoint_course_list', 'waypoint_sc_course_list' );
function waypoint_sc_course_list( array $atts ): string {
	$atts = shortcode_atts( array( 'status' => 'active', 'limit' => 10 ), $atts, 'waypoint_course_list' );
	ob_start();
	waypoint_get_template_part( 'content/course', 'list', array( 'atts' => $atts ) );
	return ob_get_clean();
}
