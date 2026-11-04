/*
 * Settings screen: sections, search, and the help bubbles.
 *
 * All sections are in the page. Switching only shows another panel, so nothing
 * typed is lost, and one save covers the whole screen. Without this script the
 * navigation still works as plain links. Enqueued as a file rather than printed
 * into the page (wp.org guideline "use wp_enqueue").
 */
( function () {
	var root = document.querySelector( '.stmc-settings' );
	if ( ! root ) {
		return;
	}
	var form = document.getElementById( 'stmc-form' );
	var panels = root.querySelectorAll( '.stmc-panel' );
	var links = root.querySelectorAll( '.stmc-nav a[data-section]' );
	var search = root.querySelector( '.stmc-search' );
	var saveButton = root.querySelector( '.stmc-save' );
	var note = root.querySelector( '.stmc-saved' );
	var noResult = root.querySelector( '.stmc-noresult' );
	var dirty = false;
	var cssEditor = null;

	function open( slug, push ) {
		var found = false;
		panels.forEach( function ( panel ) {
			var on = panel.getAttribute( 'data-section' ) === slug;
			panel.hidden = ! on;
			if ( on ) {
				found = true;
				// A panel that saves by itself has no use for the button up top.
				saveButton.hidden = '0' === panel.getAttribute( 'data-form' );
				note.hidden = saveButton.hidden;
			}
		} );
		// CodeMirror cannot measure itself while its panel is hidden.
		if ( cssEditor && 'css' === slug ) {
			cssEditor.codemirror.refresh();
		}
		if ( ! found ) {
			return false;
		}
		links.forEach( function ( link ) {
			var on = link.getAttribute( 'data-section' ) === slug;
			link.classList.toggle( 'is-current', on );
			if ( on ) {
				link.setAttribute( 'aria-current', 'page' );
			} else {
				link.removeAttribute( 'aria-current' );
			}
		} );

		var url = new URL( window.location.href );
		url.searchParams.set( 'section', slug );
		url.searchParams.delete( 'tab' );
		url.searchParams.delete( 'settings-updated' );
		if ( push ) {
			window.history.pushState( { section: slug }, '', url );
		}
		// After saving, WordPress returns to this address: the same section.
		var referer = form ? form.querySelector( 'input[name="_wp_http_referer"]' ) : null;
		if ( referer ) {
			referer.value = url.pathname + url.search;
		}
		return true;
	}

	root.addEventListener( 'click', function ( event ) {
		var link = event.target.closest( 'a[data-section]' );
		if ( ! link || event.ctrlKey || event.metaKey || event.shiftKey ) {
			return;
		}
		if ( search.value ) {
			search.value = '';
			filter( '' );
		}
		if ( open( link.getAttribute( 'data-section' ), true ) ) {
			event.preventDefault();
			window.scrollTo( 0, 0 );
		}
	} );

	window.addEventListener( 'popstate', function () {
		var slug = new URL( window.location.href ).searchParams.get( 'section' );
		open( slug || 'overview', false );
	} );

	/* Search across every section of the form. */

	var openPanel = '';

	function filter( text ) {
		var query = text.trim().toLowerCase();
		var hits = 0;

		if ( ! query ) {
			root.classList.remove( 'is-searching' );
			noResult.hidden = true;
			root.querySelectorAll( '.stmc-rows tr, .stmc-card' ).forEach( function ( element ) {
				element.hidden = false;
			} );
			if ( openPanel ) {
				open( openPanel, false );
				openPanel = '';
			}
			return;
		}

		if ( ! root.classList.contains( 'is-searching' ) ) {
			var visible = root.querySelector( '.stmc-panel:not([hidden])' );
			openPanel = visible ? visible.getAttribute( 'data-section' ) : '';
		}
		root.classList.add( 'is-searching' );

		panels.forEach( function ( panel ) {
			if ( '0' === panel.getAttribute( 'data-form' ) ) {
				panel.hidden = true;
				return;
			}
			var inPanel = 0;
			panel.querySelectorAll( '.stmc-card' ).forEach( function ( card ) {
				var inCard = 0;
				card.querySelectorAll( '.stmc-rows tr' ).forEach( function ( row ) {
					// The bubbles carry the explanation, so they are searched too.
					var match = -1 !== row.textContent.toLowerCase().indexOf( query );
					row.hidden = ! match;
					if ( match ) {
						inCard++;
					}
				} );
				card.hidden = ! inCard;
				if ( inCard && 'DETAILS' === card.tagName ) {
					card.open = true;
				}
				inPanel += inCard;
			} );
			panel.hidden = ! inPanel;
			hits += inPanel;
		} );
		saveButton.hidden = false;
		noResult.hidden = 0 !== hits;
	}

	search.addEventListener( 'input', function () {
		filter( search.value );
	} );

	/* Unsaved changes. */

	if ( form ) {
		var mark = function () {
			dirty = true;
			note.textContent = note.getAttribute( 'data-unsaved' );
			note.classList.add( 'is-dirty' );
		};
		form.addEventListener( 'input', mark );
		form.addEventListener( 'change', function ( event ) {
			mark();
			if ( 'color' === event.target.type && event.target.nextElementSibling ) {
				event.target.nextElementSibling.textContent = event.target.value;
			}
		} );
		form.addEventListener( 'submit', function () {
			dirty = false;
		} );
		window.addEventListener( 'beforeunload', function ( event ) {
			if ( dirty ) {
				event.preventDefault();
				event.returnValue = '';
			}
		} );
	}

	var cssField = document.getElementById( 'stmc-design-custom_css' );
	if ( cssField && window.stmcCssEditor && window.wp && window.wp.codeEditor ) {
		cssEditor = window.wp.codeEditor.initialize( cssField, window.stmcCssEditor );
		cssEditor.codemirror.on( 'change', function () {
			cssEditor.codemirror.save();
			form.dispatchEvent( new Event( 'input' ) );
		} );
	}

	var current = root.querySelector( '.stmc-panel:not([hidden])' );
	if ( current ) {
		open( current.getAttribute( 'data-section' ), false );
	}

	/*
	 * Help bubbles. Tap/click pins a bubble open (hover/focus alone is lost on
	 * touch); Escape or a click anywhere else closes it again.
	 */

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
		var isOpen = btn.classList.contains( 'is-open' );
		closeAll();
		if ( ! isOpen ) {
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
