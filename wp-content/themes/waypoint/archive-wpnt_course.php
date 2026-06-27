<?php get_header(); ?>

<main id="primary" class="site-main" role="main">

	<div class="page-hero">
		<div class="container">
			<h1><?php esc_html_e( 'Courses', 'waypoint' ); ?></h1>
		</div>
	</div>

	<div class="container mt-4">
		<?php if ( have_posts() ) : ?>
			<div class="dashboard-grid">
				<?php while ( have_posts() ) : the_post();
					$course_id = get_the_ID();
					$status    = get_post_meta( $course_id, '_wpnt_status', true );
					$start     = get_post_meta( $course_id, '_wpnt_start_date', true );
					$day_time  = get_post_meta( $course_id, '_wpnt_default_day_time', true );
				?>
					<div class="card">
						<h2 class="card-title" style="font-size:1rem"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<?php if ( $start ) : ?><p style="font-size:.85rem"><?php echo esc_html( date_i18n( 'd M Y', strtotime( $start ) ) ); ?></p><?php endif; ?>
						<?php if ( $day_time ) : ?><p style="font-size:.85rem"><?php echo esc_html( $day_time ); ?></p><?php endif; ?>
						<span class="status-pill status-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span>
					</div>
				<?php endwhile; ?>
			</div>
			<?php the_posts_navigation(); ?>
		<?php else : ?>
			<p class="notice-wp info"><?php esc_html_e( 'No courses found.', 'waypoint' ); ?></p>
		<?php endif; ?>
	</div>

</main>

<?php get_footer(); ?>
