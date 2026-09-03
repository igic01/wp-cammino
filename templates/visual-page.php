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
$nstarter_html    = nstarter_get_snapshot_html( $nstarter_post_id );

if ( '' === trim( $nstarter_html ) ) {
	$nstarter_html = nstarter_render_source_template( $nstarter_post_id );
}

// Remove legacy header/footer live markers from previously saved snapshots.
$nstarter_html = (string) preg_replace(
	'#<div[^>]*data-nstarter-live-section=["\']cammino_site_(?:header|footer)["\'][^>]*>\s*</div>#i',
	'',
	$nstarter_html
);

// Header and footer are regular snapshot content. Add them only when an older
// snapshot or a clean source template does not already contain them.
if ( ! preg_match( '#<header[^>]*class=["\'][^"\']*\bsite-header\b#i', $nstarter_html ) ) {
	ob_start();
	cammino_render_site_header();
	$nstarter_html = (string) ob_get_clean() . $nstarter_html;
}

if ( ! preg_match( '#<footer[^>]*class=["\'][^"\']*\bsite-footer\b#i', $nstarter_html ) ) {
	ob_start();
	cammino_render_site_footer();
	$nstarter_html .= (string) ob_get_clean();
}
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
