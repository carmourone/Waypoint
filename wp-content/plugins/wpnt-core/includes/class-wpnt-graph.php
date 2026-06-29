<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNT_Graph {

	private static array $type_cache = [];

	// -------------------------------------------------------------------------
	// Type registration
	// -------------------------------------------------------------------------

	public static function register_type(
		string $pack,
		string $table_kind,
		string $name,
		string $label = '',
		string $label_plural = ''
	): int {
		global $wpdb;
		$table = $wpdb->prefix . 'wpnt_types';

		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE pack = %s AND name = %s",
			$pack,
			$name
		) );

		$data = array(
			'table_kind'   => $table_kind,
			'label'        => $label ?: $name,
			'label_plural' => $label_plural ?: $name,
		);

		if ( $existing ) {
			$wpdb->update( $table, $data, array( 'id' => (int) $existing ) );
			self::$type_cache = array();
			return (int) $existing;
		}

		$wpdb->insert( $table, array_merge( $data, array( 'pack' => $pack, 'name' => $name ) ) );
		self::$type_cache = array();
		return (int) $wpdb->insert_id;
	}

	public static function get_type( string $name, string $pack = '' ): ?object {
		$key = $pack ? "{$pack}:{$name}" : ":{$name}";
		if ( array_key_exists( $key, self::$type_cache ) ) {
			return self::$type_cache[ $key ] ?: null;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'wpnt_types';

		if ( $pack ) {
			$row = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$table} WHERE pack = %s AND name = %s",
				$pack,
				$name
			) );
		} else {
			$row = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$table} WHERE name = %s ORDER BY (pack = 'core') DESC LIMIT 1",
				$name
			) );
		}

		self::$type_cache[ $key ] = $row ?: false;
		return $row ?: null;
	}

	public static function get_type_id( string $name, string $pack = '' ): int {
		$type = self::get_type( $name, $pack );
		return $type ? (int) $type->id : 0;
	}

	public static function clear_type_cache(): void {
		self::$type_cache = array();
	}

	// -------------------------------------------------------------------------
	// Seed built-in types — called on activation and v5 upgrade
	// -------------------------------------------------------------------------

	public static function seed_types(): void {
		self::register_type( 'core', 'u2p', 'attended',        'Attendance',          'Attendance Records' );
		self::register_type( 'core', 'u2p', 'assessed',        'Assessment',          'Assessments' );
		self::register_type( 'core', 'g2p', 'session_group',   'Session Group',       'Session Groups' );
		self::register_type( 'core', 'p2p', 'plan_session',    'Plan Session',        'Plan Sessions' );
		self::register_type( 'core', 'p2p', 'objective_skill', 'Objective Skill',     'Objective Skills' );
	}

	// -------------------------------------------------------------------------
	// U2P — User → Post edges
	// -------------------------------------------------------------------------

	/**
	 * Upsert a user→post edge.
	 *
	 * @param int $context_id Optional discriminator (e.g. session_groups.id for group-scoped
	 *                        attendance). Zero means ungrouped.
	 */
	public static function upsert_u2p(
		string $type_name,
		int $user_id,
		int $post_id,
		array $data = array(),
		int $context_id = 0,
		string $pack = ''
	): bool {
		global $wpdb;
		$type_id = self::get_type_id( $type_name, $pack );
		if ( ! $type_id || ! $user_id || ! $post_id ) {
			return false;
		}

		$table    = $wpdb->prefix . 'wpnt_u2p';
		$json     = wp_json_encode( $data );
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE type_id = %d AND user_id = %d AND post_id = %d AND context_id = %d",
			$type_id,
			$user_id,
			$post_id,
			$context_id
		) );

		if ( $existing ) {
			return (bool) $wpdb->update( $table, array( 'data' => $json ), array( 'id' => (int) $existing ) );
		}

		return (bool) $wpdb->insert( $table, array(
			'type_id'    => $type_id,
			'user_id'    => $user_id,
			'post_id'    => $post_id,
			'context_id' => $context_id,
			'data'       => $json,
		) );
	}

	/**
	 * Query u2p edges. Supported $args keys: user_id, post_id, context_id.
	 * Omit context_id to get all context values; pass 0 to get only ungrouped edges.
	 */
	public static function get_u2p( string $type_name, array $args = array(), string $pack = '' ): array {
		global $wpdb;
		$type_id = self::get_type_id( $type_name, $pack );
		if ( ! $type_id ) {
			return array();
		}

		$table = $wpdb->prefix . 'wpnt_u2p';
		$where = array( $wpdb->prepare( 'type_id = %d', $type_id ) );

		if ( ! empty( $args['user_id'] ) ) {
			$where[] = $wpdb->prepare( 'user_id = %d', (int) $args['user_id'] );
		}
		if ( ! empty( $args['post_id'] ) ) {
			$where[] = $wpdb->prepare( 'post_id = %d', (int) $args['post_id'] );
		}
		if ( isset( $args['context_id'] ) ) {
			$where[] = $wpdb->prepare( 'context_id = %d', (int) $args['context_id'] );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results(
			'SELECT * FROM ' . $table . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY created_at ASC'
		);
	}

	public static function get_u2p_row(
		string $type_name,
		int $user_id,
		int $post_id,
		int $context_id = 0,
		string $pack = ''
	): ?object {
		global $wpdb;
		$type_id = self::get_type_id( $type_name, $pack );
		if ( ! $type_id ) {
			return null;
		}

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpnt_u2p WHERE type_id = %d AND user_id = %d AND post_id = %d AND context_id = %d",
			$type_id,
			$user_id,
			$post_id,
			$context_id
		) );
	}

	public static function delete_u2p(
		string $type_name,
		int $user_id,
		int $post_id,
		int $context_id = 0,
		string $pack = ''
	): bool {
		global $wpdb;
		$type_id = self::get_type_id( $type_name, $pack );
		if ( ! $type_id ) {
			return false;
		}

		return (bool) $wpdb->delete( $wpdb->prefix . 'wpnt_u2p', array(
			'type_id'    => $type_id,
			'user_id'    => $user_id,
			'post_id'    => $post_id,
			'context_id' => $context_id,
		) );
	}

	// -------------------------------------------------------------------------
	// P2P — Post → Post edges
	// -------------------------------------------------------------------------

	public static function upsert_p2p(
		string $type_name,
		int $source_id,
		int $target_id,
		array $data = array(),
		string $pack = ''
	): bool {
		global $wpdb;
		$type_id = self::get_type_id( $type_name, $pack );
		if ( ! $type_id || ! $source_id || ! $target_id ) {
			return false;
		}

		$table    = $wpdb->prefix . 'wpnt_p2p';
		$json     = wp_json_encode( $data );
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE type_id = %d AND source_id = %d AND target_id = %d",
			$type_id,
			$source_id,
			$target_id
		) );

		if ( $existing ) {
			return (bool) $wpdb->update( $table, array( 'data' => $json ), array( 'id' => (int) $existing ) );
		}

		return (bool) $wpdb->insert( $table, array(
			'type_id'   => $type_id,
			'source_id' => $source_id,
			'target_id' => $target_id,
			'data'      => $json,
		) );
	}

	/** Supported $args keys: source_id, target_id. */
	public static function get_p2p( string $type_name, array $args = array(), string $pack = '' ): array {
		global $wpdb;
		$type_id = self::get_type_id( $type_name, $pack );
		if ( ! $type_id ) {
			return array();
		}

		$table = $wpdb->prefix . 'wpnt_p2p';
		$where = array( $wpdb->prepare( 'type_id = %d', $type_id ) );

		if ( ! empty( $args['source_id'] ) ) {
			$where[] = $wpdb->prepare( 'source_id = %d', (int) $args['source_id'] );
		}
		if ( ! empty( $args['target_id'] ) ) {
			$where[] = $wpdb->prepare( 'target_id = %d', (int) $args['target_id'] );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results(
			'SELECT * FROM ' . $table . ' WHERE ' . implode( ' AND ', $where )
		);
	}

	public static function delete_p2p(
		string $type_name,
		int $source_id,
		int $target_id,
		string $pack = ''
	): bool {
		global $wpdb;
		$type_id = self::get_type_id( $type_name, $pack );
		if ( ! $type_id ) {
			return false;
		}

		return (bool) $wpdb->delete( $wpdb->prefix . 'wpnt_p2p', array(
			'type_id'   => $type_id,
			'source_id' => $source_id,
			'target_id' => $target_id,
		) );
	}

	// -------------------------------------------------------------------------
	// G2P — BP Group → Post edges
	// -------------------------------------------------------------------------

	public static function upsert_g2p(
		string $type_name,
		int $group_id,
		int $post_id,
		array $data = array(),
		string $pack = ''
	): bool {
		global $wpdb;
		$type_id = self::get_type_id( $type_name, $pack );
		if ( ! $type_id || ! $group_id || ! $post_id ) {
			return false;
		}

		$table    = $wpdb->prefix . 'wpnt_g2p';
		$json     = wp_json_encode( $data );
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE type_id = %d AND group_id = %d AND post_id = %d",
			$type_id,
			$group_id,
			$post_id
		) );

		if ( $existing ) {
			return (bool) $wpdb->update( $table, array( 'data' => $json ), array( 'id' => (int) $existing ) );
		}

		return (bool) $wpdb->insert( $table, array(
			'type_id'  => $type_id,
			'group_id' => $group_id,
			'post_id'  => $post_id,
			'data'     => $json,
		) );
	}

	/** Supported $args keys: group_id, post_id. */
	public static function get_g2p( string $type_name, array $args = array(), string $pack = '' ): array {
		global $wpdb;
		$type_id = self::get_type_id( $type_name, $pack );
		if ( ! $type_id ) {
			return array();
		}

		$table = $wpdb->prefix . 'wpnt_g2p';
		$where = array( $wpdb->prepare( 'type_id = %d', $type_id ) );

		if ( ! empty( $args['group_id'] ) ) {
			$where[] = $wpdb->prepare( 'group_id = %d', (int) $args['group_id'] );
		}
		if ( ! empty( $args['post_id'] ) ) {
			$where[] = $wpdb->prepare( 'post_id = %d', (int) $args['post_id'] );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results(
			'SELECT * FROM ' . $table . ' WHERE ' . implode( ' AND ', $where )
		);
	}

	public static function delete_g2p( string $type_name, int $group_id, int $post_id, string $pack = '' ): bool {
		global $wpdb;
		$type_id = self::get_type_id( $type_name, $pack );
		if ( ! $type_id ) {
			return false;
		}

		return (bool) $wpdb->delete( $wpdb->prefix . 'wpnt_g2p', array(
			'type_id'  => $type_id,
			'group_id' => $group_id,
			'post_id'  => $post_id,
		) );
	}

	// -------------------------------------------------------------------------
	// Authorization
	// -------------------------------------------------------------------------

	/**
	 * Can $viewer_id read athlete data belonging to $athlete_id?
	 *
	 * Allows: own data, admins, coaches (via BP group admin/mod), parents (via BP friendship).
	 */
	public static function can_view_athlete_data( int $viewer_id, int $athlete_id ): bool {
		if ( $viewer_id === $athlete_id ) {
			return true;
		}
		if ( user_can( $viewer_id, 'manage_options' ) ) {
			return true;
		}
		if ( self::viewer_coaches_athlete( $viewer_id, $athlete_id ) ) {
			return true;
		}

		$viewer = get_userdata( $viewer_id );
		if ( $viewer && in_array( 'wpnt_parent', (array) $viewer->roles, true ) ) {
			if ( function_exists( 'friends_check_friendship' )
				&& friends_check_friendship( $viewer_id, $athlete_id ) ) {
				return true;
			}
		}

		return false;
	}

	private static function viewer_coaches_athlete( int $coach_id, int $athlete_id ): bool {
		if ( ! function_exists( 'groups_get_user_groups' ) ) {
			return false;
		}

		$result = groups_get_user_groups( $coach_id );
		if ( empty( $result['groups'] ) ) {
			return false;
		}

		foreach ( $result['groups'] as $group ) {
			$member = new BP_Groups_Member( $coach_id, $group->id );
			if ( ! $member->is_admin && ! $member->is_mod ) {
				continue;
			}
			if ( groups_is_user_member( $athlete_id, $group->id ) ) {
				return true;
			}
		}

		return false;
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	public static function decode_data( ?string $json ): array {
		if ( ! $json ) {
			return array();
		}
		$decoded = json_decode( $json, true );
		return is_array( $decoded ) ? $decoded : array();
	}
}
