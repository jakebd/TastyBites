
<footer class="site-footer">
	<div class="site-footer__inner">
		<div class="site-footer__about">
			<p class="site-footer__name"><?php bloginfo( 'name' ); ?></p>
			<p class="site-footer__tagline">Food Fuckery, Tasty Bites &amp; My 2 Cents. A chef's road trips, restaurant reviews and recipes, from 40-plus years in the business.</p>
			<ul class="site-footer__social">
				<li>
					<a class="social-link" href="https://www.facebook.com/profile.php?id=61564620414792" target="_blank" rel="noopener">
						<span class="screen-reader-text">Gengie's Tasty Bites on Facebook (opens in a new tab)</span>
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M15 3h-2.5A3.5 3.5 0 0 0 9 6.5V10H6.5v3.5H9V21h3.5v-7.5h2.8l.7-3.5h-3.5V7.2c0-.6.4-.9.9-.9H15z"/></svg>
					</a>
				</li>
				<li>
					<a class="social-link" href="https://www.instagram.com/foodfuckerytastybitesmy2cents/" target="_blank" rel="noopener">
						<span class="screen-reader-text">Gengie's Tasty Bites on Instagram (opens in a new tab)</span>
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="0.6" fill="currentColor"/></svg>
					</a>
				</li>
				<li>
					<a class="social-link" href="<?php echo esc_url( get_feed_link() ); ?>">
						<span class="screen-reader-text">Follow by RSS feed</span>
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M5 11a8 8 0 0 1 8 8M5 5a14 14 0 0 1 14 14"/><circle cx="6" cy="18" r="1.2" fill="currentColor"/></svg>
					</a>
				</li>
			</ul>
		</div>
		<nav class="site-footer__nav" aria-labelledby="footer-browse">
			<h2 id="footer-browse" class="site-footer__heading">Browse</h2>
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'site-footer__links',
				'depth'          => 1,
				'fallback_cb'    => false,
			) );
			?>
		</nav>
		<div class="site-footer__nav">
			<h2 class="site-footer__heading">More</h2>
			<ul class="site-footer__links">
				<li><a href="<?php echo esc_url( home_url( '/reviews/#how-i-rate' ) ); ?>">How I rate</a></li>
				<?php $tb_early = get_category_by_slug( TB_CAT_EARLY ); ?>
				<?php if ( $tb_early ) : ?>
					<li><a href="<?php echo esc_url( get_category_link( $tb_early ) ); ?>">The Early Years</a></li>
				<?php endif; ?>
				<?php $tb_updates = get_category_by_slug( 'updates' ); ?>
				<?php if ( $tb_updates ) : ?>
					<li><a href="<?php echo esc_url( get_category_link( $tb_updates ) ); ?>">Updates</a></li>
				<?php endif; ?>
				<li><a href="<?php echo esc_url( get_feed_link() ); ?>">RSS feed</a></li>
				<?php if ( get_privacy_policy_url() ) : ?>
					<li><a href="<?php echo esc_url( get_privacy_policy_url() ); ?>">Privacy</a></li>
				<?php endif; ?>
			</ul>
		</div>
	</div>
	<p class="site-footer__copy">© <?php echo esc_html( wp_date( 'Y' ) ); ?> Shawn Genge · <?php bloginfo( 'name' ); ?></p>
</footer>
<?php wp_footer(); ?>
</body>
</html>
