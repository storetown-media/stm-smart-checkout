<?php
/**
 * Settings page under WooCommerce → Smart Checkout.
 *
 * Deliberately minimal for P0: Settings API, two tabs, native inputs,
 * no upsell noise (wp.org guideline 11). The design tab will grow a live
 * preview in P1.
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;

class STMC_Admin {

	const PAGE = 'stmc-settings';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( STMC_FILE ), array( __CLASS__, 'action_links' ) );
	}

	public static function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Smart Checkout', 'stm-smart-checkout' ),
			__( 'Smart Checkout', 'stm-smart-checkout' ),
			'manage_woocommerce',
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	public static function register() {
		register_setting(
			'stmc_settings_group',
			STMC_Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'STMC_Settings', 'sanitize' ),
			)
		);
	}

	public static function action_links( $links ) {
		$url = admin_url( 'admin.php?page=' . self::PAGE );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'stm-smart-checkout' ) . '</a>' );
		return $links;
	}

	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$tabs = array(
			'general'  => __( 'General', 'stm-smart-checkout' ),
			'checkout' => __( 'Checkout', 'stm-smart-checkout' ),
			'legal'    => __( 'Legal', 'stm-smart-checkout' ),
			'design'   => __( 'Design', 'stm-smart-checkout' ),
		);
		$current = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $tabs[ $current ] ) ) {
			$current = 'general';
		}

		$preview_url = add_query_arg(
			STMC_Checkout_Context::PREVIEW_PARAM,
			'1',
			function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/' )
		);
		?>
		<div class="wrap stmc-settings">
			<style>
				.stmc-settings .form-table th { position: relative; }
				.stmc-help {
					display: inline-flex; align-items: center; justify-content: center;
					width: 18px; height: 18px; margin-left: 6px; padding: 0;
					vertical-align: text-bottom;
					background: #fff; border: 1.5px solid #a7aaad; border-radius: 50%;
					color: #646970; font-size: 11px; font-weight: 700; line-height: 1;
					cursor: help;
				}
				.stmc-help:hover, .stmc-help:focus, .stmc-help.is-open {
					border-color: #2271b1; color: #2271b1; outline: none;
				}
				.stmc-help.is-open { background: #2271b1; color: #fff; }
				.stmc-help__pop {
					display: none; position: absolute; z-index: 20;
					top: calc(100% - 8px); left: 0; width: 340px; max-width: 70vw;
					padding: 12px 15px; background: #1d2327; border-radius: 8px;
					box-shadow: 0 8px 24px rgba(0, 0, 0, .25);
					color: #f0f0f1; font-size: 12.5px; font-weight: 400; line-height: 1.55;
				}
				.stmc-help:hover + .stmc-help__pop,
				.stmc-help:focus + .stmc-help__pop,
				.stmc-help.is-open + .stmc-help__pop { display: block; }
			</style>
			<h1><?php esc_html_e( 'STM Smart Checkout', 'stm-smart-checkout' ); ?></h1>
			<p>
				<?php esc_html_e( 'Configure the checkout, then use preview mode to review it on the live site before enabling it for customers.', 'stm-smart-checkout' ); ?>
				<a href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open checkout preview', 'stm-smart-checkout' ); ?></a>
			</p>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<a class="nav-tab <?php echo $slug === $current ? 'nav-tab-active' : ''; ?>"
						href="<?php echo esc_url( add_query_arg( array( 'page' => self::PAGE, 'tab' => $slug ), admin_url( 'admin.php' ) ) ); ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="options.php">
				<?php settings_fields( 'stmc_settings_group' ); ?>
				<?php self::hidden_fields_for_other_tabs( $current ); ?>
				<table class="form-table" role="presentation">
					<?php if ( 'general' === $current ) : ?>
						<?php self::row_checkbox( 'general.enabled', __( 'Enable Smart Checkout', 'stm-smart-checkout' ), __( 'Off = the standard checkout renders for customers. Preview mode works regardless of this switch.', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'modules.header', __( 'Module: header band & progress', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'modules.focus', __( 'Module: distraction-free mode', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'modules.layout', __( 'Module: layout extras', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'modules.fields', __( 'Module: field improvements', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'modules.trust', __( 'Module: trust elements', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'advanced.debug', __( 'Debug logging (browser console)', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'general.remove_data_on_uninstall', __( 'Remove all settings when the plugin is deleted', 'stm-smart-checkout' ) ); ?>
					<?php elseif ( 'legal' === $current ) : ?>
						<?php self::row_checkbox( 'withdrawal.enabled', __( 'Online withdrawal form (EU withdrawal function)', 'stm-smart-checkout' ), __( 'Creates a public form page for guests and customers; submissions are never blocked and land under WooCommerce → Withdrawals.', 'stm-smart-checkout' ) ); ?>
						<?php
						$wd_page = (int) get_option( STMC_Withdrawal::PAGE_OPT );
						if ( $wd_page ) {
							echo '<tr><th scope="row">' . esc_html__( 'Form page', 'stm-smart-checkout' ) . '</th><td><a href="' . esc_url( get_permalink( $wd_page ) ) . '" target="_blank" rel="noopener">' . esc_html( get_the_title( $wd_page ) ) . '</a> &middot; <a href="' . esc_url( get_edit_post_link( $wd_page ) ) . '">' . esc_html__( 'Edit', 'stm-smart-checkout' ) . '</a></td></tr>';
						}
						?>
						<?php self::row_menu( 'withdrawal.menu_id', __( 'Add the withdrawal link to this menu', 'stm-smart-checkout' ), __( 'The link is appended automatically — no manual menu editing needed.', 'stm-smart-checkout' ) ); ?>
						<?php self::row_text( 'withdrawal.notify_email', __( 'Notify this email about new requests', 'stm-smart-checkout' ), __( 'Empty = the site admin email.', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'withdrawal.confirm_customer', __( 'Send the customer a receipt confirmation', 'stm-smart-checkout' ) ); ?>
						<?php self::row_number( 'withdrawal.period_days', __( 'Withdrawal period (days)', 'stm-smart-checkout' ), 0, 365 ); ?>
						<?php self::row_checkbox( 'withdrawal.account_limit', __( 'Hide the account button after the period ends', 'stm-smart-checkout' ), __( 'The public form stays available either way — a submission is never blocked.', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'withdrawal.success_link', __( 'Show a withdrawal link on the order confirmation', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'legal.validate_checkboxes', __( 'Verify required consent boxes on the server', 'stm-smart-checkout' ), __( 'Safety net: an order without a required tick is rejected even when the browser check was bypassed. Stays silent when WooCommerce or Germanized already reported the same box.', 'stm-smart-checkout' ) ); ?>
						<?php self::row_text( 'legal.guarantee_title', __( 'Reassurance note: lead-in', 'stm-smart-checkout' ), __( 'Example: No risk: — shown in bold in front of the text.', 'stm-smart-checkout' ) ); ?>
						<?php self::row_textarea( 'legal.guarantee_text', __( 'Reassurance note under the consent boxes', 'stm-smart-checkout' ), __( 'Optional, empty by default. Use it for a promise you really keep — e.g. that your voluntary money-back guarantee is unaffected by the download consent. Never a condition, never a legal text.', 'stm-smart-checkout' ) ); ?>
					<?php elseif ( 'checkout' === $current ) : ?>
						<?php self::row_checkbox( 'header.show_progress', __( 'Show progress bar (cart → checkout → confirmation)', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'header.show_login', __( 'Show "Already a customer?" login toggle', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'header.sr_title', __( 'Render a screen-reader page title', 'stm-smart-checkout' ), __( 'Both pages usually have no H1 once the theme header is hidden.', 'stm-smart-checkout' ) ); ?>
						<?php self::row_text( 'header.trust_1', __( 'Trust item 1 (lock icon)', 'stm-smart-checkout' ), __( 'Example: Secure SSL connection. Leave all three empty for the SSL default.', 'stm-smart-checkout' ) ); ?>
						<?php self::row_text( 'header.trust_2', __( 'Trust item 2 (shield icon)', 'stm-smart-checkout' ), __( 'Only claims that are actually true for your shop.', 'stm-smart-checkout' ) ); ?>
						<?php self::row_text( 'header.trust_3', __( 'Trust item 3 (card icon)', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'trust.under_button', __( 'Repeat trust items under the order button', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'layout.continue_shopping', __( '"Continue shopping" link on the cart', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'fields.state_optional', __( 'State/county field never required, labels untangled', 'stm-smart-checkout' ), __( 'Fixes the duplicate "Land" label for IE/GB/HU in German shops.', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'fields.autofill_attrs', __( 'Correct mobile keyboards & autofill attributes', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'fields.postcode_autofill', __( 'Postcode autofill for Germany, Austria, Switzerland', 'stm-smart-checkout' ), __( 'The city fills in automatically from bundled databases (no external service, GDPR-safe).', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'fields.account_hint', __( 'Explain "Create an account?" with an info tooltip', 'stm-smart-checkout' ), __( 'The text is built from your own registration settings, so it always matches what really happens.', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'checkout.sticky_bar', __( 'Mobile sticky order bar (total + buy button)', 'stm-smart-checkout' ), __( 'Appears on phones while the real order button is out of view.', 'stm-smart-checkout' ) ); ?>
						<?php self::row_checkbox( 'legal.popup', __( 'Open legal texts (terms, withdrawal) in an overlay', 'stm-smart-checkout' ), __( 'Customers read the linked pages without leaving the checkout; right-click still opens the page normally.', 'stm-smart-checkout' ) ); ?>
						<?php self::row_textarea( 'focus.extra_hide_selectors', __( 'Extra CSS selectors to hide (advanced)', 'stm-smart-checkout' ), __( 'One selector per line. Hidden only on cart/checkout. Never hide your footer legal links.', 'stm-smart-checkout' ) ); ?>
					<?php else : ?>
						<?php self::row_select( 'design.layout', __( 'Checkout layout', 'stm-smart-checkout' ), array( 'three-column' => __( 'Three columns (billing / payment / order — most compact)', 'stm-smart-checkout' ), 'ultra-compact' => __( 'Ultra-compact (three columns, dense — everything at a glance)', 'stm-smart-checkout' ), 'two-column' => __( 'Two columns (order summary right)', 'stm-smart-checkout' ), 'one-column' => __( 'One column', 'stm-smart-checkout' ) ) ); ?>
						<?php self::row_color( 'design.accent', __( 'Accent color', 'stm-smart-checkout' ) ); ?>
						<?php self::row_color( 'design.accent_hover', __( 'Accent hover color', 'stm-smart-checkout' ) ); ?>
						<?php self::row_color( 'design.ink', __( 'Heading color', 'stm-smart-checkout' ) ); ?>
						<?php self::row_color( 'design.text', __( 'Text color', 'stm-smart-checkout' ) ); ?>
						<?php self::row_color( 'design.muted', __( 'Secondary text color', 'stm-smart-checkout' ) ); ?>
						<?php self::row_color( 'design.bg', __( 'Page background', 'stm-smart-checkout' ) ); ?>
						<?php self::row_color( 'design.card', __( 'Card background', 'stm-smart-checkout' ) ); ?>
						<?php self::row_color( 'design.line', __( 'Border color', 'stm-smart-checkout' ) ); ?>
						<?php self::row_number( 'design.radius', __( 'Card corner radius (px)', 'stm-smart-checkout' ), 0, 32 ); ?>
						<?php self::row_select( 'design.font_scale', __( 'Font scale', 'stm-smart-checkout' ), array( '0.9' => '90%', '1' => '100%', '1.1' => '110%' ) ); ?>
						<?php self::row_url( 'design.logo_url', __( 'Checkout logo URL', 'stm-smart-checkout' ), __( 'Shown in the checkout header. Leave empty to use the site logo.', 'stm-smart-checkout' ) ); ?>
					<?php endif; ?>
				</table>
				<?php submit_button(); ?>
			</form>
			<script>
				( function () {
					// Tap/click pins a help bubble open (hover/focus alone is lost on
					// touch); Escape or a click anywhere else closes it again.
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
						var open = btn.classList.contains( 'is-open' );
						closeAll();
						if ( ! open ) {
							btn.classList.add( 'is-open' );
							btn.setAttribute( 'aria-expanded', 'true' );
						}
					} );
					document.addEventListener( 'keydown', function ( e ) {
						if ( 'Escape' === e.key ) {
							closeAll();
						}
					} );
				} )();
			</script>
		</div>
		<?php
	}

	/**
	 * The Settings API replaces the whole option on save; re-submit the values
	 * of fields that are not rendered on the current tab so switching tabs
	 * never wipes the other tab's configuration.
	 */
	private static function hidden_fields_for_other_tabs( $current_tab ) {
		$visible = array(
			'general'  => array(
				'general.enabled', 'modules.header', 'modules.focus', 'modules.layout', 'modules.fields',
				'modules.trust', 'advanced.debug', 'general.remove_data_on_uninstall',
			),
			'checkout' => array(
				'header.show_progress', 'header.show_login', 'header.sr_title',
				'header.trust_1', 'header.trust_2', 'header.trust_3',
				'trust.under_button', 'layout.continue_shopping',
				'fields.state_optional', 'fields.autofill_attrs', 'fields.postcode_autofill', 'fields.account_hint',
				'checkout.sticky_bar', 'legal.popup', 'focus.extra_hide_selectors',
			),
			'legal'    => array(
				'withdrawal.enabled', 'withdrawal.menu_id', 'withdrawal.notify_email', 'withdrawal.confirm_customer',
				'withdrawal.period_days', 'withdrawal.account_limit', 'withdrawal.success_link',
				'legal.validate_checkboxes', 'legal.guarantee_title', 'legal.guarantee_text',
			),
			'design'   => array(
				'design.layout', 'design.accent', 'design.accent_hover', 'design.ink', 'design.text', 'design.muted',
				'design.bg', 'design.card', 'design.line', 'design.radius', 'design.font_scale', 'design.logo_url',
			),
		);
		foreach ( STMC_Settings::fields() as $key => $field ) {
			if ( in_array( $key, $visible[ $current_tab ], true ) ) {
				continue;
			}
			$value = STMC_Settings::get( $key );
			if ( 'bool' === $field['type'] ) {
				if ( $value ) {
					printf( '<input type="hidden" name="%s" value="1">', esc_attr( self::name( $key ) ) );
				}
				continue;
			}
			printf( '<input type="hidden" name="%s" value="%s">', esc_attr( self::name( $key ) ), esc_attr( $value ) );
		}
	}

	private static function name( $key ) {
		$parts = explode( '.', $key );
		return STMC_Settings::OPTION . '[' . implode( '][', array_map( 'sanitize_key', $parts ) ) . ']';
	}

	/**
	 * What each setting actually does — one place for every explanation.
	 * A row whose key appears here automatically gets the "?" help icon;
	 * the short inline descriptions under the fields stay untouched.
	 *
	 * @param string $key Setting key.
	 * @return string
	 */
	private static function help_text( $key ) {
		static $map = null;
		if ( null === $map ) {
			$map = array(
				'general.enabled'                   => __( 'The master switch. On = customers see the Smart Checkout on cart and checkout. Off = customers keep the standard checkout while you configure in peace. The preview link at the top works in both states, so you can review every change on the live site without any customer noticing.', 'stm-smart-checkout' ),
				'modules.header'                    => __( 'The white header band that replaces the theme header on cart and checkout: your logo in the middle, the three trust items, the "Already a customer?" login pill and the cart → checkout → confirmation progress. The individual pieces are configured on the Checkout tab.', 'stm-smart-checkout' ),
				'modules.focus'                     => __( 'Hides everything that leads away from completing the order: theme header and menu, seals, breadcrumbs and decorative footer parts. Legal links in the footer always stay reachable. Site-specific extras can be hidden via the advanced selector list on the Checkout tab.', 'stm-smart-checkout' ),
				'modules.layout'                    => __( 'The layout machinery: the column arrangement chosen on the Design tab, the numbered section headings and the "Continue shopping" link on the cart.', 'stm-smart-checkout' ),
				'modules.fields'                    => __( 'All form-field improvements as a group: tidy field pairs (first/last name, postcode/city), state-field fixes, correct mobile keyboards, postcode autofill and the account tooltip. Each piece has its own switch on the Checkout tab.', 'stm-smart-checkout' ),
				'modules.trust'                     => __( 'The quiet reassurance row under the order button, repeating the three trust items from the header band.', 'stm-smart-checkout' ),
				'advanced.debug'                    => __( 'Writes what the checkout scripts are doing to the browser console (F12). Useful only while diagnosing a problem — leave it off in normal operation. Customers never see these messages.', 'stm-smart-checkout' ),
				'general.remove_data_on_uninstall'  => __( 'Only matters when you DELETE the plugin in the plugins list: On = all settings and the withdrawal-requests table are removed for good. Off = everything survives for a later reinstall. Simply deactivating never deletes anything.', 'stm-smart-checkout' ),

				'header.show_progress'              => __( 'The three-step line in the header band: cart → checkout → confirmation, with the current step highlighted. Customers see where they are and how little is left.', 'stm-smart-checkout' ),
				'header.show_login'                 => __( 'A small "Already a customer? Log in" pill in the header band. It unfolds the login form WooCommerce already prints on the checkout — without it, that form has no visible trigger once the theme header is hidden.', 'stm-smart-checkout' ),
				'header.sr_title'                   => __( 'Adds an invisible page heading for screen readers. With the theme header hidden, cart and checkout usually have no H1 at all — assistive technology would announce a page without a name.', 'stm-smart-checkout' ),
				'header.trust_1'                    => __( 'The three short claims in the header band, each with its icon (lock, shield, card). Keep them factual and specific to your shop — e.g. your real payment options or guarantee. If all three are empty, a single "Secure SSL connection" is shown.', 'stm-smart-checkout' ),
				'header.trust_2'                    => __( 'Second trust item, shown with the shield icon. Only claims that are actually true for your shop.', 'stm-smart-checkout' ),
				'header.trust_3'                    => __( 'Third trust item, shown with the card icon — a good place for your payment brands, e.g. "PayPal, Klarna & invoice".', 'stm-smart-checkout' ),
				'trust.under_button'                => __( 'Repeats the three trust items in small print directly under the buy button — the moment of the last hesitation. Uses the same texts as the header band, so there is only one place to maintain them.', 'stm-smart-checkout' ),
				'layout.continue_shopping'          => __( 'A small back-to-shop link above the cart table. With the theme menu hidden there is otherwise no way back to the products; the checkout page deliberately never gets one.', 'stm-smart-checkout' ),
				'fields.state_optional'             => __( 'Never marks the state/county field as required and untangles its labels. Background: for countries like Ireland or the United Kingdom, WooCommerce inserts a state field that German shops usually do not need — and its label collided with the country field.', 'stm-smart-checkout' ),
				'fields.autofill_attrs'             => __( 'Gives every field the correct autocomplete and input attributes: phones show the number pad for postcode and phone, the email keyboard for email, and browser autofill puts the right data into the right fields.', 'stm-smart-checkout' ),
				'fields.postcode_autofill'          => __( 'Type a postcode and the city fills in by itself, for Germany, Austria and Switzerland. The databases ship with the plugin — no external service is called, no customer data leaves your server (GDPR-safe). Multiple matching cities appear as a native suggestion list.', 'stm-smart-checkout' ),
				'fields.account_hint'               => __( 'Adds a small "i" next to "Create an account?" that explains what actually happens — whether a password is chosen or emailed, and what the account is good for. The wording is generated from your real registration settings, so it can never promise something else.', 'stm-smart-checkout' ),
				'checkout.sticky_bar'               => __( 'On phones, a slim bar with the order total and a buy button stays pinned at the bottom of the screen while the real button is out of view. Tapping it triggers the real button including every validation. Desktop never shows the bar.', 'stm-smart-checkout' ),
				'legal.popup'                       => __( 'Links inside the consent boxes (terms, withdrawal) open the legal text in an overlay instead of leaving the checkout. The text is loaded from your existing pages — nothing is duplicated. Right-click or middle-click still opens the normal page; if loading fails, the link falls back to normal behavior.', 'stm-smart-checkout' ),
				'focus.extra_hide_selectors'        => __( 'For site-specific elements the distraction-free mode does not know: one CSS selector per line, hidden on cart and checkout only. Example: #my-chat-widget. Never hide your footer legal links — they are legally required on every page.', 'stm-smart-checkout' ),

				'withdrawal.enabled'                => __( 'The EU "withdrawal function": a public online form (its page is created automatically) where guests and customers declare a withdrawal. A submission is never blocked by validation — soft matching links it to an order when possible — and every request lands under WooCommerce → Withdrawals with a status workflow and email notifications.', 'stm-smart-checkout' ),
				'withdrawal.menu_id'                => __( 'Pick the menu that should carry the link to the withdrawal form — typically your footer legal menu, next to imprint and privacy. The link is appended automatically and removed again when this is switched off.', 'stm-smart-checkout' ),
				'withdrawal.notify_email'           => __( 'Every new withdrawal request is mailed to this address, including a direct link to the admin view. Leave empty to use the site admin email.', 'stm-smart-checkout' ),
				'withdrawal.confirm_customer'       => __( 'Sends the customer an automatic "we received your withdrawal" receipt. It confirms receipt only — the actual assessment and refund stay with you.', 'stm-smart-checkout' ),
				'withdrawal.period_days'            => __( 'Controls the "Withdraw this order" button in My Account: it shows for this many days after the order. The legal period itself comes from your terms — 14 days is the EU minimum for consumers; a voluntary longer promise (e.g. 30 days) goes here.', 'stm-smart-checkout' ),
				'withdrawal.account_limit'          => __( 'On = the My Account button disappears once the period is over. The public form stays reachable either way — a withdrawal must never fail on a technicality, and late requests are for you to assess, not for the software to reject.', 'stm-smart-checkout' ),
				'withdrawal.success_link'           => __( 'Shows a short "you can withdraw this order online at any time" note with the form link on the order confirmation page. Transparency that customers reward — and fewer withdrawal emails in free text.', 'stm-smart-checkout' ),
				'legal.validate_checkboxes'         => __( 'Browsers enforce required boxes only client-side — a broken script or a manipulated request could order without consent. This re-checks every required box (WooCommerce terms and Germanized) on the server and rejects the order with a normal error message. It stays silent when another plugin already reported the same box, so customers never read the same complaint twice.', 'stm-smart-checkout' ),
				'legal.guarantee_title'             => __( 'The bold lead-in of the reassurance note, e.g. "No risk:". Leave empty for a note without a lead-in.', 'stm-smart-checkout' ),
				'legal.guarantee_text'              => __( 'A friendly clarification under the consent boxes — the classic use: the legally required download consent sounds like losing all rights, and this note clarifies that your voluntary money-back guarantee is unaffected. Only promise what you really keep; the note is styled as reassurance, never as one more condition.', 'stm-smart-checkout' ),

				'design.layout'                     => __( 'Three columns: billing left, payment in the middle, order summary right — the proven compact desktop stage. Ultra-compact: the same stage one type step smaller with tighter cards, for everything at a glance. Two columns: form left, order summary sticky on the right. One column: a centered vertical flow. On narrow screens every layout stacks gracefully; phones are always one column.', 'stm-smart-checkout' ),
				'design.accent'                     => __( 'The action color: buy button, radio/checkbox accents and the underline of the section headings. Use your brand\'s strongest call-to-action color.', 'stm-smart-checkout' ),
				'design.accent_hover'               => __( 'The accent color while hovering a button — usually a slightly darker shade of the accent.', 'stm-smart-checkout' ),
				'design.ink'                        => __( 'The strong text color: order summary lines, amounts, the grand total and other emphasized text.', 'stm-smart-checkout' ),
				'design.text'                       => __( 'The normal reading color for regular content text.', 'stm-smart-checkout' ),
				'design.muted'                      => __( 'The quiet color for secondary lines: descriptions, fine print, the consent texts and the tax breakdown.', 'stm-smart-checkout' ),
				'design.bg'                         => __( 'The page surface behind the cards. A very light grey lets the white cards read as cards.', 'stm-smart-checkout' ),
				'design.card'                       => __( 'The surface of the cards themselves — white on almost every shop.', 'stm-smart-checkout' ),
				'design.line'                       => __( 'Borders of cards and fields plus the thin separators between rows.', 'stm-smart-checkout' ),
				'design.radius'                     => __( 'Corner rounding of the cards in pixels; fields and buttons scale along. 0 = sharp corners, 12 = the friendly default.', 'stm-smart-checkout' ),
				'design.font_scale'                 => __( 'Scales every checkout font step together, layout untouched. 110% helps older audiences or brands with generous type; 90% packs more onto the screen.', 'stm-smart-checkout' ),
				'design.logo_url'                   => __( 'The logo in the middle of the checkout header band. Empty = your site logo. A wide, flat version works best; it is displayed at up to 46px height.', 'stm-smart-checkout' ),
			);
		}
		return isset( $map[ $key ] ) ? $map[ $key ] : '';
	}

	private static function row_open( $key, $label ) {
		$id   = esc_attr( 'stmc-' . str_replace( '.', '-', $key ) );
		$help = self::help_text( $key );
		echo '<tr><th scope="row"><label for="' . $id . '">' . esc_html( $label ) . '</label>';
		if ( '' !== $help ) {
			echo ' <button type="button" class="stmc-help" aria-expanded="false" aria-describedby="' . $id . '-help" aria-label="' . esc_attr__( 'What does this setting do?', 'stm-smart-checkout' ) . '">?</button>'
				. '<span class="stmc-help__pop" role="tooltip" id="' . $id . '-help">' . esc_html( $help ) . '</span>';
		}
		echo '</th><td>';
	}

	private static function row_close( $desc = '' ) {
		if ( '' !== $desc ) {
			printf( '<p class="description">%s</p>', esc_html( $desc ) );
		}
		echo '</td></tr>';
	}

	private static function row_checkbox( $key, $label, $desc = '' ) {
		self::row_open( $key, $label );
		printf(
			'<label><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s> %4$s</label>',
			esc_attr( 'stmc-' . str_replace( '.', '-', $key ) ),
			esc_attr( self::name( $key ) ),
			checked( (bool) STMC_Settings::get( $key ), true, false ),
			esc_html__( 'Yes', 'stm-smart-checkout' )
		);
		self::row_close( $desc );
	}

	private static function row_color( $key, $label ) {
		self::row_open( $key, $label );
		printf(
			'<input type="color" id="%1$s" name="%2$s" value="%3$s">',
			esc_attr( 'stmc-' . str_replace( '.', '-', $key ) ),
			esc_attr( self::name( $key ) ),
			esc_attr( STMC_Settings::get( $key ) )
		);
		self::row_close();
	}

	private static function row_number( $key, $label, $min, $max ) {
		self::row_open( $key, $label );
		printf(
			'<input type="number" class="small-text" id="%1$s" name="%2$s" value="%3$s" min="%4$d" max="%5$d">',
			esc_attr( 'stmc-' . str_replace( '.', '-', $key ) ),
			esc_attr( self::name( $key ) ),
			esc_attr( STMC_Settings::get( $key ) ),
			(int) $min,
			(int) $max
		);
		self::row_close();
	}

	private static function row_select( $key, $label, array $choices ) {
		self::row_open( $key, $label );
		printf( '<select id="%1$s" name="%2$s">', esc_attr( 'stmc-' . str_replace( '.', '-', $key ) ), esc_attr( self::name( $key ) ) );
		foreach ( $choices as $value => $text ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $value ),
				selected( (string) STMC_Settings::get( $key ), (string) $value, false ),
				esc_html( $text )
			);
		}
		echo '</select>';
		self::row_close();
	}

	private static function row_text( $key, $label, $desc = '' ) {
		self::row_open( $key, $label );
		printf(
			'<input type="text" class="regular-text" id="%1$s" name="%2$s" value="%3$s">',
			esc_attr( 'stmc-' . str_replace( '.', '-', $key ) ),
			esc_attr( self::name( $key ) ),
			esc_attr( STMC_Settings::get( $key ) )
		);
		self::row_close( $desc );
	}

	private static function row_textarea( $key, $label, $desc = '' ) {
		self::row_open( $key, $label );
		printf(
			'<textarea class="large-text" rows="3" id="%1$s" name="%2$s">%3$s</textarea>',
			esc_attr( 'stmc-' . str_replace( '.', '-', $key ) ),
			esc_attr( self::name( $key ) ),
			esc_textarea( STMC_Settings::get( $key ) )
		);
		self::row_close( $desc );
	}

	private static function row_menu( $key, $label, $desc = '' ) {
		self::row_open( $key, $label );
		printf( '<select id="%1$s" name="%2$s">', esc_attr( 'stmc-' . str_replace( '.', '-', $key ) ), esc_attr( self::name( $key ) ) );
		printf( '<option value="0" %s>%s</option>', selected( 0, (int) STMC_Settings::get( $key ), false ), esc_html__( '— Off —', 'stm-smart-checkout' ) );
		foreach ( wp_get_nav_menus() as $menu ) {
			printf(
				'<option value="%1$d" %2$s>%3$s</option>',
				(int) $menu->term_id,
				selected( (int) $menu->term_id, (int) STMC_Settings::get( $key ), false ),
				esc_html( $menu->name )
			);
		}
		echo '</select>';
		self::row_close( $desc );
	}

	private static function row_url( $key, $label, $desc = '' ) {
		self::row_open( $key, $label );
		printf(
			'<input type="url" class="regular-text" id="%1$s" name="%2$s" value="%3$s" placeholder="https://">',
			esc_attr( 'stmc-' . str_replace( '.', '-', $key ) ),
			esc_attr( self::name( $key ) ),
			esc_attr( STMC_Settings::get( $key ) )
		);
		self::row_close( $desc );
	}
}
