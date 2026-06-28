<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'active';
$plans = get_posts( array(
	'post_type'      => 'wpnt_training_plan',
	'posts_per_page' => 50,
	'meta_key'       => '_wpnt_status',
	'meta_value'     => $status_filter,
	'orderby'        => 'date',
	'order'          => 'DESC',
) );

$status_options = array(
	'draft'     => __( 'Draft', 'wpnt' ),
	'approved'  => __( 'Approved', 'wpnt' ),
	'active'    => __( 'Active', 'wpnt' ),
	'completed' => __( 'Completed', 'wpnt' ),
	'cancelled' => __( 'Cancelled', 'wpnt' ),
);
?>
<div class="wrap wpnt-admin-wrap">
	<h1>
		<?php esc_html_e( 'Training Plans', 'wpnt' ); ?>
		<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wpnt_training_plan' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'wpnt' ); ?></a>
	</h1>

	<ul class="subsubsub">
		<?php foreach ( $status_options as $val => $label ) : ?>
			<li>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpnt-training-plans&status=' . $val ) ); ?>"
				   class="<?php echo $status_filter === $val ? 'current' : ''; ?>">
					<?php echo esc_html( $label ); ?>
				</a>
				<?php echo $val !== array_key_last( $status_options ) ? ' | ' : ''; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if ( empty( $plans ) ) : ?>
		<div class="notice notice-info"><p><?php printf( esc_html__( 'No %s training plans found.', 'wpnt' ), esc_html( $status_options[ $status_filter ] ?? $status_filter ) ); ?></p></div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Title', 'wpnt' ); ?></th>
					<th><?php echo esc_html( WPNT_Pack::get_active_label( 'participant_label', __( 'Athlete', 'wpnt' ) ) ); ?></th>
					<th><?php esc_html_e( 'Origin', 'wpnt' ); ?></th>
					<th><?php esc_html_e( 'Scope', 'wpnt' ); ?></th>
					<th><?php esc_html_e( 'Target Date', 'wpnt' ); ?></th>
					<th><?php esc_html_e( 'Coach', 'wpnt' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $plans as $plan ) :
					$athlete_id = (int) get_post_meta( $plan->ID, '_wpnt_athlete_id', true );
					$coach_id   = (int) get_post_meta( $plan->ID, '_wpnt_assigned_coach', true );
					$origin     = get_post_meta( $plan->ID, '_wpnt_origin', true );
					$scope      = get_post_meta( $plan->ID, '_wpnt_scope', true );
					$target     = get_post_meta( $plan->ID, '_wpnt_target_date', true );
					$athlete    = $athlete_id ? get_user_by( 'id', $athlete_id ) : null;
					$coach      = $coach_id ? get_user_by( 'id', $coach_id ) : null;
				?>
					<tr>
						<td><a href="<?php echo esc_url( get_edit_post_link( $plan->ID ) ); ?>"><?php echo esc_html( $plan->post_title ); ?></a></td>
						<td><?php echo $athlete ? esc_html( $athlete->display_name ) : '—'; ?></td>
						<td><?php echo $origin ? esc_html( ucwords( str_replace( '_', ' ', $origin ) ) ) : '—'; ?></td>
						<td><?php echo $scope ? esc_html( ucwords( str_replace( '_', ' ', $scope ) ) ) : '—'; ?></td>
						<td><?php echo $target ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $target ) ) ) : '—'; ?></td>
						<td><?php echo $coach ? esc_html( $coach->display_name ) : '—'; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
