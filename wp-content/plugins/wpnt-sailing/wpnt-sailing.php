<?php
/**
 * Plugin Name: Waypoint — Sailing Pack
 * Plugin URI:  https://github.com/carmourone/waypoint
 * Description: Sailing domain pack for Waypoint. Adds boat class and wind range taxonomies, sailing-specific session fields, and seeds the Tackers / Keelboat curriculum.
 * Version:     1.0.0
 * Author:      Waypoint
 * Requires Plugins: wpnt-core
 * License:     GPL-2.0-or-later
 * Text Domain: wpnt-sailing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPNT_SAILING_VERSION', '1.0.0' );
define( 'WPNT_SAILING_DIR', plugin_dir_path( __FILE__ ) );

require_once WPNT_SAILING_DIR . 'includes/class-wpnt-sailing-taxonomies.php';
require_once WPNT_SAILING_DIR . 'includes/class-wpnt-sailing-meta.php';
require_once WPNT_SAILING_DIR . 'includes/class-wpnt-sailing-importer.php';

register_activation_hook( __FILE__, array( 'WPNT_Sailing_Importer', 'maybe_seed' ) );

add_action( 'wpnt_packs_init', function () {
	WPNT_Pack::register( array(
		'id'                      => 'sailing',
		'name'                    => 'Sailing',
		'version'                 => WPNT_SAILING_VERSION,
		'description'             => 'Boat class and wind range taxonomies, sailing-specific session conditions fields, and a seeded Tackers / Keelboat / Race curriculum.',
		'data_dir'                => WPNT_SAILING_DIR . 'data/',
		'participant_label'       => __( 'Sailor', 'wpnt-sailing' ),
		'participant_label_plural'=> __( 'Sailors', 'wpnt-sailing' ),
		'org_label'               => __( 'Club', 'wpnt-sailing' ),
	) );
} );

add_action( 'init', array( 'WPNT_Sailing_Taxonomies', 'register' ) );
add_action( 'add_meta_boxes', array( 'WPNT_Sailing_Meta', 'register' ) );
add_action( 'save_post', array( 'WPNT_Sailing_Meta', 'save' ), 10, 2 );
