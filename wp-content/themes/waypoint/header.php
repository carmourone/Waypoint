<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="https://gmpg.org/xfn/11">
<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
	<a class="sr-only skip-link" href="#primary"><?php esc_html_e( 'Skip to content', 'waypoint' ); ?></a>

	<header id="masthead" class="site-header" role="banner">
		<div class="site-header-inner">

			<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php $club = get_option( 'wpnt_club_name', '' ); ?>
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
					<path d="M3 17 L12 3 L21 17"/>
					<path d="M3 17 Q12 22 21 17"/>
				</svg>
				<?php if ( $club ) : ?>
					<?php echo esc_html( $club ); ?> <span>Waypoint</span>
				<?php else : ?>
					Way<span>point</span>
				<?php endif; ?>
			</a>

			<button class="nav-toggle" id="nav-toggle" aria-controls="primary-nav" aria-expanded="false">
				<span></span><span></span><span></span>
				<span class="sr-only"><?php esc_html_e( 'Toggle navigation', 'waypoint' ); ?></span>
			</button>

			<nav id="primary-nav" class="primary-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary Navigation', 'waypoint' ); ?>">
				<?php
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'menu_id'        => 'primary-menu',
					'container'      => false,
					'fallback_cb'    => false,
				) );
				?>
			</nav>

			<div class="header-actions">
				<?php if ( is_user_logged_in() ) :
					$user = wp_get_current_user();
					$role = waypoint_current_role();
				?>
					<span class="header-greeting"><?php echo esc_html( $user->display_name ); ?></span>
					<?php
					switch ( $role ) {
						case 'wpnt_coach':
						case 'administrator':
						case 'wpnt_org_admin':
							$dash_url = get_permalink( get_page_by_path( 'coach-dashboard' ) );
							break;
						case 'wpnt_parent':
							$dash_url = get_permalink( get_page_by_path( 'parent-dashboard' ) );
							break;
						default:
							$dash_url = get_permalink( get_page_by_path( 'my-dashboard' ) );
					}
					if ( $dash_url ) :
					?>
						<a href="<?php echo esc_url( $dash_url ); ?>" class="btn-header btn-header-outline"><?php esc_html_e( 'Dashboard', 'waypoint' ); ?></a>
					<?php endif; ?>
					<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="btn-header btn-header-outline"><?php esc_html_e( 'Log out', 'waypoint' ); ?></a>
				<?php else : ?>
					<a href="<?php echo esc_url( wp_login_url() ); ?>" class="btn-header btn-header-outline"><?php esc_html_e( 'Log in', 'waypoint' ); ?></a>
				<?php endif; ?>
			</div>

		</div>
	</header>

	<div id="content" class="site-content">
