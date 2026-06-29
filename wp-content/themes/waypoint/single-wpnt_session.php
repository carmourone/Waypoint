<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();
	$session_id  = get_the_ID();
	$viewer_id   = get_current_user_id();

	if ( ! waypoint_plugin_active() ) {
		get_footer();
		return;
	}

	$coach_id    = (int) get_post_meta( $session_id, '_wpnt_coach_id', true );
	$course_id   = (int) get_post_meta( $session_id, '_wpnt_course_id', true );
	$start       = get_post_meta( $session_id, '_wpnt_scheduled_start', true );
	$end         = get_post_meta( $session_id, '_wpnt_scheduled_end', true );
	$location    = get_post_meta( $session_id, '_wpnt_location', true );
	$status      = get_post_meta( $session_id, '_wpnt_status', true );
	$session_num = get_post_meta( $session_id, '_wpnt_session_number', true );
	$wind_range  = get_post_meta( $session_id, '_wpnts_wind_range', true );
	$boat_class  = get_post_meta( $session_id, '_wpnts_boat_class', true );

	$can_edit    = current_user_can( 'edit_wpnt_sessions' ) || current_user_can( 'manage_options' );

	$session_groups = class_exists( 'WPNT_Session_Group' ) ? WPNT_Session_Group::get_for_session( $session_id ) : array();
	$attendance     = class_exists( 'WPNT_Attendance' ) ? WPNT_Attendance::get_session_attendance( $session_id ) : array();
	$att_counts     = array_count_values( array_column( (array) $attendance, 'status' ) );

	// Training plans linked to this session (reverse p2p lookup).
	$linked_plan_edges = class_exists( 'WPNT_Training_Plan' ) ? WPNT_Training_Plan::get_session_plans( $session_id ) : array();

	// Observations from wpnt_observation CPT.
	$observations = $can_edit ? get_posts( array(
		'post_type'      => 'wpnt_observation',
		'post_status'    => array( 'publish', 'draft' ),
		'posts_per_page' => -1,
		'meta_key'       => '_wpnt_session_id',
		'meta_value'     => $session_id,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) ) : array();
?>

<main id="primary" class="site-main" role="main">

	<div class="page-hero">
		<div class="container">
			<?php if ( $course_id ) : ?>
				<p><a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>" style="color:rgba(255,255,255,.7)">&larr; <?php echo esc_html( get_the_title( $course_id ) ); ?></a></p>
			<?php endif; ?>
			<h1><?php the_title(); ?></h1>
			<p>
				<?php if ( $start ) : ?><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $start ) ) ); ?><?php endif; ?>
				<?php if ( $start ) : ?>&nbsp;<?php echo esc_html( date( 'H:i', strtotime( $start ) ) ); ?><?php endif; ?>
				<?php if ( $end ) : ?>&ndash;<?php echo esc_html( date( 'H:i', strtotime( $end ) ) ); ?><?php endif; ?>
				<?php if ( $location ) : ?> &bull; <?php echo esc_html( $location ); ?><?php endif; ?>
			</p>
			<span class="status-pill status-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span>
		</div>
	</div>

	<div class="container mt-4">
		<div class="wp-two-col">
			<div>

				<!-- Session plan / content -->
				<?php if ( get_the_content() ) : ?>
					<div class="card mb-3">
						<h2 class="card-title"><?php esc_html_e( 'Session Plan', 'waypoint' ); ?></h2>
						<div class="entry-content"><?php the_content(); ?></div>
					</div>
				<?php endif; ?>

				<!-- Attendance / Groups -->
				<?php if ( ! empty( $session_groups ) ) : ?>
					<?php foreach ( $session_groups as $sg ) : ?>
						<div class="card mb-3">
							<?php echo WPNT_Session_Group::render_group_block( $sg, $can_edit ); ?>
						</div>
					<?php endforeach; ?>
					<?php if ( $can_edit ) : ?>
						<div class="mb-3">
							<button class="btn btn-outline btn-sm wpnt-add-group" data-session-id="<?php echo esc_attr( $session_id ); ?>">
								<?php esc_html_e( '+ Add Cohort / Level Group', 'waypoint' ); ?>
							</button>
						</div>
					<?php endif; ?>
				<?php elseif ( ! empty( $attendance ) ) : ?>
					<div class="card mb-3">
						<h2 class="card-title"><?php esc_html_e( 'Attendance', 'waypoint' ); ?></h2>
						<div class="att-summary-bar">
							<?php foreach ( $att_counts as $s => $count ) : ?>
								<span class="att-chip att-<?php echo esc_attr( $s ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $s ) ) ); ?>: <?php echo esc_html( $count ); ?></span>
							<?php endforeach; ?>
							<span><?php printf( esc_html__( '%d recorded', 'waypoint' ), count( $attendance ) ); ?></span>
						</div>
					</div>
				<?php elseif ( $can_edit && $status !== 'cancelled' ) : ?>
					<div class="card mb-3">
						<h2 class="card-title"><?php esc_html_e( 'Mark Attendance', 'waypoint' ); ?></h2>
						<div class="wpnt-att-widget" data-session-id="<?php echo esc_attr( $session_id ); ?>">
							<?php echo WPNT_Attendance::render_checklist( $session_id ); ?>
						</div>
					</div>
				<?php endif; ?>

				<!-- Linked training plans -->
				<?php if ( ! empty( $linked_plan_edges ) ) : ?>
					<div class="card mb-3">
						<h2 class="card-title"><?php esc_html_e( 'Training Plans', 'waypoint' ); ?></h2>
						<?php foreach ( $linked_plan_edges as $edge ) :
							$plan_title = get_the_title( $edge->source_id );
							$plan_role  = WPNT_Training_Plan::SESSION_ROLES[ $edge->role ] ?? ucfirst( $edge->role );
						?>
							<div class="tp-linked-item">
								<a href="<?php echo esc_url( get_permalink( $edge->source_id ) ); ?>"><?php echo esc_html( $plan_title ); ?></a>
								<span class="tp-role-chip"><?php echo esc_html( $plan_role ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<!-- Observations — coaches only -->
				<?php if ( $can_edit && ! empty( $observations ) ) : ?>
					<div class="card mb-3">
						<h2 class="card-title"><?php esc_html_e( 'Observations', 'waypoint' ); ?></h2>
						<ul class="observation-feed">
							<?php foreach ( $observations as $obs ) :
								$obs_author = get_user_by( 'id', $obs->post_author );
							?>
								<li class="observation-item">
									<p><?php echo wp_kses_post( wpautop( $obs->post_content ) ); ?></p>
									<p class="obs-meta">
										<?php echo $obs_author ? esc_html( $obs_author->display_name ) : ''; ?>
										&bull; <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $obs->post_date ) ) ); ?>
									</p>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<!-- Inline edit form — coaches only -->
				<?php if ( $can_edit ) : ?>
					<details class="card mb-3" id="wpnt-session-edit-panel">
						<summary class="card-title" style="cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between">
							<?php esc_html_e( 'Edit Session', 'waypoint' ); ?>
							<span class="toggle-indicator">&#9660;</span>
						</summary>
						<form class="wpnt-form mt-2"
							  data-endpoint="sessions/<?php echo esc_attr( $session_id ); ?>"
							  data-method="PUT"
							  data-redirect="none">

							<div class="form-row">
								<label for="wpnt-s-title"><?php esc_html_e( 'Title', 'waypoint' ); ?></label>
								<input id="wpnt-s-title" name="title" type="text" class="form-control" value="<?php echo esc_attr( get_the_title() ); ?>" required>
							</div>

							<div class="form-row-2col">
								<div class="form-row">
									<label for="wpnt-s-date"><?php esc_html_e( 'Date', 'waypoint' ); ?></label>
									<input id="wpnt-s-date" name="_date" type="date" class="form-control" value="<?php echo $start ? esc_attr( date( 'Y-m-d', strtotime( $start ) ) ) : ''; ?>">
								</div>
								<div class="form-row">
									<label for="wpnt-s-start"><?php esc_html_e( 'Start', 'waypoint' ); ?></label>
									<input id="wpnt-s-start" name="_start_time" type="time" class="form-control" value="<?php echo $start ? esc_attr( date( 'H:i', strtotime( $start ) ) ) : ''; ?>">
								</div>
								<div class="form-row">
									<label for="wpnt-s-end"><?php esc_html_e( 'End', 'waypoint' ); ?></label>
									<input id="wpnt-s-end" name="_end_time" type="time" class="form-control" value="<?php echo $end ? esc_attr( date( 'H:i', strtotime( $end ) ) ) : ''; ?>">
								</div>
							</div>

							<div class="form-row">
								<label for="wpnt-s-location"><?php esc_html_e( 'Location', 'waypoint' ); ?></label>
								<input id="wpnt-s-location" name="location" type="text" class="form-control" value="<?php echo esc_attr( $location ); ?>">
							</div>

							<div class="form-row">
								<label for="wpnt-s-status"><?php esc_html_e( 'Status', 'waypoint' ); ?></label>
								<select id="wpnt-s-status" name="status" class="form-control">
									<?php foreach ( array( 'scheduled', 'delivered', 'cancelled' ) as $s_opt ) : ?>
										<option value="<?php echo esc_attr( $s_opt ); ?>" <?php selected( $status, $s_opt ); ?>><?php echo esc_html( ucfirst( $s_opt ) ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="form-row">
								<label for="wpnt-s-content"><?php esc_html_e( 'Session Plan / Notes', 'waypoint' ); ?></label>
								<textarea id="wpnt-s-content" name="content" class="form-control" rows="4"><?php echo esc_textarea( get_the_content() ); ?></textarea>
							</div>

							<?php if ( $wind_range !== false ) : ?>
								<div class="form-row-2col">
									<div class="form-row">
										<label for="wpnt-s-wind"><?php esc_html_e( 'Wind Range', 'waypoint' ); ?></label>
										<input id="wpnt-s-wind" name="wind_range" type="text" class="form-control" placeholder="e.g. 8–15kn" value="<?php echo esc_attr( $wind_range ); ?>">
									</div>
									<div class="form-row">
										<label for="wpnt-s-boat"><?php esc_html_e( 'Boat Class', 'waypoint' ); ?></label>
										<input id="wpnt-s-boat" name="boat_class" type="text" class="form-control" value="<?php echo esc_attr( $boat_class ); ?>">
									</div>
								</div>
							<?php endif; ?>

							<div class="form-actions">
								<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Save Changes', 'waypoint' ); ?></button>
							</div>
						</form>
					</details>
				<?php endif; ?>

			</div>

			<aside>
				<div class="card mb-3">
					<h3 class="card-title"><?php esc_html_e( 'Details', 'waypoint' ); ?></h3>
					<dl>
						<?php if ( $start ) : ?>
							<dt><?php esc_html_e( 'Date', 'waypoint' ); ?></dt>
							<dd><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $start ) ) ); ?></dd>
						<?php endif; ?>
						<?php if ( $start || $end ) : ?>
							<dt><?php esc_html_e( 'Time', 'waypoint' ); ?></dt>
							<dd>
								<?php if ( $start ) : ?><?php echo esc_html( date( 'H:i', strtotime( $start ) ) ); ?><?php endif; ?>
								<?php if ( $end ) : ?> &ndash; <?php echo esc_html( date( 'H:i', strtotime( $end ) ) ); ?><?php endif; ?>
							</dd>
						<?php endif; ?>
						<?php if ( $location ) : ?>
							<dt><?php esc_html_e( 'Location', 'waypoint' ); ?></dt>
							<dd><?php echo esc_html( $location ); ?></dd>
						<?php endif; ?>
						<?php if ( $course_id ) : ?>
							<dt><?php esc_html_e( 'Course', 'waypoint' ); ?></dt>
							<dd><a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>"><?php echo esc_html( get_the_title( $course_id ) ); ?></a>
								<?php if ( $session_num ) : ?>(<?php printf( esc_html__( 'Session %d', 'waypoint' ), $session_num ); ?>)<?php endif; ?>
							</dd>
						<?php endif; ?>
						<?php if ( $wind_range ) : ?>
							<dt><?php esc_html_e( 'Wind Range', 'waypoint' ); ?></dt>
							<dd><?php echo esc_html( $wind_range ); ?></dd>
						<?php endif; ?>
						<?php if ( $boat_class ) : ?>
							<dt><?php esc_html_e( 'Boat Class', 'waypoint' ); ?></dt>
							<dd><?php echo esc_html( ucfirst( $boat_class ) ); ?></dd>
						<?php endif; ?>
					</dl>
				</div>

				<?php if ( $can_edit ) : ?>
					<div class="card">
						<h3 class="card-title"><?php esc_html_e( 'Actions', 'waypoint' ); ?></h3>
						<ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.5rem">
							<li>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpnt-today&session_id=' . $session_id ) ); ?>" class="btn btn-primary btn-sm"><?php esc_html_e( 'Mark Attendance', 'waypoint' ); ?></a>
							</li>
							<?php $new_session_page = (int) get_option( 'wpnt_new_session_page_id' );
							if ( $new_session_page ) : ?>
								<li>
									<a href="<?php echo esc_url( add_query_arg( 'plan_id', '', get_permalink( $new_session_page ) ) ); ?>" class="btn btn-outline btn-sm"><?php esc_html_e( 'New Session', 'waypoint' ); ?></a>
								</li>
							<?php endif; ?>
						</ul>
					</div>
				<?php endif; ?>
			</aside>
		</div>
	</div>

</main>

<?php endwhile; ?>

<?php get_footer(); ?>
