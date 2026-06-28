<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNT_Activator {

	public static function activate(): void {
		if ( is_multisite() ) {
			// Network-activated: provision every existing site.
			foreach ( get_sites( array( 'number' => 1000 ) ) as $site ) {
				switch_to_blog( $site->blog_id );
				self::provision_site();
				restore_current_blog();
			}
		} else {
			self::provision_site();
		}
	}

	/**
	 * Called by wpmu_new_blog to provision a freshly-created site.
	 */
	public static function activate_for_site( WP_Site $new_site ): void {
		switch_to_blog( (int) $new_site->blog_id );
		self::provision_site();
		restore_current_blog();
	}

	public static function deactivate(): void {
		flush_rewrite_rules();
	}

	private static function provision_site(): void {
		WPNT_Post_Types::register();
		WPNT_Taxonomies::register();
		WPNT_Roles::add_roles();
		WPNT_DB::create_tables();
		self::create_dashboard_pages();
		flush_rewrite_rules();
	}

	/**
	 * Create the three dashboard pages on first activation (idempotent).
	 * Page IDs are stored in options so menus and widgets can reference them.
	 */
	private static function create_dashboard_pages(): void {
		$pages = array(
			'wpnt_coach_dashboard_page_id'   => array(
				'title'    => 'Coach Dashboard',
				'slug'     => 'coach-dashboard',
				'template' => 'page-templates/template-coach-dashboard.php',
			),
			'wpnt_athlete_dashboard_page_id' => array(
				'title'    => 'Athlete Dashboard',
				'slug'     => 'athlete-dashboard',
				'template' => 'page-templates/template-athlete-dashboard.php',
			),
			'wpnt_parent_dashboard_page_id'  => array(
				'title'    => 'Parent Dashboard',
				'slug'     => 'parent-dashboard',
				'template' => 'page-templates/template-parent-dashboard.php',
			),
		);

		foreach ( $pages as $option => $cfg ) {
			// Already created and still exists — skip.
			$existing_id = (int) get_option( $option, 0 );
			if ( $existing_id && get_post( $existing_id ) ) {
				continue;
			}

			// Slug already exists — adopt it.
			$by_slug = get_page_by_path( $cfg['slug'], OBJECT, 'page' );
			if ( $by_slug ) {
				update_option( $option, $by_slug->ID );
				continue;
			}

			$page_id = wp_insert_post( array(
				'post_type'   => 'page',
				'post_title'  => $cfg['title'],
				'post_name'   => $cfg['slug'],
				'post_status' => 'publish',
				'post_author' => get_current_user_id() ?: 1,
				'meta_input'  => array(
					'_wp_page_template' => $cfg['template'],
				),
			) );

			if ( $page_id && ! is_wp_error( $page_id ) ) {
				update_option( $option, $page_id );
			}
		}
	}
}
