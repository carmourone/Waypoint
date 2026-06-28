<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPNT_REST_API {

	public static function register_routes(): void {
		$ns = 'wpnt/v1';

		// Attendance
		register_rest_route( $ns, '/attendance', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'save_attendance' ),
			'permission_callback' => array( __CLASS__, 'can_mark_attendance' ),
			'args'                => array(
				'session_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
				'records'    => array( 'required' => true ),
			),
		) );

		register_rest_route( $ns, '/attendance/(?P<session_id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_attendance' ),
			'permission_callback' => array( __CLASS__, 'can_view_attendance' ),
			'args'                => array(
				'session_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
			),
		) );

		// Observations
		register_rest_route( $ns, '/observations', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'save_observation' ),
			'permission_callback' => array( __CLASS__, 'can_add_observation' ),
		) );

		register_rest_route( $ns, '/observations/session/(?P<session_id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_session_observations' ),
			'permission_callback' => array( __CLASS__, 'can_view_attendance' ),
		) );

		// Progress
		register_rest_route( $ns, '/progress', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'save_progress' ),
			'permission_callback' => array( __CLASS__, 'can_add_observation' ),
		) );

		register_rest_route( $ns, '/progress/sailor/(?P<sailor_id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_sailor_progress' ),
			'permission_callback' => array( __CLASS__, 'can_view_sailor' ),
		) );

		// Sessions — today and upcoming
		register_rest_route( $ns, '/sessions/today', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_todays_sessions' ),
			'permission_callback' => array( __CLASS__, 'can_view_sessions' ),
		) );

		register_rest_route( $ns, '/sessions/upcoming', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_upcoming_sessions' ),
			'permission_callback' => array( __CLASS__, 'can_view_sessions' ),
		) );

		// Course — generate sessions
		register_rest_route( $ns, '/course/(?P<course_id>\d+)/generate-sessions', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'generate_course_sessions' ),
			'permission_callback' => array( __CLASS__, 'can_manage_courses' ),
		) );

		// Enrolment
		register_rest_route( $ns, '/course/(?P<course_id>\d+)/enroll', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'enroll_sailor' ),
			'permission_callback' => array( __CLASS__, 'can_manage_courses' ),
		) );

		// Session Groups
		register_rest_route( $ns, '/sessions/(?P<session_id>\d+)/groups', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_session_groups' ),
				'permission_callback' => array( __CLASS__, 'can_view_attendance' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'create_session_group' ),
				'permission_callback' => array( __CLASS__, 'can_mark_attendance' ),
			),
		) );

		register_rest_route( $ns, '/session-groups/(?P<group_id>\d+)', array(
			array(
				'methods'             => 'PUT',
				'callback'            => array( __CLASS__, 'update_session_group' ),
				'permission_callback' => array( __CLASS__, 'can_mark_attendance' ),
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => array( __CLASS__, 'delete_session_group' ),
				'permission_callback' => array( __CLASS__, 'can_mark_attendance' ),
			),
		) );

		register_rest_route( $ns, '/session-groups/(?P<group_id>\d+)/attendance', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'save_group_attendance' ),
			'permission_callback' => array( __CLASS__, 'can_mark_attendance' ),
		) );

		register_rest_route( $ns, '/session-groups/(?P<group_id>\d+)/add-sailor', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'add_sailor_to_group' ),
			'permission_callback' => array( __CLASS__, 'can_mark_attendance' ),
		) );

		register_rest_route( $ns, '/session-groups/(?P<group_id>\d+)/sailors/(?P<sailor_id>\d+)', array(
			'methods'             => 'DELETE',
			'callback'            => array( __CLASS__, 'remove_sailor_from_group' ),
			'permission_callback' => array( __CLASS__, 'can_mark_attendance' ),
		) );
	}

	// -------------------------------------------------------------------------
	// Handlers
	// -------------------------------------------------------------------------

	public static function save_attendance( WP_REST_Request $request ): WP_REST_Response {
		$session_id = (int) $request->get_param( 'session_id' );
		$records    = $request->get_param( 'records' );

		if ( ! is_array( $records ) ) {
			return new WP_REST_Response( array( 'error' => 'records must be an array' ), 400 );
		}

		$results = WPNT_Attendance::bulk_mark( $session_id, $records );

		// Mark session as delivered if not already.
		$status = get_post_meta( $session_id, '_wpnt_status', true );
		if ( $status === 'scheduled' ) {
			update_post_meta( $session_id, '_wpnt_status', 'delivered' );
		}

		return new WP_REST_Response( array( 'saved' => $results ), 200 );
	}

	public static function get_attendance( WP_REST_Request $request ): WP_REST_Response {
		$session_id = (int) $request->get_param( 'session_id' );
		$rows       = WPNT_DB::get_session_attendance( $session_id );
		return new WP_REST_Response( $rows, 200 );
	}

	public static function save_observation( WP_REST_Request $request ): WP_REST_Response {
		$args = array(
			'session_id'      => absint( $request->get_param( 'session_id' ) ),
			'sailor_id'       => absint( $request->get_param( 'sailor_id' ) ),
			'course_id'       => absint( $request->get_param( 'course_id' ) ),
			'note'            => sanitize_textarea_field( $request->get_param( 'note' ) ?? '' ),
			'confidence_level'=> sanitize_text_field( $request->get_param( 'confidence_level' ) ?? '' ),
			'evidence_type'   => sanitize_text_field( $request->get_param( 'evidence_type' ) ?? '' ),
			'linked_skills'   => $request->get_param( 'linked_skills' ),
		);

		if ( ! $args['note'] ) {
			return new WP_REST_Response( array( 'error' => 'note is required' ), 400 );
		}

		$id = WPNT_DB::add_observation( $args );
		if ( ! $id ) {
			return new WP_REST_Response( array( 'error' => 'Failed to save observation' ), 500 );
		}

		return new WP_REST_Response( array( 'id' => $id ), 201 );
	}

	public static function get_session_observations( WP_REST_Request $request ): WP_REST_Response {
		$session_id = (int) $request['session_id'];
		return new WP_REST_Response( WPNT_DB::get_session_observations( $session_id ), 200 );
	}

	public static function save_progress( WP_REST_Request $request ): WP_REST_Response {
		$sailor_id = absint( $request->get_param( 'sailor_id' ) );
		$status    = sanitize_text_field( $request->get_param( 'status' ) ?? '' );
		$skill_id  = absint( $request->get_param( 'skill_id' ) );
		$node_id   = absint( $request->get_param( 'curriculum_node_id' ) );
		$evidence  = sanitize_textarea_field( $request->get_param( 'evidence' ) ?? '' );

		if ( ! $sailor_id || ! $status ) {
			return new WP_REST_Response( array( 'error' => 'sailor_id and status are required' ), 400 );
		}

		$ok = WPNT_DB::upsert_progress( $sailor_id, $status, $skill_id, $node_id, $evidence );
		return new WP_REST_Response( array( 'saved' => $ok ), $ok ? 200 : 500 );
	}

	public static function get_sailor_progress( WP_REST_Request $request ): WP_REST_Response {
		$sailor_id = (int) $request['sailor_id'];
		if ( ! self::can_view_sailor_id( $sailor_id ) ) {
			return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}
		return new WP_REST_Response( WPNT_DB::get_sailor_progress( $sailor_id ), 200 );
	}

	public static function get_todays_sessions( WP_REST_Request $request ): WP_REST_Response {
		$coach_id = current_user_can( 'wpnt_view_own_data' ) ? 0 : get_current_user_id();
		$sessions = WPNT_Session::get_todays_sessions( $coach_id );
		return new WP_REST_Response( array_map( array( __CLASS__, 'format_session' ), $sessions ), 200 );
	}

	public static function get_upcoming_sessions( WP_REST_Request $request ): WP_REST_Response {
		$coach_id = current_user_can( 'wpnt_view_own_data' ) ? 0 : get_current_user_id();
		$sessions = WPNT_Session::get_upcoming_sessions( $coach_id );
		return new WP_REST_Response( array_map( array( __CLASS__, 'format_session' ), $sessions ), 200 );
	}

	public static function generate_course_sessions( WP_REST_Request $request ): WP_REST_Response {
		$course_id = (int) $request['course_id'];
		$count     = WPNT_Course::generate_sessions( $course_id );
		return new WP_REST_Response( array( 'created' => $count ), 200 );
	}

	public static function enroll_sailor( WP_REST_Request $request ): WP_REST_Response {
		$course_id = (int) $request['course_id'];
		$sailor_id = absint( $request->get_param( 'sailor_id' ) );
		if ( ! $sailor_id ) {
			return new WP_REST_Response( array( 'error' => 'sailor_id required' ), 400 );
		}
		$ok = WPNT_Course::enroll_sailor( $course_id, $sailor_id );
		return new WP_REST_Response( array( 'enrolled' => $ok ), $ok ? 200 : 500 );
	}

	public static function get_session_groups( WP_REST_Request $request ): WP_REST_Response {
		$session_id = (int) $request['session_id'];
		$groups     = WPNT_Session_Group::get_for_session( $session_id );
		return new WP_REST_Response( $groups, 200 );
	}

	public static function create_session_group( WP_REST_Request $request ): WP_REST_Response {
		$session_id = (int) $request['session_id'];
		$args       = array(
			'label'              => sanitize_text_field( $request->get_param( 'label' ) ?? '' ),
			'course_id'          => absint( $request->get_param( 'course_id' ) ),
			'curriculum_node_id' => absint( $request->get_param( 'curriculum_node_id' ) ),
			'planned_skills'     => (array) ( $request->get_param( 'planned_skills' ) ?? array() ),
			'display_order'      => absint( $request->get_param( 'display_order' ) ),
		);

		$id = WPNT_Session_Group::create( $session_id, $args );
		if ( ! $id ) {
			return new WP_REST_Response( array( 'error' => 'Failed to create group' ), 500 );
		}
		return new WP_REST_Response( array( 'id' => $id ), 201 );
	}

	public static function update_session_group( WP_REST_Request $request ): WP_REST_Response {
		$group_id = (int) $request['group_id'];
		$args     = array();

		foreach ( array( 'label', 'course_id', 'curriculum_node_id', 'planned_skills', 'actual_skills' ) as $key ) {
			$val = $request->get_param( $key );
			if ( $val !== null ) {
				$args[ $key ] = $val;
			}
		}

		$ok = WPNT_Session_Group::update( $group_id, $args );
		return new WP_REST_Response( array( 'updated' => $ok ), $ok ? 200 : 500 );
	}

	public static function delete_session_group( WP_REST_Request $request ): WP_REST_Response {
		$group_id = (int) $request['group_id'];
		$ok       = WPNT_Session_Group::delete( $group_id );
		return new WP_REST_Response( array( 'deleted' => $ok ), $ok ? 200 : 500 );
	}

	public static function save_group_attendance( WP_REST_Request $request ): WP_REST_Response {
		$group_id   = (int) $request['group_id'];
		$session_id = absint( $request->get_param( 'session_id' ) );
		$records    = $request->get_param( 'records' );

		if ( ! $session_id || ! is_array( $records ) ) {
			return new WP_REST_Response( array( 'error' => 'session_id and records are required' ), 400 );
		}

		$results = WPNT_Session_Group::save_group_attendance( $session_id, $group_id, $records );

		// Update progress for each skill checked per sailor.
		foreach ( $records as $record ) {
			$sailor_id = absint( $record['sailor_id'] ?? 0 );
			$skills    = array_filter( array_map( 'absint', (array) ( $record['skills'] ?? array() ) ) );
			if ( $sailor_id && $skills ) {
				foreach ( $skills as $skill_id ) {
					WPNT_DB::upsert_progress( $sailor_id, 'practising', $skill_id );
				}
			}
		}

		// Mark session as delivered if still scheduled.
		if ( $session_id ) {
			$current = get_post_meta( $session_id, '_wpnt_status', true );
			if ( $current === 'scheduled' ) {
				update_post_meta( $session_id, '_wpnt_status', 'delivered' );
			}
		}

		return new WP_REST_Response( array( 'saved' => $results ), 200 );
	}

	public static function add_sailor_to_group( WP_REST_Request $request ): WP_REST_Response {
		$group_id  = (int) $request['group_id'];
		$sailor_id = absint( $request->get_param( 'sailor_id' ) );
		$enroll    = (bool) $request->get_param( 'enroll_in_course' );

		if ( ! $sailor_id ) {
			return new WP_REST_Response( array( 'error' => 'sailor_id required' ), 400 );
		}

		$ok = WPNT_Session_Group::add_adhoc_sailor( $group_id, $sailor_id, $enroll );
		return new WP_REST_Response( array( 'added' => $ok ), $ok ? 200 : 500 );
	}

	public static function remove_sailor_from_group( WP_REST_Request $request ): WP_REST_Response {
		$group_id  = (int) $request['group_id'];
		$sailor_id = (int) $request['sailor_id'];
		$ok        = WPNT_Session_Group::remove_adhoc_sailor( $group_id, $sailor_id );
		return new WP_REST_Response( array( 'removed' => $ok ), $ok ? 200 : 500 );
	}

	// -------------------------------------------------------------------------
	// Permission callbacks
	// -------------------------------------------------------------------------

	public static function can_mark_attendance(): bool {
		return current_user_can( 'edit_wpnt_sessions' ) || current_user_can( 'manage_options' );
	}

	public static function can_view_attendance(): bool {
		return current_user_can( 'read_private_wpnt_sessions' ) || current_user_can( 'manage_options' );
	}

	public static function can_add_observation(): bool {
		return current_user_can( 'edit_wpnt_sessions' ) || current_user_can( 'manage_options' );
	}

	public static function can_view_sessions(): bool {
		return is_user_logged_in();
	}

	public static function can_manage_courses(): bool {
		return current_user_can( 'edit_wpnt_courses' ) || current_user_can( 'manage_options' );
	}

	public static function can_view_sailor(): bool {
		return is_user_logged_in();
	}

	private static function can_view_sailor_id( int $sailor_id ): bool {
		$user_id = get_current_user_id();
		if ( current_user_can( 'manage_options' ) || current_user_can( 'read_private_wpnt_sessions' ) ) {
			return true;
		}
		// A sailor can see their own data.
		return $user_id === $sailor_id;
	}

	// -------------------------------------------------------------------------
	// Formatters
	// -------------------------------------------------------------------------

	private static function format_session( WP_Post $session ): array {
		return array(
			'id'                => $session->ID,
			'title'             => $session->post_title,
			'status'            => get_post_meta( $session->ID, '_wpnt_status', true ),
			'scheduled_start'   => get_post_meta( $session->ID, '_wpnt_scheduled_start', true ),
			'scheduled_end'     => get_post_meta( $session->ID, '_wpnt_scheduled_end', true ),
			'location'          => get_post_meta( $session->ID, '_wpnt_location', true ),
			'course_id'         => (int) get_post_meta( $session->ID, '_wpnt_course_id', true ),
			'session_number'    => (int) get_post_meta( $session->ID, '_wpnt_session_number', true ),
			'url'               => get_permalink( $session->ID ),
		);
	}
}
