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
			adhoc_athlete_ids    LONGTEXT                     DEFAULT NULL COMMENT 'JSON array of user IDs',
			display_order        TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY idx_sg_session (session_id),
			KEY idx_sg_course  (course_id)
		) $charset_collate;";

		// Attendance — session_group_id = 0 for ungrouped sessions (v2+).
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpnt_attendance (
			id                BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id        BIGINT(20) UNSIGNED NOT NULL,
			athlete_id        BIGINT(20) UNSIGNED NOT NULL,
			session_group_id  BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			status            VARCHAR(30)         NOT NULL DEFAULT 'attended',
			notes             TEXT                         DEFAULT NULL,
			recorded_by       BIGINT(20) UNSIGNED          DEFAULT NULL,
			created_at        DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at        DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY   session_athlete_group (session_id, athlete_id, session_group_id),
			KEY          idx_att_session  (session_id),
			KEY          idx_att_athlete  (athlete_id),
			KEY          idx_att_group    (session_group_id)
		) $charset_collate;";

		// Progress records — structured assessment against a skill or curriculum node.
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpnt_progress (
			id                   BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			athlete_id           BIGINT(20) UNSIGNED NOT NULL,
			skill_id             BIGINT(20) UNSIGNED          DEFAULT NULL,
			curriculum_node_id   BIGINT(20) UNSIGNED          DEFAULT NULL,
			status               VARCHAR(40)         NOT NULL DEFAULT 'not_started',
			evidence             TEXT                         DEFAULT NULL,
			coach_id             BIGINT(20) UNSIGNED          DEFAULT NULL,
			assessed_at          DATETIME                     DEFAULT NULL,
			created_at           DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at           DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_athlete   (athlete_id),
			KEY idx_skill     (skill_id),
			KEY idx_node      (curriculum_node_id)
		) $charset_collate;";

		// Observations are stored as wpnt_observation CPT posts (v3+).

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
		if ( version_compare( $installed, '3', '<' ) ) {
			self::upgrade_to_v3();
		}
		if ( version_compare( $installed, '4', '<' ) ) {
			self::upgrade_to_v4();
		}
	}

	private static function upgrade_to_v2(): void {
		global $wpdb;
		$att = $wpdb->prefix . 'wpnt_attendance';

		// Add session_group_id column if missing (don't assume position of sailor_id/athlete_id).
		$col = $wpdb->get_results( "SHOW COLUMNS FROM {$att} LIKE 'session_group_id'" );
		if ( empty( $col ) ) {
			$wpdb->query( "ALTER TABLE {$att} ADD COLUMN session_group_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0" );
		}

		// Drop the old two-column key if it exists.
		$old = $wpdb->get_results( "SHOW INDEX FROM {$att} WHERE Key_name = 'session_sailor'" );
		if ( ! empty( $old ) ) {
			$wpdb->query( "ALTER TABLE {$att} DROP INDEX session_sailor" );
		}

		// Add the three-column unique key under whichever name doesn't already exist.
		$has_old_key = $wpdb->get_results( "SHOW INDEX FROM {$att} WHERE Key_name = 'session_sailor_group'" );
		$has_new_key = $wpdb->get_results( "SHOW INDEX FROM {$att} WHERE Key_name = 'session_athlete_group'" );
		if ( empty( $has_old_key ) && empty( $has_new_key ) ) {
			// Use whichever column name the table currently has.
			$has_sailor_col = $wpdb->get_results( "SHOW COLUMNS FROM {$att} LIKE 'sailor_id'" );
			$id_col         = ! empty( $has_sailor_col ) ? 'sailor_id' : 'athlete_id';
			$wpdb->query( "ALTER TABLE {$att} ADD UNIQUE KEY session_athlete_group (session_id, {$id_col}, session_group_id)" );
		}

		// Create the session_groups table and bump the stored version.
		self::create_tables();
	}

	private static function upgrade_to_v3(): void {
		// Observations migrated to wpnt_observation CPT — no schema changes needed.
		update_option( 'wpnt_db_version', '3' );
	}

	private static function upgrade_to_v4(): void {
		global $wpdb;
		$att      = $wpdb->prefix . 'wpnt_attendance';
		$progress = $wpdb->prefix . 'wpnt_progress';
		$sg       = $wpdb->prefix . 'wpnt_session_groups';

		// Rename sailor_id → athlete_id in attendance.
		$has_sailor = $wpdb->get_results( "SHOW COLUMNS FROM {$att} LIKE 'sailor_id'" );
		if ( ! empty( $has_sailor ) ) {
			$wpdb->query( "ALTER TABLE {$att} CHANGE sailor_id athlete_id BIGINT(20) UNSIGNED NOT NULL" );

			// Drop old key names and create new ones.
			$old_uq = $wpdb->get_results( "SHOW INDEX FROM {$att} WHERE Key_name = 'session_sailor_group'" );
			if ( ! empty( $old_uq ) ) {
				$wpdb->query( "ALTER TABLE {$att} DROP INDEX session_sailor_group" );
			}
			if ( empty( $wpdb->get_results( "SHOW INDEX FROM {$att} WHERE Key_name = 'session_athlete_group'" ) ) ) {
				$wpdb->query( "ALTER TABLE {$att} ADD UNIQUE KEY session_athlete_group (session_id, athlete_id, session_group_id)" );
			}

			$old_idx = $wpdb->get_results( "SHOW INDEX FROM {$att} WHERE Key_name = 'idx_att_sailor'" );
			if ( ! empty( $old_idx ) ) {
				$wpdb->query( "ALTER TABLE {$att} DROP INDEX idx_att_sailor" );
				$wpdb->query( "ALTER TABLE {$att} ADD KEY idx_att_athlete (athlete_id)" );
			}
		}

		// Rename sailor_id → athlete_id in progress.
		$has_sailor = $wpdb->get_results( "SHOW COLUMNS FROM {$progress} LIKE 'sailor_id'" );
		if ( ! empty( $has_sailor ) ) {
			$wpdb->query( "ALTER TABLE {$progress} CHANGE sailor_id athlete_id BIGINT(20) UNSIGNED NOT NULL" );

			$old_idx = $wpdb->get_results( "SHOW INDEX FROM {$progress} WHERE Key_name = 'idx_sailor'" );
			if ( ! empty( $old_idx ) ) {
				$wpdb->query( "ALTER TABLE {$progress} DROP INDEX idx_sailor" );
				$wpdb->query( "ALTER TABLE {$progress} ADD KEY idx_athlete (athlete_id)" );
			}
		}

		// Rename adhoc_sailor_ids → adhoc_athlete_ids in session_groups.
		$has_sailor_col = $wpdb->get_results( "SHOW COLUMNS FROM {$sg} LIKE 'adhoc_sailor_ids'" );
		if ( ! empty( $has_sailor_col ) ) {
			$wpdb->query( "ALTER TABLE {$sg} CHANGE adhoc_sailor_ids adhoc_athlete_ids LONGTEXT DEFAULT NULL COMMENT 'JSON array of user IDs'" );
		}

		update_option( 'wpnt_db_version', '4' );
	}

	// -------------------------------------------------------------------------
	// Attendance helpers
	// -------------------------------------------------------------------------

	public static function upsert_attendance( int $session_id, int $athlete_id, string $status, string $notes = '', int $recorded_by = 0 ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'wpnt_attendance';

		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE session_id = %d AND athlete_id = %d",
			$session_id,
			$athlete_id
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
		$data['athlete_id'] = $athlete_id;
		return (bool) $wpdb->insert( $table, $data );
	}

	public static function get_session_attendance( int $session_id ): array {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpnt_attendance WHERE session_id = %d ORDER BY created_at ASC",
			$session_id
		) );
	}

	public static function get_athlete_attendance( int $athlete_id, int $course_id = 0 ): array {
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
				"SELECT * FROM {$wpdb->prefix}wpnt_attendance WHERE athlete_id = %d AND session_id IN ($ids_placeholder)",
				array_merge( array( $athlete_id ), $session_ids )
			) );
		}
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpnt_attendance WHERE athlete_id = %d ORDER BY created_at DESC",
			$athlete_id
		) );
	}

	// -------------------------------------------------------------------------
	// Progress helpers
	// -------------------------------------------------------------------------

	public static function upsert_progress( int $athlete_id, string $status, int $skill_id = 0, int $node_id = 0, string $evidence = '' ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'wpnt_progress';

		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE athlete_id = %d AND skill_id = %d AND curriculum_node_id = %d",
			$athlete_id,
			$skill_id,
			$node_id
		) );

		$data = array(
			'status'      => sanitize_text_field( $status ),
			'evidence'    => sanitize_textarea_field( $evidence ),
			'coach_id'    => get_current_user_id(),
			'assessed_at' => current_time( 'mysql' ),
		);

		if ( $existing ) {
			return (bool) $wpdb->update( $table, $data, array( 'id' => (int) $existing ) );
		}

		$data = array_merge( $data, array(
			'athlete_id'         => $athlete_id,
			'skill_id'           => $skill_id ?: null,
			'curriculum_node_id' => $node_id ?: null,
		) );
		return (bool) $wpdb->insert( $table, $data );
	}

	public static function get_athlete_progress( int $athlete_id ): array {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpnt_progress WHERE athlete_id = %d",
			$athlete_id
		) );
	}

	public static function get_progress_for_athlete_skill( int $athlete_id, int $skill_id ): ?object {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wpnt_progress WHERE athlete_id = %d AND skill_id = %d ORDER BY assessed_at DESC LIMIT 1",
			$athlete_id,
			$skill_id
		) );
	}
}
