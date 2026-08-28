=== STM Smart Checkout for WooCommerce ===
Contributors: storetownmedia
Tags: checkout, woocommerce checkout, conversion, germanized, one page checkout
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Conversion-focused, legally compliant checkout for WooCommerce — works with your gateways, your theme and Germanized instead of replacing them.

== Description ==

STM Smart Checkout turns the standard WooCommerce checkout into a focused, trustworthy buying experience — without replacing your payment gateways, your legal plugins or the WooCommerce checkout itself.

**Built for stores selling under EU/German law (DACH-ready):**

* Distraction-free checkout layouts with progress indicator and trust header
* Card-based design controlled by design tokens — customize everything from your theme, no `!important` battles
* Field manager with real-time validation and correct mobile touch keyboards
* Postcode autofill for Germany, Austria and Switzerland (city fills in automatically)
* Separate, configurable legal checkboxes (terms, privacy, right of withdrawal) with server-side validation
* Trust badges with a curated icon set
* Works with WooCommerce Germanized and German Market instead of fighting them
* Compatible with the classic (shortcode) checkout today; block-checkout compatible by design

**Philosophy:** your gateways keep rendering their own express buttons, your legal plugin keeps owning its legal texts, your theme keeps its typography. This plugin arranges everything into a checkout that converts.

The Pro version adds the express payment zone, payment-method customizer, payment-dependent required fields, EU VAT ID validation (VIES) with reverse charge, order bumps, an online withdrawal form module and a safe mode with automatic fallback. Pro is distributed separately at storetown-media.de.

== Frequently Asked Questions ==

= Does this work with the block-based checkout? =

The plugin declares compatibility with the cart/checkout blocks and does not interfere with them. The full visual layer targets the classic checkout first; block-native layouts are on the public roadmap.

= Does it work with WooCommerce Germanized / German Market? =

Yes — coexistence with both is a core design goal. The plugin respects their legal checkboxes, button texts and tax displays.

= Does it load external fonts or call external services? =

No. No remote fonts, no tracking, no external requests.

== Changelog ==

= 0.1.1 =
* Core checkout modules: header band with progress and login toggle, distraction-free mode with theme adapters, numbered section titles, two-column layout, field improvements (state handling, mobile input attributes), trust elements. German (formal and informal) translations.

= 0.1.0 =
* Initial foundation: settings framework, design token system, checkout context detection, module registry, HPOS and cart/checkout blocks compatibility declarations.
