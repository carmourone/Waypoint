<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNT_Training_Plan {

	const PROGRESS_STATUSES = array(
		'not_started'             => 'Not Started',
		'introduced'              => 'Introduced',
		'practising'              => 'Practising',
		'competent_with_help'     => 'Competent with Help',
		'competent_independently' => 'Competent Independently',
		'achieved'                => 'Achieved',
		'needs_review'            => 'Needs Review',
		'paused'                  => 'Paused',
		'closed'                  => 'Closed',
	);

	const SESSION_ROLES = array(
		'primary_delivery'        => 'Primary Delivery',
		'practice'                => 'Practice',
		'review'                  => 'Review',
		'assessment'              => 'Assessment',
		'competition_application' => 'Competition Application',
		'follow_up'               => 'Follow-up',
	);

	// -------------------------------------------------------------------------
	// Session links — p2p edges of type 'plan_session'
	// -------------------------------------------------------------------------

	public static function link_session( int $plan_id, int $session_id, string $role = 'primary_delivery' ): bool {
		if ( ! array_key_exists( $role, self::SESSION_ROLES ) ) {
			$role = 'primary_delivery';
		}
		return WPNT_Graph::upsert_p2p( 'plan_session', $plan_id, $session_id, array( 'role' => $role ) );
	}

	public static function unlink_session( int $plan_id, int $session_id ): bool {
		return WPNT_Graph::delete_p2p( 'plan_session', $plan_id, $session_id );
	}

	/**
	 * Return p2p edge objects for a plan's linked sessions. Each has a synthetic ->role property.
	 */
	public static function get_linked_sessions( int $plan_id ): array {
		$edges = WPNT_Graph::get_p2p( 'plan_session', array( 'source_id' => $plan_id ) );
		foreach ( $edges as $edge ) {
			$d = WPNT_Graph::decode_data( $edge->data );
			$edge->role = $d['role'] ?? 'primary_delivery';
		}
		return $edges;
	}

	/**
	 * Return all plans linked to a given session (reverse lookup).
	 */
	public static function get_session_plans( int $session_id ): array {
		$edges = WPNT_Graph::get_p2p( 'plan_session', array( 'target_id' => $session_id ) );
		foreach ( $edges as $edge ) {
			$d = WPNT_Graph::decode_data( $edge->data );
			$edge->role = $d['role'] ?? 'primary_delivery';
		}
		return $edges;
	}

	// -------------------------------------------------------------------------
	// Objective nodes — child wpnt_training_plan posts via post_parent
	// -------------------------------------------------------------------------

	public static function get_objectives( int $plan_id ): array {
		return get_posts( array(
			'post_type'      => 'wpnt_training_plan',
			'post_parent'    => $plan_id,
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'post_status'    => array( 'publish', 'draft' ),
		) );
	}

	// -------------------------------------------------------------------------
	// Progress records
	// -------------------------------------------------------------------------

	/**
	 * Upsert a progress record keyed by (plan_id, objective_id, subject_type, subject_id, skill_id).
	 */
	public static function record_progress( array $data ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'wpnt_progress_records';

		$status = array_key_exists( $data['status'] ?? '', self::PROGRESS_STATUSES )
			? $data['status']
			: 'not_started';

		$row = array(
			'plan_id'      => absint( $data['plan_id'] ?? 0 ),
			'objective_id' => absint( $data['objective_id'] ?? 0 ),
			'subject_type' => sanitize_key( $data['subject_type'] ?? 'individual' ),
			'subject_id'   => absint( $data['subject_id'] ?? 0 ),
			'skill_id'     => absint( $data['skill_id'] ?? 0 ),
			'session_id'   => absint( $data['session_id'] ?? 0 ),
			'status'       => $status,
			'evidence'     => sanitize_textarea_field( $data['evidence'] ?? '' ),
			'coach_note'   => sanitize_textarea_field( $data['coach_note'] ?? '' ),
			'visibility'   => in_array( $data['visibility'] ?? 'shared', array( 'shared', 'internal' ), true )
				? $data['visibility'] : 'shared',
			'recorded_by'  => get_current_user_id(),
		);

		if ( ! $row['plan_id'] || ! $row['subject_id'] ) {
			return false;
		}

		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE plan_id = %d AND objective_id = %d AND subject_type = %s AND subject_id = %d AND skill_id = %d",
			$row['plan_id'], $row['objective_id'], $row['subject_type'], $row['subject_id'], $row['skill_id']
		) );

		if ( $existing ) {
			return (bool) $wpdb->update( $table, $row, array( 'id' => (int) $existing ) );
		}
		return (bool) $wpdb->insert( $table, $row );
	}

	public static function get_progress( int $plan_id, string $subject_type = '', int $subject_id = 0 ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'wpnt_progress_records';

		if ( $subject_type && $subject_id ) {
			return $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$table} WHERE plan_id = %d AND subject_type = %s AND subject_id = %d ORDER BY updated_at DESC",
				$plan_id, $subject_type, $subject_id
			) );
		}

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE plan_id = %d ORDER BY updated_at DESC",
			$plan_id
		) );
	}

	// -------------------------------------------------------------------------
	// Plan lookup
	// -------------------------------------------------------------------------

	/**
	 * Get top-level plans for a subject using the v7 subject model.
	 */
	public static function get_for_subject( string $subject_type, int $subject_id ): array {
		return get_posts( array(
			'post_type'      => 'wpnt_training_plan',
			'posts_per_page' => -1,
			'post_parent'    => 0,
			'meta_query'     => array(
				array( 'key' => '_wpnt_subject_type', 'value' => $subject_type ),
				array( 'key' => '_wpnt_subject_id',   'value' => $subject_id ),
				array( 'key' => '_wpnt_status', 'value' => array( 'draft', 'pending_review', 'published', 'active' ), 'compare' => 'IN' ),
			),
		) );
	}

	/**
	 * Get plans for an athlete — new subject model first, legacy _wpnt_athlete_id fallback.
	 */
	public static function get_for_athlete( int $athlete_id ): array {
		$plans = self::get_for_subject( 'individual', $athlete_id );
		if ( ! empty( $plans ) ) {
			return $plans;
		}
		return get_posts( array(
			'post_type'      => 'wpnt_training_plan',
			'posts_per_page' => -1,
			'post_parent'    => 0,
			'meta_query'     => array(
				array( 'key' => '_wpnt_athlete_id', 'value' => $athlete_id ),
				array( 'key' => '_wpnt_status', 'value' => array( 'approved', 'active', 'draft', 'pending_review', 'published' ), 'compare' => 'IN' ),
			),
		) );
	}
}
