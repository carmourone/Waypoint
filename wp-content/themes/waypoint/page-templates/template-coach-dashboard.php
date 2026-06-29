<?php
/**
 * Template Name: Coach Dashboard
 * Template Post Type: page
 */

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( wp_login_url( get_permalink() ) );
	exit;
}

get_header();

$coach_id      = get_current_user_id();
$today_sessions = waypoint_plugin_active() ? WPNT_Session::get_todays_sessions( $coach_id ) : array();
$upcoming       = waypoint_plugin_active() ? WPNT_Session::get_upcoming_sessions( $coach_id, 14 ) : array();
$incomplete     = waypoint_plugin_active() ? WPNT_Session::get_incomplete_attendance_sessions( $coach_id ) : array();
$open_plans     = waypoint_plugin_active() ? get_posts( array(
	'post_type'      => 'wpnt_training_plan',
	'posts_per_page' => 10,
	'meta_query'     => array(
		array( 'key' => '_wpnt_status', 'value' => array( 'active', 'published' ), 'compare' => 'IN' ),
	),
) ) : array();
$pending_diary  = waypoint_plugin_active() ? WPNT_Diary::get_pending_review() : array();
?>

<main id="primary" class="site-main" role="main">

	<div class="page-hero">
		<div class="container">
			<h1><?php printf( esc_html__( 'Coach Dashboard', 'waypoint' ) ); ?></h1>
			<p><?php echo esc_html( date_i18n( get_option( 'date_format' ) ) ); ?></p>
		</div>
	</div>

	<div class="container mt-4 wpnt-dashboard">

		<?php get_template_part( 'template-parts/dashboard-nav', '', array( 'current' => 'coach' ) ); ?>

		<!-- Quick actions -->
		<?php
		$new_session_page = (int) get_option( 'wpnt_new_session_page_id' );
		$new_plan_page    = (int) get_option( 'wpnt_new_training_plan_page_id' );
		if ( $new_session_page || $new_plan_page ) : ?>
			<div class="wpnt-quick-actions">
				<?php if ( $new_session_page ) : ?>
					<a href="<?php echo esc_url( get_permalink( $new_session_page ) ); ?>" class="btn btn-primary btn-sm"><?php esc_html_e( '+ New Session', 'waypoint' ); ?></a>
				<?php endif; ?>
				<?php if ( $new_plan_page ) : ?>
					<a href="<?php echo esc_url( get_permalink( $new_plan_page ) ); ?>" class="btn btn-outline btn-sm"><?php esc_html_e( '+ New Training Plan', 'waypoint' ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<!-- Stat bar -->
		<div class="dashboard-grid mb-3" style="grid-template-columns: repeat(auto-fill, minmax(160px,1fr))">
			<div class="dashboard-stat">
				<div class="stat-num"><?php echo count( $today_sessions ); ?></div>
				<div class="stat-label"><?php esc_html_e( 'Sessions Today', 'waypoint' ); ?></div>
			</div>
			<div class="dashboard-stat">
				<div class="stat-num"><?php echo count( $upcoming ); ?></div>
				<div class="stat-label"><?php esc_html_e( 'Upcoming (14d)', 'waypoint' ); ?></div>
			</div>
			<div class="dashboard-stat">
				<div class="stat-num"><?php echo count( $incomplete ); ?></div>
				<div class="stat-label"><?php esc_html_e( 'Incomplete Attendance', 'waypoint' ); ?></div>
			</div>
			<div class="dashboard-stat">
				<div class="stat-num"><?php echo count( $open_plans ); ?></div>
				<div class="stat-label"><?php esc_html_e( 'Open Training Plans', 'waypoint' ); ?></div>
			</div>
			<div class="dashboard-stat">
				<div class="stat-num"><?php echo count( $pending_diary ); ?></div>
				<div class="stat-label"><?php esc_html_e( 'Diary Reviews Due', 'waypoint' ); ?></div>
			</div>
		</div>

		<!-- Today's sessions -->
		<div class="dashboard-section">
			<h2 class="dashboard-section-title"><?php esc_html_e( "Today's Sessions", 'waypoint' ); ?></h2>
			<?php if ( empty( $today_sessions ) ) : ?>
				<p class="notice-wp info"><?php esc_html_e( 'No sessions scheduled for today.', 'waypoint' ); ?></p>
			<?php else : ?>
				<div class="session-list-grid">
					<?php foreach ( $today_sessions as $s ) :
						$s_status = get_post_meta( $s->ID, '_wpnt_status', true );
						$s_start  = get_post_meta( $s->ID, '_wpnt_scheduled_start', true );
						$s_loc    = get_post_meta( $s->ID, '_wpnt_location', true );
					?>
						<div class="session-card">
							<h3><a href="<?php echo esc_url( get_permalink( $s->ID ) ); ?>"><?php echo esc_html( $s->post_title ); ?></a></h3>
							<div class="session-meta">
								<?php if ( $s_start ) echo esc_html( date( 'H:i', strtotime( $s_start ) ) ); ?>
								<?php if ( $s_loc ) : ?>&mdash; <?php echo esc_html( $s_loc ); ?><?php endif; ?>
							</div>
							<span class="status-pill status-<?php echo esc_attr( $s_status ); ?>"><?php echo esc_html( ucfirst( $s_status ) ); ?></span>
							<br class="mt-1">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpnt-today&session_id=' . $s->ID ) ); ?>" class="btn btn-primary btn-sm mt-1"><?php esc_html_e( 'Mark Attendance', 'waypoint' ); ?></a>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<!-- Upcoming sessions -->
		<?php if ( ! empty( $upcoming ) ) : ?>
			<div class="dashboard-section">
				<h2 class="dashboard-section-title"><?php esc_html_e( 'Upcoming Sessions (next 14 days)', 'waypoint' ); ?></h2>
				<div class="session-list-grid">
					<?php foreach ( $upcoming as $s ) :
						$s_status = get_post_meta( $s->ID, '_wpnt_status', true );
						$s_start  = get_post_meta( $s->ID, '_wpnt_scheduled_start', true );
					?>
						<div class="session-card">
							<h3><a href="<?php echo esc_url( get_permalink( $s->ID ) ); ?>"><?php echo esc_html( $s->post_title ); ?></a></h3>
							<div class="session-meta">
								<?php if ( $s_start ) : ?><?php echo esc_html( date_i18n( 'D d M', strtotime( $s_start ) ) ); ?> <?php echo esc_html( date( 'H:i', strtotime( $s_start ) ) ); ?><?php endif; ?>
							</div>
							<span class="status-pill status-<?php echo esc_attr( $s_status ); ?>"><?php echo esc_html( ucfirst( $s_status ) ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<!-- Incomplete attendance -->
		<?php if ( ! empty( $incomplete ) ) : ?>
			<div class="dashboard-section">
				<h2 class="dashboard-section-title"><?php esc_html_e( 'Incomplete Attendance', 'waypoint' ); ?></h2>
				<div class="notice-wp warning"><?php esc_html_e( 'These past sessions have no attendance recorded.', 'waypoint' ); ?></div>
				<ul class="session-list-grid" style="list-style:none;padding:0">
					<?php foreach ( $incomplete as $s ) : ?>
						<li class="session-card">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpnt-attendance&session_id=' . $s->ID ) ); ?>"><?php echo esc_html( $s->post_title ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<!-- Open training plans -->
		<?php if ( ! empty( $open_plans ) ) : ?>
			<div class="dashboard-section">
				<h2 class="dashboard-section-title"><?php esc_html_e( 'Open Training Plans', 'waypoint' ); ?></h2>
				<?php foreach ( $open_plans as $plan ) :
					$athlete_id = (int) get_post_meta( $plan->ID, '_wpnt_athlete_id', true );
					$athlete    = $athlete_id ? get_user_by( 'id', $athlete_id ) : null;
					$scope      = get_post_meta( $plan->ID, '_wpnt_scope', true );
				?>
					<div class="training-plan-item">
						<h4><a href="<?php echo esc_url( get_permalink( $plan->ID ) ); ?>"><?php echo esc_html( $plan->post_title ); ?></a></h4>
						<p class="tp-meta">
							<?php if ( $athlete ) : ?><?php echo esc_html( $athlete->display_name ); ?><?php endif; ?>
							<?php if ( $scope ) : ?> &bull; <?php echo esc_html( ucwords( str_replace( '_', ' ', $scope ) ) ); ?><?php endif; ?>
						</p>
					</div>
				<?php endforeach; ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpnt-training-plans' ) ); ?>" class="btn btn-outline btn-sm"><?php esc_html_e( 'All Training Plans', 'waypoint' ); ?></a>
			</div>
		<?php endif; ?>

	<!-- Pending diary reviews -->
		<?php if ( ! empty( $pending_diary ) ) : ?>
			<div class="dashboard-section">
				<h2 class="dashboard-section-title"><?php esc_html_e( 'Diary Entries Awaiting Review', 'waypoint' ); ?></h2>
				<div class="notice-wp warning"><?php esc_html_e( 'These diary entries have been submitted by athletes and need your response.', 'waypoint' ); ?></div>
				<table class="widefat" style="margin-top:.5rem">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Entry', 'waypoint' ); ?></th>
							<th><?php echo esc_html( WPNT_Pack::get_active_label( 'participant_label', __( 'Athlete', 'waypoint' ) ) ); ?></th>
							<th><?php esc_html_e( 'Date', 'waypoint' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $pending_diary as $entry ) :
							$athlete_id = (int) get_post_meta( $entry->ID, '_wpnt_athlete_id', true );
							$athlete    = $athlete_id ? get_user_by( 'id', $athlete_id ) : null;
						?>
							<tr>
								<td><a href="<?php echo esc_url( get_edit_post_link( $entry->ID ) ); ?>"><?php echo esc_html( $entry->post_title ); ?></a></td>
								<td><?php echo $athlete ? esc_html( $athlete->display_name ) : '—'; ?></td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $entry->post_date ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpnt-diary' ) ); ?>" class="btn btn-outline btn-sm" style="margin-top:.5rem"><?php esc_html_e( 'All Diary Entries', 'waypoint' ); ?></a>
			</div>
		<?php endif; ?>

	</div>

</main>

<?php get_footer(); ?>
