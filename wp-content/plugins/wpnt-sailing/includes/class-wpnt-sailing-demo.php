<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installs realistic demo data for the sailing pack.
 *
 * Idempotent: controlled by the wpnt_sailing_demo_version option.
 * Run manually from the WP admin or via WP-CLI:
 *   do_action( 'wpnt_sailing_install_demo' )
 */
class WPNT_Sailing_Demo {

	private const DEMO_VERSION = '1';

	/** Trigger via admin-post.php action wpnt_sailing_install_demo */
	public static function handle_install(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wpnt-sailing' ) );
		}
		check_admin_referer( 'wpnt_sailing_install_demo' );
		self::install();
		wp_safe_redirect( add_query_arg(
			array( 'page' => 'wpnt-sailing-demo', 'installed' => '1' ),
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/** Main entry point — called from admin-post handler and on activation. */
	public static function install(): void {
		if ( get_option( 'wpnt_sailing_demo_version' ) === self::DEMO_VERSION ) {
			return;
		}

		// ── 1. Demo users ──────────────────────────────────────────────────────
		$coach_id  = self::ensure_user( array(
			'user_login'   => 'demo_sarah_mackay',
			'user_email'   => 'sarah.mackay@demo.waypoint.test',
			'display_name' => 'Sarah Mackay',
			'first_name'   => 'Sarah',
			'last_name'    => 'Mackay',
			'role'         => 'wpnt_coach',
		) );

		$alex_id  = self::ensure_user( array(
			'user_login'   => 'demo_alex_chen',
			'user_email'   => 'alex.chen@demo.waypoint.test',
			'display_name' => 'Alex Chen',
			'first_name'   => 'Alex',
			'last_name'    => 'Chen',
			'role'         => 'wpnt_athlete',
		) );

		$sam_id = self::ensure_user( array(
			'user_login'   => 'demo_sam_rivers',
			'user_email'   => 'sam.rivers@demo.waypoint.test',
			'display_name' => 'Sam Rivers',
			'first_name'   => 'Sam',
			'last_name'    => 'Rivers',
			'role'         => 'wpnt_athlete',
		) );

		$jordan_id = self::ensure_user( array(
			'user_login'   => 'demo_jordan_okafor',
			'user_email'   => 'jordan.okafor@demo.waypoint.test',
			'display_name' => 'Jordan Okafor',
			'first_name'   => 'Jordan',
			'last_name'    => 'Okafor',
			'role'         => 'wpnt_athlete',
		) );

		$lily_id = self::ensure_user( array(
			'user_login'   => 'demo_lily_pham',
			'user_email'   => 'lily.pham@demo.waypoint.test',
			'display_name' => 'Lily Pham',
			'first_name'   => 'Lily',
			'last_name'    => 'Pham',
			'role'         => 'wpnt_athlete',
		) );

		$parent_id = self::ensure_user( array(
			'user_login'   => 'demo_pat_chen',
			'user_email'   => 'pat.chen@demo.waypoint.test',
			'display_name' => 'Pat Chen',
			'first_name'   => 'Pat',
			'last_name'    => 'Chen',
			'role'         => 'wpnt_parent',
		) );

		// Parent linked to Alex via meta (BP friendship not available in basic setup).
		update_user_meta( $parent_id, 'wpnt_children', (string) $alex_id );

		// ── 2. Course ─────────────────────────────────────────────────────────
		$course_id = self::ensure_post( array(
			'post_type'   => 'wpnt_course',
			'post_title'  => 'Tackers — Term 1 2026',
			'post_name'   => 'tackers-term-1-2026',
			'post_status' => 'publish',
			'post_author' => $coach_id,
			'meta_input'  => array(
				'_wpnt_status'      => 'active',
				'_wpnt_coach_id'    => $coach_id,
				'_wpnt_description' => 'Introductory sailing for juniors aged 8–12. Covers rigging, basic boat handling, tacking and gybing.',
				'_wpnt_start_date'  => gmdate( 'Y-m-d', strtotime( '-8 weeks' ) ),
				'_wpnt_end_date'    => gmdate( 'Y-m-d', strtotime( '+6 weeks' ) ),
			),
		) );

		// Enrol athletes (via BP group meta if BP absent, else just meta).
		$athlete_ids = array( $alex_id, $sam_id, $jordan_id, $lily_id );
		foreach ( $athlete_ids as $aid ) {
			update_user_meta( $aid, '_wpnt_enrolled_course_' . $course_id, '1' );
		}
		// Store enrollment list on course for easy lookup by WPNT_Course.
		update_post_meta( $course_id, '_wpnt_enrolled_athletes', $athlete_ids );

		// ── 3. Sessions ───────────────────────────────────────────────────────
		$s1_start = gmdate( 'Y-m-d', strtotime( '-6 weeks' ) ) . 'T09:00';
		$s1_end   = gmdate( 'Y-m-d', strtotime( '-6 weeks' ) ) . 'T11:00';
		$s1_id    = self::ensure_post( array(
			'post_type'   => 'wpnt_session',
			'post_title'  => 'Session 1 — Rigging Basics',
			'post_name'   => 'tackers-t1-2026-s1',
			'post_status' => 'publish',
			'post_author' => $coach_id,
			'meta_input'  => array(
				'_wpnt_course_id'         => $course_id,
				'_wpnt_coach_id'          => $coach_id,
				'_wpnt_status'            => 'delivered',
				'_wpnt_scheduled_start'   => $s1_start,
				'_wpnt_scheduled_end'     => $s1_end,
				'_wpnt_location'          => 'Club Dock A',
				'_wpnts_wind_range'       => '5-10kn',
				'_wpnts_boat_class'       => 'optimist',
			),
		) );

		$s2_start = gmdate( 'Y-m-d', strtotime( '-3 weeks' ) ) . 'T09:00';
		$s2_end   = gmdate( 'Y-m-d', strtotime( '-3 weeks' ) ) . 'T11:00';
		$s2_id    = self::ensure_post( array(
			'post_type'   => 'wpnt_session',
			'post_title'  => 'Session 2 — Tacking & Gybing',
			'post_name'   => 'tackers-t1-2026-s2',
			'post_status' => 'publish',
			'post_author' => $coach_id,
			'meta_input'  => array(
				'_wpnt_course_id'         => $course_id,
				'_wpnt_coach_id'          => $coach_id,
				'_wpnt_status'            => 'delivered',
				'_wpnt_scheduled_start'   => $s2_start,
				'_wpnt_scheduled_end'     => $s2_end,
				'_wpnt_location'          => 'Club Dock A',
				'_wpnts_wind_range'       => '8-15kn',
				'_wpnts_boat_class'       => 'optimist',
			),
		) );

		$s3_start = gmdate( 'Y-m-d', strtotime( '+7 days' ) ) . 'T09:00';
		$s3_end   = gmdate( 'Y-m-d', strtotime( '+7 days' ) ) . 'T11:00';
		self::ensure_post( array(
			'post_type'   => 'wpnt_session',
			'post_title'  => 'Session 3 — Mark Rounding',
			'post_name'   => 'tackers-t1-2026-s3',
			'post_status' => 'publish',
			'post_author' => $coach_id,
			'meta_input'  => array(
				'_wpnt_course_id'         => $course_id,
				'_wpnt_coach_id'          => $coach_id,
				'_wpnt_status'            => 'scheduled',
				'_wpnt_scheduled_start'   => $s3_start,
				'_wpnt_scheduled_end'     => $s3_end,
				'_wpnt_location'          => 'Club Dock A',
				'_wpnts_boat_class'       => 'optimist',
			),
		) );

		// ── 4. Attendance ─────────────────────────────────────────────────────
		if ( class_exists( 'WPNT_Attendance' ) ) {
			$att_s1 = array(
				$alex_id   => 'attended',
				$sam_id    => 'attended',
				$jordan_id => 'attended',
				$lily_id   => 'absent',
			);
			foreach ( $att_s1 as $uid => $status ) {
				WPNT_Attendance::mark( $s1_id, $uid, $status, $coach_id );
			}

			$att_s2 = array(
				$alex_id   => 'attended',
				$sam_id    => 'late',
				$jordan_id => 'attended',
				$lily_id   => 'attended',
			);
			foreach ( $att_s2 as $uid => $status ) {
				WPNT_Attendance::mark( $s2_id, $uid, $status, $coach_id );
			}
		}

		// ── 5. Training plan ──────────────────────────────────────────────────
		$plan_id = self::ensure_post( array(
			'post_type'    => 'wpnt_training_plan',
			'post_title'   => 'Tacking Technique — Term 1 Focus',
			'post_name'    => 'alex-chen-tacking-t1-2026',
			'post_status'  => 'publish',
			'post_author'  => $coach_id,
			'post_content' => 'Alex is developing solid boat control and needs to focus on consistent tack execution before moving to mark rounding drills.',
			'meta_input'   => array(
				'_wpnt_status'           => 'published',
				'_wpnt_athlete_id'       => $alex_id,
				'_wpnt_subject_type'     => 'individual',
				'_wpnt_subject_id'       => $alex_id,
				'_wpnt_scope'            => 'term',
				'_wpnt_goal'             => 'Execute 5 clean tacks in a row without stalling in 10–15 knot conditions.',
				'_wpnt_target_date'      => gmdate( 'Y-m-d', strtotime( '+5 weeks' ) ),
				'_wpnt_assigned_coach'   => $coach_id,
				'_wpnt_planned_activities' => "Week 1–2: Slow tacking drills on flat water\nWeek 3–4: Tacking in chop, add timing cues\nWeek 5: Race simulation with tack counts",
				'_wpnt_visibility'       => 'shared',
				'_wpnt_origin'           => 'coach',
			),
		) );

		// Link plan to both delivered sessions.
		if ( class_exists( 'WPNT_Training_Plan' ) ) {
			WPNT_Training_Plan::link_session( $plan_id, $s1_id );
			WPNT_Training_Plan::link_session( $plan_id, $s2_id );
		}

		// ── 6. Diary template ─────────────────────────────────────────────────
		$tmpl_id = self::ensure_post( array(
			'post_type'   => 'wpnt_diary_template',
			'post_title'  => 'Post-Training Reflection',
			'post_name'   => 'post-training-reflection',
			'post_status' => 'publish',
			'post_author' => $coach_id,
			'meta_input'  => array(
				'_wpnt_event_types' => 'training,race,squad',
				'_wpnt_questions'   => wp_json_encode( array(
					array(
						'id'         => 'overall_quality',
						'label'      => 'How was the overall quality of your sailing today?',
						'type'       => 'scale',
						'scale_type' => 'quality_5',
						'required'   => true,
					),
					array(
						'id'       => 'best_moment',
						'label'    => 'What went well today?',
						'type'     => 'short_text',
						'required' => true,
					),
					array(
						'id'       => 'improvement',
						'label'    => 'What is one thing you want to improve next session?',
						'type'     => 'short_text',
						'required' => true,
					),
					array(
						'id'         => 'effort_level',
						'label'      => 'How hard did you work today?',
						'type'       => 'scale',
						'scale_type' => 'intensity_5',
						'required'   => false,
					),
					array(
						'id'         => 'enjoyed',
						'label'      => 'I enjoyed today\'s session.',
						'type'       => 'scale',
						'scale_type' => 'agreement_5',
						'required'   => false,
					),
				) ),
			),
		) );

		// ── 7. Diary entries ─────────────────────────────────────────────────
		// Entry 1 — reviewed, coach response published.
		$entry1_id = self::ensure_post( array(
			'post_type'    => 'wpnt_diary_entry',
			'post_title'   => 'Post-Training Reflection — Alex Chen — ' . gmdate( 'Y-m-d', strtotime( '-6 weeks' ) ),
			'post_name'    => 'diary-alex-chen-s1',
			'post_status'  => 'publish',
			'post_author'  => $alex_id,
			'post_date'    => gmdate( 'Y-m-d H:i:s', strtotime( '-6 weeks' ) ),
			'post_content' => '',
			'meta_input'   => array(
				'_wpnt_status'                => 'reviewed',
				'_wpnt_athlete_id'            => $alex_id,
				'_wpnt_template_id'           => $tmpl_id,
				'_wpnt_session_id'            => $s1_id,
				'_wpnt_event_type'            => 'training',
				'_wpnt_coach_id'              => $coach_id,
				'_wpnt_coach_response'        => "Great effort today Alex! Your tacking is really coming together — I noticed much better timing when you duck the boom compared to last term.\n\nFocus for next session: try to keep your weight central as you tack rather than leaning back. This will stop the nose from dipping.",
				'_wpnt_coach_response_status' => 'published',
			),
		) );

		if ( class_exists( 'WPNT_Diary' ) ) {
			WPNT_Diary::save_responses( $entry1_id, array(
				array( 'question_id' => 'overall_quality', 'question_type' => 'scale', 'scale_type' => 'quality_5',   'response_value' => '4' ),
				array( 'question_id' => 'best_moment',     'question_type' => 'short_text',                           'response_text'  => 'My tack at the windward mark was really smooth!' ),
				array( 'question_id' => 'improvement',     'question_type' => 'short_text',                           'response_text'  => 'I want to get faster at gybing without losing speed.' ),
				array( 'question_id' => 'effort_level',    'question_type' => 'scale', 'scale_type' => 'intensity_5', 'response_value' => '4' ),
				array( 'question_id' => 'enjoyed',         'question_type' => 'scale', 'scale_type' => 'agreement_5', 'response_value' => '5' ),
			) );
		}

		// Entry 2 — submitted, awaiting coach review.
		$entry2_id = self::ensure_post( array(
			'post_type'    => 'wpnt_diary_entry',
			'post_title'   => 'Post-Training Reflection — Alex Chen — ' . gmdate( 'Y-m-d', strtotime( '-3 weeks' ) ),
			'post_name'    => 'diary-alex-chen-s2',
			'post_status'  => 'publish',
			'post_author'  => $alex_id,
			'post_date'    => gmdate( 'Y-m-d H:i:s', strtotime( '-3 weeks' ) ),
			'post_content' => '',
			'meta_input'   => array(
				'_wpnt_status'     => 'submitted',
				'_wpnt_athlete_id' => $alex_id,
				'_wpnt_template_id'=> $tmpl_id,
				'_wpnt_session_id' => $s2_id,
				'_wpnt_event_type' => 'training',
				'_wpnt_coach_id'   => $coach_id,
			),
		) );

		if ( class_exists( 'WPNT_Diary' ) ) {
			WPNT_Diary::save_responses( $entry2_id, array(
				array( 'question_id' => 'overall_quality', 'question_type' => 'scale', 'scale_type' => 'quality_5',   'response_value' => '3' ),
				array( 'question_id' => 'best_moment',     'question_type' => 'short_text',                           'response_text'  => 'Managed to gybe without capsizing even in the gusts.' ),
				array( 'question_id' => 'improvement',     'question_type' => 'short_text',                           'response_text'  => 'Need to look further ahead when tacking at the mark — I kept watching the boom.' ),
				array( 'question_id' => 'effort_level',    'question_type' => 'scale', 'scale_type' => 'intensity_5', 'response_value' => '5' ),
				array( 'question_id' => 'enjoyed',         'question_type' => 'scale', 'scale_type' => 'agreement_5', 'response_value' => '4' ),
			) );
		}

		// ── 8. Dashboard page option seeds ────────────────────────────────────
		// Only set if not already configured, so we don't clobber real pages.
		self::maybe_set_dashboard_pages( $coach_id );

		update_option( 'wpnt_sailing_demo_version', self::DEMO_VERSION );
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	/**
	 * Create or return an existing user by login.
	 */
	private static function ensure_user( array $args ): int {
		$existing = get_user_by( 'login', $args['user_login'] );
		if ( $existing ) {
			return $existing->ID;
		}

		$role = $args['role'] ?? 'subscriber';
		unset( $args['role'] );

		$args['user_pass'] = wp_generate_password( 16, false );
		$user_id           = wp_insert_user( $args );

		if ( is_wp_error( $user_id ) ) {
			return 0;
		}

		$user = new WP_User( $user_id );
		$user->set_role( $role );

		return $user_id;
	}

	/**
	 * Create or return an existing post by slug.
	 */
	private static function ensure_post( array $args ): int {
		$slug = $args['post_name'] ?? sanitize_title( $args['post_title'] ?? '' );

		$existing = get_page_by_path( $slug, OBJECT, $args['post_type'] );
		if ( $existing ) {
			return $existing->ID;
		}

		$id = wp_insert_post( $args, true );
		return is_wp_error( $id ) ? 0 : $id;
	}

	/**
	 * Create stub dashboard pages and set the option pointers if they don't exist.
	 */
	private static function maybe_set_dashboard_pages( int $coach_id ): void {
		$definitions = array(
			array(
				'option'   => 'wpnt_coach_dashboard_page_id',
				'title'    => 'Coach Dashboard',
				'template' => 'page-templates/template-coach-dashboard.php',
			),
			array(
				'option'   => 'wpnt_athlete_dashboard_page_id',
				'title'    => 'Sailor Dashboard',
				'template' => 'page-templates/template-athlete-dashboard.php',
			),
			array(
				'option'   => 'wpnt_parent_dashboard_page_id',
				'title'    => 'Parent Dashboard',
				'template' => 'page-templates/template-parent-dashboard.php',
			),
		);

		foreach ( $definitions as $def ) {
			if ( get_option( $def['option'] ) ) {
				continue; // Already configured.
			}

			$slug     = sanitize_title( $def['title'] );
			$existing = get_page_by_path( $slug, OBJECT, 'page' );

			if ( $existing ) {
				update_option( $def['option'], $existing->ID );
				continue;
			}

			$page_id = wp_insert_post( array(
				'post_type'      => 'page',
				'post_title'     => $def['title'],
				'post_name'      => $slug,
				'post_status'    => 'publish',
				'post_author'    => $coach_id,
				'page_template'  => $def['template'],
			) );

			if ( ! is_wp_error( $page_id ) && $page_id ) {
				update_option( $def['option'], $page_id );
				update_post_meta( $page_id, '_wp_page_template', $def['template'] );
			}
		}
	}

	/** Render the admin page. */
	public static function render_admin_page(): void {
		$installed = get_option( 'wpnt_sailing_demo_version' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Sailing Pack — Demo Data', 'wpnt-sailing' ); ?></h1>

			<?php if ( isset( $_GET['installed'] ) ) : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Demo data installed successfully.', 'wpnt-sailing' ); ?></p></div>
			<?php endif; ?>

			<?php if ( $installed ) : ?>
				<div class="notice notice-info"><p>
					<?php printf(
						esc_html__( 'Demo data version %s is already installed.', 'wpnt-sailing' ),
						esc_html( $installed )
					); ?>
				</p></div>
			<?php else : ?>
				<p><?php esc_html_e( 'Install realistic demo data to explore all Waypoint features. This creates demo users, a course, sessions, attendance records, a training plan, a diary template, and diary entries.', 'wpnt-sailing' ); ?></p>
				<p><strong><?php esc_html_e( 'Demo user emails use the @demo.waypoint.test domain and will not receive real email.', 'wpnt-sailing' ); ?></strong></p>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'wpnt_sailing_install_demo' ); ?>
				<input type="hidden" name="action" value="wpnt_sailing_install_demo">
				<p>
					<button type="submit" class="button button-primary">
						<?php echo $installed
							? esc_html__( 'Re-install Demo Data', 'wpnt-sailing' )
							: esc_html__( 'Install Demo Data', 'wpnt-sailing' ); ?>
					</button>
				</p>
			</form>

			<?php if ( $installed ) : ?>
				<h2><?php esc_html_e( 'Demo Accounts', 'wpnt-sailing' ); ?></h2>
				<table class="widefat" style="max-width:500px">
					<thead><tr><th><?php esc_html_e( 'Name', 'wpnt-sailing' ); ?></th><th><?php esc_html_e( 'Login', 'wpnt-sailing' ); ?></th><th><?php esc_html_e( 'Role', 'wpnt-sailing' ); ?></th></tr></thead>
					<tbody>
						<?php
						$demo_users = array(
							array( 'Sarah Mackay',   'demo_sarah_mackay',   'Coach' ),
							array( 'Alex Chen',      'demo_alex_chen',      'Sailor' ),
							array( 'Sam Rivers',     'demo_sam_rivers',     'Sailor' ),
							array( 'Jordan Okafor',  'demo_jordan_okafor',  'Sailor' ),
							array( 'Lily Pham',      'demo_lily_pham',      'Sailor' ),
							array( 'Pat Chen',       'demo_pat_chen',       'Parent' ),
						);
						foreach ( $demo_users as list( $name, $login, $role ) ) :
							$u = get_user_by( 'login', $login );
						?>
						<tr>
							<td><?php echo esc_html( $name ); ?></td>
							<td><code><?php echo esc_html( $login ); ?></code></td>
							<td><?php echo esc_html( $role ); ?><?php echo $u ? '' : ' <em>(not found)</em>'; ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
