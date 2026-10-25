<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header">
	<div class="site-header__inner">
		<a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php $logo = tb_logo_url(); ?>
			<?php if ( $logo ) : ?>
				<img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> home" width="120" height="68">
			<?php else : ?>
				<span class="site-header__name"><?php bloginfo( 'name' ); ?></span>
			<?php endif; ?>
		</a>

		<button class="site-header__toggle" type="button" aria-expanded="false" aria-controls="site-nav">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
			<span>Menu</span>
		</button>

		<div class="site-header__panel" id="site-nav">
			<nav class="site-nav" aria-label="Main">
				<?php
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'site-nav__list',
					'depth'          => 1,
					'fallback_cb'    => false,
				) );
				?>
			</nav>
			<?php get_search_form(); ?>
		</div>
	</div>
</header>
