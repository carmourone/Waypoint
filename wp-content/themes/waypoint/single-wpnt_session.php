<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();
	$session_id = get_the_ID();

	if ( ! waypoint_plugin_active() ) {
		get_footer();
		return;
	}

	$course_id   = (int) get_post_meta( $session_id, '_wpnt_course_id', true );
	$start       = get_post_meta( $session_id, '_wpnt_scheduled_start', true );
	$end         = get_post_meta( $session_id, '_wpnt_scheduled_end', true );
	$location    = get_post_meta( $session_id, '_wpnt_location', true );
	$status      = get_post_meta( $session_id, '_wpnt_status', true );
	$actual_notes= get_post_meta( $session_id, '_wpnt_actual_notes', true );
	$session_num = get_post_meta( $session_id, '_wpnt_session_number', true );

	$attendance  = WPNT_DB::get_session_attendance( $session_id );
	$observations= WPNT_DB::get_session_observations( $session_id );

	$att_counts  = array_count_values( array_column( (array) $attendance, 'status' ) );
	$total       = count( $attendance );

	$user_id     = get_current_user_id();
	$can_edit    = current_user_can( 'edit_wpnt_sessions' );
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
				<?php if ( $start && $end ) : ?>
					&mdash; <?php echo esc_html( date( 'H:i', strtotime( $start ) ) ); ?> &ndash; <?php echo esc_html( date( 'H:i', strtotime( $end ) ) ); ?>
				<?php endif; ?>
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

				<!-- Actual session notes (visible to coaches; public-safe if delivered) -->
				<?php if ( $actual_notes && $status === 'delivered' ) : ?>
					<div class="card mb-3">
						<h2 class="card-title"><?php esc_html_e( 'Session Notes', 'waypoint' ); ?></h2>
						<div class="session-notes"><?php echo wp_kses_post( wpautop( $actual_notes ) ); ?></div>
					</div>
				<?php endif; ?>

				<!-- Attendance summary -->
				<?php if ( ! empty( $attendance ) ) : ?>
					<div class="card mb-3">
						<h2 class="card-title"><?php esc_html_e( 'Attendance', 'waypoint' ); ?></h2>
						<div class="att-summary-bar">
							<?php foreach ( $att_counts as $s => $count ) : ?>
								<span class="att-chip att-<?php echo esc_attr( $s ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $s ) ) ); ?>: <?php echo esc_html( $count ); ?></span>
							<?php endforeach; ?>
							<span><?php printf( esc_html__( '%d recorded', 'waypoint' ), $total ); ?></span>
						</div>
					</div>
				<?php endif; ?>

				<!-- Observations — only for coaches -->
				<?php if ( $can_edit && ! empty( $observations ) ) : ?>
					<div class="card mb-3">
						<h2 class="card-title"><?php esc_html_e( 'Observations', 'waypoint' ); ?></h2>
						<ul class="observation-feed">
							<?php foreach ( $observations as $obs ) :
								$coach = get_user_by( 'id', $obs->coach_id );
							?>
								<li class="observation-item">
									<p><?php echo esc_html( $obs->note ); ?></p>
									<p class="obs-meta">
										<?php echo $coach ? esc_html( $coach->display_name ) : ''; ?>
										&bull; <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $obs->created_at ) ) ); ?>
									</p>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<!-- Attendance mark screen for coaches -->
				<?php if ( $can_edit && $status !== 'cancelled' ) : ?>
					<div class="card">
						<h2 class="card-title"><?php esc_html_e( 'Mark Attendance', 'waypoint' ); ?></h2>
						<div class="wpnt-att-widget" data-session-id="<?php echo esc_attr( $session_id ); ?>">
							<?php echo WPNT_Attendance::render_checklist( $session_id ); ?>
						</div>
					</div>
				<?php endif; ?>

			</div>

			<aside>
				<?php if ( $course_id ) : ?>
					<div class="card mb-3">
						<h3 class="card-title"><?php esc_html_e( 'Course', 'waypoint' ); ?></h3>
						<p><a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>"><?php echo esc_html( get_the_title( $course_id ) ); ?></a></p>
						<?php if ( $session_num ) : ?>
							<p><?php printf( esc_html__( 'Session %d', 'waypoint' ), $session_num ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $can_edit ) : ?>
					<div class="card">
						<h3 class="card-title"><?php esc_html_e( 'Actions', 'waypoint' ); ?></h3>
						<ul style="list-style:none;padding:0;margin:0">
							<li><a href="<?php echo esc_url( get_edit_post_link( $session_id ) ); ?>" class="btn btn-outline btn-sm"><?php esc_html_e( 'Edit Session', 'waypoint' ); ?></a></li>
							<li class="mt-1"><a href="<?php echo esc_url( admin_url( 'admin.php?page=wpnt-today&session_id=' . $session_id ) ); ?>" class="btn btn-primary btn-sm"><?php esc_html_e( 'Open in Today Screen', 'waypoint' ); ?></a></li>
						</ul>
					</div>
				<?php endif; ?>
			</aside>
		</div>
	</div>

</main>

<?php endwhile; ?>

<?php get_footer(); ?>
