<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();
	$course_id = get_the_ID();

	if ( ! waypoint_plugin_active() ) {
		get_footer();
		return;
	}

	$node_id    = (int) get_post_meta( $course_id, '_wpnt_node_id', true );
	$start      = get_post_meta( $course_id, '_wpnt_start_date', true );
	$end        = get_post_meta( $course_id, '_wpnt_end_date', true );
	$day_time   = get_post_meta( $course_id, '_wpnt_default_day_time', true );
	$status     = get_post_meta( $course_id, '_wpnt_status', true );
	$sessions   = WPNT_Course::get_course_sessions( $course_id );
	$athletes        = WPNT_Course::get_enrolled_athletes( $course_id );
	$participants_lbl = class_exists( 'WPNT_Pack' ) ? WPNT_Pack::get_active_label( 'participant_label_plural', __( 'Athletes', 'waypoint' ) ) : __( 'Athletes', 'waypoint' );
	$node       = $node_id ? get_post( $node_id ) : null;
?>

<main id="primary" class="site-main" role="main">

	<div class="page-hero">
		<div class="container">
			<h1><?php the_title(); ?></h1>
			<p>
				<?php if ( $node ) : ?><span><?php echo esc_html( $node->post_title ); ?> &mdash; </span><?php endif; ?>
				<?php if ( $start ) : ?><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $start ) ) ); ?><?php endif; ?>
				<?php if ( $end ) : ?> &ndash; <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $end ) ) ); ?><?php endif; ?>
			</p>
			<span class="status-pill status-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span>
		</div>
	</div>

	<div class="container mt-4">
		<div class="wp-two-col">
			<div>
				<!-- Course description -->
				<?php if ( get_the_content() ) : ?>
					<div class="card mb-3">
						<div class="entry-content"><?php the_content(); ?></div>
					</div>
				<?php endif; ?>

				<!-- Sessions list -->
				<div class="dashboard-section">
					<h2 class="dashboard-section-title"><?php esc_html_e( 'Sessions', 'waypoint' ); ?> <span class="status-pill status-scheduled"><?php echo count( $sessions ); ?></span></h2>
					<?php if ( empty( $sessions ) ) : ?>
						<p class="notice-wp info"><?php esc_html_e( 'No sessions scheduled yet.', 'waypoint' ); ?></p>
					<?php else : ?>
						<div class="session-list-grid">
							<?php foreach ( $sessions as $s ) :
								$s_status = get_post_meta( $s->ID, '_wpnt_status', true );
								$s_start  = get_post_meta( $s->ID, '_wpnt_scheduled_start', true );
								$s_num    = get_post_meta( $s->ID, '_wpnt_session_number', true );
								$s_loc    = get_post_meta( $s->ID, '_wpnt_location', true );
							?>
								<div class="session-card">
									<h3><a href="<?php echo esc_url( get_permalink( $s->ID ) ); ?>"><?php echo esc_html( $s->post_title ); ?></a></h3>
									<div class="session-meta">
										<?php if ( $s_start ) : ?>
											<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $s_start ) ) ); ?>
											&mdash; <?php echo esc_html( date( 'H:i', strtotime( $s_start ) ) ); ?>
										<?php endif; ?>
										<?php if ( $s_loc ) : ?><br><?php echo esc_html( $s_loc ); ?><?php endif; ?>
									</div>
									<span class="status-pill status-<?php echo esc_attr( $s_status ); ?>"><?php echo esc_html( ucfirst( $s_status ) ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<aside>
				<!-- Enrolled athletes -->
				<div class="card mb-3">
					<h3 class="card-title"><?php printf( esc_html__( '%s (%d)', 'waypoint' ), esc_html( $participants_lbl ), count( $athletes ) ); ?></h3>
					<?php if ( empty( $athletes ) ) : ?>
						<p><?php echo esc_html( sprintf( __( 'No %s enrolled yet.', 'waypoint' ), strtolower( $participants_lbl ) ) ); ?></p>
					<?php else : ?>
						<ul class="att-summary-bar" style="flex-direction:column;gap:.25rem">
							<?php foreach ( $athletes as $athlete ) : ?>
								<li><?php echo esc_html( $athlete->display_name ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>

				<!-- Course meta -->
				<div class="card">
					<h3 class="card-title"><?php esc_html_e( 'Details', 'waypoint' ); ?></h3>
					<dl>
						<?php if ( $day_time ) : ?>
							<dt><?php esc_html_e( 'Schedule', 'waypoint' ); ?></dt>
							<dd><?php echo esc_html( $day_time ); ?></dd>
						<?php endif; ?>
						<?php if ( $node ) : ?>
							<dt><?php esc_html_e( 'Curriculum', 'waypoint' ); ?></dt>
							<dd><?php echo esc_html( $node->post_title ); ?></dd>
						<?php endif; ?>
						<dt><?php esc_html_e( 'Status', 'waypoint' ); ?></dt>
						<dd><span class="status-pill status-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span></dd>
					</dl>
				</div>
			</aside>
		</div>
	</div>

</main>

<?php endwhile; ?>

<?php get_footer(); ?>
