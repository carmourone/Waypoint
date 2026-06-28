<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNT_Taxonomies {

	public static function register(): void {
		// Skill area — e.g. boat handling, tactics, safety, fitness.
		register_taxonomy( 'wpnt_skill_area', array( 'wpnt_skill', 'wpnt_drill' ), array(
			'label'             => __( 'Skill Area', 'wpnt' ),
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'skill-area' ),
		) );

		// Experience level — e.g. beginner, intermediate, advanced.
		register_taxonomy( 'wpnt_exp_level', array( 'wpnt_course', 'wpnt_session_tmpl', 'wpnt_curriculum' ), array(
			'label'             => __( 'Experience Level', 'wpnt' ),
			'hierarchical'      => false,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'experience-level' ),
		) );

		// Season — e.g. "Term 3 2026", "Summer 2026".
		register_taxonomy( 'wpnt_season', array( 'wpnt_course' ), array(
			'label'             => __( 'Season', 'wpnt' ),
			'hierarchical'      => false,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'season' ),
		) );

		// Session type — e.g. on-water, land-based, theory, race-training.
		register_taxonomy( 'wpnt_session_type', array( 'wpnt_session', 'wpnt_session_tmpl' ), array(
			'label'             => __( 'Session Type', 'wpnt' ),
			'hierarchical'      => false,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'session-type' ),
		) );
	}
}
