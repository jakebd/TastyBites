<?php
/**
 * Page not found.
 */
get_header();
?>
<main id="main" class="archive">
	<header class="archive__header wrap">
		<span class="stamp stamp--tilt">Wrong turn</span>
		<h1 class="archive__title">This page took the back roads</h1>
		<p class="archive__intro">We couldn't find it. Try searching, or head back to the <a href="<?php echo esc_url( home_url( '/' ) ); ?>">homepage</a>.</p>
		<div class="archive__search"><?php get_search_form(); ?></div>
	</header>
</main>
<?php
get_footer();
