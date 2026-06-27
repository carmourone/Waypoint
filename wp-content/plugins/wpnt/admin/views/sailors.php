<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
$args   = array(
	'role'    => 'wpnt_sailor',
	'orderby' => 'display_name',
	'order'   => 'ASC',
	'number'  => 50,
);
if ( $search ) {
	$args['search']         = '*' . $search . '*';
	$args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
}
$sailors = get_users( $args );
?>
<div class="wrap wpnt-admin-wrap">
	<h1><?php esc_html_e( 'Sailors', 'wpnt' ); ?></h1>

	<form method="get" class="wpnt-search-form">
		<input type="hidden" name="page" value="wpnt-sailors">
		<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search sailors…', 'wpnt' ); ?>" class="regular-text">
		<?php submit_button( __( 'Search', 'wpnt' ), 'secondary', '', false ); ?>
	</form>

	<?php if ( empty( $sailors ) ) : ?>
		<div class="notice notice-info"><p><?php esc_html_e( 'No sailors found.', 'wpnt' ); ?></p></div>
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
				<?php foreach ( $sailors as $sailor ) :
					// Find courses this sailor is enrolled in.
					global $wpdb;
					$enrolled_courses = $wpdb->get_results( $wpdb->prepare(
						"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wpnt_enrolled_sailors' AND meta_value LIKE %s",
						'%' . $wpdb->esc_like( (string) $sailor->ID ) . '%'
					) );
				?>
					<tr>
						<td><a href="<?php echo esc_url( get_edit_user_link( $sailor->ID ) ); ?>"><?php echo esc_html( $sailor->display_name ); ?></a></td>
						<td><?php echo esc_html( $sailor->user_email ); ?></td>
						<td><?php echo esc_html( count( $enrolled_courses ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( get_edit_user_link( $sailor->ID ) ); ?>"><?php esc_html_e( 'Edit', 'wpnt' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
