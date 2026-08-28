/**
 * STM Smart Checkout — vanilla JS core.
 *
 * Philosophy (inherited from the Magento edition): no jQuery dependency of our
 * own. WooCommerce's classic checkout emits jQuery events, so this core
 * bridges them onto plain DOM CustomEvents once; feature modules subscribe to
 * `stmc:*` events and stay framework-free on both Classic and Blocks.
 */
( function () {
	'use strict';

	var S = ( window.STMC = window.STMC || {} );
	var data = window.stmcData || {};

	S.debug = !! data.debug;
	S.isBlockCheckout = !! data.isBlock;

	S.log = function () {
		if ( S.debug && window.console && console.log ) {
			console.log.apply( console, [ '[STMC]' ].concat( Array.prototype.slice.call( arguments ) ) );
		}
	};

	/** Dispatch a namespaced CustomEvent on document. */
	S.emit = function ( name, detail ) {
		document.dispatchEvent( new CustomEvent( 'stmc:' + name, { detail: detail || {} } ) );
	};

	/** Subscribe helper: STMC.on('updated_checkout', fn). */
	S.on = function ( name, fn ) {
		document.addEventListener( 'stmc:' + name, fn );
	};

	// Bridge the classic checkout's jQuery events to DOM events (idempotent).
	function bridgeJQueryEvents() {
		if ( ! window.jQuery || S._bridged ) {
			return;
		}
		S._bridged = true;
		var events = [ 'updated_checkout', 'updated_cart_totals', 'checkout_error', 'payment_method_selected', 'added_to_cart' ];
		events.forEach( function ( name ) {
			window.jQuery( document.body ).on( name, function () {
				S.log( 'event', name );
				S.emit( name );
			} );
		} );
		S.log( 'jQuery event bridge active' );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', bridgeJQueryEvents );
	} else {
		bridgeJQueryEvents();
	}

	// No login form in the DOM (theme/option dependent)? Then the toggle
	// has nothing to open — remove it instead of leaving a dead button.
	function pruneLoginToggle() {
		if ( document.querySelector( 'form.woocommerce-form-login, form.login' ) ) {
			return;
		}
		document.querySelectorAll( '.stmc-login-toggle' ).forEach( function ( btn ) {
			btn.remove();
		} );
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', pruneLoginToggle );
	} else {
		pruneLoginToggle();
	}

	/*
	 * Login toggle in the header band. The login form is already in the DOM;
	 * we toggle its INLINE display (WooCommerce ships it with style="display:none").
	 * Deliberately not the "showlogin" class — WooCommerce's own handler would
	 * double-toggle. Delegated on document so it survives DOM replacement.
	 */
	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest ? e.target.closest( '.stmc-login-toggle' ) : null;
		if ( ! btn ) {
			return;
		}
		e.preventDefault();
		var form = document.querySelector( 'form.woocommerce-form-login, form.login' );
		if ( ! form ) {
			return;
		}
		if ( ! form.id ) {
			form.id = 'stmc-loginform';
		}
		var open = form.offsetParent !== null;
		form.style.display = open ? 'none' : '';
		btn.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
		if ( ! open ) {
			form.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			var field = form.querySelector( 'input[name="username"]' );
			if ( field ) {
				window.setTimeout( function () {
					field.focus();
				}, 350 );
			}
		}
	}, false );

	/*
	 * Info tooltips (.stmc-info): a mouse reveals them via CSS :hover, a
	 * keyboard via :focus — but a finger does neither, because iOS gives
	 * buttons no focus on tap. Hence the explicit open state. Delegated on
	 * document so buttons inside replaced fragments keep working.
	 */
	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest ? e.target.closest( '.stmc-info' ) : null;
		var open = document.querySelectorAll( '.stmc-info.is-open' );
		if ( btn ) {
			e.preventDefault();
			var wasOpen = btn.classList.contains( 'is-open' );
			open.forEach( function ( x ) {
				x.classList.remove( 'is-open' );
			} );
			if ( ! wasOpen ) {
				btn.classList.add( 'is-open' );
			}
			return;
		}
		// A click anywhere else closes it — but not inside the bubble itself,
		// so the text stays selectable.
		if ( open.length && ! ( e.target.closest && e.target.closest( '.stmc-info__pop' ) ) ) {
			open.forEach( function ( x ) {
				x.classList.remove( 'is-open' );
			} );
		}
	}, false );

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key !== 'Escape' ) {
			return;
		}
		document.querySelectorAll( '.stmc-info.is-open' ).forEach( function ( x ) {
			x.classList.remove( 'is-open' );
		} );
	} );

	/*
	 * Postcode → city autofill (DE/AT/CH, bundled databases). Fills the city
	 * only when it is empty or was autofilled before — a hand-typed city is
	 * never overwritten; multiple matches feed a native <datalist>.
	 */
	function postcodeAutofill() {
		if ( ! data.postcodeAutofill ) {
			return;
		}
		var LENGTHS = { DE: 5, AT: 4, CH: 4 };
		var ajaxUrl = ( window.wc_checkout_params && window.wc_checkout_params.wc_ajax_url )
			? window.wc_checkout_params.wc_ajax_url.replace( '%%endpoint%%', 'stmc_postcode' )
			: '/?wc-ajax=stmc_postcode';
		var timers = {};

		function fieldSet( prefix ) {
			return {
				country: document.getElementById( prefix + '_country' ),
				postcode: document.getElementById( prefix + '_postcode' ),
				city: document.getElementById( prefix + '_city' ),
			};
		}

		function countryOf( f ) {
			if ( ! f.country ) {
				return '';
			}
			return ( f.country.value || '' ).toUpperCase();
		}

		function apply( prefix ) {
			var f = fieldSet( prefix );
			if ( ! f.postcode || ! f.city ) {
				return;
			}
			var country = countryOf( f );
			var need = LENGTHS[ country ];
			var pc = ( f.postcode.value || '' ).replace( /\D/g, '' );
			if ( ! need || pc.length !== need ) {
				return;
			}
			fetch( ajaxUrl + '&country=' + country + '&postcode=' + pc, { credentials: 'same-origin' } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					var cities = ( res && res.cities ) || [];
					if ( ! cities.length ) {
						return;
					}
					// Datalist for every match (native suggestion UI).
					var listId = 'stmc-cities-' + prefix;
					var list = document.getElementById( listId );
					if ( ! list ) {
						list = document.createElement( 'datalist' );
						list.id = listId;
						document.body.appendChild( list );
					}
					list.innerHTML = '';
					cities.forEach( function ( c ) {
						var o = document.createElement( 'option' );
						o.value = c;
						list.appendChild( o );
					} );
					f.city.setAttribute( 'list', listId );

					var untouched = f.city.value === '' || f.city.getAttribute( 'data-stmc-autofilled' ) === '1';
					if ( untouched ) {
						f.city.value = cities[ 0 ];
						f.city.setAttribute( 'data-stmc-autofilled', '1' );
						f.city.classList.remove( 'stmc-autofilled' );
						void f.city.offsetWidth; // restart the highlight animation
						f.city.classList.add( 'stmc-autofilled' );
						f.city.dispatchEvent( new Event( 'change', { bubbles: true } ) );
						S.log( 'postcode autofill', prefix, pc, cities[ 0 ] );
					}
				} )
				.catch( function () {} );
		}

		document.addEventListener( 'input', function ( e ) {
			var m = /^(billing|shipping)_postcode$/.exec( e.target.id || '' );
			if ( ! m ) {
				// A hand-edited city clears the autofill marker.
				if ( /^(billing|shipping)_city$/.test( e.target.id || '' ) ) {
					e.target.removeAttribute( 'data-stmc-autofilled' );
				}
				return;
			}
			var prefix = m[ 1 ];
			window.clearTimeout( timers[ prefix ] );
			timers[ prefix ] = window.setTimeout( function () {
				apply( prefix );
			}, 250 );
		} );
	}
	postcodeAutofill();

	/*
	 * Mobile sticky order bar: appears when the real place-order button
	 * leaves the viewport; its button proxies a click to the real one so
	 * validation, legal checkboxes and gateways all run natively.
	 */
	function stickyBar() {
		var bar = document.getElementById( 'stmc-sticky-bar' );
		if ( ! bar ) {
			return;
		}
		var mq = window.matchMedia( '(max-width: 992px)' );

		function target() {
			return document.getElementById( 'place_order' );
		}

		function show( on ) {
			var visible = on && mq.matches;
			bar.toggleAttribute( 'hidden', ! visible );
			bar.setAttribute( 'aria-hidden', visible ? 'false' : 'true' );
			document.documentElement.classList.toggle( 'stmc-has-sticky-bar', visible );
		}

		bar.querySelector( '.stmc-sticky-bar__btn' ).addEventListener( 'click', function () {
			var btn = target();
			if ( btn ) {
				btn.click();
			}
		} );

		if ( 'IntersectionObserver' in window ) {
			var observed = null;
			var io = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					show( ! entry.isIntersecting );
				} );
			}, { threshold: 0 } );
			var attach = function () {
				var btn = target();
				if ( btn && btn !== observed ) {
					if ( observed ) {
						io.unobserve( observed );
					}
					io.observe( btn );
					observed = btn;
				}
			};
			attach();
			// The button node is replaced with every checkout fragment update.
			S.on( 'updated_checkout', attach );
		}

		// Mirror the live total into the bar after every refresh.
		S.on( 'updated_checkout', function () {
			var src = document.querySelector( '#order_review .order-total .woocommerce-Price-amount' );
			var dst = bar.querySelector( '.stmc-sticky-bar__amount' );
			if ( src && dst ) {
				dst.innerHTML = src.outerHTML;
			}
		} );
	}
	stickyBar();

	S.log( 'core ready', { blockCheckout: S.isBlockCheckout } );
} )();
