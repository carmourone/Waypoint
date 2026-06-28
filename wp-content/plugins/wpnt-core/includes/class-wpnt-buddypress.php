<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BuddyPress integration — lightweight hooks only.
 * Heavy BP logic stays here so the main plugin stays clean.
 */
class WPNT_BuddyPress {

	public static function init(): void {
		// Add Waypoint-specific profile fields to BP member profiles.
		add_action( 'bp_before_member_header_meta', array( __CLASS__, 'member_header_meta' ) );

		// When a course BP group is created externally, link it back.
		add_action( 'groups_created_group', array( __CLASS__, 'maybe_link_group_to_course' ), 10, 2 );
	}

	/**
	 * Show role badge and active course count under the BP member avatar.
	 */
	public static function member_header_meta(): void {
		$user_id = bp_displayed_user_id();
		$user    = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}

		$roles = $user->roles;
		$org_lbl         = WPNT_Pack::get_active_label( 'org_label', __( 'Org', 'wpnt' ) );
		$participant_lbl = WPNT_Pack::get_active_label( 'participant_label', __( 'Athlete', 'wpnt' ) );
		$label_map = array(
			'wpnt_org_admin'  => $org_lbl . ' ' . __( 'Admin', 'wpnt' ),
			'wpnt_coach'      => __( 'Coach', 'wpnt' ),
			'wpnt_asst_coach' => __( 'Assistant Coach', 'wpnt' ),
			'wpnt_athlete'    => $participant_lbl,
			'wpnt_parent'     => __( 'Parent', 'wpnt' ),
		);

		foreach ( $roles as $role ) {
			if ( isset( $label_map[ $role ] ) ) {
				echo '<span class="wpnt-role-badge">' . esc_html( $label_map[ $role ] ) . '</span>';
				break;
			}
		}
	}

	/**
	 * After a BuddyPress group is created, if its name matches a course title,
	 * auto-link the group ID to the course post meta.
	 */
	public static function maybe_link_group_to_course( int $group_id, object $member ): void {
		$group = groups_get_group( $group_id );
		if ( ! $group ) {
			return;
		}

		$courses = get_posts( array(
			'post_type'      => 'wpnt_course',
			'title'          => $group->name,
			'posts_per_page' => 1,
			'post_status'    => array( 'publish', 'draft' ),
		) );

		if ( ! empty( $courses ) ) {
			update_post_meta( $courses[0]->ID, '_wpnt_bp_group_id', $group_id );
		}
	}

	/**
	 * Create a BuddyPress group for a course and store the group ID.
	 * Call this from course creation screens when the coach opts in.
	 */
	public static function create_course_group( int $course_id ): int|false {
		if ( ! function_exists( 'groups_create_group' ) ) {
			return false;
		}

		$course = get_post( $course_id );
		if ( ! $course ) {
			return false;
		}

		$existing = (int) get_post_meta( $course_id, '_wpnt_bp_group_id', true );
		if ( $existing ) {
			return $existing;
		}

		$group_id = groups_create_group( array(
			'creator_id'   => $course->post_author,
			'name'         => $course->post_title,
			'description'  => wp_strip_all_tags( $course->post_content ),
			'slug'         => sanitize_title( $course->post_title ),
			'status'       => 'private',
			'enable_forum' => false,
		) );

		if ( ! is_wp_error( $group_id ) && $group_id ) {
			update_post_meta( $course_id, '_wpnt_bp_group_id', $group_id );
			return $group_id;
		}

		return false;
	}
}
