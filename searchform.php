<?php $tb_search_id = wp_unique_id( 'search-' ); ?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $tb_search_id ); ?>">Search the blog</label>
	<input class="search-form__input" type="search" id="<?php echo esc_attr( $tb_search_id ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Search trips, dishes, places">
	<button class="search-form__button" type="submit" aria-label="Search">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
	</button>
</form>
