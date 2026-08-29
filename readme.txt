=== STM Smart Checkout for WooCommerce ===
Contributors: storetownmedia
Tags: checkout, woocommerce checkout, conversion, germanized, one page checkout
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.20
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

= 0.1.20 =
* Theme-proof layout round, measured on a second shop (Basel theme, Bootstrap checkout template). Theme templates that wrap the billing and order anchors in their own grid markup (a .col-sm-6 gave the billing column 50% of its 50%) become layout-transparent — the checkout grid now always places the anchors themselves, whatever the template wraps them in. The default template's "Product / Subtotal" table head is retired (our column title already labels the area; as a re-flowed line its two cells overlapped). Gateway description boxes with intrinsically sized badges (Klarna) can no longer push past the card edge. Themes without a server-side adapter get a narrow distraction-free CSS fallback: the page-title hero banner and the breadcrumb step aside while the screen-reader heading keeps the page named.

= 0.1.19 =
* Context translations restored: the rebuilt language files had silently lost every msgctxt entry (the page slug and the state-field labels fell back to English) — found on the first install on a second shop, where the withdrawal page appeared with a German title but an English slug. The build tool now writes GNU context keys correctly, and the page self-heal treats title and slug independently, so existing installs fix their slug on the next upgrade (WordPress keeps the old-slug redirect).

= 0.1.18 =
* Plugin Check green again: the help-icon markup escapes its id at the exact output spot (a pre-escaped variable is invisible to the reviewer's static analysis), and reading WooCommerce's order-notes switch carries its documented justification.

= 0.1.17 =
* Withdrawal round from the live review. The form pre-fills with the customer's data — logged-in customers get their billing details plus their most recent order suggested (number, date, address), everything editable. The address is entered as single fields exactly like the checkout (street, postcode, city) instead of one big blob. The form speaks the checkout's design language now: white card, label blue, 46px controls, choice cards for the scope, a full-width call-to-action. Fixed: German shops got a page literally titled "Withdrawal" — the textdomain loaded after the page was created; the priority is corrected and existing untouched pages heal themselves (title and slug, WordPress keeps the old-slug redirect). The last untranslated strings are gone. New: the order-notes field ("Additional information") can be switched off in the backend.

= 0.1.16 =
* Every setting now explains itself: a "?" icon beside each label opens a plain-language help bubble — what the option does, the background, and when to use it. Hover, focus or tap; Escape closes. The help texts live in one map, so the short inline hints under the fields stay untouched. Fully translated.

= 0.1.15 =
* The one-column layout is actually one column now. Without its own rules the theme decided — The7 turns the checkout form into its own two-column flexbox, so "one column" silently rendered like "two columns". The layout is a centered 760px flex column: express buttons keep their order, then billing, then the order block, everything stacked.

= 0.1.14 =
* Compact review round: on shipping-free carts the "Additional information" section (order notes) moves under the payment methods instead of dangling as a lone card below the address — the numbering follows the story (payment becomes step 2, the notes read as a quiet sub-block). First/last name (and postcode/city) now sit on exactly the same line: WooCommerce's float-era `margin-top` on `.form-row-last` tilted the grid pair by 6px.

= 0.1.13 =
* Parity round with the live shop's proven checkout, plus a new layout. Step numbering now follows the form flow (1 billing details, 2 additional information, 3 payment — the order summary is the unnumbered constant); step heads grow to the measured 1.15rem in the title blue with the number in a matching disc. Field and payment-method labels speak in the label blue at content size. Consent boxes and "Create an account?" become modern switches on individual white cards. In the order column the totals table now leads, consents and the buy button follow, and the money lines re-order so the grand total closes the column with zebra rhythm on the quiet lines. Payment and order share ONE wide card split by a soft center line (three-column stage now starts at 1160px). The express area gets its measured title size and a translated "OR" divider. New: an ultra-compact layout — the three-column stage one type step down with tight cards and fields, modeled on the Magento edition; on touch screens density yields to reachability (16px field text, full-height targets).

= 0.1.12 =
* Plugin Check housekeeping — the remaining review warnings are resolved: request values in the withdrawal form and the postcode lookup are unslashed and sanitized before use, withdrawal table queries use the %i identifier placeholder (WordPress 6.2+), and the deliberate direct calls to the plugin's own table carry documented justifications. No functional change.

= 0.1.11 =
* One typographic system across all three checkout columns. Section titles now run in the body font instead of the theme display face (a checkout is one surface, not three widgets), payment method names drop from the theme reading size to content size so the middle column stops shouting, brand icons follow the type down, and the scale is down to five deliberate steps driven by tokens: fine print, secondary, content, controls, emphasis. Adds a --stmc-fs-xs token and raises content size to 15px.

= 0.1.10 =
* Consistent typography in the order summary. WooCommerce wraps every amount in its own price elements and themes style those wrappers directly, bypassing the cell — on The7 that meant a 13px tax label beside an 18px amount, three different blues in one column, and a grand total rendered in a completely different font family. The price wrappers now inherit from their cell, so each amount matches its label in family, size and color, with weight alone marking the value and the grand total as the only emphasized line.

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
