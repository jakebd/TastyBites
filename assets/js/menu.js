// Phone menu: the Menu button shows/hides the nav and search panel.
( function () {
	var toggle = document.querySelector( '.site-header__toggle' );
	var panel = document.getElementById( 'site-nav' );
	if ( ! toggle || ! panel ) {
		return;
	}
	toggle.addEventListener( 'click', function () {
		var open = toggle.getAttribute( 'aria-expanded' ) === 'true';
		toggle.setAttribute( 'aria-expanded', String( ! open ) );
		panel.classList.toggle( 'is-open', ! open );
	} );
} )();
