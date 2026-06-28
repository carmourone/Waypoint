<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and saves meta boxes for all Waypoint post types.
 *
 * Each post type has its own method that adds the box and renders the fields.
 * A single save() handler dispatches to per-type savers.
 */
class WPNT_Meta_Boxes {

	public static function register(): void {
		add_meta_box( 'wpnt_curriculum_meta', __( 'Curriculum Details', 'wpnt' ), array( __CLASS__, 'curriculum_fields' ), 'wpnt_curriculum', 'normal', 'high' );
		add_meta_box( 'wpnt_node_meta', __( 'Node Details', 'wpnt' ), array( __CLASS__, 'node_fields' ), 'wpnt_node', 'normal', 'high' );
		add_meta_box( 'wpnt_skill_meta', __( 'Skill Details', 'wpnt' ), array( __CLASS__, 'skill_fields' ), 'wpnt_skill', 'normal', 'high' );
		add_meta_box( 'wpnt_drill_meta', __( 'Drill Details', 'wpnt' ), array( __CLASS__, 'drill_fields' ), 'wpnt_drill', 'normal', 'high' );
		add_meta_box( 'wpnt_session_tmpl_meta', __( 'Template Details', 'wpnt' ), array( __CLASS__, 'session_tmpl_fields' ), 'wpnt_session_tmpl', 'normal', 'high' );
		add_meta_box( 'wpnt_course_meta', __( 'Course Details', 'wpnt' ), array( __CLASS__, 'course_fields' ), 'wpnt_course', 'normal', 'high' );
		add_meta_box( 'wpnt_session_meta', __( 'Session Details', 'wpnt' ), array( __CLASS__, 'session_fields' ), 'wpnt_session', 'normal', 'high' );
		add_meta_box( 'wpnt_training_plan_meta', __( 'Training Plan Details', 'wpnt' ), array( __CLASS__, 'training_plan_fields' ), 'wpnt_training_plan', 'normal', 'high' );
		add_meta_box( 'wpnt_observation_meta', __( 'Observation Details', 'wpnt' ), array( __CLASS__, 'observation_fields' ), 'wpnt_observation', 'side', 'default' );
		add_meta_box( 'wpnt_diary_template_meta', __( 'Template Details', 'wpnt' ), array( __CLASS__, 'diary_template_fields' ), 'wpnt_diary_template', 'normal', 'high' );
		add_meta_box( 'wpnt_diary_entry_meta', __( 'Entry Details', 'wpnt' ), array( __CLASS__, 'diary_entry_fields' ), 'wpnt_diary_entry', 'side', 'default' );
	}

	// -------------------------------------------------------------------------
	// Render helpers
	// -------------------------------------------------------------------------

	public static function curriculum_fields( WP_Post $post ): void {
		wp_nonce_field( 'wpnt_save_meta', 'wpnt_meta_nonce' );
		$version = get_post_meta( $post->ID, '_wpnt_version', true );
		$org     = get_post_meta( $post->ID, '_wpnt_org', true );
		echo '<table class="form-table"><tbody>';
		self::text_row( __( 'Organisation', 'wpnt' ), 'wpnt_org', $org, 'e.g. Australian Sailing' );
		self::text_row( __( 'Version', 'wpnt' ), 'wpnt_version', $version, 'e.g. 2.0' );
		echo '</tbody></table>';
	}

	public static function node_fields( WP_Post $post ): void {
		wp_nonce_field( 'wpnt_save_meta', 'wpnt_meta_nonce' );
		$curriculum_id = get_post_meta( $post->ID, '_wpnt_curriculum_id', true );
		$node_type     = get_post_meta( $post->ID, '_wpnt_node_type', true );
		$order         = get_post_meta( $post->ID, '_wpnt_order', true );

		$curricula = get_posts( array( 'post_type' => 'wpnt_curriculum', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );

		echo '<table class="form-table"><tbody>';
		echo '<tr><th><label for="wpnt_curriculum_id">' . esc_html__( 'Curriculum Pack', 'wpnt' ) . '</label></th><td>';
		echo '<select name="wpnt_curriculum_id" id="wpnt_curriculum_id">';
		echo '<option value="">' . esc_html__( '— Select —', 'wpnt' ) . '</option>';
		foreach ( $curricula as $c ) {
			printf( '<option value="%d"%s>%s</option>', $c->ID, selected( $curriculum_id, $c->ID, false ), esc_html( $c->post_title ) );
		}
		echo '</select></td></tr>';

		self::select_row( __( 'Node Type', 'wpnt' ), 'wpnt_node_type', $node_type, array(
			''              => '— Select —',
			'pathway'       => 'Pathway',
			'program'       => 'Program',
			'level'         => 'Level',
			'module'        => 'Module',
			'skill_group'   => 'Skill Group',
			'session_block' => 'Session Block',
		) );
		self::text_row( __( 'Order', 'wpnt' ), 'wpnt_order', $order, '1', 'number' );
		echo '</tbody></table>';
	}

	public static function skill_fields( WP_Post $post ): void {
		wp_nonce_field( 'wpnt_save_meta', 'wpnt_meta_nonce' );
		$node_id   = get_post_meta( $post->ID, '_wpnt_node_id', true );
		$criteria  = get_post_meta( $post->ID, '_wpnt_assessment_criteria', true );
		$prereqs   = get_post_meta( $post->ID, '_wpnt_prerequisite_skills', true );
		echo '<table class="form-table"><tbody>';
		self::post_select_row( __( 'Curriculum Node', 'wpnt' ), 'wpnt_node_id', $node_id, 'wpnt_node' );
		self::textarea_row( __( 'Assessment Criteria', 'wpnt' ), 'wpnt_assessment_criteria', $criteria );
		self::text_row( __( 'Prerequisite Skill IDs', 'wpnt' ), 'wpnt_prerequisite_skills', $prereqs, 'Comma-separated post IDs' );
		echo '</tbody></table>';
	}

	public static function drill_fields( WP_Post $post ): void {
		wp_nonce_field( 'wpnt_save_meta', 'wpnt_meta_nonce' );
		$setup        = get_post_meta( $post->ID, '_wpnt_setup', true );
		$instructions = get_post_meta( $post->ID, '_wpnt_instructions', true );
		$variations   = get_post_meta( $post->ID, '_wpnt_variations', true );
		$fallbacks    = get_post_meta( $post->ID, '_wpnt_fallbacks', true );
		$equipment    = get_post_meta( $post->ID, '_wpnt_equipment', true );
		echo '<table class="form-table"><tbody>';
		self::textarea_row( __( 'Equipment', 'wpnt' ), 'wpnt_equipment', $equipment );
		self::textarea_row( __( 'Setup', 'wpnt' ), 'wpnt_setup', $setup );
		self::textarea_row( __( 'Instructions', 'wpnt' ), 'wpnt_instructions', $instructions );
		self::textarea_row( __( 'Variations', 'wpnt' ), 'wpnt_variations', $variations );
		self::textarea_row( __( 'Fallback Options', 'wpnt' ), 'wpnt_fallbacks', $fallbacks, 'Light-wind, heavy-wind, land-based, etc.' );
		echo '</tbody></table>';
	}

	public static function session_tmpl_fields( WP_Post $post ): void {
		wp_nonce_field( 'wpnt_save_meta', 'wpnt_meta_nonce' );
		$duration   = get_post_meta( $post->ID, '_wpnt_duration_mins', true );
		$briefing   = get_post_meta( $post->ID, '_wpnt_briefing_notes', true );
		$safety     = get_post_meta( $post->ID, '_wpnt_safety_notes', true );
		$equipment  = get_post_meta( $post->ID, '_wpnt_equipment', true );
		echo '<table class="form-table"><tbody>';
		self::text_row( __( 'Duration (minutes)', 'wpnt' ), 'wpnt_duration_mins', $duration, '90', 'number' );
		self::textarea_row( __( 'Briefing Notes', 'wpnt' ), 'wpnt_briefing_notes', $briefing );
		self::textarea_row( __( 'Safety Notes', 'wpnt' ), 'wpnt_safety_notes', $safety );
		self::textarea_row( __( 'Equipment', 'wpnt' ), 'wpnt_equipment', $equipment );
		echo '</tbody></table>';
	}

	public static function course_fields( WP_Post $post ): void {
		wp_nonce_field( 'wpnt_save_meta', 'wpnt_meta_nonce' );
		$node_id    = get_post_meta( $post->ID, '_wpnt_node_id', true );
		$start_date = get_post_meta( $post->ID, '_wpnt_start_date', true );
		$end_date   = get_post_meta( $post->ID, '_wpnt_end_date', true );
		$day_time   = get_post_meta( $post->ID, '_wpnt_default_day_time', true );
		$status     = get_post_meta( $post->ID, '_wpnt_status', true );
		$bp_group   = get_post_meta( $post->ID, '_wpnt_bp_group_id', true );
		echo '<table class="form-table"><tbody>';
		self::post_select_row( __( 'Curriculum Node', 'wpnt' ), 'wpnt_node_id', $node_id, 'wpnt_node' );
		self::text_row( __( 'Start Date', 'wpnt' ), 'wpnt_start_date', $start_date, '', 'date' );
		self::text_row( __( 'End Date', 'wpnt' ), 'wpnt_end_date', $end_date, '', 'date' );
		self::text_row( __( 'Default Day / Time', 'wpnt' ), 'wpnt_default_day_time', $day_time, 'e.g. Saturday 09:00' );
		self::select_row( __( 'Status', 'wpnt' ), 'wpnt_status', $status, array(
			'active'    => 'Active',
			'draft'     => 'Draft',
			'completed' => 'Completed',
			'cancelled' => 'Cancelled',
		) );
		self::text_row( __( 'BuddyPress Group ID', 'wpnt' ), 'wpnt_bp_group_id', $bp_group, 'Optional' );
		echo '</tbody></table>';
	}

	public static function session_fields( WP_Post $post ): void {
		wp_nonce_field( 'wpnt_save_meta', 'wpnt_meta_nonce' );
		$course_id       = get_post_meta( $post->ID, '_wpnt_course_id', true );
		$session_number  = get_post_meta( $post->ID, '_wpnt_session_number', true );
		$start           = get_post_meta( $post->ID, '_wpnt_scheduled_start', true );
		$end             = get_post_meta( $post->ID, '_wpnt_scheduled_end', true );
		$location        = get_post_meta( $post->ID, '_wpnt_location', true );
		$template_id     = get_post_meta( $post->ID, '_wpnt_template_id', true );
		$status          = get_post_meta( $post->ID, '_wpnt_status', true );
		$actual_notes    = get_post_meta( $post->ID, '_wpnt_actual_notes', true );
		echo '<table class="form-table"><tbody>';
		self::post_select_row( __( 'Course', 'wpnt' ), 'wpnt_course_id', $course_id, 'wpnt_course' );
		self::text_row( __( 'Session Number', 'wpnt' ), 'wpnt_session_number', $session_number, '', 'number' );
		self::text_row( __( 'Scheduled Start', 'wpnt' ), 'wpnt_scheduled_start', $start, '', 'datetime-local' );
		self::text_row( __( 'Scheduled End', 'wpnt' ), 'wpnt_scheduled_end', $end, '', 'datetime-local' );
		self::text_row( __( 'Location', 'wpnt' ), 'wpnt_location', $location );
		self::post_select_row( __( 'Session Template', 'wpnt' ), 'wpnt_template_id', $template_id, 'wpnt_session_tmpl' );
		self::select_row( __( 'Status', 'wpnt' ), 'wpnt_status', $status, array(
			'scheduled'   => 'Scheduled',
			'delivered'   => 'Delivered',
			'cancelled'   => 'Cancelled',
			'rescheduled' => 'Rescheduled',
			'draft'       => 'Draft',
		) );
		self::textarea_row( __( 'Actual Session Notes', 'wpnt' ), 'wpnt_actual_notes', $actual_notes );
		echo '</tbody></table>';
	}

	public static function observation_fields( WP_Post $post ): void {
		wp_nonce_field( 'wpnt_save_meta', 'wpnt_meta_nonce' );
		$session_id      = get_post_meta( $post->ID, '_wpnt_session_id', true );
		$athlete_id      = get_post_meta( $post->ID, '_wpnt_athlete_id', true );
		$course_id       = get_post_meta( $post->ID, '_wpnt_course_id', true );
		$confidence      = get_post_meta( $post->ID, '_wpnt_confidence_level', true );
		$evidence_type   = get_post_meta( $post->ID, '_wpnt_evidence_type', true );
		$participant_lbl = WPNT_Pack::get_active_label( 'participant_label', __( 'Athlete', 'wpnt' ) );
		?>
		<p>
			<label><?php esc_html_e( 'Session', 'wpnt' ); ?><br>
			<select name="wpnt_obs_session_id" style="width:100%">
				<option value=""><?php esc_html_e( '— None —', 'wpnt' ); ?></option>
				<?php foreach ( get_posts( array( 'post_type' => 'wpnt_session', 'posts_per_page' => 50, 'orderby' => 'date', 'order' => 'DESC' ) ) as $s ) : ?>
					<option value="<?php echo esc_attr( $s->ID ); ?>"<?php selected( $session_id, $s->ID ); ?>><?php echo esc_html( $s->post_title ); ?></option>
				<?php endforeach; ?>
			</select></label>
		</p>
		<p>
			<label><?php echo esc_html( $participant_lbl ); ?> (<?php esc_html_e( 'leave blank for group', 'wpnt' ); ?>)<br>
			<select name="wpnt_obs_athlete_id" style="width:100%">
				<option value=""><?php esc_html_e( '— Group —', 'wpnt' ); ?></option>
				<?php foreach ( get_users( array( 'role' => 'wpnt_athlete', 'orderby' => 'display_name', 'number' => 200 ) ) as $u ) : ?>
					<option value="<?php echo esc_attr( $u->ID ); ?>"<?php selected( $athlete_id, $u->ID ); ?>><?php echo esc_html( $u->display_name ); ?></option>
				<?php endforeach; ?>
			</select></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Confidence Level', 'wpnt' ); ?><br>
			<select name="wpnt_obs_confidence_level" style="width:100%">
				<option value=""><?php esc_html_e( '— Select —', 'wpnt' ); ?></option>
				<?php foreach ( array( 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low' ) as $val => $lbl ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $confidence, $val ); ?>><?php echo esc_html( $lbl ); ?></option>
				<?php endforeach; ?>
			</select></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Evidence Type', 'wpnt' ); ?><br>
			<select name="wpnt_obs_evidence_type" style="width:100%">
				<option value=""><?php esc_html_e( '— Select —', 'wpnt' ); ?></option>
				<?php foreach ( array( 'verbal' => 'Verbal', 'demonstrated' => 'Demonstrated', 'recorded' => 'Recorded', 'written' => 'Written' ) as $val => $lbl ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $evidence_type, $val ); ?>><?php echo esc_html( $lbl ); ?></option>
				<?php endforeach; ?>
			</select></label>
		</p>
		<p class="description"><?php esc_html_e( 'Draft = coach-only. Publish = visible to participant and parent.', 'wpnt' ); ?></p>
		<?php
	}

	public static function training_plan_fields( WP_Post $post ): void {
		wp_nonce_field( 'wpnt_save_meta', 'wpnt_meta_nonce' );
		$plan_type    = get_post_meta( $post->ID, '_wpnt_plan_type', true );
		$subject_type = get_post_meta( $post->ID, '_wpnt_subject_type', true ) ?: 'individual';
		$subject_id   = get_post_meta( $post->ID, '_wpnt_subject_id', true );
		$athlete_id   = get_post_meta( $post->ID, '_wpnt_athlete_id', true );
		$course_id    = get_post_meta( $post->ID, '_wpnt_course_id', true );
		$origin       = get_post_meta( $post->ID, '_wpnt_origin', true );
		$scope        = get_post_meta( $post->ID, '_wpnt_scope', true );
		$goal         = get_post_meta( $post->ID, '_wpnt_goal', true );
		$activities   = get_post_meta( $post->ID, '_wpnt_planned_activities', true );
		$status       = get_post_meta( $post->ID, '_wpnt_status', true );
		$target       = get_post_meta( $post->ID, '_wpnt_target_date', true );
		$coach_id     = get_post_meta( $post->ID, '_wpnt_assigned_coach', true );
		$visibility   = get_post_meta( $post->ID, '_wpnt_visibility', true ) ?: 'shared';

		$participant_lbl = WPNT_Pack::get_active_label( 'participant_label', __( 'Athlete', 'wpnt' ) );
		echo '<table class="form-table"><tbody>';
		self::select_row( __( 'Plan Type', 'wpnt' ), 'wpnt_plan_type', $plan_type, array(
			''              => '— Select —',
			'pathway'       => 'Pathway',
			'level'         => 'Level',
			'module'        => 'Module',
			'session_block' => 'Session Block',
			'individual'    => 'Individual Plan',
			'group'         => 'Group Plan',
		) );
		self::select_row( __( 'Subject Type', 'wpnt' ), 'wpnt_subject_type', $subject_type, array(
			'individual'    => 'Individual Athlete',
			'group'         => 'Group',
			'course_cohort' => 'Course Cohort',
			'squad'         => 'Squad',
			'club'          => 'Club',
			'program'       => 'Program',
		) );
		self::text_row( __( 'Subject ID', 'wpnt' ), 'wpnt_subject_id', $subject_id, 'User or post ID' );
		self::user_select_row( $participant_lbl . ' ' . __( '(legacy)', 'wpnt' ), 'wpnt_athlete_id', $athlete_id, 'wpnt_athlete' );
		self::post_select_row( __( 'Course (optional)', 'wpnt' ), 'wpnt_course_id', $course_id, 'wpnt_course' );
		self::select_row( __( 'Origin', 'wpnt' ), 'wpnt_origin', $origin, array(
			''                   => '— Select —',
			'missed_session'     => 'Missed Session',
			'partial_attendance' => 'Partial Attendance',
			'skill_gap'          => 'Skill Gap',
			'coach_observation'  => 'Coach Observation',
			'progression_goal'   => 'Progression Goal',
			'confidence_issue'   => 'Confidence Issue',
			'race_review'        => 'Race Review',
			'parent_request'     => 'Parent Request',
			'coach_request'      => 'Coach Request',
		) );
		self::select_row( __( 'Scope', 'wpnt' ), 'wpnt_scope', $scope, array(
			''               => '— Select —',
			'micro'          => 'Micro',
			'single_session' => 'Single Session',
			'multi_session'  => 'Multi-Session',
			'term'           => 'Term',
			'season'         => 'Season',
			'pathway'        => 'Pathway',
		) );
		self::textarea_row( __( 'Goal', 'wpnt' ), 'wpnt_goal', $goal );
		self::textarea_row( __( 'Planned Activities', 'wpnt' ), 'wpnt_planned_activities', $activities );
		self::select_row( __( 'Status', 'wpnt' ), 'wpnt_status', $status, array(
			'draft'          => 'Draft',
			'pending_review' => 'Pending Review',
			'published'      => 'Published',
			'active'         => 'Active',
			'completed'      => 'Completed',
			'cancelled'      => 'Cancelled',
		) );
		self::select_row( __( 'Visibility', 'wpnt' ), 'wpnt_visibility', $visibility, array(
			'shared'   => 'Shared (athlete + parent)',
			'internal' => 'Internal (coach only)',
		) );
		self::text_row( __( 'Target Date', 'wpnt' ), 'wpnt_target_date', $target, '', 'date' );
		self::user_select_row( __( 'Assigned Coach', 'wpnt' ), 'wpnt_assigned_coach', $coach_id, 'wpnt_coach' );
		echo '</tbody></table>';
	}

	public static function diary_template_fields( WP_Post $post ): void {
		wp_nonce_field( 'wpnt_save_meta', 'wpnt_meta_nonce' );
		$event_types = get_post_meta( $post->ID, '_wpnt_event_types', true );
		echo '<table class="form-table"><tbody>';
		self::text_row( __( 'Event Types (comma-separated)', 'wpnt' ), 'wpnt_diary_event_types', $event_types, 'training,competition,event' );
		echo '</tbody></table>';
		echo '<p class="description">' . esc_html__( 'Questions are stored as JSON in the post content or via _wpnt_questions meta.', 'wpnt' ) . '</p>';
	}

	public static function diary_entry_fields( WP_Post $post ): void {
		wp_nonce_field( 'wpnt_save_meta', 'wpnt_meta_nonce' );
		$athlete_id  = get_post_meta( $post->ID, '_wpnt_athlete_id', true );
		$template_id = get_post_meta( $post->ID, '_wpnt_template_id', true );
		$event_type  = get_post_meta( $post->ID, '_wpnt_event_type', true );
		$session_id  = get_post_meta( $post->ID, '_wpnt_session_id', true );
		$status      = get_post_meta( $post->ID, '_wpnt_status', true );
		?>
		<p>
			<label><?php esc_html_e( 'Status', 'wpnt' ); ?><br>
			<select name="wpnt_diary_status" style="width:100%">
				<?php foreach ( array( 'draft' => 'Draft', 'submitted' => 'Submitted', 'reviewed' => 'Reviewed' ) as $val => $lbl ) : ?>
					<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $status, $val ); ?>><?php echo esc_html( $lbl ); ?></option>
				<?php endforeach; ?>
			</select></label>
		</p>
		<p><strong><?php esc_html_e( 'Athlete ID:', 'wpnt' ); ?></strong> <?php echo esc_html( $athlete_id ?: '—' ); ?></p>
		<p><strong><?php esc_html_e( 'Template ID:', 'wpnt' ); ?></strong> <?php echo esc_html( $template_id ?: '—' ); ?></p>
		<p><strong><?php esc_html_e( 'Event Type:', 'wpnt' ); ?></strong> <?php echo esc_html( $event_type ?: '—' ); ?></p>
		<p><strong><?php esc_html_e( 'Session ID:', 'wpnt' ); ?></strong> <?php echo esc_html( $session_id ?: '—' ); ?></p>
		<?php
	}

	// -------------------------------------------------------------------------
	// Save
	// -------------------------------------------------------------------------

	public static function save( int $post_id, WP_Post $post ): void {
		if ( ! isset( $_POST['wpnt_meta_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpnt_meta_nonce'] ) ), 'wpnt_save_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			// curriculum
			'wpnt_org'                  => '_wpnt_org',
			'wpnt_version'              => '_wpnt_version',
			// node
			'wpnt_curriculum_id'        => '_wpnt_curriculum_id',
			'wpnt_node_type'            => '_wpnt_node_type',
			'wpnt_order'                => '_wpnt_order',
			// skill
			'wpnt_node_id'              => '_wpnt_node_id',
			'wpnt_assessment_criteria'  => '_wpnt_assessment_criteria',
			'wpnt_prerequisite_skills'  => '_wpnt_prerequisite_skills',
			// drill / template
			'wpnt_setup'                => '_wpnt_setup',
			'wpnt_instructions'         => '_wpnt_instructions',
			'wpnt_variations'           => '_wpnt_variations',
			'wpnt_fallbacks'            => '_wpnt_fallbacks',
			'wpnt_equipment'            => '_wpnt_equipment',
			'wpnt_briefing_notes'       => '_wpnt_briefing_notes',
			'wpnt_safety_notes'         => '_wpnt_safety_notes',
			'wpnt_duration_mins'        => '_wpnt_duration_mins',
			// course
			'wpnt_start_date'           => '_wpnt_start_date',
			'wpnt_end_date'             => '_wpnt_end_date',
			'wpnt_default_day_time'     => '_wpnt_default_day_time',
			'wpnt_status'               => '_wpnt_status',
			'wpnt_bp_group_id'          => '_wpnt_bp_group_id',
			// session
			'wpnt_course_id'            => '_wpnt_course_id',
			'wpnt_session_number'       => '_wpnt_session_number',
			'wpnt_scheduled_start'      => '_wpnt_scheduled_start',
			'wpnt_scheduled_end'        => '_wpnt_scheduled_end',
			'wpnt_location'             => '_wpnt_location',
			'wpnt_template_id'          => '_wpnt_template_id',
			'wpnt_actual_notes'         => '_wpnt_actual_notes',
			// training plan
			'wpnt_plan_type'            => '_wpnt_plan_type',
			'wpnt_subject_type'         => '_wpnt_subject_type',
			'wpnt_subject_id'           => '_wpnt_subject_id',
			'wpnt_athlete_id'           => '_wpnt_athlete_id',
			'wpnt_origin'               => '_wpnt_origin',
			'wpnt_scope'                => '_wpnt_scope',
			'wpnt_goal'                 => '_wpnt_goal',
			'wpnt_planned_activities'   => '_wpnt_planned_activities',
			'wpnt_target_date'          => '_wpnt_target_date',
			'wpnt_assigned_coach'       => '_wpnt_assigned_coach',
			'wpnt_visibility'           => '_wpnt_visibility',
			// diary template
			'wpnt_diary_event_types'    => '_wpnt_event_types',
			// diary entry
			'wpnt_diary_status'         => '_wpnt_status',
			// observation
			'wpnt_obs_session_id'       => '_wpnt_session_id',
			'wpnt_obs_athlete_id'       => '_wpnt_athlete_id',
			'wpnt_obs_course_id'        => '_wpnt_course_id',
			'wpnt_obs_confidence_level' => '_wpnt_confidence_level',
			'wpnt_obs_evidence_type'    => '_wpnt_evidence_type',
		);

		foreach ( $fields as $field => $meta_key ) {
			if ( ! isset( $_POST[ $field ] ) ) {
				continue;
			}
			$value = sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) );
			if ( $value === '' ) {
				delete_post_meta( $post_id, $meta_key );
			} else {
				update_post_meta( $post_id, $meta_key, $value );
			}
		}
	}

	// -------------------------------------------------------------------------
	// Field rendering helpers
	// -------------------------------------------------------------------------

	private static function text_row( string $label, string $name, mixed $value, string $placeholder = '', string $type = 'text' ): void {
		printf(
			'<tr><th><label for="%1$s">%2$s</label></th><td><input type="%3$s" id="%1$s" name="%1$s" value="%4$s" placeholder="%5$s" class="regular-text"></td></tr>',
			esc_attr( $name ),
			esc_html( $label ),
			esc_attr( $type ),
			esc_attr( $value ),
			esc_attr( $placeholder )
		);
	}

	private static function textarea_row( string $label, string $name, mixed $value, string $placeholder = '' ): void {
		printf(
			'<tr><th><label for="%1$s">%2$s</label></th><td><textarea id="%1$s" name="%1$s" rows="4" class="large-text" placeholder="%3$s">%4$s</textarea></td></tr>',
			esc_attr( $name ),
			esc_html( $label ),
			esc_attr( $placeholder ),
			esc_textarea( $value )
		);
	}

	private static function select_row( string $label, string $name, mixed $current, array $options ): void {
		echo '<tr><th><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th><td>';
		echo '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '">';
		foreach ( $options as $val => $text ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $val ), selected( $current, $val, false ), esc_html( $text ) );
		}
		echo '</select></td></tr>';
	}

	private static function post_select_row( string $label, string $name, mixed $current, string $post_type ): void {
		$posts = get_posts( array( 'post_type' => $post_type, 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC', 'post_status' => array( 'publish', 'draft' ) ) );
		echo '<tr><th><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th><td>';
		echo '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '">';
		echo '<option value="">' . esc_html__( '— Select —', 'wpnt' ) . '</option>';
		foreach ( $posts as $p ) {
			printf( '<option value="%d"%s>%s</option>', $p->ID, selected( $current, $p->ID, false ), esc_html( $p->post_title ) );
		}
		echo '</select></td></tr>';
	}

	private static function user_select_row( string $label, string $name, mixed $current, string $role ): void {
		$users = get_users( array( 'role' => $role, 'orderby' => 'display_name' ) );
		echo '<tr><th><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th><td>';
		echo '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '">';
		echo '<option value="">' . esc_html__( '— Select —', 'wpnt' ) . '</option>';
		foreach ( $users as $u ) {
			printf( '<option value="%d"%s>%s</option>', $u->ID, selected( $current, $u->ID, false ), esc_html( $u->display_name ) );
		}
		echo '</select></td></tr>';
	}
}
