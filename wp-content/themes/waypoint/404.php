<?php get_header(); ?>

<main id="primary" class="site-main" role="main">
	<div class="container mt-4">
		<div class="card text-center">
			<h1><?php esc_html_e( '404 — Page Not Found', 'waypoint' ); ?></h1>
			<p><?php esc_html_e( 'The page you were looking for could not be found.', 'waypoint' ); ?></p>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-primary"><?php esc_html_e( 'Back to Home', 'waypoint' ); ?></a>
		</div>
	</div>
</main>

<?php get_footer(); ?>
