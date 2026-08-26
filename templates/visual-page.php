<?php
/**
 * Bare document wrapper for opt-in editable Cammino pages.
 *
 * @package Cammino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$nstarter_post_id = get_queried_object_id();
$nstarter_html    = nstarter_get_snapshot_html( $nstarter_post_id );

if ( '' === trim( $nstarter_html ) ) {
	$nstarter_html = nstarter_render_source_template( $nstarter_post_id );
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
		<?php echo $nstarter_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
