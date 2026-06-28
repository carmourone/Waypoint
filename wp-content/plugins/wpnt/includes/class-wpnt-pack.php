<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lightweight registry for Waypoint domain packs.
 *
 * Packs call WPNT_Pack::register() inside a wpnt_packs_init callback.
 * The registry is used by the Waypoint settings screen to list installed packs
 * and by the importer to locate curriculum data files.
 */
class WPNT_Pack {

	private static array $registry = array();

	/**
	 * Register a domain pack.
	 *
	 * @param array{
	 *   id:          string,
	 *   name:        string,
	 *   version:     string,
	 *   description: string,
	 *   data_dir?:   string,
	 * } $args
	 */
	public static function register( array $args ): void {
		$id = sanitize_key( $args['id'] ?? '' );
		if ( ! $id ) {
			return;
		}
		self::$registry[ $id ] = wp_parse_args( $args, array(
			'name'        => $id,
			'version'     => '1.0.0',
			'description' => '',
			'data_dir'    => '',
		) );
	}

	public static function get_all(): array {
		return self::$registry;
	}

	public static function get( string $id ): ?array {
		return self::$registry[ $id ] ?? null;
	}

	public static function is_active( string $id ): bool {
		return isset( self::$registry[ $id ] );
	}
}
