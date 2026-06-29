<?php
/**
 * Dashboard role switcher — shows links to every dashboard the current user
 * is entitled to access, highlighting the one they're on now.
 *
 * Expected arg: $current (string) — key of the active dashboard: 'coach', 'athlete', or 'parent'.
 */

$links   = wpnt_user_dashboard_links();
$current = $args['current'] ?? '';

if ( count( $links ) < 2 ) {
	return; // No switcher needed when only one dashboard is accessible.
}
?>
<div class="dashboard-switcher">
	<?php foreach ( $links as $link ) :
		$is_current = $link['key'] === $current;
	?>
		<a href="<?php echo esc_url( $link['url'] ); ?>"
		   class="dashboard-switcher-btn<?php echo $is_current ? ' is-current' : ''; ?>"
		   <?php echo $is_current ? 'aria-current="page"' : ''; ?>>
			<?php echo esc_html( $link['label'] ); ?>
		</a>
	<?php endforeach; ?>
</div>
