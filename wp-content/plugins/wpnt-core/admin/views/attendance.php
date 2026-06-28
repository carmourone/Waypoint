<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$session_id = isset( $_GET['session_id'] ) ? absint( $_GET['session_id'] ) : 0;

// Default to the next scheduled session.
if ( ! $session_id ) {
	$upcoming = get_posts( array(
		'post_type'      => 'wpnt_session',
		'posts_per_page' => 1,
		'meta_key'       => '_wpnt_status',
		'meta_value'     => 'scheduled',
		'orderby'        => 'meta_value',
		'meta_key'       => '_wpnt_scheduled_start',
		'order'          => 'ASC',
	) );
	$session_id = ! empty( $upcoming ) ? $upcoming[0]->ID : 0;
}

$sessions = get_posts( array(
	'post_type'      => 'wpnt_session',
	'posts_per_page' => 30,
	'meta_key'       => '_wpnt_scheduled_start',
	'orderby'        => 'meta_value',
	'order'          => 'DESC',
	'post_status'    => 'publish',
) );
?>
<div class="wrap wpnt-admin-wrap">
	<h1><?php esc_html_e( 'Attendance', 'wpnt' ); ?></h1>

	<div class="wpnt-filter-bar">
		<form method="get">
			<input type="hidden" name="page" value="wpnt-attendance">
			<label for="wpnt-session-select"><?php esc_html_e( 'Session:', 'wpnt' ); ?></label>
			<select id="wpnt-session-select" name="session_id" onchange="this.form.submit()">
				<option value=""><?php esc_html_e( '— Select session —', 'wpnt' ); ?></option>
				<?php foreach ( $sessions as $s ) :
					$start = get_post_meta( $s->ID, '_wpnt_scheduled_start', true );
				?>
					<option value="<?php echo esc_attr( $s->ID ); ?>"<?php selected( $session_id, $s->ID ); ?>>
						<?php echo esc_html( $s->post_title ); ?><?php if ( $start ) : ?> (<?php echo esc_html( date( 'd M Y', strtotime( $start ) ) ); ?>)<?php endif; ?>
					</option>
				<?php endforeach; ?>
			</select>
		</form>
	</div>

	<?php if ( $session_id ) : ?>
		<h2><?php echo esc_html( get_the_title( $session_id ) ); ?></h2>
		<?php echo WPNT_Attendance::render_checklist( $session_id ); ?>
	<?php else : ?>
		<div class="notice notice-info"><p><?php esc_html_e( 'Select a session above to mark attendance.', 'wpnt' ); ?></p></div>
	<?php endif; ?>
</div>
