<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNT_Course {

	/**
	 * Generate dated session posts for a course based on start date, end date,
	 * and default day/time.
	 *
	 * Expected meta already saved on $course_id:
	 *   _wpnt_start_date       — Y-m-d
	 *   _wpnt_end_date         — Y-m-d
	 *   _wpnt_default_day_time — e.g. "Saturday 09:00"
	 */
	public static function generate_sessions( int $course_id ): int {
		$start_date   = get_post_meta( $course_id, '_wpnt_start_date', true );
		$end_date     = get_post_meta( $course_id, '_wpnt_end_date', true );
		$day_time     = get_post_meta( $course_id, '_wpnt_default_day_time', true );
		$course_title = get_the_title( $course_id );

		if ( ! $start_date || ! $end_date || ! $day_time ) {
			return 0;
		}

		$parts = explode( ' ', trim( $day_time ), 2 );
		$day   = $parts[0] ?? 'Saturday';
		$time  = $parts[1] ?? '09:00';

		$current  = new DateTime( $start_date );
		$end      = new DateTime( $end_date );
		$number   = 1;
		$created  = 0;

		while ( $current <= $end ) {
			if ( strtolower( $current->format( 'l' ) ) === strtolower( $day ) ) {
				$start_dt = $current->format( 'Y-m-d' ) . 'T' . $time;
				$end_dt   = $current->format( 'Y-m-d' ) . 'T' . self::add_ninety_minutes( $time );

				$session_id = wp_insert_post( array(
					'post_type'   => 'wpnt_session',
					'post_title'  => sprintf( '%s — Session %d', $course_title, $number ),
					'post_status' => 'publish',
					'post_author' => get_post_field( 'post_author', $course_id ),
				) );

				if ( $session_id && ! is_wp_error( $session_id ) ) {
					update_post_meta( $session_id, '_wpnt_course_id', $course_id );
					update_post_meta( $session_id, '_wpnt_session_number', $number );
					update_post_meta( $session_id, '_wpnt_scheduled_start', $start_dt );
					update_post_meta( $session_id, '_wpnt_scheduled_end', $end_dt );
					update_post_meta( $session_id, '_wpnt_status', 'scheduled' );
					$number++;
					$created++;
				}
			}
			$current->modify( '+1 day' );
		}

		return $created;
	}

	public static function get_course_sessions( int $course_id ): array {
		return get_posts( array(
			'post_type'      => 'wpnt_session',
			'posts_per_page' => -1,
			'meta_key'       => '_wpnt_course_id',
			'meta_value'     => $course_id,
			'orderby'        => 'meta_value_num',
			'meta_key'       => '_wpnt_session_number',
			'order'          => 'ASC',
		) );
	}

	public static function get_enrolled_athletes( int $course_id ): array {
		// Prefer BP group membership when available.
		$group_id = (int) get_post_meta( $course_id, '_wpnt_bp_group_id', true );
		if ( $group_id && function_exists( 'groups_get_group_members' ) ) {
			$result = groups_get_group_members( array( 'group_id' => $group_id, 'per_page' => -1 ) );
			if ( ! empty( $result['members'] ) ) {
				return $result['members'];
			}
		}

		// Fall back to postmeta-stored ID list (legacy / BP-less installs).
		$ids = get_post_meta( $course_id, '_wpnt_enrolled_athletes', true )
			?: get_post_meta( $course_id, '_wpnt_enrolled_sailors', true ); // pre-v5 key
		if ( ! $ids ) {
			return array();
		}
		$ids = array_filter( array_map( 'absint', explode( ',', $ids ) ) );
		if ( empty( $ids ) ) {
			return array();
		}
		return get_users( array(
			'include' => $ids,
			'orderby' => 'display_name',
		) );
	}

	public static function enroll_athlete( int $course_id, int $athlete_id ): bool {
		// Sync to BP group when available.
		$group_id = (int) get_post_meta( $course_id, '_wpnt_bp_group_id', true );
		if ( $group_id && function_exists( 'groups_join_group' ) ) {
			groups_join_group( $group_id, $athlete_id );
		}

		$current = get_post_meta( $course_id, '_wpnt_enrolled_athletes', true );
		$ids     = $current ? array_filter( array_map( 'absint', explode( ',', $current ) ) ) : array();
		if ( in_array( $athlete_id, $ids, true ) ) {
			return true;
		}
		$ids[] = $athlete_id;
		return (bool) update_post_meta( $course_id, '_wpnt_enrolled_athletes', implode( ',', $ids ) );
	}

	public static function unenroll_athlete( int $course_id, int $athlete_id ): bool {
		// Sync removal from BP group when available.
		$group_id = (int) get_post_meta( $course_id, '_wpnt_bp_group_id', true );
		if ( $group_id && function_exists( 'groups_remove_member' ) ) {
			groups_remove_member( $athlete_id, $group_id );
		}

		$current = get_post_meta( $course_id, '_wpnt_enrolled_athletes', true );
		$ids     = $current ? array_filter( array_map( 'absint', explode( ',', $current ) ) ) : array();
		$ids     = array_values( array_diff( $ids, array( $athlete_id ) ) );
		return (bool) update_post_meta( $course_id, '_wpnt_enrolled_athletes', implode( ',', $ids ) );
	}

	private static function add_ninety_minutes( string $time ): string {
		$dt = DateTime::createFromFormat( 'H:i', $time );
		if ( ! $dt ) {
			return '10:30';
		}
		$dt->modify( '+90 minutes' );
		return $dt->format( 'H:i' );
	}
}
