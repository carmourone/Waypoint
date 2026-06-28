<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNT_DB {

	public static function create_tables(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = array();

		// Edge type registry — domain packs register types here; no ALTER TABLE ever needed.
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpnt_types (
			id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			pack         VARCHAR(80)                  NOT NULL DEFAULT 'core',
			table_kind   ENUM('u2p','p2p','g2p')      NOT NULL,
			name         VARCHAR(80)                  NOT NULL,
			label        VARCHAR(120)                          DEFAULT NULL,
			label_plural VARCHAR(120)                          DEFAULT NULL,
			schema_def   LONGTEXT                              DEFAULT NULL COMMENT 'Optional JSON Schema for data validation',
			PRIMARY KEY  (id),
			UNIQUE KEY   uniq_type (pack, name)
		) $charset_collate;";

		// User → Post edges (attendance, skill assessment).
		// context_id discriminates sub-contexts (e.g. session_groups.id for group-scoped
		// attendance). Zero means no sub-context.
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpnt_u2p (
			id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			type_id    BIGINT(20) UNSIGNED NOT NULL,
			user_id    BIGINT(20) UNSIGNED NOT NULL,
			post_id    BIGINT(20) UNSIGNED NOT NULL,
			context_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			data       LONGTEXT                      DEFAULT NULL COMMENT 'JSON edge payload',
			created_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY   uniq_u2p (type_id, user_id, post_id, context_id),
			KEY          idx_user    (user_id),
			KEY          idx_post    (post_id),
			KEY          idx_context (context_id),
			KEY          idx_type    (type_id)
		) $charset_collate;";

		// Post → Post edges (session→course, session→skill, plan→skill).
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpnt_p2p (
			id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			type_id    BIGINT(20) UNSIGNED NOT NULL,
			source_id  BIGINT(20) UNSIGNED NOT NULL,
			target_id  BIGINT(20) UNSIGNED NOT NULL,
			data       LONGTEXT                      DEFAULT NULL COMMENT 'JSON edge payload',
			created_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY   uniq_p2p (type_id, source_id, target_id),
			KEY          idx_source (source_id),
			KEY          idx_target (target_id),
			KEY          idx_type   (type_id)
		) $charset_collate;";

		// BP Group → Post edges (squad enrolled in course).
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpnt_g2p (
			id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			type_id    BIGINT(20) UNSIGNED NOT NULL,
			group_id   BIGINT(20) UNSIGNED NOT NULL,
			post_id    BIGINT(20) UNSIGNED NOT NULL,
			data       LONGTEXT                      DEFAULT NULL COMMENT 'JSON edge payload',
			created_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY   uniq_g2p (type_id, group_id, post_id),
			KEY          idx_group (group_id),
			KEY          idx_post  (post_id),
			KEY          idx_type  (type_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		update_option( 'wpnt_db_version', WPNT_DB_VERSION );
	}

	// -------------------------------------------------------------------------
	// Schema migration
	// -------------------------------------------------------------------------

	public static function maybe_upgrade(): void {
		$installed = (string) get_option( 'wpnt_db_version', '0' );

		// Fresh install — v6 is the baseline; skip the legacy migration chain entirely.
		if ( $installed === '0' ) {
			self::upgrade_to_v6();
			return;
		}

		// Existing installs step through each version in order.
		if ( version_compare( $installed, '2', '<' ) ) {
			self::upgrade_to_v2();
		}
		if ( version_compare( $installed, '3', '<' ) ) {
			self::upgrade_to_v3();
		}
		if ( version_compare( $installed, '4', '<' ) ) {
			self::upgrade_to_v4();
		}
		if ( version_compare( $installed, '5', '<' ) ) {
			self::upgrade_to_v5();
		}
		if ( version_compare( $installed, '6', '<' ) ) {
			self::upgrade_to_v6();
		}
	}

	// -------------------------------------------------------------------------
	// v2–v4: legacy migrations kept for installs upgrading from pre-v5
	// -------------------------------------------------------------------------

	private static function upgrade_to_v2(): void {
		global $wpdb;
		$att = $wpdb->prefix . 'wpnt_attendance';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$att}'" ) ) {
			$col = $wpdb->get_results( "SHOW COLUMNS FROM {$att} LIKE 'session_group_id'" );
			if ( empty( $col ) ) {
				$wpdb->query( "ALTER TABLE {$att} ADD COLUMN session_group_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0" );
			}
			$old = $wpdb->get_results( "SHOW INDEX FROM {$att} WHERE Key_name = 'session_sailor'" );
			if ( ! empty( $old ) ) {
				$wpdb->query( "ALTER TABLE {$att} DROP INDEX session_sailor" );
			}
			$has_key = $wpdb->get_results( "SHOW INDEX FROM {$att} WHERE Key_name IN ('session_sailor_group','session_athlete_group')" );
			if ( empty( $has_key ) ) {
				$has_sailor = $wpdb->get_results( "SHOW COLUMNS FROM {$att} LIKE 'sailor_id'" );
				$id_col     = ! empty( $has_sailor ) ? 'sailor_id' : 'athlete_id';
				$wpdb->query( "ALTER TABLE {$att} ADD UNIQUE KEY session_athlete_group (session_id, {$id_col}, session_group_id)" );
			}
		}

		update_option( 'wpnt_db_version', '2' );
	}

	private static function upgrade_to_v3(): void {
		update_option( 'wpnt_db_version', '3' );
	}

	private static function upgrade_to_v4(): void {
		global $wpdb;
		$att      = $wpdb->prefix . 'wpnt_attendance';
		$progress = $wpdb->prefix . 'wpnt_progress';
		$sg       = $wpdb->prefix . 'wpnt_session_groups';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$att}'" ) ) {
			$has_sailor = $wpdb->get_results( "SHOW COLUMNS FROM {$att} LIKE 'sailor_id'" );
			if ( ! empty( $has_sailor ) ) {
				$wpdb->query( "ALTER TABLE {$att} CHANGE sailor_id athlete_id BIGINT(20) UNSIGNED NOT NULL" );
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
		}

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$progress}'" ) ) {
			$has_sailor = $wpdb->get_results( "SHOW COLUMNS FROM {$progress} LIKE 'sailor_id'" );
			if ( ! empty( $has_sailor ) ) {
				$wpdb->query( "ALTER TABLE {$progress} CHANGE sailor_id athlete_id BIGINT(20) UNSIGNED NOT NULL" );
				$old_idx = $wpdb->get_results( "SHOW INDEX FROM {$progress} WHERE Key_name = 'idx_sailor'" );
				if ( ! empty( $old_idx ) ) {
					$wpdb->query( "ALTER TABLE {$progress} DROP INDEX idx_sailor" );
					$wpdb->query( "ALTER TABLE {$progress} ADD KEY idx_athlete (athlete_id)" );
				}
			}
		}

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$sg}'" ) ) {
			$has_col = $wpdb->get_results( "SHOW COLUMNS FROM {$sg} LIKE 'adhoc_sailor_ids'" );
			if ( ! empty( $has_col ) ) {
				$wpdb->query( "ALTER TABLE {$sg} CHANGE adhoc_sailor_ids adhoc_athlete_ids LONGTEXT DEFAULT NULL COMMENT 'JSON array of user IDs'" );
			}
		}

		update_option( 'wpnt_db_version', '4' );
	}

	// -------------------------------------------------------------------------
	// v5: graph-based schema
	// -------------------------------------------------------------------------

	private static function upgrade_to_v5(): void {
		self::create_tables();
		WPNT_Graph::seed_types();
	}

	// -------------------------------------------------------------------------
	// v6: session_group g2p type; remove unused aspirational edge types
	// -------------------------------------------------------------------------

	private static function upgrade_to_v6(): void {
		global $wpdb;
		self::create_tables();
		WPNT_Graph::seed_types();
		// Remove aspirational types seeded in v5 that are not used by any code.
		foreach ( array( 'session_of', 'covers', 'planned', 'enrolled' ) as $name ) {
			$wpdb->delete( $wpdb->prefix . 'wpnt_types', array( 'pack' => 'core', 'name' => $name ) );
		}
		WPNT_Graph::clear_type_cache();
		// Legacy tables (wpnt_attendance, wpnt_progress, wpnt_session_groups) are left in
		// place on upgraded installs and are no longer written to by the application.
		// Drop them manually once historical data is no longer needed.
	}
}
