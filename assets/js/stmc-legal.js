/**
 * STM Smart Checkout — legal text overlay.
 *
 * Delegated on document (legal checkboxes get re-rendered on every
 * update_order_review). Fetches same-origin, extracts the main content,
 * strips anything interactive, caches per URL. Any failure falls back to the
 * link's native target="_blank".
 */
( function () {
	'use strict';

	var modal = null;
	var opener = null;
	var cache = {};

	// The last entry is the block checkout: our consent field renders its label
	// inside WooCommerce's checkbox component, links included.
	var LINK_SELECTOR = '.wc-gzd-checkbox-placeholder a, .woocommerce-terms-and-conditions-wrapper a, a.stmc-legal-link, .wc-block-components-checkbox__label a';
	var CONTENT_SELECTORS = [ 'main .entry-content', '#main .wf-container-main', '.entry-content', '#main', 'main', 'article' ];

	function el() {
		if ( ! modal ) {
			modal = document.getElementById( 'stmc-modal' );
		}
		return modal;
	}

	function show( open ) {
		var m = el();
		if ( ! m ) {
			return;
		}
		if ( open ) {
			m.removeAttribute( 'hidden' );
			document.documentElement.classList.add( 'stmc-modal-open' );
			var x = m.querySelector( '.stmc-modal__x' );
			if ( x ) {
				x.focus();
			}
		} else {
			m.setAttribute( 'hidden', '' );
			document.documentElement.classList.remove( 'stmc-modal-open' );
			if ( opener && opener.focus ) {
				opener.focus();
			}
			opener = null;
		}
	}

	/** Reduce a fetched page to readable text: no scripts, forms, chrome, hero. */
	function sanitize( source ) {
		var box = document.createElement( 'div' );
		while ( source.firstChild ) {
			box.appendChild( source.firstChild );
		}
		var kill = box.querySelectorAll( 'script,style,link,noscript,iframe,form,button,input,select,textarea,aside,nav,header,footer,section[class*="hero"],div[class*="hero"]' );
		for ( var i = kill.length - 1; i >= 0; i-- ) {
			kill[ i ].parentNode.removeChild( kill[ i ] );
		}
		var h1 = box.querySelector( 'h1' );
		if ( h1 && h1.parentNode ) {
			h1.parentNode.removeChild( h1 ); // Title already sits in the dialog head.
		}
		// In-page anchor links (tables of contents, jump markers) are useless
		// inside the overlay — keep their text, drop the link.
		box.querySelectorAll( 'a[href^="#"]' ).forEach( function ( a ) {
			var t = document.createTextNode( a.textContent );
			a.parentNode.replaceChild( t, a );
		} );
		return box;
	}

	function load( url, fallbackHref ) {
		var body = el().querySelector( '.stmc-modal__body' );
		if ( cache[ url ] ) {
			body.innerHTML = '';
			body.appendChild( cache[ url ].cloneNode( true ) );
			body.scrollTop = 0;
			return;
		}
		body.textContent = body.getAttribute( 'data-loading' ) || '…';
		fetch( url, { credentials: 'same-origin' } )
			.then( function ( r ) {
				if ( ! r.ok ) {
					throw new Error( 'http ' + r.status );
				}
				return r.text();
			} )
			.then( function ( html ) {
				var doc = new DOMParser().parseFromString( html, 'text/html' );
				var source = null;
				for ( var i = 0; i < CONTENT_SELECTORS.length && ! source; i++ ) {
					source = doc.querySelector( CONTENT_SELECTORS[ i ] );
				}
				var clean = sanitize( source || doc.body );
				if ( ( clean.textContent || '' ).replace( /\s+/g, '' ).length < 200 ) {
					throw new Error( 'empty' );
				}
				cache[ url ] = clean;
				body.innerHTML = '';
				body.appendChild( clean.cloneNode( true ) );
				body.scrollTop = 0;
			} )
			.catch( function () {
				body.innerHTML = '';
				var p = document.createElement( 'p' );
				p.textContent = body.getAttribute( 'data-error' ) + ' ';
				var a = document.createElement( 'a' );
				a.href = fallbackHref;
				a.target = '_blank';
				a.rel = 'noopener';
				a.textContent = body.getAttribute( 'data-open-page' );
				p.appendChild( a );
				body.appendChild( p );
			} );
	}

	document.addEventListener( 'click', function ( e ) {
		var m = el();
		if ( ! m ) {
			return;
		}

		if ( ! m.hasAttribute( 'hidden' ) ) {
			var close = e.target.closest ? e.target.closest( '[data-stmc-modal-close]' ) : null;
			if ( close ) {
				e.preventDefault();
				show( false );
				return;
			}
			// Links inside the loaded legal text must not navigate the checkout away.
			var inner = e.target.closest ? e.target.closest( '.stmc-modal__body a' ) : null;
			if ( inner && inner.getAttribute( 'href' ) ) {
				e.preventDefault();
				window.open( inner.href, '_blank', 'noopener' );
				return;
			}
		}

		var link = e.target.closest ? e.target.closest( LINK_SELECTOR ) : null;
		if ( ! link || ! link.getAttribute( 'href' ) ) {
			return;
		}
		e.preventDefault();
		e.stopPropagation();

		var target;
		try {
			target = new URL( link.getAttribute( 'href' ), location.href );
			target.protocol = location.protocol;
			target.host = location.host; // Same-origin, and the current language's version.
		} catch ( err ) {
			window.open( link.href, '_blank', 'noopener' );
			return;
		}

		opener = link;
		el().querySelector( '.stmc-modal__title' ).textContent = link.getAttribute( 'data-title' ) || ( link.textContent || '' ).trim();
		show( true );
		load( target.href, link.href );
	}, true );

	document.addEventListener( 'keydown', function ( e ) {
		var m = el();
		if ( e.key === 'Escape' && m && ! m.hasAttribute( 'hidden' ) ) {
			show( false );
		}
	} );
} )();
