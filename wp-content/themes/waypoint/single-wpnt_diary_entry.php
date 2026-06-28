<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();
	$entry_id        = get_the_ID();
	$viewer_id       = get_current_user_id();
	$athlete_id      = (int) get_post_meta( $entry_id, '_wpnt_athlete_id', true );
	$template_id     = (int) get_post_meta( $entry_id, '_wpnt_template_id', true );
	$event_type      = get_post_meta( $entry_id, '_wpnt_event_type', true );
	$session_id      = (int) get_post_meta( $entry_id, '_wpnt_session_id', true );
	$status          = get_post_meta( $entry_id, '_wpnt_status', true );
	$coach_response  = get_post_meta( $entry_id, '_wpnt_coach_response', true );
	$response_status = get_post_meta( $entry_id, '_wpnt_coach_response_status', true );
	$coach_id        = (int) get_post_meta( $entry_id, '_wpnt_coach_id', true );

	if ( ! class_exists( 'WPNT_Diary' ) || ! WPNT_Diary::can_view( $entry_id, $viewer_id ) ) {
		wp_safe_redirect( home_url() );
		exit;
	}

	$athlete     = $athlete_id ? get_user_by( 'id', $athlete_id ) : null;
	$coach       = $coach_id ? get_user_by( 'id', $coach_id ) : null;
	$responses   = WPNT_Diary::get_responses( $entry_id );
	$participant = class_exists( 'WPNT_Pack' ) ? WPNT_Pack::get_active_label( 'participant_label', __( 'Athlete', 'waypoint' ) ) : __( 'Athlete', 'waypoint' );
?>

<main id="primary" class="site-main" role="main">

	<div class="page-hero">
		<div class="container">
			<h1><?php the_title(); ?></h1>
			<?php if ( $athlete ) : ?>
				<p><?php printf( esc_html__( '%s: %s', 'waypoint' ), esc_html( $participant ), esc_html( $athlete->display_name ) ); ?></p>
			<?php endif; ?>
			<span class="status-pill status-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span>
		</div>
	</div>

	<div class="container mt-4">
		<div class="wp-two-col">
			<div>

				<!-- Entry body -->
				<?php if ( get_the_content() ) : ?>
					<div class="card mb-3">
						<h2 class="card-title"><?php esc_html_e( 'Entry', 'waypoint' ); ?></h2>
						<div class="entry-content"><?php the_content(); ?></div>
					</div>
				<?php endif; ?>

				<!-- Question responses -->
				<?php if ( ! empty( $responses ) ) : ?>
					<div class="card mb-3">
						<h2 class="card-title"><?php esc_html_e( 'Responses', 'waypoint' ); ?></h2>
						<?php foreach ( $responses as $r ) :
							$display = $r->response_text ?? $r->response_value ?? '';
							if ( $r->scale_type && $r->response_value && class_exists( 'WPNT_Diary' ) ) {
								$labels  = WPNT_Diary::get_scale_labels( $r->scale_type );
								$display = $labels[ $r->response_value ] ?? $r->response_value;
							}
						?>
							<div class="diary-response mb-2">
								<strong><?php echo esc_html( $r->question_id ); ?></strong>
								<p><?php echo esc_html( $display ); ?></p>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<!-- Coach response — always parent-visible when published -->
				<?php if ( $coach_response && $response_status === 'published' ) : ?>
					<div class="card mb-3" style="border-left:3px solid var(--color-primary,#006494)">
						<h2 class="card-title"><?php esc_html_e( "Coach's Response", 'waypoint' ); ?></h2>
						<?php if ( $coach ) : ?>
							<p class="tp-meta"><?php echo esc_html( $coach->display_name ); ?></p>
						<?php endif; ?>
						<?php echo wp_kses_post( wpautop( $coach_response ) ); ?>
					</div>
				<?php endif; ?>

			</div>

			<aside>
				<div class="card">
					<h3 class="card-title"><?php esc_html_e( 'Details', 'waypoint' ); ?></h3>
					<dl>
						<?php if ( $event_type ) : ?>
							<dt><?php esc_html_e( 'Event Type', 'waypoint' ); ?></dt>
							<dd><?php echo esc_html( ucfirst( $event_type ) ); ?></dd>
						<?php endif; ?>
						<dt><?php esc_html_e( 'Date', 'waypoint' ); ?></dt>
						<dd><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( get_the_date( 'Y-m-d' ) ) ) ); ?></dd>
						<?php if ( $session_id ) : ?>
							<dt><?php esc_html_e( 'Session', 'waypoint' ); ?></dt>
							<dd><a href="<?php echo esc_url( get_permalink( $session_id ) ); ?>"><?php echo esc_html( get_the_title( $session_id ) ); ?></a></dd>
						<?php endif; ?>
						<?php if ( $template_id ) : ?>
							<dt><?php esc_html_e( 'Template', 'waypoint' ); ?></dt>
							<dd><?php echo esc_html( get_the_title( $template_id ) ); ?></dd>
						<?php endif; ?>
					</dl>
				</div>
			</aside>
		</div>
	</div>

</main>

<?php endwhile; ?>

<?php get_footer(); ?>
