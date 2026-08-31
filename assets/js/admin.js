/*
 * Settings screen: the help bubbles.
 *
 * Tap/click pins a bubble open (hover/focus alone is lost on touch); Escape or
 * a click anywhere else closes it again. Enqueued as a file rather than printed
 * into the page (wp.org guideline "use wp_enqueue").
 */
( function () {
	function closeAll() {
		document.querySelectorAll( '.stmc-help.is-open' ).forEach( function ( b ) {
			b.classList.remove( 'is-open' );
			b.setAttribute( 'aria-expanded', 'false' );
		} );
	}

	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '.stmc-help' );
		if ( ! btn ) {
			closeAll();
			return;
		}
		var open = btn.classList.contains( 'is-open' );
		closeAll();
		if ( ! open ) {
			btn.classList.add( 'is-open' );
			btn.setAttribute( 'aria-expanded', 'true' );
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key ) {
			closeAll();
		}
	} );
}() );
