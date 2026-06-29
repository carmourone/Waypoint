<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();
	$plan_id         = get_the_ID();
	$athlete_id      = (int) get_post_meta( $plan_id, '_wpnt_athlete_id', true );
	$course_id       = (int) get_post_meta( $plan_id, '_wpnt_course_id', true );
	$origin          = get_post_meta( $plan_id, '_wpnt_origin', true );
	$scope           = get_post_meta( $plan_id, '_wpnt_scope', true );
	$goal            = get_post_meta( $plan_id, '_wpnt_goal', true );
	$activities      = get_post_meta( $plan_id, '_wpnt_planned_activities', true );
	$status          = get_post_meta( $plan_id, '_wpnt_status', true );
	$target          = get_post_meta( $plan_id, '_wpnt_target_date', true );
	$visibility      = get_post_meta( $plan_id, '_wpnt_visibility', true ) ?: 'shared';
	$coach_id        = (int) get_post_meta( $plan_id, '_wpnt_assigned_coach', true );
	$athlete         = $athlete_id ? get_user_by( 'id', $athlete_id ) : null;
	$coach           = $coach_id ? get_user_by( 'id', $coach_id ) : null;
	$participant_lbl = class_exists( 'WPNT_Pack' ) ? WPNT_Pack::get_active_label( 'participant_label', __( 'Athlete', 'waypoint' ) ) : __( 'Athlete', 'waypoint' );
	$can_edit        = current_user_can( 'edit_wpnt_sessions' ) || current_user_can( 'manage_options' );
	$linked_edges    = class_exists( 'WPNT_Training_Plan' ) ? WPNT_Training_Plan::get_linked_sessions( $plan_id ) : array();

	// Only the athlete, their parents, coaches, and admins can view.
	$user_id  = get_current_user_id();
	$can_view = current_user_can( 'read_private_wpnt_sessions' )
		|| $user_id === $athlete_id
		|| $user_id === $coach_id;

	if ( ! $can_view ) {
		wp_safe_redirect( home_url() );
		exit;
	}
?>

<main id="primary" class="site-main" role="main">

	<div class="page-hero">
		<div class="container">
			<h1><?php the_title(); ?></h1>
			<?php if ( $athlete ) : ?>
				<p><?php printf( esc_html__( '%s: %s', 'waypoint' ), esc_html( $participant_lbl ), esc_html( $athlete->display_name ) ); ?></p>
			<?php endif; ?>
			<span class="status-pill status-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span>
		</div>
	</div>

	<div class="container mt-4">
		<div class="wp-two-col">
			<div>

				<?php if ( $goal ) : ?>
					<div class="card mb-3">
						<h2 class="card-title"><?php esc_html_e( 'Goal', 'waypoint' ); ?></h2>
						<p><?php echo esc_html( $goal ); ?></p>
					</div>
				<?php endif; ?>

				<?php if ( $activities ) : ?>
					<div class="card mb-3">
						<h2 class="card-title"><?php esc_html_e( 'Planned Activities', 'waypoint' ); ?></h2>
						<?php echo wp_kses_post( wpautop( $activities ) ); ?>
					</div>
				<?php endif; ?>

				<?php if ( get_the_content() ) : ?>
					<div class="card mb-3">
						<h2 class="card-title"><?php esc_html_e( 'Coach Notes', 'waypoint' ); ?></h2>
						<div class="entry-content"><?php the_content(); ?></div>
					</div>
				<?php endif; ?>

				<!-- Linked sessions -->
				<?php if ( ! empty( $linked_edges ) ) : ?>
					<div class="card mb-3">
						<h2 class="card-title"><?php esc_html_e( 'Sessions', 'waypoint' ); ?></h2>
						<?php foreach ( $linked_edges as $edge ) :
							$s_id    = (int) $edge->target_id;
							$s_start = get_post_meta( $s_id, '_wpnt_scheduled_start', true );
							$s_loc   = get_post_meta( $s_id, '_wpnt_location', true );
							$s_stat  = get_post_meta( $s_id, '_wpnt_status', true );
							$s_role  = WPNT_Training_Plan::SESSION_ROLES[ $edge->role ] ?? ucfirst( $edge->role );
						?>
							<div class="tp-linked-item">
								<a href="<?php echo esc_url( get_permalink( $s_id ) ); ?>"><?php echo esc_html( get_the_title( $s_id ) ); ?></a>
								<span class="tp-role-chip"><?php echo esc_html( $s_role ); ?></span>
								<?php if ( $s_start ) : ?>
									<span class="tp-meta"><?php echo esc_html( date_i18n( 'D d M', strtotime( $s_start ) ) ); ?></span>
								<?php endif; ?>
								<?php if ( $s_loc ) : ?>
									<span class="tp-meta">&bull; <?php echo esc_html( $s_loc ); ?></span>
								<?php endif; ?>
								<span class="status-pill status-<?php echo esc_attr( $s_stat ); ?>"><?php echo esc_html( ucfirst( $s_stat ) ); ?></span>
							</div>
						<?php endforeach; ?>

						<?php if ( $can_edit ) :
							$new_session_page = (int) get_option( 'wpnt_new_session_page_id' );
							if ( $new_session_page ) :
						?>
							<div class="mt-2">
								<a href="<?php echo esc_url( add_query_arg( 'plan_id', $plan_id, get_permalink( $new_session_page ) ) ); ?>" class="btn btn-outline btn-sm"><?php esc_html_e( '+ New Session for this Plan', 'waypoint' ); ?></a>
							</div>
						<?php endif; endif; ?>
					</div>
				<?php elseif ( $can_edit ) :
					$new_session_page = (int) get_option( 'wpnt_new_session_page_id' );
					if ( $new_session_page ) :
				?>
					<div class="card mb-3">
						<a href="<?php echo esc_url( add_query_arg( 'plan_id', $plan_id, get_permalink( $new_session_page ) ) ); ?>" class="btn btn-outline btn-sm"><?php esc_html_e( '+ Add Session to this Plan', 'waypoint' ); ?></a>
					</div>
				<?php endif; endif; ?>

				<!-- Inline edit form — coaches only -->
				<?php if ( $can_edit ) : ?>
					<details class="card mb-3" id="wpnt-plan-edit-panel">
						<summary class="card-title" style="cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between">
							<?php esc_html_e( 'Edit Plan', 'waypoint' ); ?>
							<span class="toggle-indicator">&#9660;</span>
						</summary>
						<form class="wpnt-form mt-2"
							  data-endpoint="training-plans/<?php echo esc_attr( $plan_id ); ?>"
							  data-method="PUT"
							  data-redirect="none">

							<div class="form-row">
								<label for="wpnt-p-title"><?php esc_html_e( 'Title', 'waypoint' ); ?></label>
								<input id="wpnt-p-title" name="title" type="text" class="form-control" value="<?php echo esc_attr( get_the_title() ); ?>" required>
							</div>

							<div class="form-row">
								<label for="wpnt-p-goal"><?php esc_html_e( 'Goal', 'waypoint' ); ?></label>
								<textarea id="wpnt-p-goal" name="goal" class="form-control" rows="2"><?php echo esc_textarea( $goal ); ?></textarea>
							</div>

							<div class="form-row-2col">
								<div class="form-row">
									<label for="wpnt-p-scope"><?php esc_html_e( 'Scope', 'waypoint' ); ?></label>
									<select id="wpnt-p-scope" name="scope" class="form-control">
										<?php foreach ( array( '' => '—', 'term' => 'Term', 'season' => 'Season', 'event' => 'Event', 'long_term' => 'Long-term', 'development' => 'Development' ) as $val => $lbl ) : ?>
											<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $scope, $val ); ?>><?php echo esc_html( $lbl ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="form-row">
									<label for="wpnt-p-target"><?php esc_html_e( 'Target Date', 'waypoint' ); ?></label>
									<input id="wpnt-p-target" name="target_date" type="date" class="form-control" value="<?php echo esc_attr( $target ); ?>">
								</div>
							</div>

							<div class="form-row">
								<label for="wpnt-p-activities"><?php esc_html_e( 'Planned Activities', 'waypoint' ); ?></label>
								<textarea id="wpnt-p-activities" name="planned_activities" class="form-control" rows="4"><?php echo esc_textarea( $activities ); ?></textarea>
							</div>

							<div class="form-row">
								<label for="wpnt-p-content"><?php esc_html_e( 'Coach Notes', 'waypoint' ); ?></label>
								<textarea id="wpnt-p-content" name="content" class="form-control" rows="3"><?php echo esc_textarea( get_the_content() ); ?></textarea>
							</div>

							<div class="form-row-2col">
								<div class="form-row">
									<label for="wpnt-p-status"><?php esc_html_e( 'Status', 'waypoint' ); ?></label>
									<select id="wpnt-p-status" name="status" class="form-control">
										<?php foreach ( array( 'draft' => 'Draft', 'published' => 'Published', 'active' => 'Active', 'completed' => 'Completed' ) as $val => $lbl ) : ?>
											<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $status, $val ); ?>><?php echo esc_html( $lbl ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="form-row">
									<label for="wpnt-p-visibility"><?php esc_html_e( 'Visibility', 'waypoint' ); ?></label>
									<select id="wpnt-p-visibility" name="visibility" class="form-control">
										<option value="shared" <?php selected( $visibility, 'shared' ); ?>><?php esc_html_e( 'Shared with athlete', 'waypoint' ); ?></option>
										<option value="internal" <?php selected( $visibility, 'internal' ); ?>><?php esc_html_e( 'Internal only', 'waypoint' ); ?></option>
									</select>
								</div>
							</div>

							<div class="form-actions">
								<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Save Changes', 'waypoint' ); ?></button>
							</div>
						</form>
					</details>
				<?php endif; ?>

			</div>

			<aside>
				<div class="card">
					<h3 class="card-title"><?php esc_html_e( 'Details', 'waypoint' ); ?></h3>
					<dl>
						<?php if ( $origin ) : ?>
							<dt><?php esc_html_e( 'Origin', 'waypoint' ); ?></dt>
							<dd><?php echo esc_html( ucwords( str_replace( '_', ' ', $origin ) ) ); ?></dd>
						<?php endif; ?>
						<?php if ( $scope ) : ?>
							<dt><?php esc_html_e( 'Scope', 'waypoint' ); ?></dt>
							<dd><?php echo esc_html( ucwords( str_replace( '_', ' ', $scope ) ) ); ?></dd>
						<?php endif; ?>
						<?php if ( $target ) : ?>
							<dt><?php esc_html_e( 'Target Date', 'waypoint' ); ?></dt>
							<dd><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $target ) ) ); ?></dd>
						<?php endif; ?>
						<?php if ( $coach ) : ?>
							<dt><?php esc_html_e( 'Coach', 'waypoint' ); ?></dt>
							<dd><?php echo esc_html( $coach->display_name ); ?></dd>
						<?php endif; ?>
						<?php if ( $course_id ) : ?>
							<dt><?php esc_html_e( 'Course', 'waypoint' ); ?></dt>
							<dd><a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>"><?php echo esc_html( get_the_title( $course_id ) ); ?></a></dd>
						<?php endif; ?>
					</dl>
				</div>
			</aside>
		</div>
	</div>

</main>

<?php endwhile; ?>

<?php get_footer(); ?>
