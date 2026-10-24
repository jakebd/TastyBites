
<footer class="site-footer">
	<div class="site-footer__inner">
		<p class="site-footer__name"><?php bloginfo( 'name' ); ?> · Food Fuckery, Tasty Bites &amp; My 2 Cents</p>
		<ul class="site-footer__social">
			<li>
				<a class="social-link" href="https://www.facebook.com/profile.php?id=61564620414792" target="_blank" rel="noopener" aria-label="Gengie's Tasty Bites on Facebook (opens in a new tab)">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h-2.5A3.5 3.5 0 0 0 9 6.5V10H6.5v3.5H9V21h3.5v-7.5h2.8l.7-3.5h-3.5V7.2c0-.6.4-.9.9-.9H15z"/></svg>
				</a>
			</li>
			<li>
				<a class="social-link" href="https://www.instagram.com/foodfuckerytastybitesmy2cents/" target="_blank" rel="noopener" aria-label="Gengie's Tasty Bites on Instagram (opens in a new tab)">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.7" r="0.6" fill="currentColor"/></svg>
				</a>
			</li>
			<li>
				<a class="social-link" href="<?php echo esc_url( get_feed_link() ); ?>" aria-label="Follow by RSS feed">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 11a8 8 0 0 1 8 8M5 5a14 14 0 0 1 14 14"/><circle cx="6" cy="18" r="1.2" fill="currentColor"/></svg>
				</a>
			</li>
		</ul>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
