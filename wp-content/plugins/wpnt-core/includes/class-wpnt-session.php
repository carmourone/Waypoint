<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNT_Session {

	/**
	 * Return sessions scheduled for today across all courses.
	 */
	public static function get_todays_sessions( int $coach_id = 0 ): array {
		$today = current_time( 'Y-m-d' );

		$args = array(
			'post_type'      => 'wpnt_session',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_wpnt_scheduled_start',
					'value'   => array( $today . 'T00:00', $today . 'T23:59' ),
					'compare' => 'BETWEEN',
					'type'    => 'CHAR',
				),
			),
		);

		if ( $coach_id ) {
			// Filter to sessions belonging to courses this coach is assigned to.
			$coach_course_ids = self::get_coach_course_ids( $coach_id );
			if ( empty( $coach_course_ids ) ) {
				return array();
			}
			$args['meta_query'][] = array(
				'key'     => '_wpnt_course_id',
				'value'   => $coach_course_ids,
				'compare' => 'IN',
			);
		}

		return get_posts( $args );
	}

	/**
	 * Return upcoming sessions (next 14 days) for a coach.
	 */
	public static function get_upcoming_sessions( int $coach_id = 0, int $days = 14 ): array {
		$today = current_time( 'Y-m-d' );
		$until = date( 'Y-m-d', strtotime( "+{$days} days", current_time( 'timestamp' ) ) );

		$args = array(
			'post_type'      => 'wpnt_session',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_wpnt_scheduled_start',
					'value'   => array( $today . 'T00:00', $until . 'T23:59' ),
					'compare' => 'BETWEEN',
					'type'    => 'CHAR',
				),
			),
			'orderby'        => 'meta_value',
			'meta_key'       => '_wpnt_scheduled_start',
			'order'          => 'ASC',
		);

		if ( $coach_id ) {
			$course_ids = self::get_coach_course_ids( $coach_id );
			if ( empty( $course_ids ) ) {
				return array();
			}
			$args['meta_query'][] = array(
				'key'     => '_wpnt_course_id',
				'value'   => $course_ids,
				'compare' => 'IN',
			);
		}

		return get_posts( $args );
	}

	/**
	 * Sessions with no attendance recorded that have already been delivered.
	 */
	public static function get_incomplete_attendance_sessions( int $coach_id = 0 ): array {
		global $wpdb;

		$past     = current_time( 'Y-m-d' ) . 'T23:59';
		$args     = array(
			'post_type'      => 'wpnt_session',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_wpnt_scheduled_start',
					'value'   => $past,
					'compare' => '<',
					'type'    => 'CHAR',
				),
				array(
					'key'     => '_wpnt_status',
					'value'   => array( 'scheduled', 'delivered' ),
					'compare' => 'IN',
				),
			),
		);

		if ( $coach_id ) {
			$course_ids = self::get_coach_course_ids( $coach_id );
			if ( empty( $course_ids ) ) {
				return array();
			}
			$args['meta_query'][] = array(
				'key'     => '_wpnt_course_id',
				'value'   => $course_ids,
				'compare' => 'IN',
			);
		}

		$sessions = get_posts( $args );

		return array_filter( $sessions, function( $s ) use ( $wpdb ) {
			$type_id = WPNT_Graph::get_type_id( 'attended' );
			if ( ! $type_id ) {
				return true;
			}
			$count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}wpnt_u2p WHERE type_id = %d AND post_id = %d",
				$type_id,
				$s->ID
			) );
			return (int) $count === 0;
		} );
	}

	/**
	 * Clone a session template into a new session post for a given course.
	 */
	public static function create_from_template( int $template_id, int $course_id, string $scheduled_start, int $session_number = 0 ): int|WP_Error {
		$template = get_post( $template_id );
		if ( ! $template || $template->post_type !== 'wpnt_session_tmpl' ) {
			return new WP_Error( 'wpnt_invalid_template', 'Invalid session template.' );
		}

		$session_id = wp_insert_post( array(
			'post_type'    => 'wpnt_session',
			'post_title'   => $template->post_title,
			'post_content' => $template->post_content,
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
		) );

		if ( is_wp_error( $session_id ) ) {
			return $session_id;
		}

		$tmpl_meta_keys = array(
			'_wpnt_equipment', '_wpnt_briefing_notes', '_wpnt_safety_notes', '_wpnt_duration_mins',
		);
		foreach ( $tmpl_meta_keys as $key ) {
			$val = get_post_meta( $template_id, $key, true );
			if ( $val !== '' ) {
				update_post_meta( $session_id, $key, $val );
			}
		}

		update_post_meta( $session_id, '_wpnt_course_id', $course_id );
		update_post_meta( $session_id, '_wpnt_template_id', $template_id );
		update_post_meta( $session_id, '_wpnt_scheduled_start', sanitize_text_field( $scheduled_start ) );
		update_post_meta( $session_id, '_wpnt_status', 'scheduled' );

		if ( $session_number ) {
			update_post_meta( $session_id, '_wpnt_session_number', $session_number );
		}

		return $session_id;
	}

	private static function get_coach_course_ids( int $coach_id ): array {
		global $wpdb;
		return $wpdb->get_col( $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wpnt_coaches' AND meta_value LIKE %s",
			'%' . $wpdb->esc_like( (string) $coach_id ) . '%'
		) );
	}
}
