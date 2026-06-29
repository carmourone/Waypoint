<?php
/**
 * Template Name: Athlete Dashboard
 * Template Post Type: page
 */

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( wp_login_url( get_permalink() ) );
	exit;
}

get_header();

$athlete_id = get_current_user_id();
$user       = wp_get_current_user();

$enrolled_courses  = waypoint_plugin_active() ? WPNT_Course::get_courses_for_athlete( $athlete_id ) : array();
$enrolled_course_ids = array_map( fn( $c ) => $c->ID, $enrolled_courses );

// Upcoming sessions across enrolled courses.
$upcoming_sessions = array();
if ( waypoint_plugin_active() && ! empty( $enrolled_course_ids ) ) {
	$today             = current_time( 'Y-m-d' );
	$upcoming_sessions = get_posts( array(
		'post_type'      => 'wpnt_session',
		'posts_per_page' => 8,
		'meta_query'     => array(
			array( 'key' => '_wpnt_course_id', 'value' => $enrolled_course_ids, 'compare' => 'IN' ),
			array( 'key' => '_wpnt_scheduled_start', 'value' => $today . 'T00:00', 'compare' => '>=', 'type' => 'CHAR' ),
		),
		'orderby'  => 'meta_value',
		'meta_key' => '_wpnt_scheduled_start',
		'order'    => 'ASC',
	) );
}

// Attendance history with decoded status.
$attendance_history = waypoint_plugin_active() ? WPNT_Attendance::get_athlete_attendance( $athlete_id ) : array();

// Training plans for this athlete.
$training_plans = waypoint_plugin_active() ? WPNT_Training_Plan::get_for_athlete( $athlete_id ) : array();

// Recent diary entries.
$diary_entries = array();
if ( waypoint_plugin_active() ) {
	$diary_entries = WPNT_Diary::get_for_athlete( $athlete_id, array( 'per_page' => 5 ) );
}
?>

<main id="primary" class="site-main" role="main">

	<div class="page-hero">
		<div class="container">
			<h1><?php printf( esc_html__( 'Hello, %s', 'waypoint' ), esc_html( $user->display_name ) ); ?></h1>
			<p><?php echo esc_html( date_i18n( get_option( 'date_format' ) ) ); ?></p>
		</div>
	</div>

	<div class="container mt-4 wpnt-dashboard">

		<?php get_template_part( 'template-parts/dashboard-nav', '', array( 'current' => 'athlete' ) ); ?>

		<!-- Current courses -->
		<div class="dashboard-section">
			<h2 class="dashboard-section-title"><?php esc_html_e( 'My Courses', 'waypoint' ); ?></h2>
			<?php if ( empty( $enrolled_courses ) ) : ?>
				<p class="notice-wp info"><?php esc_html_e( 'You are not enrolled in any courses yet.', 'waypoint' ); ?></p>
			<?php else : ?>
				<div class="dashboard-grid">
					<?php foreach ( $enrolled_courses as $course ) :
						$c_status = get_post_meta( $course->ID, '_wpnt_status', true );
						$sessions = WPNT_Course::get_course_sessions( $course->ID );
					?>
						<div class="card">
							<h3 class="card-title"><a href="<?php echo esc_url( get_permalink( $course->ID ) ); ?>"><?php echo esc_html( $course->post_title ); ?></a></h3>
							<p><?php printf( esc_html__( '%d sessions', 'waypoint' ), count( $sessions ) ); ?></p>
							<span class="status-pill status-<?php echo esc_attr( $c_status ); ?>"><?php echo esc_html( ucfirst( $c_status ) ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Upcoming sessions -->
		<?php if ( ! empty( $upcoming_sessions ) ) : ?>
			<div class="dashboard-section">
				<h2 class="dashboard-section-title"><?php esc_html_e( 'Upcoming Sessions', 'waypoint' ); ?></h2>
				<div class="session-list-grid">
					<?php foreach ( $upcoming_sessions as $s ) :
						$s_start = get_post_meta( $s->ID, '_wpnt_scheduled_start', true );
						$s_loc   = get_post_meta( $s->ID, '_wpnt_location', true );
					?>
						<div class="session-card">
							<h3><a href="<?php echo esc_url( get_permalink( $s->ID ) ); ?>"><?php echo esc_html( $s->post_title ); ?></a></h3>
							<div class="session-meta">
								<?php if ( $s_start ) : ?><?php echo esc_html( date_i18n( 'D d M', strtotime( $s_start ) ) ); ?> <?php echo esc_html( date( 'H:i', strtotime( $s_start ) ) ); ?><?php endif; ?>
								<?php if ( $s_loc ) : ?><br><?php echo esc_html( $s_loc ); ?><?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<!-- Attendance summary -->
		<?php if ( ! empty( $attendance_history ) ) : ?>
			<div class="dashboard-section">
				<h2 class="dashboard-section-title"><?php esc_html_e( 'Attendance History', 'waypoint' ); ?></h2>
				<?php
				$att_counts = array_count_values( array_column( $attendance_history, 'status' ) );
				$total      = count( $attendance_history );
				$attended   = $att_counts['attended'] ?? 0;
				$pct        = $total ? round( $attended / $total * 100 ) : 0;
				?>
				<div class="att-summary-bar mb-2">
					<?php foreach ( $att_counts as $s => $count ) : ?>
						<span class="att-chip att-<?php echo esc_attr( $s ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $s ) ) ); ?>: <?php echo esc_html( $count ); ?></span>
					<?php endforeach; ?>
				</div>
				<div class="progress-bar-wrap">
					<div class="wpnt-progress-bar" style="max-width:300px"><div class="wpnt-progress-bar-fill" data-pct="<?php echo esc_attr( $pct ); ?>" style="width:<?php echo esc_attr( $pct ); ?>%"></div></div>
					<small><?php printf( esc_html__( '%d%% attendance rate (%d/%d sessions)', 'waypoint' ), $pct, $attended, $total ); ?></small>
				</div>
			</div>
		<?php endif; ?>

		<!-- Training plans -->
		<?php if ( ! empty( $training_plans ) ) : ?>
			<div class="dashboard-section">
				<h2 class="dashboard-section-title"><?php esc_html_e( 'My Training Plans', 'waypoint' ); ?></h2>
				<?php foreach ( $training_plans as $plan ) :
					$goal   = get_post_meta( $plan->ID, '_wpnt_goal', true );
					$scope  = get_post_meta( $plan->ID, '_wpnt_scope', true );
					$target = get_post_meta( $plan->ID, '_wpnt_target_date', true );
				?>
					<div class="training-plan-item">
						<h4><a href="<?php echo esc_url( get_permalink( $plan->ID ) ); ?>"><?php echo esc_html( $plan->post_title ); ?></a></h4>
						<p class="tp-meta">
							<?php if ( $scope ) : ?><?php echo esc_html( ucwords( str_replace( '_', ' ', $scope ) ) ); ?><?php endif; ?>
							<?php if ( $target ) : ?> &bull; <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $target ) ) ); ?><?php endif; ?>
						</p>
						<?php if ( $goal ) : ?><p class="tp-goal"><?php echo esc_html( $goal ); ?></p><?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	<!-- Diary entries -->
		<?php if ( ! empty( $diary_entries ) ) : ?>
			<div class="dashboard-section">
				<h2 class="dashboard-section-title"><?php esc_html_e( 'My Diary', 'waypoint' ); ?></h2>
				<?php foreach ( $diary_entries as $entry ) :
					$entry_status   = get_post_meta( $entry->ID, '_wpnt_status', true );
					$event_type     = get_post_meta( $entry->ID, '_wpnt_event_type', true );
					$coach_response = get_post_meta( $entry->ID, '_wpnt_coach_response', true );
					$response_status = get_post_meta( $entry->ID, '_wpnt_coach_response_status', true );
				?>
					<div class="training-plan-item">
						<h4><a href="<?php echo esc_url( get_permalink( $entry->ID ) ); ?>"><?php echo esc_html( $entry->post_title ); ?></a></h4>
						<p class="tp-meta">
							<?php if ( $event_type ) : ?><?php echo esc_html( ucfirst( $event_type ) ); ?><?php endif; ?>
							&bull; <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $entry->post_date ) ) ); ?>
							&bull; <span class="status-pill status-<?php echo esc_attr( $entry_status ); ?>"><?php echo esc_html( ucfirst( $entry_status ) ); ?></span>
							<?php if ( $response_status === 'published' ) : ?>
								&bull; <span class="status-pill status-reviewed"><?php esc_html_e( 'Coach responded', 'waypoint' ); ?></span>
							<?php endif; ?>
						</p>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	</div>

</main>

<?php get_footer(); ?>
