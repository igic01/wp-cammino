<?php
/**
 * Bare document wrapper for opt-in editable Cammino pages.
 *
 * @package Cammino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( nstarter_is_preview_request() || isset( $_GET['cammino_snapshot'] ) ) {
	nocache_headers();
}

$nstarter_post_id = get_queried_object_id();
$nstarter_source_slug = nstarter_get_native_source_template_slug( $nstarter_post_id );
$nstarter_html    = nstarter_get_snapshot_html( $nstarter_post_id );

if ( '' === trim( $nstarter_html ) ) {
	$nstarter_html = nstarter_render_source_template( $nstarter_post_id );
}

// Existing home snapshots used separate placeholder/count controls. Upgrade
// only inside the editor preview; saving then persists the ordered event IDs.
if ( nstarter_is_preview_request() && function_exists( 'cammino_upgrade_legacy_home_event_picker_html' ) ) {
	$nstarter_html = cammino_upgrade_legacy_home_event_picker_html( $nstarter_html );
}

// Remove legacy header/footer live markers from previously saved snapshots.
$nstarter_html = (string) preg_replace(
	'#<div[^>]*data-nstarter-live-section=["\']cammino_site_(?:header|footer)["\'][^>]*>\s*</div>#i',
	'',
	$nstarter_html
);

// Remove the retired star icon from older saved snapshots without requiring
// editors to regenerate the page and lose their existing visual changes.
$nstarter_html = (string) preg_replace(
	'#(?<=\s)fa-\x73tar(?=[\s"\'])#i',
	'fa-heart',
	$nstarter_html
);

// Keep logos in older saved snapshots aligned with the current brand assets.
$nstarter_html = str_replace(
	array( '/assets/logos/long_logo.svg', '/assets/logos/logo.svg' ),
	array( '/assets/logos/new_long_logo.svg', '/assets/logos/new_logo.svg' ),
	$nstarter_html
);

// Replace legacy floating/note icons while preserving saved page copy.
$cammino_mark_logo_url = esc_url( NSTARTER_URL . '/assets/logos/new_logo.svg' );
$nstarter_html = (string) preg_replace(
	'#(<span\b[^>]*class=["\'][^"\']*\bfloating-path__icon\b[^>]*>)\s*<i\b[^>]*>.*?</i>\s*(</span>)#is',
	'$1<img src="' . $cammino_mark_logo_url . '" alt="" width="627" height="523">$2',
	$nstarter_html
);
$nstarter_html = (string) preg_replace(
	'#(<div\b[^>]*class=["\'][^"\']*\bfloating-path--bottom\b[^>]*>)\s*<i\b[^>]*>.*?</i>#is',
	'$1<img src="' . $cammino_mark_logo_url . '" alt="" width="627" height="523">',
	$nstarter_html
);
$nstarter_html = (string) preg_replace(
	'#<span\b[^>]*class=["\'][^"\']*\bnote-icon\b[^>]*>\s*<i\b[^>]*>.*?</i>\s*</span>#is',
	'<img src="' . $cammino_mark_logo_url . '" alt="" width="627" height="523">',
	$nstarter_html
);

// Keep the Main Project hero CTA current in older saved snapshots.
$nstarter_html = (string) preg_replace(
	'#<a\b(?=[^>]*class=["\'][^"\']*\bbutton--coral\b)(?=[^>]*href=["\'](?:\#o-projekte|https://www\.exallievi\.sk/darujmeusmev/?)["\'])[^>]*>(?=\s*Spoznajte\s+projekt\b)#iu',
	'<a class="button button--coral" href="https://www.exallievi.sk/darujmeusmev/" target="_blank" rel="noopener noreferrer">',
	$nstarter_html
);

// Convert legacy expandable QR controls in saved snapshots into donation links.
$nstarter_html = (string) preg_replace(
	'#<div\b(?=[^>]*class=["\'][^"\']*\bqr-placeholder\b)[^>]*>(.*?)</div>#is',
	'<a class="qr-placeholder" href="https://cammino.darujme.sk/darujmeusmev/" target="_blank" rel="noopener noreferrer" aria-label="Podporiť OZ Cammino (otvorí sa na novej karte)">$1</a>',
	$nstarter_html
);
$nstarter_html = (string) preg_replace(
	'#<a\b(?=[^>]*class=["\'][^"\']*\bqr-placeholder\b)[^>]*>#i',
	'<a class="qr-placeholder" href="https://cammino.darujme.sk/darujmeusmev/" target="_blank" rel="noopener noreferrer" aria-label="Podporiť OZ Cammino (otvorí sa na novej karte)">',
	$nstarter_html
);
$nstarter_html = (string) preg_replace(
	'#<small\b[^>]*class=["\'][^"\']*\bqr-hint\b[^>]*>.*?</small>#is',
	'',
	$nstarter_html
);

// Saved snapshots can contain older copies of the site shell. Always replace
// them with the current shared header and footer so every page stays visually
// consistent. These remain normal theme markup, not live-section markers.
$nstarter_html = (string) preg_replace(
	'#<header\b[^>]*class=["\'][^"\']*\bsite-header\b[^>]*>.*?</header>#is',
	'',
	$nstarter_html
);
$nstarter_html = (string) preg_replace(
	'#<a\b[^>]*class=["\'][^"\']*\bskip-link\b[^"\']*["\'][^>]*>.*?</a>#is',
	'',
	$nstarter_html
);

ob_start();
cammino_render_site_header();
$nstarter_html = (string) ob_get_clean() . $nstarter_html;

$nstarter_html = (string) preg_replace(
	'#<footer\b[^>]*class=["\'][^"\']*\bsite-footer\b[^>]*>.*?</footer>#is',
	'',
	$nstarter_html
);

ob_start();
cammino_render_site_footer();
$nstarter_html .= (string) ob_get_clean();
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#faf6ee">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<div id="nstarter-snapshot" data-nstarter-snapshot-root>
		<?php echo nstarter_expand_live_sections( $nstarter_html, $nstarter_post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
