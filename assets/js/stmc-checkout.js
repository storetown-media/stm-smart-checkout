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

	S.log( 'core ready', { blockCheckout: S.isBlockCheckout } );
} )();
