<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNT_Sailing_Taxonomies {

	public static function register(): void {
		// Boat class — e.g. Laser, Optimist, 29er, Tackers dinghy.
		register_taxonomy( 'wpnt_boat_class', array( 'wpnt_drill', 'wpnt_skill', 'wpnt_session_tmpl', 'wpnt_course' ), array(
			'label'             => __( 'Boat Class', 'wpnt-sailing' ),
			'hierarchical'      => false,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'boat-class' ),
		) );

		// Wind range — suitable conditions for a drill or template.
		register_taxonomy( 'wpnt_wind_range', array( 'wpnt_drill', 'wpnt_session_tmpl' ), array(
			'label'             => __( 'Wind Range', 'wpnt-sailing' ),
			'hierarchical'      => false,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => false,
			'rewrite'           => array( 'slug' => 'wind-range' ),
		) );
	}
}
