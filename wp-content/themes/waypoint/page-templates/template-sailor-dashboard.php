<?php
/**
 * Template Name: Sailor Dashboard
 * Template Post Type: page
 */

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( wp_login_url( get_permalink() ) );
	exit;
}

get_header();

$sailor_id = get_current_user_id();
$user      = wp_get_current_user();

// Courses this sailor is enrolled in.
$enrolled_courses = array();
if ( waypoint_plugin_active() ) {
	global $wpdb;
	$course_ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wpnt_enrolled_sailors' AND meta_value LIKE %s",
		'%' . $wpdb->esc_like( (string) $sailor_id ) . '%'
	) );
	if ( $course_ids ) {
		$enrolled_courses = get_posts( array(
			'post_type' => 'wpnt_course',
			'include'   => $course_ids,
			'posts_per_page' => -1,
		) );
	}
}

// Upcoming sessions.
$upcoming_sessions = array();
if ( waypoint_plugin_active() && $course_ids ) {
	$today = current_time( 'Y-m-d' );
	$upcoming_sessions = get_posts( array(
		'post_type'      => 'wpnt_session',
		'posts_per_page' => 8,
		'meta_query'     => array(
			array( 'key' => '_wpnt_course_id', 'value' => $course_ids, 'compare' => 'IN' ),
			array( 'key' => '_wpnt_scheduled_start', 'value' => $today . 'T00:00', 'compare' => '>=', 'type' => 'CHAR' ),
		),
		'orderby'  => 'meta_value',
		'meta_key' => '_wpnt_scheduled_start',
		'order'    => 'ASC',
	) );
}

// Attendance history.
$attendance_history = waypoint_plugin_active() ? WPNT_DB::get_sailor_attendance( $sailor_id ) : array();

// Training plans.
$training_plans = array();
if ( waypoint_plugin_active() ) {
	$training_plans = get_posts( array(
		'post_type'      => 'wpnt_training_plan',
		'posts_per_page' => 5,
		'meta_query'     => array(
			array( 'key' => '_wpnt_sailor_id', 'value' => $sailor_id ),
			array( 'key' => '_wpnt_status', 'value' => array( 'approved', 'active' ), 'compare' => 'IN' ),
		),
	) );
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
				$total = count( $attendance_history );
				$attended = $att_counts['attended'] ?? 0;
				$pct = $total ? round( $attended / $total * 100 ) : 0;
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
					$goal    = get_post_meta( $plan->ID, '_wpnt_goal', true );
					$scope   = get_post_meta( $plan->ID, '_wpnt_scope', true );
					$target  = get_post_meta( $plan->ID, '_wpnt_target_date', true );
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

	</div>

</main>

<?php get_footer(); ?>
