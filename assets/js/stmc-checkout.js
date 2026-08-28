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

	S.log( 'core ready', { blockCheckout: S.isBlockCheckout } );
} )();
