<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$participant_lbl  = WPNT_Pack::get_active_label( 'participant_label', __( 'Athlete', 'wpnt' ) );
$participants_lbl = WPNT_Pack::get_active_label( 'participant_label_plural', __( 'Athletes', 'wpnt' ) );

$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
$args   = array(
	'role'    => 'wpnt_athlete',
	'orderby' => 'display_name',
	'order'   => 'ASC',
	'number'  => 50,
);
if ( $search ) {
	$args['search']         = '*' . $search . '*';
	$args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
}
$athletes = get_users( $args );
?>
<div class="wrap wpnt-admin-wrap">
	<h1><?php echo esc_html( $participants_lbl ); ?></h1>

	<form method="get" class="wpnt-search-form">
		<input type="hidden" name="page" value="wpnt-sailors">
		<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php echo esc_attr( sprintf( __( 'Search %s…', 'wpnt' ), strtolower( $participants_lbl ) ) ); ?>" class="regular-text">
		<?php submit_button( __( 'Search', 'wpnt' ), 'secondary', '', false ); ?>
	</form>

	<?php if ( empty( $athletes ) ) : ?>
		<div class="notice notice-info"><p><?php echo esc_html( sprintf( __( 'No %s found.', 'wpnt' ), strtolower( $participants_lbl ) ) ); ?></p></div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'wpnt' ); ?></th>
					<th><?php esc_html_e( 'Email', 'wpnt' ); ?></th>
					<th><?php esc_html_e( 'Active Courses', 'wpnt' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'wpnt' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $athletes as $athlete ) :
					global $wpdb;
					$enrolled_courses = $wpdb->get_results( $wpdb->prepare(
						"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wpnt_enrolled_sailors' AND meta_value LIKE %s",
						'%' . $wpdb->esc_like( (string) $athlete->ID ) . '%'
					) );
				?>
					<tr>
						<td><a href="<?php echo esc_url( get_edit_user_link( $athlete->ID ) ); ?>"><?php echo esc_html( $athlete->display_name ); ?></a></td>
						<td><?php echo esc_html( $athlete->user_email ); ?></td>
						<td><?php echo esc_html( count( $enrolled_courses ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( get_edit_user_link( $athlete->ID ) ); ?>"><?php esc_html_e( 'Edit', 'wpnt' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
