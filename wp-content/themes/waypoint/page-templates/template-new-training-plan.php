<?php
/**
 * Template Name: New Training Plan
 * Template Post Type: page
 */

if ( ! is_user_logged_in() || ! current_user_can( 'edit_wpnt_sessions' ) ) {
	wp_safe_redirect( wp_login_url( get_permalink() ) );
	exit;
}

get_header();

$participant_lbl = class_exists( 'WPNT_Pack' ) ? WPNT_Pack::get_active_label( 'participant_label', __( 'Athlete', 'waypoint' ) ) : __( 'Athlete', 'waypoint' );
$coach_dashboard = (int) get_option( 'wpnt_coach_dashboard_page_id' );

$athletes = get_users( array(
	'role'    => 'wpnt_athlete',
	'orderby' => 'display_name',
	'order'   => 'ASC',
) );

$courses = get_posts( array(
	'post_type'      => 'wpnt_course',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
) );
?>

<main id="primary" class="site-main" role="main">

	<div class="page-hero">
		<div class="container">
			<?php if ( $coach_dashboard ) : ?>
				<p><a href="<?php echo esc_url( get_permalink( $coach_dashboard ) ); ?>" style="color:rgba(255,255,255,.7)">&larr; <?php esc_html_e( 'Dashboard', 'waypoint' ); ?></a></p>
			<?php endif; ?>
			<h1><?php esc_html_e( 'New Training Plan', 'waypoint' ); ?></h1>
		</div>
	</div>

	<div class="container mt-4">
		<div class="wpnt-form-wrap">

			<form class="wpnt-form card" data-endpoint="training-plans" data-method="POST">

				<div class="form-row">
					<label for="wpnt-p-title"><?php esc_html_e( 'Title', 'waypoint' ); ?> <span class="required">*</span></label>
					<input id="wpnt-p-title" name="title" type="text" class="form-control" required placeholder="<?php esc_attr_e( 'e.g. Term 2 Development Plan', 'waypoint' ); ?>">
				</div>

				<?php if ( ! empty( $athletes ) ) : ?>
					<div class="form-row">
						<label for="wpnt-p-athlete"><?php echo esc_html( $participant_lbl ); ?> <span class="form-hint"><?php esc_html_e( 'optional — leave blank for a group plan', 'waypoint' ); ?></span></label>
						<select id="wpnt-p-athlete" name="athlete_id" class="form-control">
							<option value=""><?php printf( esc_html__( '— No specific %s —', 'waypoint' ), esc_html( strtolower( $participant_lbl ) ) ); ?></option>
							<?php foreach ( $athletes as $a ) : ?>
								<option value="<?php echo esc_attr( $a->ID ); ?>"><?php echo esc_html( $a->display_name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $courses ) ) : ?>
					<div class="form-row">
						<label for="wpnt-p-course"><?php esc_html_e( 'Course', 'waypoint' ); ?> <span class="form-hint"><?php esc_html_e( 'optional', 'waypoint' ); ?></span></label>
						<select id="wpnt-p-course" name="course_id" class="form-control">
							<option value=""><?php esc_html_e( '— Not linked to a course —', 'waypoint' ); ?></option>
							<?php foreach ( $courses as $c ) : ?>
								<option value="<?php echo esc_attr( $c->ID ); ?>"><?php echo esc_html( $c->post_title ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>

				<div class="form-row">
					<label for="wpnt-p-goal"><?php esc_html_e( 'Goal', 'waypoint' ); ?> <span class="form-hint"><?php esc_html_e( 'optional', 'waypoint' ); ?></span></label>
					<textarea id="wpnt-p-goal" name="goal" class="form-control" rows="2" placeholder="<?php esc_attr_e( 'What should the athlete achieve by the end of this plan?', 'waypoint' ); ?>"></textarea>
				</div>

				<div class="form-row-2col">
					<div class="form-row">
						<label for="wpnt-p-scope"><?php esc_html_e( 'Scope', 'waypoint' ); ?></label>
						<select id="wpnt-p-scope" name="scope" class="form-control">
							<?php foreach ( array( '' => '—', 'term' => 'Term', 'season' => 'Season', 'event' => 'Event', 'long_term' => 'Long-term', 'development' => 'Development' ) as $val => $lbl ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $lbl ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="form-row">
						<label for="wpnt-p-target"><?php esc_html_e( 'Target Date', 'waypoint' ); ?> <span class="form-hint"><?php esc_html_e( 'optional', 'waypoint' ); ?></span></label>
						<input id="wpnt-p-target" name="target_date" type="date" class="form-control">
					</div>
				</div>

				<div class="form-row">
					<label for="wpnt-p-activities"><?php esc_html_e( 'Planned Activities', 'waypoint' ); ?> <span class="form-hint"><?php esc_html_e( 'optional', 'waypoint' ); ?></span></label>
					<textarea id="wpnt-p-activities" name="planned_activities" class="form-control" rows="4" placeholder="<?php esc_attr_e( 'Drills, race practice, fitness components…', 'waypoint' ); ?>"></textarea>
				</div>

				<div class="form-row">
					<label for="wpnt-p-content"><?php esc_html_e( 'Coach Notes', 'waypoint' ); ?> <span class="form-hint"><?php esc_html_e( 'optional', 'waypoint' ); ?></span></label>
					<textarea id="wpnt-p-content" name="content" class="form-control" rows="3" placeholder="<?php esc_attr_e( 'Internal notes or context for this plan…', 'waypoint' ); ?>"></textarea>
				</div>

				<div class="form-row-2col">
					<div class="form-row">
						<label for="wpnt-p-status"><?php esc_html_e( 'Status', 'waypoint' ); ?></label>
						<select id="wpnt-p-status" name="status" class="form-control">
							<option value="draft"><?php esc_html_e( 'Draft', 'waypoint' ); ?></option>
							<option value="active" selected><?php esc_html_e( 'Active', 'waypoint' ); ?></option>
							<option value="published"><?php esc_html_e( 'Published', 'waypoint' ); ?></option>
						</select>
					</div>
					<div class="form-row">
						<label for="wpnt-p-visibility"><?php esc_html_e( 'Visibility', 'waypoint' ); ?></label>
						<select id="wpnt-p-visibility" name="visibility" class="form-control">
							<option value="shared" selected><?php esc_html_e( 'Shared with athlete', 'waypoint' ); ?></option>
							<option value="internal"><?php esc_html_e( 'Internal only', 'waypoint' ); ?></option>
						</select>
					</div>
				</div>

				<div class="form-actions">
					<button type="submit" class="btn btn-primary"><?php esc_html_e( 'Create Training Plan', 'waypoint' ); ?></button>
					<?php if ( $coach_dashboard ) : ?>
						<a href="<?php echo esc_url( get_permalink( $coach_dashboard ) ); ?>" class="btn btn-outline"><?php esc_html_e( 'Cancel', 'waypoint' ); ?></a>
					<?php endif; ?>
				</div>

			</form>

		</div>
	</div>

</main>

<?php get_footer(); ?>
