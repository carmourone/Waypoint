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
		flush_rewrite_rules();
	}
}
