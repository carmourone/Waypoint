<?php
/**
 * Template Name: Parent Dashboard
 * Template Post Type: page
 */

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( wp_login_url( get_permalink() ) );
	exit;
}

get_header();

$parent_id = get_current_user_id();
$user      = wp_get_current_user();

// Resolve children: BP friends who are athletes (preferred) or explicit meta link.
$child_ids = array();
if ( function_exists( 'friends_get_friend_user_ids' ) ) {
	$friend_ids = friends_get_friend_user_ids( $parent_id );
	if ( $friend_ids ) {
		$child_ids = get_users( array(
			'include' => $friend_ids,
			'role'    => 'wpnt_athlete',
			'fields'  => 'ID',
		) );
		$child_ids = array_map( 'intval', $child_ids );
	}
}
if ( empty( $child_ids ) ) {
	$meta      = get_user_meta( $parent_id, 'wpnt_children', true );
	$child_ids = $meta ? array_filter( array_map( 'absint', explode( ',', $meta ) ) ) : array();
}

$children = $child_ids ? get_users( array( 'include' => array_values( $child_ids ) ) ) : array();
?>

<main id="primary" class="site-main" role="main">

	<div class="page-hero">
		<div class="container">
			<h1><?php printf( esc_html__( 'Hello, %s', 'waypoint' ), esc_html( $user->display_name ) ); ?></h1>
			<p><?php echo esc_html( date_i18n( get_option( 'date_format' ) ) ); ?></p>
		</div>
	</div>

	<div class="container mt-4 wpnt-dashboard">

		<?php get_template_part( 'template-parts/dashboard-nav', '', array( 'current' => 'parent' ) ); ?>

		<?php if ( empty( $children ) ) : ?>
			<div class="notice-wp info">
				<?php esc_html_e( 'No athletes are linked to your account yet. Please contact your organisation administrator.', 'waypoint' ); ?>
			</div>
		<?php endif; ?>

		<?php foreach ( $children as $child ) :
			$enrolled_courses = waypoint_plugin_active() ? WPNT_Course::get_courses_for_athlete( $child->ID ) : array();
			$course_ids       = array_map( fn( $c ) => $c->ID, $enrolled_courses );

			$today    = current_time( 'Y-m-d' );
			$upcoming = $course_ids ? get_posts( array(
				'post_type'      => 'wpnt_session',
				'posts_per_page' => 5,
				'meta_query'     => array(
					array( 'key' => '_wpnt_course_id', 'value' => $course_ids, 'compare' => 'IN' ),
					array( 'key' => '_wpnt_scheduled_start', 'value' => $today . 'T00:00', 'compare' => '>=', 'type' => 'CHAR' ),
				),
				'orderby'  => 'meta_value',
				'meta_key' => '_wpnt_scheduled_start',
				'order'    => 'ASC',
			) ) : array();

			$att_history = waypoint_plugin_active() ? WPNT_Attendance::get_athlete_attendance( $child->ID ) : array();
			$att_counts  = array_count_values( array_column( $att_history, 'status' ) );
			$total       = count( $att_history );
			$attended    = $att_counts['attended'] ?? 0;

			$approved_plans = get_posts( array(
				'post_type'      => 'wpnt_training_plan',
				'posts_per_page' => 5,
				'meta_query'     => array(
					array( 'key' => '_wpnt_athlete_id', 'value' => $child->ID ),
					array( 'key' => '_wpnt_status', 'value' => array( 'approved', 'active' ), 'compare' => 'IN' ),
				),
			) );
		?>
			<div class="card mb-3">
				<h2 class="card-title"><?php echo esc_html( $child->display_name ); ?></h2>

				<!-- Enrolled courses -->
				<?php if ( ! empty( $enrolled_courses ) ) : ?>
					<h3><?php esc_html_e( 'Current Courses', 'waypoint' ); ?></h3>
					<ul style="list-style:none;padding:0;margin:0 0 1rem">
						<?php foreach ( $enrolled_courses as $c ) : ?>
							<li><a href="<?php echo esc_url( get_permalink( $c->ID ) ); ?>"><?php echo esc_html( $c->post_title ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<!-- Upcoming sessions -->
				<?php if ( ! empty( $upcoming ) ) : ?>
					<h3><?php esc_html_e( 'Upcoming Sessions', 'waypoint' ); ?></h3>
					<ul class="att-summary-bar" style="flex-direction:column;gap:.25rem;margin-bottom:1rem">
						<?php foreach ( $upcoming as $s ) :
							$s_start = get_post_meta( $s->ID, '_wpnt_scheduled_start', true );
						?>
							<li>
								<a href="<?php echo esc_url( get_permalink( $s->ID ) ); ?>"><?php echo esc_html( $s->post_title ); ?></a>
								<?php if ( $s_start ) : ?>&mdash; <?php echo esc_html( date_i18n( 'D d M H:i', strtotime( $s_start ) ) ); ?><?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<!-- Attendance summary -->
				<?php if ( $total ) : ?>
					<h3><?php esc_html_e( 'Attendance', 'waypoint' ); ?></h3>
					<div class="att-summary-bar mb-2">
						<?php foreach ( $att_counts as $s => $c ) : ?>
							<span class="att-chip att-<?php echo esc_attr( $s ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $s ) ) ); ?>: <?php echo esc_html( $c ); ?></span>
						<?php endforeach; ?>
					</div>
					<?php if ( $total ) :
						$pct = round( $attended / $total * 100 );
					?>
						<div class="progress-bar-wrap mb-2">
							<div class="wpnt-progress-bar" style="max-width:200px"><div class="wpnt-progress-bar-fill" data-pct="<?php echo esc_attr( $pct ); ?>" style="width:<?php echo esc_attr( $pct ); ?>%"></div></div>
							<small><?php printf( esc_html__( '%d%% (%d/%d)', 'waypoint' ), $pct, $attended, $total ); ?></small>
						</div>
					<?php endif; ?>
				<?php endif; ?>

				<!-- Approved training plans -->
				<?php if ( ! empty( $approved_plans ) ) : ?>
					<h3><?php esc_html_e( 'Training Plans', 'waypoint' ); ?></h3>
					<?php foreach ( $approved_plans as $plan ) :
						$goal = get_post_meta( $plan->ID, '_wpnt_goal', true );
					?>
						<div class="training-plan-item">
							<h4><a href="<?php echo esc_url( get_permalink( $plan->ID ) ); ?>"><?php echo esc_html( $plan->post_title ); ?></a></h4>
							<?php if ( $goal ) : ?><p class="tp-goal"><?php echo esc_html( $goal ); ?></p><?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>

			</div>
		<?php endforeach; ?>

	</div>

</main>

<?php get_footer(); ?>
