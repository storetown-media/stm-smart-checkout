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
				if ( 'check' === slug ) {
					runCheck( false );
				}
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

	/* Trust items: own image from the media library, and the preview. */

	var trustEdits = root.querySelectorAll( '.stmc-trust-edit' );
	var preview = root.querySelector( '.stmc-tp' );

	function trustSymbol( edit ) {
		var radio = edit.querySelector( 'input[type="radio"]:checked' );
		var own = edit.querySelector( '.stmc-icon__own img' );
		if ( radio && 'custom' === radio.value && own ) {
			var image = document.createElement( 'img' );
			image.src = own.src;
			image.alt = '';
			return image;
		}
		var fallback = edit.querySelector( 'input[type="radio"][value="' + edit.getAttribute( 'data-default' ) + '"]' );
		var svg = ( radio && 'custom' !== radio.value ? radio : fallback ).parentNode.querySelector( 'svg' );
		return svg.cloneNode( true );
	}

	function trustLine( target, items ) {
		target.textContent = '';
		items.forEach( function ( item ) {
			var span = document.createElement( 'span' );
			span.appendChild( item.symbol.cloneNode( true ) );
			span.appendChild( document.createTextNode( item.text ) );
			target.appendChild( span );
		} );
	}

	function drawTrust() {
		if ( ! preview ) {
			return;
		}
		var items = [];
		trustEdits.forEach( function ( edit ) {
			var text = edit.querySelector( 'input[type="text"]' ).value.trim();
			if ( text ) {
				items.push( { symbol: trustSymbol( edit ), text: text } );
			}
		} );
		if ( ! items.length ) {
			items.push( { symbol: trustSymbol( trustEdits[ 0 ] ), text: preview.getAttribute( 'data-fallback' ) } );
		}
		trustLine( preview.querySelector( '.stmc-tp__band' ), items );
		trustLine( preview.querySelector( '.stmc-tp__row' ), items );

		var under = document.getElementById( 'stmc-trust-under_button' );
		preview.querySelector( '.stmc-tp__under' ).hidden = !! under && ! under.checked;
		var accent = document.getElementById( 'stmc-design-accent' );
		if ( accent ) {
			preview.style.setProperty( '--stmc-tp-accent', accent.value );
		}
	}

	function trustChanged( edit ) {
		edit.querySelector( '.stmc-trust-media' ).dispatchEvent( new Event( 'input', { bubbles: true } ) );
	}

	function setOwnImage( edit, id, url ) {
		var holder = edit.querySelector( '.stmc-icon__own' );
		holder.textContent = '';
		if ( url ) {
			var image = document.createElement( 'img' );
			image.src = url;
			image.alt = '';
			holder.appendChild( image );
		}
		edit.querySelector( '.stmc-trust-media' ).value = id;
		edit.querySelector( '.stmc-own-actions' ).hidden = ! url;
	}

	function checkIcon( edit, value ) {
		var radio = edit.querySelector( 'input[type="radio"][value="' + value + '"]' );
		if ( radio ) {
			radio.checked = true;
		}
	}

	function chooseImage( edit, before ) {
		if ( ! window.wp || ! window.wp.media ) {
			return;
		}
		var chosen = false;
		var frame = window.wp.media( {
			title: edit.getAttribute( 'data-title' ),
			button: { text: edit.getAttribute( 'data-button' ) },
			library: { type: 'image' },
			multiple: false,
		} );
		frame.on( 'select', function () {
			var file = frame.state().get( 'selection' ).first().toJSON();
			var sizes = file.sizes || {};
			chosen = true;
			setOwnImage( edit, file.id, ( sizes.medium || sizes.full || file ).url );
			checkIcon( edit, 'custom' );
			trustChanged( edit );
		} );
		// WordPress closes the frame before it reports the selection.
		frame.on( 'close', function () {
			setTimeout( function () {
				if ( ! chosen && ! edit.querySelector( '.stmc-icon__own img' ) ) {
					checkIcon( edit, before );
					drawTrust();
				}
			}, 0 );
		} );
		frame.open();
	}

	trustEdits.forEach( function ( edit ) {
		var own = edit.querySelector( '.stmc-icon--own input' );
		var before = edit.getAttribute( 'data-default' );

		edit.addEventListener( 'change', function ( event ) {
			if ( 'radio' === event.target.type && 'custom' !== event.target.value ) {
				before = event.target.value;
			}
		} );
		var start = edit.querySelector( 'input[type="radio"]:checked' );
		if ( start && 'custom' !== start.value ) {
			before = start.value;
		}
		own.addEventListener( 'click', function () {
			if ( ! edit.querySelector( '.stmc-icon__own img' ) ) {
				chooseImage( edit, before );
			}
		} );
		edit.querySelector( '.stmc-own-change' ).addEventListener( 'click', function () {
			chooseImage( edit, before );
		} );
		edit.querySelector( '.stmc-own-remove' ).addEventListener( 'click', function () {
			setOwnImage( edit, 0, '' );
			checkIcon( edit, before );
			trustChanged( edit );
		} );
	} );

	if ( form ) {
		form.addEventListener( 'input', drawTrust );
		form.addEventListener( 'change', drawTrust );
	}
	drawTrust();

	/* Forms that delete something ask first. */

	root.addEventListener( 'submit', function ( event ) {
		var question = event.target.getAttribute( 'data-stmc-confirm' );
		// eslint-disable-next-line no-alert -- a plain question at a plain moment.
		if ( question && ! window.confirm( question ) ) {
			event.preventDefault();
		}
	} );

	/* Checkout check: runs when its panel opens. The server escapes what it sends. */

	function runCheck( again ) {
		var box = root.querySelector( '.stmc-ck' );
		if ( ! box || ( box.getAttribute( 'data-ran' ) && ! again ) ) {
			return;
		}
		var out = box.querySelector( '.stmc-ck__out' );
		var button = box.querySelector( '.stmc-ck__run' );
		box.setAttribute( 'data-ran', '1' );
		button.disabled = true;
		out.textContent = box.getAttribute( 'data-running' );

		var body = new URLSearchParams();
		body.append( 'action', 'stmc_check' );
		body.append( 'nonce', box.getAttribute( 'data-nonce' ) );
		fetch( window.ajaxurl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( reply ) {
				return reply.json();
			} )
			.then( function ( reply ) {
				if ( ! reply || ! reply.success ) {
					throw new Error( 'check' );
				}
				out.innerHTML = reply.data.html;
			} )
			.catch( function () {
				out.textContent = box.getAttribute( 'data-error' );
			} )
			.then( function () {
				button.disabled = false;
			} );
	}

	var checkButton = root.querySelector( '.stmc-ck__run' );
	if ( checkButton ) {
		checkButton.addEventListener( 'click', function () {
			runCheck( true );
		} );
	}

	/* Logo and type: the media library, own-font fields, and the preview. */

	var shapePreview = root.querySelector( '.stmc-sp' );

	function setting( name ) {
		return document.getElementById( 'stmc-design-' + name );
	}

	function fontOf( key, own ) {
		var stacks = JSON.parse( shapePreview.getAttribute( 'data-fonts' ) );
		if ( 'custom' === key ) {
			return own.value.trim() ? own.value.trim() + ', sans-serif' : '';
		}
		return stacks[ key ] || '';
	}

	function drawShape() {
		if ( ! shapePreview ) {
			return;
		}
		var style = shapePreview.style;
		var bodyKey = setting( 'font_family' ).value;
		var headKey = setting( 'heading_font' ).value;
		var body = fontOf( bodyKey, setting( 'font_custom' ) );
		var head = fontOf( headKey, setting( 'heading_font_custom' ) );
		var size = parseInt( setting( 'font_size' ).value, 10 ) || 15;
		var own = parseInt( setting( 'heading_size' ).value, 10 ) || 0;
		var logo = setting( 'logo_url' ).value.trim();
		var image = shapePreview.querySelector( '.stmc-sp__logo' );
		var button = setting( 'button_custom' );
		var label = document.getElementById( 'stmc-legal-button_text' );

		setting( 'font_custom' ).closest( 'tr' ).hidden = 'custom' !== bodyKey;
		setting( 'heading_font_custom' ).closest( 'tr' ).hidden = 'custom' !== headKey;

		style.setProperty( '--sp-font', body || 'inherit' );
		style.setProperty( '--sp-hfont', head || body || 'inherit' );
		style.setProperty( '--sp-size', size + 'px' );
		style.setProperty( '--sp-hsize', ( own || Math.round( size * 1.2267 ) ) + 'px' );
		style.setProperty( '--sp-hweight', setting( 'heading_weight' ).value );
		style.setProperty( '--sp-hcase', setting( 'heading_case' ).value );
		style.setProperty( '--sp-line-w', setting( 'heading_line' ).checked ? '2px' : '0px' );
		style.setProperty( '--sp-label', setting( 'label_weight' ).value );
		style.setProperty( '--sp-logo', ( parseInt( setting( 'logo_height' ).value, 10 ) || 34 ) + 'px' );
		style.setProperty( '--sp-radius', ( parseInt( setting( 'radius' ).value, 10 ) || 0 ) + 'px' );
		[ [ '--sp-accent', 'accent' ], [ '--sp-title', 'title' ], [ '--sp-ink', 'ink' ], [ '--sp-labelc', 'label' ], [ '--sp-text', 'text' ], [ '--sp-bg', 'bg' ], [ '--sp-card', 'card' ], [ '--sp-line', 'line' ] ].forEach( function ( pair ) {
			if ( setting( pair[ 1 ] ) ) {
				style.setProperty( pair[ 0 ], setting( pair[ 1 ] ).value );
			}
		} );

		shapePreview.querySelector( '.stmc-sp__num' ).hidden = ! setting( 'step_numbers' ).checked;
		image.hidden = ! logo;
		if ( logo && image.getAttribute( 'src' ) !== logo ) {
			image.setAttribute( 'src', logo );
		}
		shapePreview.querySelector( '.stmc-sp__site' ).hidden = !! logo;

		var buy = shapePreview.querySelector( '.stmc-sp__btn' );
		if ( label ) {
			buy.textContent = label.value.trim() || label.placeholder;
		}
		buy.style.background = button && button.checked ? setting( 'button_bg' ).value : '';
		buy.style.color = button && button.checked ? setting( 'button_text' ).value : '';
	}

	var logoPick = root.querySelector( '.stmc-logo-pick' );
	if ( logoPick ) {
		logoPick.addEventListener( 'click', function () {
			if ( ! window.wp || ! window.wp.media ) {
				return;
			}
			var frame = window.wp.media( {
				title: logoPick.getAttribute( 'data-title' ),
				button: { text: logoPick.getAttribute( 'data-button' ) },
				library: { type: 'image' },
				multiple: false,
			} );
			frame.on( 'select', function () {
				var field = setting( 'logo_url' );
				field.value = frame.state().get( 'selection' ).first().toJSON().url;
				field.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			} );
			frame.open();
		} );
	}

	if ( form ) {
		form.addEventListener( 'input', drawShape );
		form.addEventListener( 'change', drawShape );
	}
	drawShape();

	/* Buy button: its colours in both states, and whether the label stays readable. */

	var buySwitch = document.getElementById( 'stmc-design-button_custom' );

	function luminance( hex ) {
		var parts = [ 1, 3, 5 ].map( function ( at ) {
			var value = parseInt( hex.substr( at, 2 ), 16 ) / 255;
			return value <= 0.03928 ? value / 12.92 : Math.pow( ( value + 0.055 ) / 1.055, 2.4 );
		} );
		return 0.2126 * parts[ 0 ] + 0.7152 * parts[ 1 ] + 0.0722 * parts[ 2 ];
	}

	function contrast( one, two ) {
		var a = luminance( one );
		var b = luminance( two );
		return ( Math.max( a, b ) + 0.05 ) / ( Math.min( a, b ) + 0.05 );
	}

	function drawBuy() {
		if ( ! buySwitch ) {
			return;
		}
		var bg = document.getElementById( 'stmc-design-button_bg' );
		var hover = document.getElementById( 'stmc-design-button_hover' );
		var text = document.getElementById( 'stmc-design-button_text' );
		var row = root.querySelector( '.stmc-buy-row' );
		var label = document.getElementById( 'stmc-legal-button_text' );

		[ bg, hover, text ].forEach( function ( input ) {
			input.closest( 'tr' ).hidden = ! buySwitch.checked;
		} );
		row.hidden = ! buySwitch.checked;

		row.querySelectorAll( '.stmc-buy__btn' ).forEach( function ( button ) {
			button.style.background = 'hover' === button.getAttribute( 'data-state' ) ? hover.value : bg.value;
			button.style.color = text.value;
			if ( label ) {
				button.textContent = label.value.trim() || label.placeholder;
			}
		} );
		row.querySelector( '.stmc-buy__warn' ).hidden = Math.min( contrast( bg.value, text.value ), contrast( hover.value, text.value ) ) >= 4.5;
	}

	if ( form ) {
		form.addEventListener( 'input', drawBuy );
		form.addEventListener( 'change', drawBuy );
	}
	drawBuy();

	/* Consent: the sentence customers read, for the choice on screen. */

	var consentPreview = root.querySelector( '.stmc-cp' );

	function radioValue( name ) {
		var radio = root.querySelector( 'input[name="stmc_settings[legal][' + name + ']"]:checked' );
		return radio ? radio.value : '';
	}

	function drawConsent() {
		if ( ! consentPreview ) {
			return;
		}
		var style = radioValue( 'consent_style' );
		var links = radioValue( 'consent_links' );
		var own = document.getElementById( 'stmc-legal-consent_text' );
		var error = document.getElementById( 'stmc-legal-consent_error' );
		var mode = document.getElementById( 'stmc-legal-consent' );
		var builtIn = JSON.parse( consentPreview.getAttribute( 'data-texts' ) )[ style + '-' + links ];
		var sentence = own && own.value.trim() ? own.value.trim() : builtIn;
		var target = consentPreview.querySelector( '.stmc-cp__text' );
		var pattern = /\{(terms|revocation)\}([\s\S]*?)\{\/\1\}/g;
		var last = 0;
		var match;

		target.textContent = '';
		while ( null !== ( match = pattern.exec( sentence ) ) ) {
			target.appendChild( document.createTextNode( sentence.slice( last, match.index ) ) );
			// A page nobody chose gives no link at the checkout, so none here either.
			var link = document.createElement( 'span' );
			link.className = consentPreview.querySelector( '[data-page="' + match[ 1 ] + '"] a' ) ? 'stmc-cp__link' : '';
			link.textContent = match[ 2 ];
			target.appendChild( link );
			last = pattern.lastIndex;
		}
		target.appendChild( document.createTextNode( sentence.slice( last ) ) );

		var off = !! mode && 'off' === mode.value;
		consentPreview.querySelector( '.stmc-cp__box' ).hidden = off;
		consentPreview.querySelector( '.stmc-cp__off' ).hidden = ! off;
		consentPreview.querySelector( '.stmc-cp__switch' ).hidden = 'notice' === style;
		consentPreview.querySelector( '[data-page="revocation"]' ).hidden = 'terms' === links;
		consentPreview.querySelector( '.stmc-cp__terms-hint' ).hidden = 'terms' !== links;
		if ( own ) {
			own.placeholder = builtIn;
		}
		if ( error ) {
			error.closest( 'tr' ).hidden = 'notice' === style;
		}
	}

	if ( form ) {
		form.addEventListener( 'input', drawConsent );
		form.addEventListener( 'change', drawConsent );
	}
	drawConsent();

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
