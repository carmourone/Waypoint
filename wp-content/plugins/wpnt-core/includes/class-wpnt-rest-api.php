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

		register_rest_route( $ns, '/progress/athlete/(?P<athlete_id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_athlete_progress' ),
			'permission_callback' => array( __CLASS__, 'can_view_athlete' ),
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
			'callback'            => array( __CLASS__, 'enroll_athlete' ),
			'permission_callback' => array( __CLASS__, 'can_manage_courses' ),
		) );

		// Session Groups — identity: (session_id, bp_group_id)
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
				'args'                => array(
					'bp_group_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
				),
			),
		) );

		register_rest_route( $ns, '/sessions/(?P<session_id>\d+)/groups/(?P<bp_group_id>\d+)', array(
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

		register_rest_route( $ns, '/sessions/(?P<session_id>\d+)/groups/(?P<bp_group_id>\d+)/attendance', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'save_group_attendance' ),
			'permission_callback' => array( __CLASS__, 'can_mark_attendance' ),
			'args'                => array(
				'records' => array( 'required' => true ),
			),
		) );

		register_rest_route( $ns, '/sessions/(?P<session_id>\d+)/groups/(?P<bp_group_id>\d+)/add-athlete', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'add_athlete_to_group' ),
			'permission_callback' => array( __CLASS__, 'can_mark_attendance' ),
			'args'                => array(
				'athlete_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
			),
		) );

		register_rest_route( $ns, '/sessions/(?P<session_id>\d+)/groups/(?P<bp_group_id>\d+)/athletes/(?P<athlete_id>\d+)', array(
			'methods'             => 'DELETE',
			'callback'            => array( __CLASS__, 'remove_athlete_from_group' ),
			'permission_callback' => array( __CLASS__, 'can_mark_attendance' ),
		) );

		// Training Plans
		register_rest_route( $ns, '/training-plans', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_training_plans' ),
				'permission_callback' => array( __CLASS__, 'can_view_sessions' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'create_training_plan' ),
				'permission_callback' => array( __CLASS__, 'can_add_observation' ),
			),
		) );

		register_rest_route( $ns, '/training-plans/(?P<plan_id>\d+)', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_training_plan' ),
				'permission_callback' => array( __CLASS__, 'can_view_sessions' ),
			),
			array(
				'methods'             => 'PUT',
				'callback'            => array( __CLASS__, 'update_training_plan' ),
				'permission_callback' => array( __CLASS__, 'can_add_observation' ),
			),
		) );

		register_rest_route( $ns, '/training-plans/(?P<plan_id>\d+)/sessions', array(
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'link_plan_session' ),
				'permission_callback' => array( __CLASS__, 'can_add_observation' ),
				'args'                => array(
					'session_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
				),
			),
			array(
				'methods'             => 'DELETE',
				'callback'            => array( __CLASS__, 'unlink_plan_session' ),
				'permission_callback' => array( __CLASS__, 'can_add_observation' ),
				'args'                => array(
					'session_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
				),
			),
		) );

		register_rest_route( $ns, '/training-plans/(?P<plan_id>\d+)/progress', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_plan_progress' ),
				'permission_callback' => array( __CLASS__, 'can_view_sessions' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'record_plan_progress' ),
				'permission_callback' => array( __CLASS__, 'can_add_observation' ),
			),
		) );

		// Diary
		register_rest_route( $ns, '/diary/templates', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_diary_templates' ),
			'permission_callback' => array( __CLASS__, 'can_view_sessions' ),
		) );

		register_rest_route( $ns, '/diary/entries', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_diary_entries' ),
				'permission_callback' => array( __CLASS__, 'can_view_sessions' ),
			),
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'create_diary_entry' ),
				'permission_callback' => 'is_user_logged_in',
			),
		) );

		register_rest_route( $ns, '/diary/entries/(?P<entry_id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_diary_entry' ),
			'permission_callback' => array( __CLASS__, 'can_view_sessions' ),
		) );

		register_rest_route( $ns, '/diary/entries/(?P<entry_id>\d+)/responses', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'save_diary_responses' ),
			'permission_callback' => 'is_user_logged_in',
		) );

		register_rest_route( $ns, '/diary/entries/(?P<entry_id>\d+)/submit', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'submit_diary_entry' ),
			'permission_callback' => 'is_user_logged_in',
		) );

		register_rest_route( $ns, '/diary/entries/(?P<entry_id>\d+)/coach-review', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'coach_review_diary_entry' ),
			'permission_callback' => array( __CLASS__, 'can_add_observation' ),
		) );

		register_rest_route( $ns, '/diary/entries/(?P<entry_id>\d+)/publish-response', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'publish_diary_coach_response' ),
			'permission_callback' => array( __CLASS__, 'can_add_observation' ),
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
		$rows       = WPNT_Attendance::get_session_attendance( $session_id );
		return new WP_REST_Response( array_values( $rows ), 200 );
	}

	public static function save_observation( WP_REST_Request $request ): WP_REST_Response {
		$note = sanitize_textarea_field( $request->get_param( 'note' ) ?? '' );
		if ( ! $note ) {
			return new WP_REST_Response( array( 'error' => 'note is required' ), 400 );
		}

		$session_id = absint( $request->get_param( 'session_id' ) );
		$title      = $session_id
			? sprintf( 'Observation — %s', get_the_title( $session_id ) )
			: sprintf( 'Observation — %s', current_time( 'Y-m-d' ) );

		$post_id = wp_insert_post( array(
			'post_type'    => 'wpnt_observation',
			'post_title'   => $title,
			'post_content' => $note,
			'post_status'  => 'draft',
			'post_author'  => get_current_user_id(),
		) );

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return new WP_REST_Response( array( 'error' => 'Failed to save observation' ), 500 );
		}

		if ( $session_id ) {
			update_post_meta( $post_id, '_wpnt_session_id', $session_id );
		}
		$athlete_id = absint( $request->get_param( 'athlete_id' ) );
		if ( $athlete_id ) {
			update_post_meta( $post_id, '_wpnt_athlete_id', $athlete_id );
		}
		$course_id = absint( $request->get_param( 'course_id' ) );
		if ( $course_id ) {
			update_post_meta( $post_id, '_wpnt_course_id', $course_id );
		}

		$confidence = sanitize_text_field( $request->get_param( 'confidence_level' ) ?? '' );
		if ( $confidence ) {
			update_post_meta( $post_id, '_wpnt_confidence_level', $confidence );
		}
		$evidence_type = sanitize_text_field( $request->get_param( 'evidence_type' ) ?? '' );
		if ( $evidence_type ) {
			update_post_meta( $post_id, '_wpnt_evidence_type', $evidence_type );
		}
		$linked_skills = $request->get_param( 'linked_skills' );
		if ( $linked_skills ) {
			update_post_meta( $post_id, '_wpnt_linked_skills', wp_json_encode( array_map( 'absint', (array) $linked_skills ) ) );
		}

		return new WP_REST_Response( array( 'id' => $post_id ), 201 );
	}

	public static function get_session_observations( WP_REST_Request $request ): WP_REST_Response {
		$session_id  = (int) $request['session_id'];
		$post_status = current_user_can( 'edit_wpnt_observations' ) ? array( 'publish', 'draft' ) : array( 'publish' );

		$posts = get_posts( array(
			'post_type'      => 'wpnt_observation',
			'post_status'    => $post_status,
			'posts_per_page' => -1,
			'meta_key'       => '_wpnt_session_id',
			'meta_value'     => $session_id,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );

		$data = array_map( function( WP_Post $p ) {
			return array(
				'id'               => $p->ID,
				'note'             => $p->post_content,
				'status'           => $p->post_status,
				'date'             => $p->post_date,
				'author_id'        => (int) $p->post_author,
				'session_id'       => (int) get_post_meta( $p->ID, '_wpnt_session_id', true ),
				'athlete_id'       => (int) get_post_meta( $p->ID, '_wpnt_athlete_id', true ),
				'course_id'        => (int) get_post_meta( $p->ID, '_wpnt_course_id', true ),
				'confidence_level' => get_post_meta( $p->ID, '_wpnt_confidence_level', true ),
				'evidence_type'    => get_post_meta( $p->ID, '_wpnt_evidence_type', true ),
				'linked_skills'    => json_decode( get_post_meta( $p->ID, '_wpnt_linked_skills', true ) ?? '[]', true ),
			);
		}, $posts );

		return new WP_REST_Response( $data, 200 );
	}

	public static function save_progress( WP_REST_Request $request ): WP_REST_Response {
		$athlete_id = absint( $request->get_param( 'athlete_id' ) );
		$status     = sanitize_text_field( $request->get_param( 'status' ) ?? '' );
		$skill_id   = absint( $request->get_param( 'skill_id' ) );
		$node_id    = absint( $request->get_param( 'curriculum_node_id' ) );
		$evidence   = sanitize_textarea_field( $request->get_param( 'evidence' ) ?? '' );

		if ( ! $athlete_id || ! $status ) {
			return new WP_REST_Response( array( 'error' => 'athlete_id and status are required' ), 400 );
		}

		$post_id = $skill_id ?: $node_id;
		if ( ! $post_id ) {
			return new WP_REST_Response( array( 'error' => 'skill_id or curriculum_node_id required' ), 400 );
		}

		$ok = WPNT_Graph::upsert_u2p( 'assessed', $athlete_id, $post_id, array(
			'status'      => $status,
			'evidence'    => $evidence,
			'coach_id'    => get_current_user_id(),
			'assessed_at' => current_time( 'mysql' ),
		) );
		return new WP_REST_Response( array( 'saved' => $ok ), $ok ? 200 : 500 );
	}

	public static function get_athlete_progress( WP_REST_Request $request ): WP_REST_Response {
		$athlete_id = (int) $request['athlete_id'];
		if ( ! self::can_view_athlete_id( $athlete_id ) ) {
			return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}
		$rows = WPNT_Graph::get_u2p( 'assessed', array( 'user_id' => $athlete_id ) );
		return new WP_REST_Response( $rows, 200 );
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

	public static function enroll_athlete( WP_REST_Request $request ): WP_REST_Response {
		$course_id  = (int) $request['course_id'];
		$athlete_id = absint( $request->get_param( 'athlete_id' ) );
		if ( ! $athlete_id ) {
			return new WP_REST_Response( array( 'error' => 'athlete_id required' ), 400 );
		}
		$ok = WPNT_Course::enroll_athlete( $course_id, $athlete_id );
		return new WP_REST_Response( array( 'enrolled' => $ok ), $ok ? 200 : 500 );
	}

	public static function get_session_groups( WP_REST_Request $request ): WP_REST_Response {
		$session_id = (int) $request['session_id'];
		$edges      = WPNT_Session_Group::get_for_session( $session_id );
		$data       = array_map( function ( $edge ) {
			$d = WPNT_Graph::decode_data( $edge->data );
			return array(
				'bp_group_id'       => (int) $edge->group_id,
				'session_id'        => (int) $edge->post_id,
				'label'             => $d['label'] ?? '',
				'planned_skills'    => $d['planned_skills'] ?? array(),
				'actual_skills'     => $d['actual_skills'] ?? array(),
				'adhoc_athlete_ids' => $d['adhoc_athlete_ids'] ?? array(),
				'display_order'     => (int) ( $d['display_order'] ?? 0 ),
			);
		}, $edges );
		return new WP_REST_Response( $data, 200 );
	}

	public static function create_session_group( WP_REST_Request $request ): WP_REST_Response {
		$session_id  = (int) $request['session_id'];
		$bp_group_id = absint( $request->get_param( 'bp_group_id' ) );

		if ( ! $bp_group_id ) {
			return new WP_REST_Response( array( 'error' => 'bp_group_id required' ), 400 );
		}

		$args = array(
			'label'          => sanitize_text_field( $request->get_param( 'label' ) ?? '' ),
			'planned_skills' => (array) ( $request->get_param( 'planned_skills' ) ?? array() ),
			'display_order'  => absint( $request->get_param( 'display_order' ) ),
		);

		$ok = WPNT_Session_Group::upsert( $bp_group_id, $session_id, $args );
		if ( ! $ok ) {
			return new WP_REST_Response( array( 'error' => 'Failed to create group' ), 500 );
		}
		return new WP_REST_Response( array( 'bp_group_id' => $bp_group_id, 'session_id' => $session_id ), 201 );
	}

	public static function update_session_group( WP_REST_Request $request ): WP_REST_Response {
		$session_id  = (int) $request['session_id'];
		$bp_group_id = (int) $request['bp_group_id'];
		$args        = array();

		foreach ( array( 'label', 'planned_skills', 'actual_skills', 'display_order' ) as $key ) {
			$val = $request->get_param( $key );
			if ( $val !== null ) {
				$args[ $key ] = $val;
			}
		}

		$ok = WPNT_Session_Group::upsert( $bp_group_id, $session_id, $args );
		return new WP_REST_Response( array( 'updated' => $ok ), $ok ? 200 : 500 );
	}

	public static function delete_session_group( WP_REST_Request $request ): WP_REST_Response {
		$session_id  = (int) $request['session_id'];
		$bp_group_id = (int) $request['bp_group_id'];
		$ok          = WPNT_Session_Group::delete( $bp_group_id, $session_id );
		return new WP_REST_Response( array( 'deleted' => $ok ), $ok ? 200 : 500 );
	}

	public static function save_group_attendance( WP_REST_Request $request ): WP_REST_Response {
		$session_id  = (int) $request['session_id'];
		$bp_group_id = (int) $request['bp_group_id'];
		$records     = $request->get_param( 'records' );

		if ( ! is_array( $records ) ) {
			return new WP_REST_Response( array( 'error' => 'records must be an array' ), 400 );
		}

		$results = WPNT_Session_Group::save_group_attendance( $bp_group_id, $session_id, $records );

		foreach ( $records as $record ) {
			$athlete_id = absint( $record['athlete_id'] ?? 0 );
			$skills     = array_filter( array_map( 'absint', (array) ( $record['skills'] ?? array() ) ) );
			if ( $athlete_id && $skills ) {
				foreach ( $skills as $skill_id ) {
					WPNT_Graph::upsert_u2p( 'assessed', $athlete_id, $skill_id, array(
						'status'      => 'practising',
						'coach_id'    => get_current_user_id(),
						'assessed_at' => current_time( 'mysql' ),
					) );
				}
			}
		}

		$current = get_post_meta( $session_id, '_wpnt_status', true );
		if ( $current === 'scheduled' ) {
			update_post_meta( $session_id, '_wpnt_status', 'delivered' );
		}

		return new WP_REST_Response( array( 'saved' => $results ), 200 );
	}

	public static function add_athlete_to_group( WP_REST_Request $request ): WP_REST_Response {
		$session_id  = (int) $request['session_id'];
		$bp_group_id = (int) $request['bp_group_id'];
		$athlete_id  = absint( $request->get_param( 'athlete_id' ) );
		$enroll      = (bool) $request->get_param( 'enroll_in_course' );

		if ( ! $athlete_id ) {
			return new WP_REST_Response( array( 'error' => 'athlete_id required' ), 400 );
		}

		$ok = WPNT_Session_Group::add_adhoc_athlete( $bp_group_id, $session_id, $athlete_id, $enroll );
		return new WP_REST_Response( array( 'added' => $ok ), $ok ? 200 : 500 );
	}

	public static function remove_athlete_from_group( WP_REST_Request $request ): WP_REST_Response {
		$session_id  = (int) $request['session_id'];
		$bp_group_id = (int) $request['bp_group_id'];
		$athlete_id  = (int) $request['athlete_id'];
		$ok          = WPNT_Session_Group::remove_adhoc_athlete( $bp_group_id, $session_id, $athlete_id );
		return new WP_REST_Response( array( 'removed' => $ok ), $ok ? 200 : 500 );
	}

	// -------------------------------------------------------------------------
	// Training plan handlers
	// -------------------------------------------------------------------------

	public static function get_training_plans( WP_REST_Request $request ): WP_REST_Response {
		$athlete_id   = absint( $request->get_param( 'athlete_id' ) );
		$subject_type = sanitize_key( $request->get_param( 'subject_type' ) ?? '' );
		$subject_id   = absint( $request->get_param( 'subject_id' ) );

		if ( $athlete_id ) {
			if ( ! self::can_view_athlete_id( $athlete_id ) ) {
				return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
			}
			$plans = WPNT_Training_Plan::get_for_athlete( $athlete_id );
		} elseif ( $subject_type && $subject_id ) {
			$plans = WPNT_Training_Plan::get_for_subject( $subject_type, $subject_id );
		} else {
			return new WP_REST_Response( array( 'error' => 'athlete_id or subject_type+subject_id required' ), 400 );
		}

		return new WP_REST_Response( array_map( array( __CLASS__, 'format_plan' ), $plans ), 200 );
	}

	public static function create_training_plan( WP_REST_Request $request ): WP_REST_Response {
		$title    = sanitize_text_field( $request->get_param( 'title' ) ?? '' );
		$goal     = sanitize_textarea_field( $request->get_param( 'goal' ) ?? '' );
		$parentid = absint( $request->get_param( 'parent_id' ) );

		if ( ! $title ) {
			return new WP_REST_Response( array( 'error' => 'title is required' ), 400 );
		}

		$plan_id = wp_insert_post( array(
			'post_type'    => 'wpnt_training_plan',
			'post_title'   => $title,
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'post_parent'  => $parentid,
		) );

		if ( is_wp_error( $plan_id ) || ! $plan_id ) {
			return new WP_REST_Response( array( 'error' => 'Failed to create plan' ), 500 );
		}

		$meta_map = array(
			'plan_type'    => '_wpnt_plan_type',
			'subject_type' => '_wpnt_subject_type',
			'subject_id'   => '_wpnt_subject_id',
			'athlete_id'   => '_wpnt_athlete_id',
			'scope'        => '_wpnt_scope',
			'origin'       => '_wpnt_origin',
			'target_date'  => '_wpnt_target_date',
		);
		foreach ( $meta_map as $param => $key ) {
			$val = $request->get_param( $param );
			if ( $val !== null ) {
				update_post_meta( $plan_id, $key, sanitize_text_field( (string) $val ) );
			}
		}
		if ( $goal ) {
			update_post_meta( $plan_id, '_wpnt_goal', $goal );
		}
		update_post_meta( $plan_id, '_wpnt_status', 'draft' );
		update_post_meta( $plan_id, '_wpnt_visibility', 'shared' );

		return new WP_REST_Response( array( 'id' => $plan_id ), 201 );
	}

	public static function get_training_plan( WP_REST_Request $request ): WP_REST_Response {
		$plan_id = (int) $request['plan_id'];
		$post    = get_post( $plan_id );
		if ( ! $post || $post->post_type !== 'wpnt_training_plan' ) {
			return new WP_REST_Response( array( 'error' => 'Not found' ), 404 );
		}
		return new WP_REST_Response( self::format_plan( $post ), 200 );
	}

	public static function update_training_plan( WP_REST_Request $request ): WP_REST_Response {
		$plan_id = (int) $request['plan_id'];
		$post    = get_post( $plan_id );
		if ( ! $post || $post->post_type !== 'wpnt_training_plan' ) {
			return new WP_REST_Response( array( 'error' => 'Not found' ), 404 );
		}

		$updates = array();
		$title   = $request->get_param( 'title' );
		if ( $title !== null ) {
			wp_update_post( array( 'ID' => $plan_id, 'post_title' => sanitize_text_field( $title ) ) );
		}

		$meta_map = array(
			'plan_type'    => '_wpnt_plan_type',
			'subject_type' => '_wpnt_subject_type',
			'subject_id'   => '_wpnt_subject_id',
			'status'       => '_wpnt_status',
			'scope'        => '_wpnt_scope',
			'origin'       => '_wpnt_origin',
			'target_date'  => '_wpnt_target_date',
			'visibility'   => '_wpnt_visibility',
		);
		foreach ( $meta_map as $param => $key ) {
			$val = $request->get_param( $param );
			if ( $val !== null ) {
				update_post_meta( $plan_id, $key, sanitize_text_field( (string) $val ) );
			}
		}
		foreach ( array( 'goal', 'planned_activities' ) as $param ) {
			$val = $request->get_param( $param );
			if ( $val !== null ) {
				update_post_meta( $plan_id, '_wpnt_' . $param, sanitize_textarea_field( (string) $val ) );
			}
		}

		return new WP_REST_Response( array( 'updated' => true ), 200 );
	}

	public static function link_plan_session( WP_REST_Request $request ): WP_REST_Response {
		$plan_id    = (int) $request['plan_id'];
		$session_id = absint( $request->get_param( 'session_id' ) );
		$role       = sanitize_key( $request->get_param( 'role' ) ?? 'primary_delivery' );
		$ok         = WPNT_Training_Plan::link_session( $plan_id, $session_id, $role );
		return new WP_REST_Response( array( 'linked' => $ok ), $ok ? 200 : 500 );
	}

	public static function unlink_plan_session( WP_REST_Request $request ): WP_REST_Response {
		$plan_id    = (int) $request['plan_id'];
		$session_id = absint( $request->get_param( 'session_id' ) );
		$ok         = WPNT_Training_Plan::unlink_session( $plan_id, $session_id );
		return new WP_REST_Response( array( 'unlinked' => $ok ), $ok ? 200 : 500 );
	}

	public static function get_plan_progress( WP_REST_Request $request ): WP_REST_Response {
		$plan_id      = (int) $request['plan_id'];
		$subject_type = sanitize_key( $request->get_param( 'subject_type' ) ?? '' );
		$subject_id   = absint( $request->get_param( 'subject_id' ) );
		$rows         = WPNT_Training_Plan::get_progress( $plan_id, $subject_type, $subject_id );
		return new WP_REST_Response( $rows, 200 );
	}

	public static function record_plan_progress( WP_REST_Request $request ): WP_REST_Response {
		$plan_id = (int) $request['plan_id'];
		$data    = array(
			'plan_id'      => $plan_id,
			'objective_id' => absint( $request->get_param( 'objective_id' ) ),
			'subject_type' => sanitize_key( $request->get_param( 'subject_type' ) ?? 'individual' ),
			'subject_id'   => absint( $request->get_param( 'subject_id' ) ),
			'skill_id'     => absint( $request->get_param( 'skill_id' ) ),
			'session_id'   => absint( $request->get_param( 'session_id' ) ),
			'status'       => sanitize_key( $request->get_param( 'status' ) ?? 'not_started' ),
			'evidence'     => sanitize_textarea_field( $request->get_param( 'evidence' ) ?? '' ),
			'coach_note'   => sanitize_textarea_field( $request->get_param( 'coach_note' ) ?? '' ),
			'visibility'   => sanitize_key( $request->get_param( 'visibility' ) ?? 'shared' ),
		);
		$ok = WPNT_Training_Plan::record_progress( $data );
		return new WP_REST_Response( array( 'saved' => $ok ), $ok ? 200 : 500 );
	}

	private static function format_plan( WP_Post $plan ): array {
		return array(
			'id'           => $plan->ID,
			'title'        => $plan->post_title,
			'plan_type'    => get_post_meta( $plan->ID, '_wpnt_plan_type', true ),
			'subject_type' => get_post_meta( $plan->ID, '_wpnt_subject_type', true ),
			'subject_id'   => (int) get_post_meta( $plan->ID, '_wpnt_subject_id', true ),
			'athlete_id'   => (int) get_post_meta( $plan->ID, '_wpnt_athlete_id', true ),
			'status'       => get_post_meta( $plan->ID, '_wpnt_status', true ),
			'scope'        => get_post_meta( $plan->ID, '_wpnt_scope', true ),
			'goal'         => get_post_meta( $plan->ID, '_wpnt_goal', true ),
			'target_date'  => get_post_meta( $plan->ID, '_wpnt_target_date', true ),
			'visibility'   => get_post_meta( $plan->ID, '_wpnt_visibility', true ) ?: 'shared',
			'parent_id'    => (int) $plan->post_parent,
		);
	}

	// -------------------------------------------------------------------------
	// Diary handlers
	// -------------------------------------------------------------------------

	public static function get_diary_templates( WP_REST_Request $request ): WP_REST_Response {
		$templates = WPNT_Diary::get_templates();
		$data      = array_map( function( WP_Post $t ) {
			return array(
				'id'        => $t->ID,
				'title'     => $t->post_title,
				'questions' => WPNT_Diary::get_template_questions( $t->ID ),
			);
		}, $templates );
		return new WP_REST_Response( $data, 200 );
	}

	public static function get_diary_entries( WP_REST_Request $request ): WP_REST_Response {
		$athlete_id = absint( $request->get_param( 'athlete_id' ) );
		$status     = sanitize_key( $request->get_param( 'status' ) ?? '' );

		if ( ! $athlete_id ) {
			$athlete_id = get_current_user_id();
		}

		if ( ! self::can_view_athlete_id( $athlete_id ) ) {
			return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}

		$args    = $status ? array( 'status' => $status ) : array();
		$entries = WPNT_Diary::get_for_athlete( $athlete_id, $args );
		$data    = array_map( array( __CLASS__, 'format_diary_entry' ), $entries );
		return new WP_REST_Response( $data, 200 );
	}

	public static function create_diary_entry( WP_REST_Request $request ): WP_REST_Response {
		$athlete_id  = absint( $request->get_param( 'athlete_id' ) ) ?: get_current_user_id();
		$template_id = absint( $request->get_param( 'template_id' ) );
		$event_type  = sanitize_key( $request->get_param( 'event_type' ) ?? 'training' );
		$session_id  = absint( $request->get_param( 'session_id' ) );
		$title       = sanitize_text_field( $request->get_param( 'title' ) ?? '' ) ?: sprintf( 'Diary — %s', current_time( 'Y-m-d' ) );

		$post_id = wp_insert_post( array(
			'post_type'   => 'wpnt_diary_entry',
			'post_title'  => $title,
			'post_status' => 'publish',
			'post_author' => $athlete_id,
		) );

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return new WP_REST_Response( array( 'error' => 'Failed to create entry' ), 500 );
		}

		update_post_meta( $post_id, '_wpnt_athlete_id', $athlete_id );
		update_post_meta( $post_id, '_wpnt_template_id', $template_id );
		update_post_meta( $post_id, '_wpnt_event_type', $event_type );
		update_post_meta( $post_id, '_wpnt_status', 'draft' );
		if ( $session_id ) {
			update_post_meta( $post_id, '_wpnt_session_id', $session_id );
		}

		return new WP_REST_Response( array( 'id' => $post_id ), 201 );
	}

	public static function get_diary_entry( WP_REST_Request $request ): WP_REST_Response {
		$entry_id = (int) $request['entry_id'];
		$post     = get_post( $entry_id );
		if ( ! $post || $post->post_type !== 'wpnt_diary_entry' ) {
			return new WP_REST_Response( array( 'error' => 'Not found' ), 404 );
		}
		if ( ! WPNT_Diary::can_view( $entry_id, get_current_user_id() ) ) {
			return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}
		$data              = self::format_diary_entry( $post );
		$data['responses'] = WPNT_Diary::get_responses( $entry_id );
		return new WP_REST_Response( $data, 200 );
	}

	public static function save_diary_responses( WP_REST_Request $request ): WP_REST_Response {
		$entry_id  = (int) $request['entry_id'];
		$post      = get_post( $entry_id );
		if ( ! $post || $post->post_type !== 'wpnt_diary_entry' ) {
			return new WP_REST_Response( array( 'error' => 'Not found' ), 404 );
		}
		$athlete_id = (int) get_post_meta( $entry_id, '_wpnt_athlete_id', true );
		if ( get_current_user_id() !== $athlete_id && ! current_user_can( 'manage_options' ) ) {
			return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}
		$responses = $request->get_param( 'responses' );
		if ( ! is_array( $responses ) ) {
			return new WP_REST_Response( array( 'error' => 'responses must be an array' ), 400 );
		}
		$ok = WPNT_Diary::save_responses( $entry_id, $responses );
		return new WP_REST_Response( array( 'saved' => $ok ), $ok ? 200 : 500 );
	}

	public static function submit_diary_entry( WP_REST_Request $request ): WP_REST_Response {
		$entry_id   = (int) $request['entry_id'];
		$athlete_id = (int) get_post_meta( $entry_id, '_wpnt_athlete_id', true );
		if ( get_current_user_id() !== $athlete_id && ! current_user_can( 'manage_options' ) ) {
			return new WP_REST_Response( array( 'error' => 'Forbidden' ), 403 );
		}
		$ok = WPNT_Diary::submit( $entry_id );
		return new WP_REST_Response( array( 'submitted' => $ok ), $ok ? 200 : 400 );
	}

	public static function coach_review_diary_entry( WP_REST_Request $request ): WP_REST_Response {
		$entry_id = (int) $request['entry_id'];
		$text     = sanitize_textarea_field( $request->get_param( 'response_text' ) ?? '' );
		$publish  = $request->get_param( 'publish' ) === true || $request->get_param( 'publish' ) === 'yes' ? 'yes' : 'no';
		if ( ! $text ) {
			return new WP_REST_Response( array( 'error' => 'response_text is required' ), 400 );
		}
		$ok = WPNT_Diary::coach_review( $entry_id, $text, $publish );
		return new WP_REST_Response( array( 'saved' => $ok ), $ok ? 200 : 400 );
	}

	public static function publish_diary_coach_response( WP_REST_Request $request ): WP_REST_Response {
		$entry_id = (int) $request['entry_id'];
		$ok       = WPNT_Diary::publish_coach_response( $entry_id );
		return new WP_REST_Response( array( 'published' => $ok ), $ok ? 200 : 400 );
	}

	private static function format_diary_entry( WP_Post $entry ): array {
		return array(
			'id'                    => $entry->ID,
			'title'                 => $entry->post_title,
			'date'                  => $entry->post_date,
			'athlete_id'            => (int) get_post_meta( $entry->ID, '_wpnt_athlete_id', true ),
			'template_id'           => (int) get_post_meta( $entry->ID, '_wpnt_template_id', true ),
			'event_type'            => get_post_meta( $entry->ID, '_wpnt_event_type', true ),
			'session_id'            => (int) get_post_meta( $entry->ID, '_wpnt_session_id', true ),
			'status'                => get_post_meta( $entry->ID, '_wpnt_status', true ),
			'coach_response_status' => get_post_meta( $entry->ID, '_wpnt_coach_response_status', true ),
			'coach_response'        => get_post_meta( $entry->ID, '_wpnt_coach_response', true ),
			'coach_id'              => (int) get_post_meta( $entry->ID, '_wpnt_coach_id', true ),
		);
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
		return current_user_can( 'edit_wpnt_observations' ) || current_user_can( 'manage_options' );
	}

	public static function can_view_sessions(): bool {
		return is_user_logged_in();
	}

	public static function can_manage_courses(): bool {
		return current_user_can( 'edit_wpnt_courses' ) || current_user_can( 'manage_options' );
	}

	public static function can_view_athlete(): bool {
		return is_user_logged_in();
	}

	private static function can_view_athlete_id( int $athlete_id ): bool {
		return WPNT_Graph::can_view_athlete_data( get_current_user_id(), $athlete_id );
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
