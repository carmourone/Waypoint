<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNT_Post_Types {

	public static function register(): void {
		self::register_curriculum_pack();
		self::register_curriculum_node();
		self::register_skill();
		self::register_drill();
		self::register_session_template();
		self::register_course();
		self::register_session();
		self::register_training_plan();
		self::register_observation();
		self::register_diary_template();
		self::register_diary_entry();
	}

	private static function register_curriculum_pack(): void {
		register_post_type( 'wpnt_curriculum', array(
			'label'               => __( 'Curriculum Packs', 'wpnt' ),
			'labels'              => self::labels( 'Curriculum Pack', 'Curriculum Packs' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'wpnt',
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-book-alt',
			'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'capability_type'     => array( 'wpnt_curriculum', 'wpnt_curriculums' ),
			'map_meta_cap'        => true,
			'hierarchical'        => false,
			'rewrite'             => array( 'slug' => 'curriculum' ),
		) );
	}

	private static function register_curriculum_node(): void {
		register_post_type( 'wpnt_node', array(
			'label'               => __( 'Curriculum Nodes', 'wpnt' ),
			'labels'              => self::labels( 'Curriculum Node', 'Curriculum Nodes' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'wpnt',
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'editor', 'page-attributes' ),
			'capability_type'     => array( 'wpnt_node', 'wpnt_nodes' ),
			'map_meta_cap'        => true,
			'hierarchical'        => true,
			'rewrite'             => array( 'slug' => 'curriculum-node' ),
		) );
	}

	private static function register_skill(): void {
		register_post_type( 'wpnt_skill', array(
			'label'               => __( 'Skills', 'wpnt' ),
			'labels'              => self::labels( 'Skill', 'Skills' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'wpnt',
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'editor' ),
			'capability_type'     => array( 'wpnt_skill', 'wpnt_skills' ),
			'map_meta_cap'        => true,
			'rewrite'             => array( 'slug' => 'skill' ),
		) );
	}

	private static function register_drill(): void {
		register_post_type( 'wpnt_drill', array(
			'label'               => __( 'Drills', 'wpnt' ),
			'labels'              => self::labels( 'Drill', 'Drills' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'wpnt',
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'editor', 'thumbnail' ),
			'capability_type'     => array( 'wpnt_drill', 'wpnt_drills' ),
			'map_meta_cap'        => true,
			'rewrite'             => array( 'slug' => 'drill' ),
		) );
	}

	private static function register_session_template(): void {
		register_post_type( 'wpnt_session_tmpl', array(
			'label'               => __( 'Session Templates', 'wpnt' ),
			'labels'              => self::labels( 'Session Template', 'Session Templates' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'wpnt',
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'editor' ),
			'capability_type'     => array( 'wpnt_session_tmpl', 'wpnt_session_tmpls' ),
			'map_meta_cap'        => true,
			'rewrite'             => array( 'slug' => 'session-template' ),
		) );
	}

	private static function register_course(): void {
		register_post_type( 'wpnt_course', array(
			'label'               => __( 'Courses', 'wpnt' ),
			'labels'              => self::labels( 'Course', 'Courses' ),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => 'wpnt',
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-welcome-learn-more',
			'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'capability_type'     => array( 'wpnt_course', 'wpnt_courses' ),
			'map_meta_cap'        => true,
			'rewrite'             => array( 'slug' => 'course' ),
		) );
	}

	private static function register_session(): void {
		register_post_type( 'wpnt_session', array(
			'label'               => __( 'Sessions', 'wpnt' ),
			'labels'              => self::labels( 'Session', 'Sessions' ),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => 'wpnt',
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'editor', 'revisions' ),
			'capability_type'     => array( 'wpnt_session', 'wpnt_sessions' ),
			'map_meta_cap'        => true,
			'rewrite'             => array( 'slug' => 'session' ),
		) );
	}

	private static function register_training_plan(): void {
		register_post_type( 'wpnt_training_plan', array(
			'label'               => __( 'Training Plans', 'wpnt' ),
			'labels'              => self::labels( 'Training Plan', 'Training Plans' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'editor', 'revisions', 'page-attributes' ),
			'capability_type'     => array( 'wpnt_training_plan', 'wpnt_training_plans' ),
			'map_meta_cap'        => true,
			'hierarchical'        => true,
			'rewrite'             => array( 'slug' => 'training-plan' ),
		) );
	}

	private static function register_diary_template(): void {
		register_post_type( 'wpnt_diary_template', array(
			'label'           => __( 'Diary Templates', 'wpnt' ),
			'labels'          => self::labels( 'Diary Template', 'Diary Templates' ),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'wpnt',
			'show_in_rest'    => true,
			'supports'        => array( 'title', 'editor', 'revisions' ),
			'capability_type' => array( 'wpnt_diary_template', 'wpnt_diary_templates' ),
			'map_meta_cap'    => true,
			'rewrite'         => false,
		) );
	}

	private static function register_diary_entry(): void {
		register_post_type( 'wpnt_diary_entry', array(
			'label'           => __( 'Diary Entries', 'wpnt' ),
			'labels'          => self::labels( 'Diary Entry', 'Diary Entries' ),
			'public'          => false,
			'show_ui'         => false,
			'show_in_rest'    => false,
			'supports'        => array( 'title', 'editor' ),
			'capability_type' => array( 'wpnt_diary_entry', 'wpnt_diary_entries' ),
			'map_meta_cap'    => true,
			'rewrite'         => false,
		) );
	}

	private static function register_observation(): void {
		register_post_type( 'wpnt_observation', array(
			'label'               => __( 'Observations', 'wpnt' ),
			'labels'              => self::labels( 'Observation', 'Observations' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'wpnt',
			'show_in_rest'        => false,
			'supports'            => array( 'title', 'editor', 'revisions' ),
			'capability_type'     => array( 'wpnt_observation', 'wpnt_observations' ),
			'map_meta_cap'        => true,
			'rewrite'             => false,
		) );
	}

	private static function labels( string $singular, string $plural ): array {
		return array(
			'name'               => $plural,
			'singular_name'      => $singular,
			'add_new'            => 'Add New',
			'add_new_item'       => "Add New $singular",
			'edit_item'          => "Edit $singular",
			'new_item'           => "New $singular",
			'view_item'          => "View $singular",
			'search_items'       => "Search $plural",
			'not_found'          => "No $plural found",
			'not_found_in_trash' => "No $plural in trash",
		);
	}
}
