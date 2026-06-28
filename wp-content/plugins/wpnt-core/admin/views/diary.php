<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tab    = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'pending';
$paged  = max( 1, absint( $_GET['paged'] ?? 1 ) );

if ( $tab === 'pending' ) {
	$entries = WPNT_Diary::get_pending_review();
} else {
	$entries = get_posts( array(
		'post_type'      => 'wpnt_diary_entry',
		'posts_per_page' => 30,
		'paged'          => $paged,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_query'     => array(
			array( 'key' => '_wpnt_status', 'value' => 'reviewed' ),
		),
	) );
}

$participant_lbl = WPNT_Pack::get_active_label( 'participant_label', __( 'Athlete', 'wpnt' ) );
?>
<div class="wrap wpnt-admin-wrap">
	<h1><?php esc_html_e( 'Athlete Diary', 'wpnt' ); ?></h1>

	<ul class="subsubsub">
		<li>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpnt-diary&tab=pending' ) ); ?>"
			   class="<?php echo $tab === 'pending' ? 'current' : ''; ?>">
				<?php esc_html_e( 'Pending Review', 'wpnt' ); ?>
				<?php if ( $tab === 'pending' ) : ?> <span class="count">(<?php echo count( $entries ); ?>)</span><?php endif; ?>
			</a> |
		</li>
		<li>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpnt-diary&tab=reviewed' ) ); ?>"
			   class="<?php echo $tab === 'reviewed' ? 'current' : ''; ?>">
				<?php esc_html_e( 'Reviewed', 'wpnt' ); ?>
			</a>
		</li>
	</ul>

	<?php if ( empty( $entries ) ) : ?>
		<div class="notice notice-info"><p>
			<?php echo $tab === 'pending'
				? esc_html__( 'No entries awaiting review.', 'wpnt' )
				: esc_html__( 'No reviewed entries found.', 'wpnt' ); ?>
		</p></div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Title', 'wpnt' ); ?></th>
					<th><?php echo esc_html( $participant_lbl ); ?></th>
					<th><?php esc_html_e( 'Event Type', 'wpnt' ); ?></th>
					<th><?php esc_html_e( 'Status', 'wpnt' ); ?></th>
					<th><?php esc_html_e( 'Date', 'wpnt' ); ?></th>
					<th><?php esc_html_e( 'Coach Response', 'wpnt' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $entries as $entry ) :
					$athlete_id     = (int) get_post_meta( $entry->ID, '_wpnt_athlete_id', true );
					$event_type     = get_post_meta( $entry->ID, '_wpnt_event_type', true );
					$status         = get_post_meta( $entry->ID, '_wpnt_status', true );
					$response_status = get_post_meta( $entry->ID, '_wpnt_coach_response_status', true );
					$athlete        = $athlete_id ? get_user_by( 'id', $athlete_id ) : null;
				?>
					<tr>
						<td><a href="<?php echo esc_url( get_edit_post_link( $entry->ID ) ); ?>"><?php echo esc_html( $entry->post_title ); ?></a></td>
						<td><?php echo $athlete ? esc_html( $athlete->display_name ) : '—'; ?></td>
						<td><?php echo $event_type ? esc_html( ucfirst( $event_type ) ) : '—'; ?></td>
						<td><span class="status-pill"><?php echo esc_html( ucfirst( $status ) ); ?></span></td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $entry->post_date ) ) ); ?></td>
						<td><?php echo $response_status ? esc_html( ucfirst( $response_status ) ) : '—'; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
