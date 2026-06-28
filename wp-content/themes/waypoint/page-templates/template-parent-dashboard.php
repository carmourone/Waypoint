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

// Children linked to this parent via user meta.
$child_ids = get_user_meta( $parent_id, 'wpnt_children', true );
$child_ids = $child_ids ? array_filter( array_map( 'absint', explode( ',', $child_ids ) ) ) : array();

$children = $child_ids ? get_users( array( 'include' => $child_ids ) ) : array();
?>

<main id="primary" class="site-main" role="main">

	<div class="page-hero">
		<div class="container">
			<h1><?php printf( esc_html__( 'Hello, %s', 'waypoint' ), esc_html( $user->display_name ) ); ?></h1>
			<p><?php echo esc_html( date_i18n( get_option( 'date_format' ) ) ); ?></p>
		</div>
	</div>

	<div class="container mt-4 wpnt-dashboard">

		<?php if ( empty( $children ) ) : ?>
			<div class="notice-wp info">
				<?php esc_html_e( 'No children are linked to your account yet. Please contact your organisation administrator.', 'waypoint' ); ?>
			</div>
		<?php endif; ?>

		<?php foreach ( $children as $child ) :
			global $wpdb;
			$course_ids = $wpdb->get_col( $wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wpnt_enrolled_sailors' AND meta_value LIKE %s",
				'%' . $wpdb->esc_like( (string) $child->ID ) . '%'
			) );

			$enrolled_courses = $course_ids ? get_posts( array(
				'post_type'      => 'wpnt_course',
				'include'        => $course_ids,
				'posts_per_page' => -1,
			) ) : array();

			$today     = current_time( 'Y-m-d' );
			$upcoming  = $course_ids ? get_posts( array(
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

			$att_history = WPNT_DB::get_athlete_attendance( $child->ID );
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

				<!-- Attendance -->
				<?php if ( $total ) : ?>
					<h3><?php esc_html_e( 'Attendance', 'waypoint' ); ?></h3>
					<div class="att-summary-bar mb-2">
						<?php foreach ( $att_counts as $s => $c ) : ?>
							<span class="att-chip att-<?php echo esc_attr( $s ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $s ) ) ); ?>: <?php echo esc_html( $c ); ?></span>
						<?php endforeach; ?>
					</div>
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
