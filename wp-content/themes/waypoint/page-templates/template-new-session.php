<?php
/**
 * Template Name: New Session
 * Template Post Type: page
 */

if ( ! is_user_logged_in() || ! current_user_can( 'edit_wpnt_sessions' ) ) {
	wp_safe_redirect( wp_login_url( get_permalink() ) );
	exit;
}

get_header();

// Pre-fill plan_id from query string when arriving from a training plan page.
$prefill_plan_id = absint( $_GET['plan_id'] ?? 0 );
$prefill_plan    = $prefill_plan_id ? get_post( $prefill_plan_id ) : null;

// Populate course and plan dropdowns.
$courses = get_posts( array(
	'post_type'      => 'wpnt_course',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
) );

$plans = get_posts( array(
	'post_type'      => 'wpnt_training_plan',
	'post_status'    => 'publish',
	'posts_per_page' => 50,
	'orderby'        => 'title',
	'order'          => 'ASC',
	'meta_query'     => array(
		array( 'key' => '_wpnt_status', 'value' => array( 'draft', 'published', 'active' ), 'compare' => 'IN' ),
	),
) );

$participant_lbl = class_exists( 'WPNT_Pack' ) ? WPNT_Pack::get_active_label( 'participant_label', __( 'Athlete', 'waypoint' ) ) : __( 'Athlete', 'waypoint' );
$coach_dashboard = (int) get_option( 'wpnt_coach_dashboard_page_id' );
?>

<main id="primary" class="site-main" role="main">

	<div class="page-hero">
		<div class="container">
			<?php if ( $coach_dashboard ) : ?>
				<p><a href="<?php echo esc_url( get_permalink( $coach_dashboard ) ); ?>" style="color:rgba(255,255,255,.7)">&larr; <?php esc_html_e( 'Dashboard', 'waypoint' ); ?></a></p>
			<?php endif; ?>
			<h1><?php esc_html_e( 'New Session', 'waypoint' ); ?></h1>
			<?php if ( $prefill_plan ) : ?>
				<p><?php printf( esc_html__( 'For plan: %s', 'waypoint' ), esc_html( $prefill_plan->post_title ) ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="container mt-4">
		<div class="wpnt-form-wrap">

			<form class="wpnt-form card" data-endpoint="sessions" data-method="POST">

				<div class="form-row">
					<label for="wpnt-s-title"><?php esc_html_e( 'Title', 'waypoint' ); ?> <span class="required">*</span></label>
					<input id="wpnt-s-title" name="title" type="text" class="form-control" required placeholder="<?php esc_attr_e( 'e.g. Session 1 — Tacking Basics', 'waypoint' ); ?>">
				</div>

				<div class="form-row-2col">
					<div class="form-row">
						<label for="wpnt-s-date"><?php esc_html_e( 'Date', 'waypoint' ); ?> <span class="required">*</span></label>
						<input id="wpnt-s-date" name="_date" type="date" class="form-control" required>
					</div>
					<div class="form-row">
						<label for="wpnt-s-start"><?php esc_html_e( 'Start Time', 'waypoint' ); ?></label>
						<input id="wpnt-s-start" name="_start_time" type="time" class="form-control" value="09:00">
					</div>
					<div class="form-row">
						<label for="wpnt-s-end"><?php esc_html_e( 'End Time', 'waypoint' ); ?></label>
						<input id="wpnt-s-end" name="_end_time" type="time" class="form-control" value="11:00">
					</div>
				</div>

				<div class="form-row">
					<label for="wpnt-s-location"><?php esc_html_e( 'Location', 'waypoint' ); ?></label>
					<input id="wpnt-s-location" name="location" type="text" class="form-control" placeholder="<?php esc_attr_e( 'e.g. Club Dock A', 'waypoint' ); ?>">
				</div>

				<?php if ( ! empty( $courses ) ) : ?>
					<div class="form-row">
						<label for="wpnt-s-course"><?php esc_html_e( 'Course', 'waypoint' ); ?> <span class="form-hint"><?php esc_html_e( 'optional', 'waypoint' ); ?></span></label>
						<select id="wpnt-s-course" name="course_id" class="form-control">
							<option value=""><?php esc_html_e( '— No course (standalone session) —', 'waypoint' ); ?></option>
							<?php foreach ( $courses as $c ) : ?>
								<option value="<?php echo esc_attr( $c->ID ); ?>"><?php echo esc_html( $c->post_title ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $plans ) ) : ?>
					<div class="form-row">
						<label for="wpnt-s-plan"><?php esc_html_e( 'Training Plan', 'waypoint' ); ?> <span class="form-hint"><?php esc_html_e( 'optional', 'waypoint' ); ?></span></label>
						<select id="wpnt-s-plan" name="plan_id" class="form-control">
							<option value=""><?php esc_html_e( '— Not linked to a plan —', 'waypoint' ); ?></option>
							<?php foreach ( $plans as $p ) :
								$p_athlete_id = (int) get_post_meta( $p->ID, '_wpnt_athlete_id', true );
								$p_athlete    = $p_athlete_id ? get_user_by( 'id', $p_athlete_id ) : null;
								$label        = $p->post_title . ( $p_athlete ? ' (' . $p_athlete->display_name . ')' : '' );
							?>
								<option value="<?php echo esc_attr( $p->ID ); ?>" <?php selected( $prefill_plan_id, $p->ID ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>

				<div class="form-row">
					<label for="wpnt-s-status"><?php esc_html_e( 'Status', 'waypoint' ); ?></label>
					<select id="wpnt-s-status" name="status" class="form-control">
						<option value="scheduled" selected><?php esc_html_e( 'Scheduled', 'waypoint' ); ?></option>
						<option value="delivered"><?php esc_html_e( 'Delivered', 'waypoint' ); ?></option>
						<option value="cancelled"><?php esc_html_e( 'Cancelled', 'waypoint' ); ?></option>
					</select>
				</div>

				<div class="form-row">
					<label for="wpnt-s-content"><?php esc_html_e( 'Session Plan / Notes', 'waypoint' ); ?> <span class="form-hint"><?php esc_html_e( 'optional', 'waypoint' ); ?></span></label>
					<textarea id="wpnt-s-content" name="content" class="form-control" rows="4" placeholder="<?php esc_attr_e( 'Session objectives, drills, safety notes…', 'waypoint' ); ?>"></textarea>
				</div>

				<details class="form-row">
					<summary class="form-hint" style="cursor:pointer"><?php esc_html_e( 'Conditions (wind, boat class)', 'waypoint' ); ?></summary>
					<div class="form-row-2col mt-2">
						<div class="form-row">
							<label for="wpnt-s-wind"><?php esc_html_e( 'Wind Range', 'waypoint' ); ?></label>
							<input id="wpnt-s-wind" name="wind_range" type="text" class="form-control" placeholder="e.g. 8–15kn">
						</div>
						<div class="form-row">
							<label for="wpnt-s-boat"><?php esc_html_e( 'Boat Class', 'waypoint' ); ?></label>
							<input id="wpnt-s-boat" name="boat_class" type="text" class="form-control" placeholder="e.g. optimist">
						</div>
					</div>
				</details>

				<div class="form-actions">
					<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Create Session', 'waypoint' ); ?></button>
					<?php if ( $coach_dashboard ) : ?>
						<a href="<?php echo esc_url( get_permalink( $coach_dashboard ) ); ?>" class="btn btn-outline"><?php esc_html_e( 'Cancel', 'waypoint' ); ?></a>
					<?php endif; ?>
				</div>

			</form>

		</div>
	</div>

</main>

<?php get_footer(); ?>
