=== STM Smart Checkout for WooCommerce ===
Contributors: jobhunter99
Tags: checkout, woocommerce checkout, conversion, germanized, one page checkout
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.38
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
* Styles the classic (shortcode) cart and checkout. If your store uses the Cart and Checkout blocks, the plugin says so on its settings screen and offers a reversible one-click switch — it never changes your pages on its own

**Philosophy:** your gateways keep rendering their own express buttons, your legal plugin keeps owning its legal texts, your theme keeps its typography. This plugin arranges everything into a checkout that converts.

A separate Pro add-on adds three things: the online withdrawal form required across the EU since 19 June 2026 (with its own management screen), a mobile sticky order bar, and an ultra-compact checkout layout. Pro is distributed from storetown-media.de. Everything described above is in this free plugin and stays there.

**Auf Deutsch**

STM Smart Checkout macht aus der Standard-Kasse von WooCommerce eine fokussierte, vertrauenswürdige Kaufstrecke — ohne Ihre Zahlungsarten, Ihr Rechts-Plugin oder die WooCommerce-Kasse selbst zu ersetzen.

* Ablenkungsfreie Kassen-Layouts mit Fortschrittsanzeige und Trust-Kopfband
* Kartenbasiertes Design über Design-Tokens steuerbar — alles aus dem Backend anpassbar, ohne `!important`-Kämpfe mit dem Theme
* Feldverwaltung mit Sofortprüfung und den richtigen Tastaturen auf dem Handy
* PLZ-Autovervollständigung für Deutschland, Österreich und die Schweiz (der Ort füllt sich selbst)
* Rechtstexte (AGB, Widerruf) im Overlay lesbar, ohne die Kasse zu verlassen — plus serverseitige Absicherung, die die Pflichthäkchen nach dem Absenden erneut prüft
* Pflichtangaben auch ohne Rechts-Plugin: Einwilligung, Knopfbeschriftung nach § 312j BGB, MwSt.-Ausweisung und Lieferzeit je Artikel — jeweils nur dann, wenn kein anderes Plugin sie bereits liefert
* Arbeitet mit WooCommerce Germanized und German Market zusammen, statt gegen sie

**Grundsatz:** Ihre Zahlungsarten rendern weiter ihre eigenen Express-Knöpfe, Ihr Rechts-Plugin behält seine Rechtstexte, Ihr Theme behält seine Typografie. Dieses Plugin ordnet alles zu einer Kasse, die verkauft.

== Frequently Asked Questions ==

= Does this work with the block-based checkout? =

Not yet — and the plugin now tells you so instead of quietly doing nothing. Its visual layer is built on the classic (shortcode) cart and checkout, and none of its hooks run inside the Cart and Checkout blocks. When your store uses those blocks, the settings screen names the affected pages, states plainly that the settings below will not change what your customers see, and offers to switch those pages to the classic cart and checkout in one click. The block markup is saved, so the way back is one click as well. Nothing is changed without that click. Block-native layouts are on the public roadmap.

= Does it work with WooCommerce Germanized / German Market? =

Yes — coexistence with both is a core design goal. The plugin respects their legal checkboxes, button texts and tax displays.

= Do I need a legal plugin for a German shop? =

No. If none is present, the checkout supplies the mandatory pieces itself: the consent box for terms and cancellation policy, the § 312j BGB button label, the VAT statement in the order summary, and the delivery time under each product. Each of these is decided by asking whether another plugin is actually rendering it — not by asking whether one is installed — so nothing ever appears twice.

= What does it deliberately not cover? =

Unit prices (PAngV) and sending the cancellation policy with the order confirmation mail stay with a dedicated legal plugin. The settings screen states this in plain language rather than leaving you to find out later, and it shows what the automatic detection last found on your checkout.

= Can I try it without switching my live checkout over? =

Yes. Leave the plugin switched off and open the checkout with `?stmc_preview=1` as a shop manager — you see the Smart Checkout, customers keep seeing the standard one. `?stmc_preview=off` ends the preview again, and the settings screen tells you while it is running, with the link that ends it.

= What happens to my checkout if I deactivate the plugin? =

You get the standard WooCommerce checkout back, unchanged. The plugin arranges and supplements the existing checkout; it does not replace the template, the gateways or the order process, and it stores nothing your shop would miss.

= Does it work with my theme? =

It is built to. Themes with a server-side adapter (The7, Storefront) use their native distraction-free path; every other theme gets the plugin's own minimal full-page template, so the theme's header, menus and footer are never built while styles, analytics, consent tools and chat widgets keep working. Layout rules place the checkout anchors themselves, whatever markup a theme template wraps them in.

= Is it compatible with HPOS? =

Yes, HPOS (High-Performance Order Storage) is fully supported. Compatibility with the cart/checkout blocks is declared in the sense that this plugin does not interfere with them — it does not style them either; see the block-checkout question above.

= Does it load external fonts or call external services? =

No. No remote fonts, no tracking, no external requests. The postcode databases for DE/AT/CH ship with the plugin.

== Credits ==

The bundled postcode databases for Germany, Austria and Switzerland are derived from the free geographical database GeoNames (https://www.geonames.org/), used under the Creative Commons Attribution 4.0 licence (https://creativecommons.org/licenses/by/4.0/).

They ship as plain JSON inside the plugin and are read locally. Nothing is fetched at runtime, and no address a customer types is sent anywhere.

== Screenshots ==

1. The three-column checkout: trust header band with progress, express payment area, and address, payment and order summary side by side.
2. The buy zone in reading order — totals, required consent, reassurance note, buy button, trust row.
3. Terms and cancellation policy open in an overlay, without leaving the checkout.
4. The same checkout on a phone: one column, reachable targets, express buttons kept.
5. The design tab — layout, the two blues, font size in pixels, every setting with its own plain-language help bubble.
6. The legal tab with "detected at the checkout": what the automatic detection last found, and which required statements the plugin is standing down from because another plugin delivers them.

== Changelog ==

= 0.1.38 =
* The shipping-address switch speaks in the checkout's voice. Its label carries the text, so a theme's label rules land there rather than on the heading around it — Basel sets 22px and uppercase on `.woocommerce-form__label`, and that one line shouted at 66px while the rest of the checkout spoke normally. Font size, weight, letter spacing and casing now inherit from the row, stated on the label and on the span inside it, because which of the two a theme targets differs.

= 0.1.37 =
* Customers can enter a different shipping address again. The rule that steps the theme's own section headings aside — so the plugin's numbered titles can take their place — also caught the shipping block, and WooCommerce does not put a heading there: it puts the "Ship to a different address?" checkbox inside that h3. Hiding it removed the only control that opens the shipping address and left an empty card in its place, on every shop that offers shipping. The shipping block is now excluded from that rule and its heading is styled as what it actually is: a switch, in the body font, with a 44px tap target. Reported from a live shop; the two test shops had never rendered the section, one forcing shipping to the billing address and the other selling a virtual article.

= 0.1.36 =
* The plugin now says it when your cart and checkout are built from blocks. A stock WooCommerce install renders both pages from the Cart and Checkout blocks; this plugin extends the classic cart and checkout, and none of its hooks fire inside the blocks. Until now that combination was silent in every direction: the settings screen offered its full set of options, the checkout kept looking exactly as before, and WooCommerce said nothing either — because the plugin declares block compatibility, which is true (it breaks nothing) and which switches off Woo's own warning. The settings screen now names the affected pages above the tabs, states plainly that nothing below will change what customers see, and offers to switch those pages to the classic cart and checkout in one click. The block markup is kept in the page's meta, so the way back is one click as well and stays visible for as long as the switch is in place. The same note appears once on the plugins list, where it can be dismissed.

= 0.1.35 =
* Preview mode can be left again. Once a shop manager had opened the preview, every later visit showed the Smart Checkout while the settings screen kept saying "off" — which reads like a broken switch. `?stmc_preview=0` (also "off", "no") clears the cookie and takes effect in the same request, and the settings screen now says out loud whenever the cookie is set: preview is on for you, this is why the checkout looks switched on, customers still see the standard checkout — with the link that ends it. Customers were never affected.

= 0.1.34 =
* Delivery time per line item — the last piece a shop without a legal plugin was missing in its checkout. Shown under every product in cart and checkout, resolved from the most specific source that knows one: a value typed onto this product (new field in the product's Shipping tab) wins, then Germanized's own delivery-time term (variation falling back to parent), then the shop-wide default. The `stmc_delivery_time` filter has the last word for shops that compute it from stock, a supplier feed or the shipping zone. Where a legal plugin already states the delivery time for a product, this one stays away — decided per product, not per shop.

= 0.1.33 =
* The two blues become settings. The step-heading blue and the field-label blue were hardcoded in the token file, which made them the only colors a shop could not change; both are colour settings on the Design tab now, with their current values as defaults, so nothing shifts visually. They stay deliberately separate from "Heading color" — labels are read while filling in, headings while orienting, and one colour for both flattens the form into a grey block. The reassurance note moves back between the consent boxes and the buy button, where it comments on the consent right above it.

= 0.1.32 =
* The order summary states its VAT. With gross prices WooCommerce prints no tax row at all, reasoning that the price already contains it — so on a shop whose legal plugin has stopped rendering, the summary charges VAT and says nothing about it. The checkout now states it itself, one row per tax rate beside the other money lines, wherever no legal plugin is doing it. The percentage comes from the tax rate, never from its name: shops name their rates freely, and a legal statement must not depend on what someone typed into a settings field.

= 0.1.31 =
* Lite and Pro become two plugins. The withdrawal complex, the mobile sticky order bar and the ultra-compact layout move to a separate STM Smart Checkout Pro plugin, so the free plugin neither carries nor loads paid code. Lite grows the extension points that make an add-on possible instead of a fork: `stmc_settings_fields`, `stmc_admin_tab_fields`, `stmc_admin_tab_{slug}`, `stmc_layouts`, `stmc_layout_labels` and public row helpers, so both plugins speak one visual language. New `STMC_Settings::layout()` resolves the effective layout from the raw option, so a stored layout whose provider is momentarily absent degrades to its nearest relative instead of silently falling back to the default.

= 0.1.30 =
* The checkout stands on its own legally. For shops with neither Germanized nor German Market, the plugin now owns the buy-button label wherever no legal plugin sets one (§ 312j BGB) — previously a compliant label arrived only by way of WooCommerce's German translation, which is accidental compliance, not compliance. Priority 5, so anything hooking later keeps the last word, and a setting for shops with their own wording. Plus a slot for the information that must be readable in the same glance as the button, printed inside the place-order row so it travels with the button wherever the layout moves it. The settings screen now says plainly what the plugin does not cover when no legal plugin is present.

= 0.1.29 =
* Font size in pixels. The type scale had one knob expressed as a percentage of rem, which handed the real size to the theme's root font size — the same setting rendered differently on two shops. It is a pixel value now, with every step a fixed ratio of it, chosen to reproduce the previous scale exactly at the 15px default; the old percentage is migrated rather than reset. Payment rows stay one row: a gateway setting `display:block` inline on its own rows made the block-level label start a new line, stranding the radio above it — an inline-flex label sits beside the radio there and is blockified back to flex where the row is flex, so nothing changes elsewhere. No `!important` involved.

= 0.1.28 =
* The consent box detects the legal plugin itself. The switch becomes a three-way choice with Automatic as the default: the box asks the checkout whether a legal plugin is actually printing consent boxes, and only steps in when none is. Asked by hook, never by "is the plugin installed" — that distinction is the whole feature: a legal plugin can sit there active and configured and still render nothing, and a presence check would leave such a checkout with no consent at all. The decision is recorded and shown on the settings screen, because automatic behaviour nobody can inspect is how a checkout ends up legally naked without anyone noticing.

= 0.1.27 =
* The checkout can carry its own legal consent — one required checkbox for terms and cancellation policy, seated between the grand total and the buy button, rendered with WooCommerce's own terms-wrapper class names so the existing card chrome, switch and invalid state apply. Links fill themselves from the pages the shop already registered; a placeholder whose page is unknown keeps its plain words instead of producing a dead link. Server-side validation reuses the existing required-checkbox path, and the acceptance is written onto the order together with the exact wording shown — a stored "yes" pointing at today's text proves nothing about last year's order. Order notes become a one-line disclosure at the end of the address column, so the field that hardly anyone fills stops costing a card and a step number.

= 0.1.26 =
* DHL preferred services join the payment column, right under the methods — the integration only re-prioritizes their hook, so everything Shiptastic built (its script, refresh behavior, conditions) keeps working untouched. And a new default makes the totals block third-party-proof: any unknown row a plugin prints into the totals now lands after the grand total automatically — extras can never interrupt the money story again.

= 0.1.25 =
* First member of the integrations family: Shiptastic (+ DHL). The pickup-location offer ("Not at home?") no longer interrupts the address fields mid-flow — it closes the address block as a quiet card. DHL's preferred services (delivery-day tiles, drop-off location, neighbor) speak the checkout's design language now and settle after the grand total, where delivery fine-tuning belongs. The adapter is guarded (no-op without Shiptastic) and never forks the plugin's behavior — the pattern every future integration follows.

= 0.1.24 =
* Order-summary thumbnails survive theme lazy-loaders. Basel's lazy filter swapped the image source for its placeholder and lost the original along the way — the thumbnail rendered briefly, then stayed empty. The markup is now hand-built from the attachment URL, untouched by the attachment-image filter pipeline any lazy plugin hooks into; browser-native lazy loading still defers offscreen images, variations fall back to the parent image, missing images to the WooCommerce placeholder.

= 0.1.23 =
* The full-page checkout footer configures itself and bends to your will: pick the exact pages in the backend (multi-select), or a whole menu — and without any choice the line now fills itself with the legal pages your site already registered (Germanized's imprint, privacy and withdrawal pages, WooCommerce's terms page, WordPress' privacy page), published pages only, in reading order.

= 0.1.22 =
* Order summary round from the second-shop review. Product images now render reliably in the summary regardless of the legal plugin (when Germanized already provides one, no second image is added). Quantity steppers beside each product let customers fix amounts right at the checkout — changes run through WooCommerce's own refresh with all totals, stock limits and sold-individually respected; a setting turns them off. The coupon prompt is owner-switchable too: shops that never issue codes can retire the field that sends customers code-hunting.

= 0.1.21 =
* Distraction-free works on every theme now: on themes without a built-in adapter, cart and checkout render through the plugin's own minimal full page (template_include) — the theme's header, menus and footer are never built at all, while styles, analytics, consent tools and chat widgets keep working. A configurable legal-links line under the checkout keeps imprint, privacy and terms reachable (pick your legal menu in the backend; the WordPress privacy page is the minimum fallback). Adapter themes (The7, Storefront) stay on their native path; owners can force the template on or off.

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
