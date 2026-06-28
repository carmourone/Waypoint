<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();
	$plan_id   = get_the_ID();
	$athlete_id      = (int) get_post_meta( $plan_id, '_wpnt_athlete_id', true );
	$course_id       = (int) get_post_meta( $plan_id, '_wpnt_course_id', true );
	$origin          = get_post_meta( $plan_id, '_wpnt_origin', true );
	$scope           = get_post_meta( $plan_id, '_wpnt_scope', true );
	$goal            = get_post_meta( $plan_id, '_wpnt_goal', true );
	$activities      = get_post_meta( $plan_id, '_wpnt_planned_activities', true );
	$status          = get_post_meta( $plan_id, '_wpnt_status', true );
	$target          = get_post_meta( $plan_id, '_wpnt_target_date', true );
	$coach_id        = (int) get_post_meta( $plan_id, '_wpnt_assigned_coach', true );
	$athlete         = $athlete_id ? get_user_by( 'id', $athlete_id ) : null;
	$coach           = $coach_id ? get_user_by( 'id', $coach_id ) : null;
	$participant_lbl = class_exists( 'WPNT_Pack' ) ? WPNT_Pack::get_active_label( 'participant_label', __( 'Athlete', 'waypoint' ) ) : __( 'Athlete', 'waypoint' );

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
