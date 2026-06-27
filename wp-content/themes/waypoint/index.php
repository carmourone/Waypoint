<?php get_header(); ?>

<main id="primary" class="site-main" role="main">

	<?php if ( have_posts() ) : ?>

		<div class="page-hero">
			<div class="container">
				<h1><?php the_archive_title(); ?></h1>
				<?php the_archive_description( '<p>', '</p>' ); ?>
			</div>
		</div>

		<div class="container mt-4">
			<div class="post-list">
				<?php while ( have_posts() ) : the_post(); ?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'card mb-2' ); ?>>
						<h2 class="card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<div class="post-excerpt"><?php the_excerpt(); ?></div>
						<a href="<?php the_permalink(); ?>" class="btn btn-outline btn-sm mt-1"><?php esc_html_e( 'Read more', 'waypoint' ); ?></a>
					</article>
				<?php endwhile; ?>
			</div>

			<?php the_posts_navigation(); ?>
		</div>

	<?php else : ?>

		<div class="container mt-4">
			<div class="card">
				<h2><?php esc_html_e( 'Nothing here yet.', 'waypoint' ); ?></h2>
				<p><?php esc_html_e( 'No content was found. Please check back later.', 'waypoint' ); ?></p>
			</div>
		</div>

	<?php endif; ?>

</main>

<?php get_footer(); ?>
