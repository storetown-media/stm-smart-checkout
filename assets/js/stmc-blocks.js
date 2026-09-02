/**
 * STM Smart Checkout — block checkout bridge.
 *
 * One job: hand the shop's buy-button label to the Checkout block. The block
 * never applies woocommerce_order_button_text (checked in the installed
 * WooCommerce source, not assumed); its label is read through the
 * placeOrderButtonLabel checkout filter, and that filter lives in the
 * wc-blocks-checkout script this file depends on. No build step: the API is
 * exposed as window.wc.blocksCheckout.
 *
 * Loaded in the footer with everything else. Order does not matter here —
 * measured: 40 scripts behind the block's own bundle, and the label was on
 * the button. The block reads its filters when it renders.
 */
( function () {
	'use strict';

	var data = window.stmcBlocks || {};
	var api = window.wc && window.wc.blocksCheckout;

	if ( ! api || typeof api.registerCheckoutFilters !== 'function' ) {
		return;
	}

	var label = String( data.buttonLabel || '' ).trim();
	if ( ! label ) {
		return;
	}

	api.registerCheckoutFilters( 'stm-smart-checkout', {
		// Same type in, same type out: a filter returning something that is
		// not a string is rejected by the block and the default label stays.
		placeOrderButtonLabel: function ( value ) {
			return label || value;
		}
	} );
}() );
