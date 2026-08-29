=== STM Smart Checkout for WooCommerce ===
Contributors: storetownmedia
Tags: checkout, woocommerce checkout, conversion, germanized, one page checkout
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.9
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
* Legal texts (terms, right of withdrawal) readable in an overlay without leaving the checkout, plus a server-side safety net that verifies required consent boxes again after submit
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

= 0.1.9 =
* Payment method rows are flex rows: a gateway with a wide brand icon strip (credit card) no longer pushes its label onto a second line, stranding the radio button above it — an inline-flex label is atomic and cannot break, so it dropped as a whole. Radio buttons now match the consent checkboxes at 18px. Order summary: rows get horizontal padding so labels and amounts no longer touch the card edge, the theme's grey table background is dropped in favor of the card surface, and row separators are drawn on the row instead of the cells (as flex items the cells were split by the column gap, breaking each line into two segments).

= 0.1.8 =
* Design round on the three-column layout: fixed column numbering 1 address / 2 payment / 3 order (the CSS counter told the story backwards because WooCommerce renders the order markup before the payment markup); the address and additional-information titles were accidentally hidden by our own heading rule. Consent boxes: consecutive cards merge into one quiet group, text flows as a normal block with hanging indent (WooCommerce core's inline label + line-height 2 tore it apart). Order summary: rows rebuilt as label-left/amount-right lines against The7's stacked block cells, product names wrap instead of truncating, tidy 44px thumbnails; Germanized's relocated duplicate "Your order" heading hidden.

= 0.1.7 =
* Legal round: server-side safety net for required consent boxes (an order without a required tick is rejected even when the browser check was bypassed — and stays silent when WooCommerce or Germanized already reported the same box, extendable via the `stmc_required_checkboxes` filter). Optional reassurance note under the consent boxes. "Create an account?" info tooltip whose wording is derived from your own registration settings.

= 0.1.6 =
* Postcode autofill for DE/AT/CH from bundled databases (no external service) — the city fills in automatically, multiple matches feed a native suggestion list. Mobile sticky order bar: total + buy button pinned while the real button is out of view; the proxy click runs every native validation.

= 0.1.5 =
* Three-column checkout layout (billing / payment / order) — the most compact arrangement, selectable under Design. Express area polish: WooPayments' English separator hidden, gateway description rhythm, theme content padding removed on checkout surfaces.

= 0.1.4 =
* Consumer rights: online withdrawal form (EU withdrawal function) — auto-created form page for guests and customers, automatic menu placement from the backend, "Withdraw this order" in My Account, soft order matching that never blocks a submission, merchant notification + customer receipt emails, and a management screen with status workflow under WooCommerce → Withdrawals.

= 0.1.3 =
* Legal module: terms and withdrawal texts open in an accessible overlay without leaving the checkout (native link behavior as fallback). Styled legal checkboxes, order table refinements, cart page round: tidy table columns, checkout button before wallet buttons, quiet update button.

= 0.1.2 =
* Design round on live The7 + Germanized shop: express payment areas stack tidily above the columns, theme percentage-width traps neutralized in the grid, field pair raster (name, postcode/city), payment method rows with contained brand icons, empty section shells lose their card chrome.

= 0.1.1 =
* Core checkout modules: header band with progress and login toggle, distraction-free mode with theme adapters, numbered section titles, two-column layout, field improvements (state handling, mobile input attributes), trust elements. German (formal and informal) translations.

= 0.1.0 =
* Initial foundation: settings framework, design token system, checkout context detection, module registry, HPOS and cart/checkout blocks compatibility declarations.
