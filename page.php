<?php
/**
 * Generic page.
 */
get_header();

while ( have_posts() ) :
	the_post();
	?>
	<main id="main" class="single">
		<header class="entry__header wrap wrap--narrow">
			<h1 class="entry__title"><?php the_title(); ?></h1>
		</header>
		<div class="entry__content prose">
			<?php the_content(); ?>
		</div>
	</main>
	<?php
endwhile;

get_footer();
