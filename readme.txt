=== STM Smart Checkout for WooCommerce ===
Contributors: jobhunter99
Tags: checkout, woocommerce checkout, conversion, germanized, checkout fields
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.55
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Distraction-free WooCommerce checkout with the German and EU legal essentials built in. Works with your gateways, theme and Germanized.

== Description ==

**Your checkout is the most expensive page in your shop. This plugin makes it earn its keep, without replacing your gateways, your legal plugin or WooCommerce.**

Switch on preview mode and look at your checkout the way a customer does, while your customers keep the standard checkout. If you do not like it, deactivate the plugin and everything is as it was: no pages changed, no data locked in.

https://www.youtube.com/watch?v=fd0-KXFZthI

The film shows every feature on a real checkout: the free plugin first, Pro from 4:29. To try it yourself, open the [demo checkout](https://woo-shop.de/kasse/?add-to-cart=451&stmc_lang=en). The demo shop runs on sample data and shows the free plugin and Pro together.

= What sets it apart =

* **It stands down instead of fighting.** Before it prints a consent box, a button label or a VAT line, it asks the *rendered* checkout whether another plugin already prints one. That is why it runs next to WooCommerce Germanized and German Market without duplicate texts.
* **German and EU legal essentials are built in.** With no legal plugin at all the checkout is still complete (on the Checkout block with WooCommerce 9.8 or newer).
* **Postcode autofill for Germany, Austria and Switzerland** with the databases inside the plugin. No external service, no API key.
* **Nothing phones home.** No external fonts, no tracking, no remote requests. HPOS compatible.

= All free features =

**Layout and focus**

* Three layouts: three columns, two columns or one column.
* Progress bar across cart, checkout and confirmation.
* Distraction-free mode: theme header, menu, breadcrumbs and seals go, with a full-page template where a theme needs it.
* Checkout footer with the legal pages you tick, or a menu of your own.
* "Continue shopping" link on the cart.

**Trust**

* Header band with your logo, three trust claims and an "Already a customer?" login toggle.
* Ten bundled icons or your own image for each claim.
* Trust row and a reassurance note next to the buy button.

**Legal**

* Consent as a box to tick or as a notice with links, naming terms and cancellation, with the exact wording stored on the order.
* Your own buy button label for § 312j BGB, which also wins over Germanized.
* Mandatory notice above the buy button, VAT statement, and delivery time per item from the most specific source.
* Legal texts open in an overlay, without leaving the checkout.
* Server-side safety net: a request without a required tick is refused.

**Fields and order summary**

* Tidied field pairs, the right mobile keyboard and autofill attributes, instant validation.
* State or county never required, and an info tooltip for "Create an account?".
* Order notes on, off or folded away, coupon field on or off.
* Product images and quantity controls in the order summary.

**Design**

* Colours for accent, headings, labels, text, lines and backgrounds, all as design tokens instead of `!important`.
* Own buy button colours with live preview and contrast warning.
* Logo from the media library, fonts without any outside server, heading size, weight and case, step numbers, corner radius.
* Own CSS with a code editor, and a list of selectors to hide.

**Tools**

* Preview mode for shop managers, and every building block can be switched off.
* **Checkout check:** one click tests legal pages, buy button wording, consent, footer links, payment methods and page cache, and every finding links to its setting. A technical check, not legal advice.
* **Backup and transfer:** settings as a file for another shop, the last 15 states back with one click, and a reset to the defaults.
* Settings search, plain-language help on every setting, and a reversible switch from the Cart and Checkout blocks to the classic pages.

Classic cart and checkout are styled in full; the blocks get everything except the column layouts and the field improvements. The settings screen says which is which, and the plugin never changes your pages on its own.

= STM Smart Checkout Pro =

The free plugin is complete in itself and stays that way. The paid add-on adds:

* **Online withdrawal form** required EU-wide since 19 June 2026 (directive 2023/2673): for guests and customers, with notification, receipt and a management screen.
* **Checkout field editor:** your own fields in 22 field types, WooCommerce's fields moved, renamed or hidden, with conditions, checks and a layout preview.
* **Field analysis:** at which field and payment method orders fail, as daily sums on your own server.
* **Layout test:** two layouts take turns in blocks of hours, without a cookie and without a visitor identifier.
* **Card testing protection:** limits per connection, a hidden trap, a minimum time and a warning mail.
* **Bundle discounts** that reach PayPal as a real discount, with a progress hint.
* **Checkout offers:** cards at the checkout that add a product with one click.
* **VAT ID with live VIES check and reverse charge.** An unreachable EU service never grants an exemption.
* **Express zone:** one band above the form for wallet buttons, through the providers' own filters.
* **Payment-dependent required fields**, re-checked on the server.
* **Method customizer:** name, note, symbol and order per payment method, with 33 bundled symbols.
* **Safe mode:** after a fatal error the standard checkout takes over, visibly and reversibly.
* **Mobile sticky order bar** and an **ultra-compact layout**.

Pro requires this free plugin: [STM Smart Checkout Pro for WooCommerce](https://en.storetown-media.de/produkt/stm-smart-checkout-pro-woocommerce/). This plugin has a page of its own as well: [STM Smart Checkout for WooCommerce](https://en.storetown-media.de/produkt/stm-smart-checkout-woocommerce/)

== Frequently Asked Questions ==

= Does this work with the block-based checkout? =

In part, and the plugin says exactly which part. Everything around the form works on the Cart and Checkout blocks: the full-page template, the trust header band, the step indicator, the legal footer line and the design tokens on the button, the fields and the order summary. Inside the form, the pieces that matter under German law are there as well: a required consent box for terms and cancellation policy that WooCommerce validates on the server — a request without the tick is refused — and writes onto the order together with the exact wording; on the block checkout this consent box needs WooCommerce 9.8 or newer, the first version with required checkboxes in its additional checkout fields. On older versions the plugin leaves WooCommerce's own terms text or checkbox in place, in place of its notice and links above the buy button; the server does not check it, and the settings screen says so; the mandatory notice from your settings and the links to both texts one line above the buy button, the links opening in the same overlay as on the classic checkout; the buy button label from your settings; and the delivery time under each line item, in the cart block and in the order summary, resolved from the same sources as on the classic checkout. Also on the blocks since 0.1.47: the trust row under the buy button and the reassurance note under the consent box, in the same markup and the same stylesheet the classic checkout uses. Also on the blocks since 0.1.48: the switches for the coupon prompt and the order-notes field — the block checkout already folds both away behind a single line, so the separate "expandable line" setting for the notes has nothing left to do there. Not yet on the blocks: the column layouts and the field manager with its postcode autofill. The settings screen lists this, and the one-click switch to the classic cart and checkout remains for shops that want all of it today — the block markup is kept, so the way back is one click as well.

= Does it work with WooCommerce Germanized / German Market? =

Yes — coexistence with both is a core design goal. The plugin respects their legal checkboxes, button texts and tax displays.

= Is there a paid version, and what does it add? =

Yes. STM Smart Checkout Pro is a separate add-on that requires this free plugin. It adds the online withdrawal form and its management screen, the express zone for wallet buttons, payment-dependent required fields, the payment method customizer, a checkout field editor, field analysis, a layout test, card testing protection, safe mode, a mobile sticky order bar and an ultra-compact layout. Everything described in the description above is part of this free plugin and stays that way — Pro adds, it does not unlock. Details and pricing: [STM Smart Checkout Pro for WooCommerce](https://en.storetown-media.de/produkt/stm-smart-checkout-pro-woocommerce/)

= Does the free plugin include the online withdrawal form required since June 2026? =

No — that one is in Pro. EU directive 2023/2673 requires an online withdrawal function for distance contracts from 19 June 2026, and it is more than a form: a public page reachable for guests, pre-filling for logged-in customers, order matching that never turns a consumer away, notification and receipt mails, and a management screen with a status workflow. What the free plugin does cover is the checkout side of German and EU law — consent, the § 312j BGB button label, VAT statement, delivery time and the mandatory notice above the buy button.

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

The bundled postcode databases for Germany, Austria and Switzerland are derived from the free geographical database [GeoNames](https://www.geonames.org/), used under the [Creative Commons Attribution 4.0 licence](https://creativecommons.org/licenses/by/4.0/).

They ship as plain JSON inside the plugin and are read locally. Nothing is fetched at runtime, and no address a customer types is sent anywhere.

== Screenshots ==

1. Three-column checkout: address, payment and order side by side, with the trust header band and progress bar.
2. Two-column checkout.
3. One-column checkout.
4. The same checkout on a phone.
5. The buy zone: order summary, consent notice, buy button and trust row.
6. Legal texts open in an overlay, without leaving the checkout.
7. The cart in the same look, with progress bar.
8. Overview: every building block can be switched off.
9. Structure: layouts, header band and distraction-free mode.
10. Input help: postcode autofill, mobile keyboards and field fixes.
11. Order summary: notes, coupon field, product images and quantity controls.
12. Trust: three claims with bundled icons or your own image, with a live preview.
13. Consent: a box to tick or a notice with links, with a preview of the checkout.
14. Buy button and notices: your own label and colours.
15. Legal pages and the checkout footer.
16. Colours as design tokens.
17. Logo, fonts and headings without any outside server.
18. Own CSS with a code editor.
19. Checkout check: what is in order, and a link to the right setting for each finding.
20. Backup and transfer: settings as a file, earlier states and a reset.

== Changelog ==

Older entries (0.1.0 to 0.1.28) are kept in `CHANGELOG-ARCHIVE.txt` in the plugin's
repository: https://github.com/storetown-media/stm-smart-checkout
They are left out here because WordPress.org truncates this section at 5,000 words,
which would drop them silently instead of on purpose.

= 0.1.55 =
* Changed: in the three-column layouts the "different address" switch and the order notes now sit under the payment methods. The address column gets shorter, the middle column no longer stays half empty, and a ticked shipping address unfolds where there is room.
* Fixed: the sign in front of the opened order notes line showed a broken character.

= 0.1.54 =
* New: Backup and transfer. Download your settings as a file, import them in another shop, and bring back any of the last 15 states with one click. Links to pages, menus and images stay in the shop they belong to.
* New: Checkout check. One screen shows what is in order and what is not: legal pages, buy button, consent, footer, payment, shipping, HTTPS and page cache, each finding with a link to the fix.
* New: logo from the media library with its height, fonts for text and headings without any outside server, heading size, weight, letter case, line and step numbers, label weight, all with a live preview.
* New: own colours for the buy button, with a live preview and a contrast warning. They also win over a theme that paints the button itself.
* Changed: the footer pages are chosen with tick boxes, and the settings show what is in the footer now. Fixed: on the full-page template a footer added by the site itself no longer appears below the legal line.
* Fixed: a legal page with little text, such as a form page, no longer shows an error in the overlay, and the settings warn when the cancellation link leads to the withdrawal form only.
* Change: a buy button label entered in the settings now wins, also over Germanized. The settings show the current wording and where it comes from.
* Fixed: the text above the buy button stays in place. It vanished with the first totals refresh, and it now keeps its styling.
* New: one clear choice for terms at the checkout: a box to tick or a notice with links, naming terms and cancellation policy or terms only, with a preview. The choice also applies to Germanized's terms box.
* New: every trust item can use one of ten icons or your own image from the media library, with a live preview on the settings screen. If an image is deleted, the item falls back to its standard icon.
* Fix: Product names no longer break into single letters in the three-column layout when Germanized is active.
* Fix: On block themes the checkout uses its full width and sits centred; WooCommerce limited it to 1000px.
* Change: The three column heads share one size and line, order totals take less height, the quantity control sits under the product name.
* Change: The layout choice shows a drawing and a short description per layout.
* New settings screen: one navigation with clearly named sections instead of four tabs, compact cards, switches, and a search across every setting. One save covers the whole screen; old links with ?tab= keep working.
* The layout is picked from small drawings, the colours are grouped by what they colour, and an overview shows the state of the checkout at a glance.
* Add-ons can bring their own sections through the new filter stmc_admin_sections. Rows of an add-on written for the tabs stay available under Add-on settings.
* New: your own CSS for cart and checkout, under Design, with WordPress's code editor. It loads after everything else, for themes that need a nudge.
* Fixed: a fixed header from a theme or another plugin no longer covers the header band and the step indicator. The plugin measures it and starts the page below it.
* Fixed on block themes: WooCommerce notices, the country field, the line above order items and the height of first and last name are consistent now.

= 0.1.53 =
* Fixed: on the Checkout block with WooCommerce older than 9.8, the consent box was optional. WooCommerce has no required checkboxes in its additional checkout fields before 9.8, so it showed the box as "(optional)" and accepted orders without the tick. The box now appears on the block from WooCommerce 9.8; the classic checkout is unchanged and still works from WooCommerce 8.3.
* Fixed: on those older versions the plugin also took away WooCommerce's own terms text and checkbox. They now stay in place, instead of the plugin's notice and links above the buy button. The server does not check them there, and the settings screen says so and names the options: update WooCommerce, or switch to the classic checkout.
* Fixed: field hints at the checkout are readable again. On themes that keep WooCommerce's blue tooltip style, grey text sat on a blue box.

= 0.1.52 =
* Fixed: with the plugin's own consent box in charge, every order was rejected with "Please read and accept the terms and conditions to proceed with your order" as soon as a WooCommerce terms page was set — no matter what the customer ticked. The consent box takes the place of WooCommerce's own terms checkbox, but the server-side check still demanded that checkbox, which was never on the page. Reported by a customer of a live shop on 12.10.2026.
* WooCommerce's terms checkbox is now demanded only when it was actually rendered, recognised by the "terms-field" companion WooCommerce posts with it. That is the rule WooCommerce core applies, and the one this plugin already used for Germanized's checkboxes.

= 0.1.51 =
* Fixed: in the two-column layout the order summary hung below the address column instead of beside it, with the column empty above it and only the "Your order" heading in place. That heading rendered next to the summary rather than in it, and a second item in the right column has nowhere to go but the next grid row — which starts below the full height of the address column. The heading now sits inside the summary card, where the billing and additional-information headings sit as well.
* A tab of its own now describes what the paid add-on adds, and the plugin's row on the Plugins page carries one link to it. Both disappear the moment the add-on is installed — nobody should be sold what they already own.
* Deliberately nowhere else. The directory permits upselling but binds it to guideline 11, which is explicit about the shape: prompts and notices "must be limited in scope and used sparingly, be that contextually or only on the plugin's setting page". So there is no admin notice, dismissible or not, anywhere in this plugin.
* The tab describes the add-on rather than showing switches that do nothing. Shipping locked functionality and selling the key is what guideline 5 forbids, and a settings screen full of dead controls is a poor way to explain a product anyway.
* No banner is fetched, no image loaded, no counter pinged: the panel is static markup and local strings. The listing promises this plugin never phones home, and an upgrade page is no reason to start.
* Every claim on the tab names a module that exists. The readme once promised features that did not, and this list is checked against the shipped classes rather than against a roadmap.

= 0.1.50 =
* No code changes. This release exists so the plugin directory serves a rewritten description — WordPress.org reads it from the stable tag, so a new text needs a new version.
* The description now leads with what actually sets this plugin apart instead of listing twelve features: that it asks the rendered checkout whether another plugin already prints a consent box, a button label or a VAT line before printing its own; that the German and EU legal essentials work with no legal plugin at all; that the postcode databases for Germany, Austria and Switzerland sit inside the plugin so no customer address leaves your server; and that nothing phones home.
* Preview mode moved from the eleventh bullet to the second sentence. Trying the plugin on your own live site while customers keep seeing the standard checkout is the most useful thing a shop can do with it, and it was buried.
* The Pro section was incomplete and is now correct: bundle discounts and checkout offers had been shipping since 0.6.0 and 0.7.0 without ever being described, and the VAT ID field with its live VIES check and reverse charge has been added.
* The tag "one page checkout" has been replaced with "checkout fields" — this plugin is not a one page checkout, and the field manager it does have is free.

= 0.1.49 =
* On the block checkout the own consent box now stands down for a legal plugin that renders its own — until now a shop running Germanized read two consent texts on one page, ours as a required checkbox and Germanized's below it, with the setting on "automatic".
* Why it took a different answer than the classic checkout: the block field has to be registered at woocommerce_init, because the Store API needs it registered to validate and store it, and at that moment no legal plugin has hooked the checkout yet. Measured in a real request — the detection answers "nobody" there and "Germanized" a few hooks later, too late to matter.
* So on the blocks, and only there, the question is answered by presence rather than by hook, and as narrowly as possible: not "is Germanized installed" but "is its block integration loaded". Registering the field and declining to render it later would have been better, but WooCommerce offers no filter for that.
* The settings screen says what was found, because a decision made on presence can be wrong and must not be wrong silently. A shop whose legal plugin is installed but not showing a consent box switches the setting to "on".

= 0.1.48 =
* The coupon prompt and the order-notes field can now be switched off on the block checkout too — the same two settings that already governed them on the classic pages, so a shop configures once and both surfaces agree.
* Measured before it was promised, and the measurement moved the answer: WooCommerce hands the block app a couponsEnabled flag and offers a filter behind it, but that flag is global for the request — the Store API checks the same function, so using it would not hide a prompt, it would refuse coupons the shop still issues by link. The narrow lever is the block wrapper itself.
* The order-notes filter the classic checkout uses does not exist in the block path at all; the block that draws the field carries no logic to hook into. What works is that the Checkout block renders server-side as a tree of empty wrappers and the app mounts into them — no wrapper, nothing to mount. Verified through first paint, through a re-render, and on the way back.
* Said plainly rather than sold: the block checkout already folds the notes and the coupon behind a single line by itself, so the separate "expandable line" setting for the notes changes nothing there. The settings screen says so.

= 0.1.47 =
* The trust row and the reassurance note reach the block checkout. The row of small trust items sits under the buy button, where the last doubt happens, and the reassurance note under the consent box it comments on — the same markup, the same settings and the same stylesheet the classic checkout uses, read from one place so the two can never drift apart.
* Measured first, because the reasonable guess was wrong: the block checkout offers no hook under its button and no slot either — the four Slot/Fill points WooCommerce exposes all sit in the order summary. What does work is appending markup after a block, and the checkout's React tree leaves it alone: through the first paint, and through re-renders while a customer types.
* The block stylesheet no longer depends on the legal module. A shop that lets a legal plugin handle consent kept its buy button, fields and cards unstyled on the blocks, because the whole layer hung on one switch. The design travels with the plugin now; the button label, the consent box and the legal line still stand down with the module they belong to.

= 0.1.46 =
* The block checkout wears the plugin's design. Every step of the Checkout block is a card now — the same background, border, radius and shadow as the classic sections — with the classic numbered title: a disc in the title blue counting contact, address, shipping and payment, and the accent rule beneath. The additional-information block and the order notes keep the card and skip the number; they are addenda, not steps. The order summary heading speaks in the same voice. All of it is stylesheet only, hung on the block's own class names as measured on WooCommerce 11, and switched off with the plugin.
* Not attempted, and said so: the three-column choreography of the classic checkout. The block offers no wrapper for two independent columns of steps, and a grid would couple the address and payment columns row by row — a tall payment step forcing an equally tall blank beside it. The layout setting keeps applying to the classic checkout only, as the settings screen states.

= 0.1.45 =
* The legal core works on the Cart and Checkout blocks. A required consent box for terms and cancellation policy is registered through WooCommerce's additional checkout fields, so the Store API validates it on the server — a checkout request without the tick, or with it set to false, is refused before an order exists — and the order carries the moment of consent and the exact sentence agreed to, as it does on the classic checkout. The links to both texts stand one line above the buy button, in the place WooCommerce reserved for legal text, and open in the plugin's overlay. The buy button carries the label from your settings; the blocks never read the classic filter, and what looked compliant on German shops so far was WooCommerce's translation of "Place order" and nothing more.
* The design tokens reach into the block form: the buy button, the fields, the section titles and the order summary wear the same accent, radius and type scale as the shell around them. The shell itself — full-page template, trust band, step indicator, legal footer — has worked on the blocks all along; it hangs on page hooks, not on the classic checkout's rendering hooks, which is more than the previous FAQ answer allowed for.
* The mandatory notice and the delivery time follow. The notice from your settings — the essential order details the courts want readable right before the decision — shares the line above the buy button with the legal links, in body size where the links stay quiet. The delivery time under each item rides on WooCommerce's own item data, the mechanism the Store API and both blocks already render under a product name: it appears in the cart block and in the checkout's order summary, resolved from the same sources as on the classic checkout (the product's own value, a legal plugin's delivery term, the shop-wide default), stands down for virtual items and where a legal plugin labels the item, and is printed once even on a shop whose cart is classic while its checkout is a block.
* Not on the blocks yet, and said so on the settings screen: the column layouts, the field manager and postcode autofill, the trust row, the reassurance note, the coupon and order-note controls. Every piece above stays out on WooCommerce older than the additional-fields API; the classic checkout is unaffected there.

= 0.1.44 =
* The logo field no longer promises something the checkout does not do. Its help text said an empty field falls back to your site logo; the header band actually prints your site title as text, which is what the code has always done. A shop owner who left the field empty expecting a logo got a line of text and no explanation. Both help texts now describe the behaviour, and say out loud that the site logo and this field are different places in WordPress.
* The German translation follows the WordPress glossary. The German polyglots team pointed at the glossary and the style guide when granting editor rights, and four terms did not match it: Knopf is Button, Kästchen is Checkbox, Fußbereich is Footer, and Site is Website. 38 places in each of the two German catalogues, with the article corrected wherever the gender changed with the word — a Kästchen is neuter, a Checkbox is not, and a blind replacement would have left "das Checkbox" behind in a dozen sentences. Translations are delivered by WordPress.org and are not part of this package.

= 0.1.43 =
* The links to our own pages sit at the end of the description instead of its second line. A shop owner opening a directory page wants to know what the plugin does; a link to the vendor's shop before the first feature reads as an advertisement, whatever it points at. Both language sections now describe the plugin in full first and name the pages afterwards, where someone who has read that far might actually want them. The first link is now halfway down the description rather than in its opening. No code changed.

= 0.1.42 =
* The links in the description are links. WordPress.org renders a readme with a Markdown subset — bold, lists and headings came through, but a bare URL stays plain text, so every address in the description sat there unclickable. They are written as proper links now, with the product's name as the anchor instead of the address, which is also what a search engine reads. The two attribution links in Credits had the same problem and are fixed with them. No code changed.

= 0.1.41 =
* The description says what the plugin actually does. It listed eight bullets for a checkout that has closer to twenty settings worth naming, and it described the paid add-on as "three things" when it has carried seven since the end of August — an understatement is as inaccurate as a promise, and this readme has been on the wrong side of that before. Both product pages are linked now, in the language of the section that links them, and the German half spells out the legal features under the words a German shop actually searches for: Button-Lösung, § 312j BGB, Widerrufsformular, AGB-Checkbox, MwSt.-Ausweisung, Lieferzeit. Two questions were added to the FAQ, one of them stating plainly that the online withdrawal form required since 19 June 2026 is in Pro and not in this plugin. No code changed.

= 0.1.40 =
* The plugin's own homepage link no longer leads to the paid version. The Plugin URI in the header pointed at a path that redirects to the Pro product page, and WordPress.org publishes that header field as this plugin's homepage — so the free plugin's link led to a sales page for a different product. It now points at this plugin's own page. Both header URLs are the English ones, because the directory listing is written in English; the German product pages are carried by the German translation of those fields, which is what translatable header URLs are for.

= 0.1.39 =
* The settings screen loads its stylesheet and its script as files. Both were printed into the page as a style and a script block, which renders the same but skips everything WordPress does around an asset: a version the caches can see, one place from which a site can dequeue or replace them, and markup that stays out of the middle of the form. The distraction-free module's extra hide rules take the same route and hang off the checkout stylesheet now instead of being written into the page head — and the child combinator survives their sanitizer, so "header > .bar" hides that bar rather than everything below the header.
* Values written into a style element are stripped of the characters that could leave it. The word between the express buttons and the form is translatable, and a translation is a string from outside the code: one carrying a closing style tag would have ended the element and turned the rest of the declaration into markup. The colour and size tokens pass the same filter, applied late, where the value is written into the CSS rather than where it was read.
* Translations come from WordPress rather than from the package. The plugin no longer calls load_plugin_textdomain and no longer ships compiled .mo files: for a plugin in the directory, WordPress delivers translations into wp-content/languages/plugins/ and loads them on the first translated string without being asked, so a bundled copy would only give one language two sources — of which the packaged one is always the older. The Domain Path header goes with them, because it pointed at a folder the package no longer carries. The German translation stays maintained in the source repository and goes to translate.wordpress.org, where anyone who finds a better word can correct it.

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

