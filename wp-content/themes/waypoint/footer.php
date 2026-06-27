	</div><!-- #content -->

	<footer id="colophon" class="site-footer" role="contentinfo">
		<div class="site-footer-inner">
			<div class="footer-brand">
				<?php $club = get_option( 'wpnt_club_name', '' ); ?>
				<strong><?php echo $club ? esc_html( $club ) . ' — ' : ''; ?>Waypoint</strong>
				<br>
				<small><?php esc_html_e( 'Sailing coaching and program delivery', 'waypoint' ); ?></small>
			</div>

			<nav class="footer-nav" aria-label="<?php esc_attr_e( 'Footer Navigation', 'waypoint' ); ?>">
				<?php
				wp_nav_menu( array(
					'theme_location' => 'footer',
					'container'      => false,
					'fallback_cb'    => false,
					'depth'          => 1,
				) );
				?>
			</nav>

			<div class="footer-credit">
				<small><?php printf( esc_html__( '&copy; %d %s', 'waypoint' ), date( 'Y' ), esc_html( get_bloginfo( 'name' ) ) ); ?></small>
			</div>
		</div>
	</footer>

</div><!-- #page -->

<?php wp_footer(); ?>

<script>
(function() {
  var toggle = document.getElementById('nav-toggle');
  var nav    = document.getElementById('primary-nav');
  if (toggle && nav) {
    toggle.addEventListener('click', function() {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }
})();
</script>
</body>
</html>
