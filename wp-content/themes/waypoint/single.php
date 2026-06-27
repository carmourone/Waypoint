<?php get_header(); ?>

<main id="primary" class="site-main" role="main">
	<div class="container mt-4">
		<?php while ( have_posts() ) : the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

				<header class="entry-header">
					<h1 class="entry-title"><?php the_title(); ?></h1>
					<div class="entry-meta">
						<?php the_date(); ?> &mdash; <?php the_author(); ?>
					</div>
				</header>

				<div class="entry-content mt-3">
					<?php the_content(); ?>
				</div>

			</article>
		<?php endwhile; ?>
	</div>
</main>

<?php get_footer(); ?>
