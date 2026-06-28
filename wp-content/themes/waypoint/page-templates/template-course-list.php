<?php
/**
 * Template Name: Course List
 * Template Post Type: page
 */

get_header();

$paged = max( 1, get_query_var( 'paged' ) );

$query = new WP_Query( array(
	'post_type'      => 'wpnt_course',
	'posts_per_page' => 12,
	'paged'          => $paged,
	'meta_query'     => array(
		array(
			'key'     => '_wpnt_status',
			'value'   => array( 'active', 'draft' ),
			'compare' => 'IN',
		),
	),
	'orderby'        => 'title',
	'order'          => 'ASC',
) );
?>

<main id="primary" class="site-main" role="main">

	<div class="page-hero">
		<div class="container">
			<h1><?php the_title(); ?></h1>
			<p><?php esc_html_e( 'Browse all available courses.', 'waypoint' ); ?></p>
		</div>
	</div>

	<div class="container mt-4">

		<?php if ( $query->have_posts() ) : ?>
			<div class="dashboard-grid">
				<?php while ( $query->have_posts() ) : $query->the_post();
					$course_id = get_the_ID();
					$status    = get_post_meta( $course_id, '_wpnt_status', true );
					$start     = get_post_meta( $course_id, '_wpnt_start_date', true );
					$end       = get_post_meta( $course_id, '_wpnt_end_date', true );
					$day_time  = get_post_meta( $course_id, '_wpnt_default_day_time', true );
					$node_id   = (int) get_post_meta( $course_id, '_wpnt_node_id', true );
					$node      = $node_id ? get_post( $node_id ) : null;

					$athletes         = waypoint_plugin_active() ? WPNT_Course::get_enrolled_athletes( $course_id ) : array();
					$participants_lbl = class_exists( 'WPNT_Pack' ) ? WPNT_Pack::get_active_label( 'participant_label_plural', __( 'Athletes', 'waypoint' ) ) : __( 'Athletes', 'waypoint' );
				?>
					<div class="card">
						<h2 class="card-title" style="font-size:1rem"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<?php if ( $node ) : ?><p style="font-size:.8rem;color:#6b7280"><?php echo esc_html( $node->post_title ); ?></p><?php endif; ?>
						<?php if ( $start ) : ?>
							<p style="font-size:.85rem">
								<?php echo esc_html( date_i18n( 'd M Y', strtotime( $start ) ) ); ?>
								<?php if ( $end ) : ?>&ndash; <?php echo esc_html( date_i18n( 'd M Y', strtotime( $end ) ) ); ?><?php endif; ?>
							</p>
						<?php endif; ?>
						<?php if ( $day_time ) : ?><p style="font-size:.85rem"><?php echo esc_html( $day_time ); ?></p><?php endif; ?>
						<p style="font-size:.8rem;color:#6b7280"><?php printf( esc_html__( '%d %s enrolled', 'waypoint' ), count( $athletes ), esc_html( strtolower( $participants_lbl ) ) ); ?></p>
						<span class="status-pill status-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span>
						<br>
						<a href="<?php the_permalink(); ?>" class="btn btn-outline btn-sm mt-1"><?php esc_html_e( 'View Course', 'waypoint' ); ?></a>
					</div>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>

			<div class="mt-3">
				<?php
				echo paginate_links( array(
					'total'   => $query->max_num_pages,
					'current' => $paged,
				) );
				?>
			</div>

		<?php else : ?>
			<div class="notice-wp info">
				<?php esc_html_e( 'No courses found. Check back soon.', 'waypoint' ); ?>
			</div>
		<?php endif; ?>

	</div>

</main>

<?php get_footer(); ?>
