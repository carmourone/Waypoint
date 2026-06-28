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
 *
 * Participant label keys (optional, falls back to core defaults):
 *   participant_label         — singular, e.g. "Sailor", "Player"   (default: "Athlete")
 *   participant_label_plural  — plural,   e.g. "Sailors", "Players" (default: "Athletes")
 *   org_label                 — org type, e.g. "Club", "Academy"    (default: "Org")
 */
class WPNT_Pack {

	private static array $registry = array();

	/**
	 * Register a domain pack.
	 *
	 * @param array{
	 *   id:                       string,
	 *   name:                     string,
	 *   version:                  string,
	 *   description:              string,
	 *   data_dir?:                string,
	 *   participant_label?:       string,
	 *   participant_label_plural?: string,
	 *   org_label?:               string,
	 * } $args
	 */
	public static function register( array $args ): void {
		$id = sanitize_key( $args['id'] ?? '' );
		if ( ! $id ) {
			return;
		}
		self::$registry[ $id ] = wp_parse_args( $args, array(
			'name'                     => $id,
			'version'                  => '1.0.0',
			'description'              => '',
			'data_dir'                 => '',
			'participant_label'        => '',
			'participant_label_plural' => '',
			'org_label'                => '',
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

	/**
	 * Get a label from the first registered pack that provides it.
	 * Falls back to $default if no pack defines the key.
	 *
	 * @param string $key     One of: participant_label, participant_label_plural, org_label.
	 * @param string $default Fallback value (core generic term).
	 */
	public static function get_active_label( string $key, string $default ): string {
		foreach ( self::$registry as $pack ) {
			if ( ! empty( $pack[ $key ] ) ) {
				return (string) $pack[ $key ];
			}
		}
		return $default;
	}
}
