<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNT_DB {

	public static function create_tables(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = array();

		// Session Groups — one record per cohort/level within a session (v2+).
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpnt_session_groups (
			id                   BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id           BIGINT(20) UNSIGNED NOT NULL,
			course_id            BIGINT(20) UNSIGNED          DEFAULT NULL,
			curriculum_node_id   BIGINT(20) UNSIGNED          DEFAULT NULL,
			label                VARCHAR(255)                 DEFAULT NULL,
			planned_skills       LONGTEXT                     DEFAULT NULL COMMENT 'JSON array of skill post IDs',
			actual_skills        LONGTEXT                     DEFAULT NULL COMMENT 'JSON array of skill post IDs',
			adhoc_sailor_ids     LONGTEXT                     DEFAULT NULL COMMENT 'JSON array of user IDs',
			display_order        TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY idx_sg_session (session_id),
			KEY idx_sg_course  (course_id)
		) $charset_collate;";

		// Attendance — session_group_id = 0 for ungrouped sessions (v2+).
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpnt_attendance (
			id                BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id        BIGINT(20) UNSIGNED NOT NULL,
			sailor_id         BIGINT(20) UNSIGNED NOT NULL,
			session_group_id  BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			status            VARCHAR(30)         NOT NULL DEFAULT 'attended',
			notes             TEXT                         DEFAULT NULL,
			recorded_by       BIGINT(20) UNSIGNED          DEFAULT NULL,
			created_at        DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at        DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY   session_sailor_group (session_id, sailor_id, session_group_id),
			KEY          idx_att_session  (session_id),
			KEY          idx_att_sailor   (sailor_id),
			KEY          idx_att_group    (session_group_id)
		) $charset_collate;";

		// Progress records — structured assessment against a skill or curriculum node.
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpnt_progress (
			id                   BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			sailor_id            BIGINT(20) UNSIGNED NOT NULL,
			skill_id             BIGINT(20) UNSIGNED          DEFAULT NULL,
			curriculum_node_id   BIGINT(20) UNSIGNED          DEFAULT NULL,
			status               VARCHAR(40)         NOT NULL DEFAULT 'not_started',
			evidence             TEXT                         DEFAULT NULL,
			coach_id             BIGINT(20) UNSIGNED          DEFAULT NULL,
			assessed_at          DATETIME                     DEFAULT NULL,
			created_at           DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at           DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_sailor    (sailor_id),
			KEY idx_skill     (skill_id),
			KEY idx_node      (curriculum_node_id)
		) $charset_collate;";

		// Observations — coach notes about an individual sailor or a group.
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpnt_observations (
			id               BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id       BIGINT(20) UNSIGNED          DEFAULT NULL,
			sailor_id        BIGINT(20) UNSIGNED          DEFAULT NULL,
			course_id        BIGINT(20) UNSIGNED          DEFAULT NULL,
			coach_id         BIGINT(20) UNSIGNED NOT NULL,
			note             TEXT                NOT NULL,
			confidence_level VARCHAR(20)                  DEFAULT NULL,
			evidence_type    VARCHAR(40)                  DEFAULT NULL,
			linked_skills    LONGTEXT                     DEFAULT NULL COMMENT 'JSON array of skill post IDs',
			created_at       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_session (session_id),
			KEY idx_sailor  (sailor_id),
			KEY idx_coach   (coach_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		update_option( 'wpnt_db_version', WPNT_DB_VERSION );
	}

	/**
	 * Run incremental schema upgrades on plugin load when the stored version is behind.
	 */
	public static function maybe_upgrade(): void {
		$installed = get_option( 'wpnt_db_version', '0' );
		if ( version_compare( $installed, '2', '<' ) ) {
			self::upgrade_to_v2();
		}
	}

	private static function upgrade_to_v2(): void {
		global $wpdb;
		$att = $wpdb->prefix . 'wpnt_attendance';

		// Add session_group_id column if missing.
		$col = $wpdb->get_results( "SHOW COLUMNS FROM {$att} LIKE 'session_group_id'" );
		if ( empty( $col ) ) {
			$wpdb->query( "ALTER TABLE {$att} ADD COLUMN session_group_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER sailor_id" );
		}

		// Replace old UNIQUE KEY with the new three-column one.
		$old = $wpdb->get_results( "SHOW INDEX FROM {$att} WHERE Key_name = 'session_sailor'" );
		if ( ! empty( $old ) ) {
			$wpdb->query( "ALTER TABLE {$att} DROP INDEX session_sailor" );
		}
		$new = $wpdb->get_results( "SHOW INDEX FROM {$att} WHERE Key_name = 'session_sailor_group'" );
		if ( empty( $new ) ) {
			$wpdb->query( "ALTER TABLE {$att} ADD UNIQUE KEY session_sailor_group (session_id, sailor_id, session_group_id)" );
		}

		// Create the session_groups table and bump the stored version.
		self::create_tables();
	}

	// -------------------------------------------------------------------------
	// Attendance helpers
	// -------------------------------------------------------------------------

	public static function upsert_attendance( int $session_id, int $sailor_id, string $status, string $notes = '', int $recorded_by = 0 ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'wpnt_attendance';

		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE session_id = %d AND sailor_id = %d",
			$session_id,
			$sailor_id
		) );

		$data = array(
			'status'      => sanitize_text_field( $status ),
			'notes'       => sanitize_textarea_field( $notes ),
			'recorded_by' => $recorded_by ?: get_current_user_id(),
		);

		if ( $existing ) {
			return (bool) $wpdb->update( $table, $data, array( 'id' => (int) $existing ), array( '%s', '%s', '%d' ), array( '%d' ) );
		}

		$data['session_id'] = $session_id;
		$data['sailor_id']  = $sailor_id;
		return (bool) $wpdb->insert( $table, $data );
	}

	public static function get_session_attendance( int $session_id ): array {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpnt_attendance WHERE session_id = %d ORDER BY created_at ASC",
			$session_id
		) );
	}

	public static function get_sailor_attendance( int $sailor_id, int $course_id = 0 ): array {
		global $wpdb;
		if ( $course_id ) {
			$session_ids = get_posts( array(
				'post_type'      => 'wpnt_session',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_wpnt_course_id',
				'meta_value'     => $course_id,
			) );
			if ( empty( $session_ids ) ) {
				return array();
			}
			$ids_placeholder = implode( ',', array_fill( 0, count( $session_ids ), '%d' ) );
			return $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wpnt_attendance WHERE sailor_id = %d AND session_id IN ($ids_placeholder)",
				array_merge( array( $sailor_id ), $session_ids )
			) );
		}
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpnt_attendance WHERE sailor_id = %d ORDER BY created_at DESC",
			$sailor_id
		) );
	}

	// -------------------------------------------------------------------------
	// Progress helpers
	// -------------------------------------------------------------------------

	public static function upsert_progress( int $sailor_id, string $status, int $skill_id = 0, int $node_id = 0, string $evidence = '' ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'wpnt_progress';

		$where = array( 'sailor_id' => $sailor_id );
		$where_fmt = array( '%d' );
		if ( $skill_id ) {
			$where['skill_id'] = $skill_id;
			$where_fmt[]       = '%d';
		}
		if ( $node_id ) {
			$where['curriculum_node_id'] = $node_id;
			$where_fmt[]                 = '%d';
		}

		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE sailor_id = %d AND skill_id = %d AND curriculum_node_id = %d",
			$sailor_id,
			$skill_id,
			$node_id
		) );

		$data = array(
			'status'    => sanitize_text_field( $status ),
			'evidence'  => sanitize_textarea_field( $evidence ),
			'coach_id'  => get_current_user_id(),
			'assessed_at' => current_time( 'mysql' ),
		);

		if ( $existing ) {
			return (bool) $wpdb->update( $table, $data, array( 'id' => (int) $existing ) );
		}

		$data = array_merge( $data, array(
			'sailor_id'          => $sailor_id,
			'skill_id'           => $skill_id ?: null,
			'curriculum_node_id' => $node_id ?: null,
		) );
		return (bool) $wpdb->insert( $table, $data );
	}

	public static function get_sailor_progress( int $sailor_id ): array {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpnt_progress WHERE sailor_id = %d",
			$sailor_id
		) );
	}

	public static function get_progress_for_sailor_skill( int $sailor_id, int $skill_id ): ?object {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpnt_progress WHERE sailor_id = %d AND skill_id = %d ORDER BY assessed_at DESC LIMIT 1",
			$sailor_id,
			$skill_id
		) );
	}

	// -------------------------------------------------------------------------
	// Observation helpers
	// -------------------------------------------------------------------------

	public static function add_observation( array $args ): int|false {
		global $wpdb;
		$data = array(
			'session_id'      => absint( $args['session_id'] ?? 0 ) ?: null,
			'sailor_id'       => absint( $args['sailor_id'] ?? 0 ) ?: null,
			'course_id'       => absint( $args['course_id'] ?? 0 ) ?: null,
			'coach_id'        => absint( $args['coach_id'] ?? get_current_user_id() ),
			'note'            => sanitize_textarea_field( $args['note'] ?? '' ),
			'confidence_level'=> sanitize_text_field( $args['confidence_level'] ?? '' ),
			'evidence_type'   => sanitize_text_field( $args['evidence_type'] ?? '' ),
			'linked_skills'   => isset( $args['linked_skills'] ) ? wp_json_encode( array_map( 'absint', (array) $args['linked_skills'] ) ) : null,
		);
		$result = $wpdb->insert( $wpdb->prefix . 'wpnt_observations', $data );
		return $result ? $wpdb->insert_id : false;
	}

	public static function get_session_observations( int $session_id ): array {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpnt_observations WHERE session_id = %d ORDER BY created_at DESC",
			$session_id
		) );
	}

	public static function get_sailor_observations( int $sailor_id ): array {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpnt_observations WHERE sailor_id = %d ORDER BY created_at DESC",
			$sailor_id
		) );
	}
}
