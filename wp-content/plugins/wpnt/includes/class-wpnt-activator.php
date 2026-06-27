<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNT_Activator {

	public static function activate(): void {
		WPNT_Post_Types::register();
		WPNT_Taxonomies::register();
		WPNT_Roles::add_roles();
		WPNT_DB::create_tables();
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
