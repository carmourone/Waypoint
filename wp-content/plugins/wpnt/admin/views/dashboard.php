<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$club_name = get_option( 'wpnt_club_name', 'Your Club' );

$course_count = wp_count_posts( 'wpnt_course' );
$active_courses = isset( $course_count->publish ) ? (int) $course_count->publish : 0;

$session_count = wp_count_posts( 'wpnt_session' );
$total_sessions = isset( $session_count->publish ) ? (int) $session_count->publish : 0;

$tp_count = wp_count_posts( 'wpnt_training_plan' );
$open_plans = isset( $tp_count->publish ) ? (int) $tp_count->publish : 0;

$sailors = get_users( array( 'role' => 'wpnt_sailor', 'count_total' => true ) );
$sailor_count = count( $sailors );

$today_sessions = WPNT_Session::get_todays_sessions();
?>
<div class="wrap wpnt-admin-wrap">
	<h1 class="wpnt-page-title"><?php printf( esc_html__( 'Waypoint — %s', 'wpnt' ), esc_html( $club_name ) ); ?></h1>

	<div class="wpnt-stat-cards">
		<div class="wpnt-stat-card">
			<span class="wpnt-stat-number"><?php echo esc_html( $active_courses ); ?></span>
			<span class="wpnt-stat-label"><?php esc_html_e( 'Active Courses', 'wpnt' ); ?></span>
		</div>
		<div class="wpnt-stat-card">
			<span class="wpnt-stat-number"><?php echo esc_html( count( $today_sessions ) ); ?></span>
			<span class="wpnt-stat-label"><?php esc_html_e( 'Sessions Today', 'wpnt' ); ?></span>
		</div>
		<div class="wpnt-stat-card">
			<span class="wpnt-stat-number"><?php echo esc_html( $sailor_count ); ?></span>
			<span class="wpnt-stat-label"><?php esc_html_e( 'Sailors', 'wpnt' ); ?></span>
		</div>
		<div class="wpnt-stat-card">
			<span class="wpnt-stat-number"><?php echo esc_html( $open_plans ); ?></span>
			<span class="wpnt-stat-label"><?php esc_html_e( 'Training Plans', 'wpnt' ); ?></span>
		</div>
	</div>

	<div class="wpnt-dashboard-columns">
		<div class="wpnt-dashboard-col">
			<h2><?php esc_html_e( "Today's Sessions", 'wpnt' ); ?></h2>
			<?php if ( empty( $today_sessions ) ) : ?>
				<p class="wpnt-empty"><?php esc_html_e( 'No sessions scheduled for today.', 'wpnt' ); ?></p>
			<?php else : ?>
				<ul class="wpnt-session-list">
					<?php foreach ( $today_sessions as $s ) :
						$start  = get_post_meta( $s->ID, '_wpnt_scheduled_start', true );
						$status = get_post_meta( $s->ID, '_wpnt_status', true );
					?>
						<li class="wpnt-session-item wpnt-status-<?php echo esc_attr( $status ); ?>">
							<a href="<?php echo esc_url( get_edit_post_link( $s->ID ) ); ?>"><?php echo esc_html( $s->post_title ); ?></a>
							<?php if ( $start ) : ?>
								<span class="wpnt-time"><?php echo esc_html( date( 'H:i', strtotime( $start ) ) ); ?></span>
							<?php endif; ?>
							<span class="wpnt-badge wpnt-badge-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=wpnt-today' ) ); ?>" class="button"><?php esc_html_e( 'View Full Today Screen', 'wpnt' ); ?></a></p>
		</div>

		<div class="wpnt-dashboard-col">
			<h2><?php esc_html_e( 'Quick Links', 'wpnt' ); ?></h2>
			<ul class="wpnt-quick-links">
				<li><a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpnt_course' ) ); ?>"><?php esc_html_e( 'Create Course', 'wpnt' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpnt_session' ) ); ?>"><?php esc_html_e( 'All Sessions', 'wpnt' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=wpnt-attendance' ) ); ?>"><?php esc_html_e( 'Mark Attendance', 'wpnt' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpnt_training_plan' ) ); ?>"><?php esc_html_e( 'New Training Plan', 'wpnt' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpnt_curriculum' ) ); ?>"><?php esc_html_e( 'Curriculum Packs', 'wpnt' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpnt_skill' ) ); ?>"><?php esc_html_e( 'Skills', 'wpnt' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=wpnt_drill' ) ); ?>"><?php esc_html_e( 'Drills', 'wpnt' ); ?></a></li>
			</ul>
		</div>
	</div>
</div>
