<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$coach_id      = get_current_user_id();
$today_sessions = WPNT_Session::get_todays_sessions( current_user_can( 'manage_options' ) ? 0 : $coach_id );
$requested_session = isset( $_GET['session_id'] ) ? absint( $_GET['session_id'] ) : 0;
$active_session    = $requested_session ?: ( ! empty( $today_sessions ) ? $today_sessions[0]->ID : 0 );
?>
<div class="wrap wpnt-admin-wrap">
	<h1><?php esc_html_e( "Today's Sessions", 'wpnt' ); ?> — <?php echo esc_html( date_i18n( get_option( 'date_format' ) ) ); ?></h1>

	<?php if ( empty( $today_sessions ) ) : ?>
		<div class="notice notice-info"><p><?php esc_html_e( 'No sessions scheduled for today.', 'wpnt' ); ?></p></div>
	<?php else : ?>

		<div class="wpnt-today-layout">
			<div class="wpnt-today-sidebar">
				<h3><?php esc_html_e( 'Sessions', 'wpnt' ); ?></h3>
				<ul class="wpnt-session-nav">
					<?php foreach ( $today_sessions as $s ) :
						$status = get_post_meta( $s->ID, '_wpnt_status', true );
						$start  = get_post_meta( $s->ID, '_wpnt_scheduled_start', true );
					?>
						<li class="<?php echo $s->ID === $active_session ? 'active' : ''; ?>">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpnt-today&session_id=' . $s->ID ) ); ?>">
								<strong><?php echo esc_html( $s->post_title ); ?></strong><br>
								<?php if ( $start ) : ?><span><?php echo esc_html( date( 'H:i', strtotime( $start ) ) ); ?></span><?php endif; ?>
								<span class="wpnt-badge wpnt-badge-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="wpnt-today-main">
				<?php if ( $active_session ) :
					$s          = get_post( $active_session );
					$course_id  = (int) get_post_meta( $active_session, '_wpnt_course_id', true );
					$template   = get_post_meta( $active_session, '_wpnt_template_id', true );
					$location   = get_post_meta( $active_session, '_wpnt_location', true );
					$status     = get_post_meta( $active_session, '_wpnt_status', true );
					$start      = get_post_meta( $active_session, '_wpnt_scheduled_start', true );
					$end        = get_post_meta( $active_session, '_wpnt_scheduled_end', true );
				?>
					<div class="wpnt-session-header">
						<h2><?php echo esc_html( $s->post_title ); ?></h2>
						<?php if ( $course_id ) : ?>
							<p><?php esc_html_e( 'Course:', 'wpnt' ); ?> <a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>"><?php echo esc_html( get_the_title( $course_id ) ); ?></a></p>
						<?php endif; ?>
						<?php if ( $start || $end ) : ?>
							<p><?php echo esc_html( $start ? date( 'H:i', strtotime( $start ) ) : '' ); ?> – <?php echo esc_html( $end ? date( 'H:i', strtotime( $end ) ) : '' ); ?><?php if ( $location ) : ?> @ <?php echo esc_html( $location ); ?><?php endif; ?></p>
						<?php endif; ?>
					</div>

					<div class="wpnt-today-attendance">
						<h3><?php esc_html_e( 'Attendance', 'wpnt' ); ?></h3>
						<?php
						$session_groups = WPNT_Session_Group::get_for_session( $active_session );
						if ( ! empty( $session_groups ) ) :
							foreach ( $session_groups as $sg ) :
								echo WPNT_Session_Group::render_group_block( $sg, true );
							endforeach;
						else :
							echo WPNT_Attendance::render_checklist( $active_session );
						endif;
						?>
						<div class="wpnt-add-group-row" style="margin-top:.75rem">
							<button class="button wpnt-add-group" data-session-id="<?php echo esc_attr( $active_session ); ?>">
								<?php esc_html_e( '+ Add Cohort / Level Group', 'wpnt' ); ?>
							</button>
						</div>
					</div>

					<div class="wpnt-today-notes">
						<h3><?php esc_html_e( 'Quick Session Note', 'wpnt' ); ?></h3>
						<textarea id="wpnt-group-note" rows="4" class="large-text" placeholder="<?php esc_attr_e( 'Group note — what happened today?', 'wpnt' ); ?>"><?php echo esc_textarea( get_post_meta( $active_session, '_wpnt_actual_notes', true ) ); ?></textarea>

						<h3><?php esc_html_e( 'Add Observation', 'wpnt' ); ?></h3>
						<div class="wpnt-obs-form">
							<input type="text" id="wpnt-obs-sailor" placeholder="<?php esc_attr_e( 'Sailor name or leave blank for group…', 'wpnt' ); ?>" class="regular-text">
							<textarea id="wpnt-obs-note" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Observation note…', 'wpnt' ); ?>"></textarea>
							<select id="wpnt-obs-confidence">
								<option value=""><?php esc_html_e( 'Confidence level (optional)', 'wpnt' ); ?></option>
								<option value="high"><?php esc_html_e( 'High', 'wpnt' ); ?></option>
								<option value="medium"><?php esc_html_e( 'Medium', 'wpnt' ); ?></option>
								<option value="low"><?php esc_html_e( 'Low', 'wpnt' ); ?></option>
							</select>
							<button class="button" id="wpnt-add-obs" data-session-id="<?php echo esc_attr( $active_session ); ?>"><?php esc_html_e( 'Add Observation', 'wpnt' ); ?></button>
						</div>

						<p>
							<button class="button button-primary" id="wpnt-save-notes" data-session-id="<?php echo esc_attr( $active_session ); ?>">
								<?php esc_html_e( 'Save Session Notes', 'wpnt' ); ?>
							</button>
							<a class="button" href="<?php echo esc_url( get_edit_post_link( $active_session ) ); ?>">
								<?php esc_html_e( 'Full Session Edit', 'wpnt' ); ?>
							</a>
						</p>
					</div>
				<?php endif; ?>
			</div>
		</div>

	<?php endif; ?>
</div>
