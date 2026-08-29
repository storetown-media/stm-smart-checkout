<?php
/**
 * Distraction-free fullpage template.
 *
 * Rendered via template_include on cart/checkout surfaces when the active
 * theme has no server-side adapter (or the owner forces it): the theme's
 * header, menus, sidebars and footer are never rendered at all — the concept's
 * strategy 1 for theme-agnostic focus. wp_head()/wp_footer() stay, so styles,
 * analytics, consent tools and support widgets keep working; the header band
 * arrives through wp_body_open. Legal links stay reachable via the footer
 * line below (§5 TMG — a checkout must never orphan imprint & privacy).
 *
 * @package STM_Smart_Checkout
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'stmc-fullpage' ); ?>>
<?php wp_body_open(); ?>

<main class="stmc-fullpage__main" id="stmc-main">
	<?php
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	?>
</main>

<footer class="stmc-fullpage__footer">
	<?php STMC_Module_Focus::legal_footer(); ?>
</footer>

<?php wp_footer(); ?>
</body>
</html>
