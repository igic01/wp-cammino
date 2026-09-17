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
$nstarter_restore_id = '';
$nstarter_saved_html = null;
if ( nstarter_is_preview_request() && current_user_can( 'edit_post', $nstarter_post_id ) && isset( $_GET['nstarter_restore_version'], $_GET['nstarter_restore_token'], $_GET['nstarter_restore_source'] ) ) {
	$nstarter_requested_token = sanitize_text_field( wp_unslash( $_GET['nstarter_restore_token'] ) );
	$nstarter_requested_source = sanitize_text_field( wp_unslash( $_GET['nstarter_restore_source'] ) );
	if ( $nstarter_source_slug === $nstarter_requested_source && hash_equals( nstarter_snapshot_token( $nstarter_post_id ), $nstarter_requested_token ) ) {
		$nstarter_version = nstarter_get_snapshot_version( $nstarter_post_id, sanitize_text_field( wp_unslash( $_GET['nstarter_restore_version'] ) ) );
		if ( $nstarter_version ) {
			$nstarter_saved_html = $nstarter_version['html'];
			$nstarter_restore_id = $nstarter_version['id'];
		}
	}
}
$nstarter_merge = nstarter_render_merged_page_html( $nstarter_post_id, $nstarter_saved_html );
$nstarter_html = $nstarter_merge['html'];

// The shell always comes from the current theme, outside saved content.
ob_start();
cammino_render_site_header();
$nstarter_html = (string) ob_get_clean() . $nstarter_html;
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
	<div id="nstarter-snapshot" data-nstarter-snapshot-root<?php if ( nstarter_is_preview_request() && current_user_can( 'edit_post', $nstarter_post_id ) ) : ?>
		data-nstarter-snapshot-source="<?php echo esc_attr( $nstarter_source_slug ); ?>"
		data-nstarter-snapshot-token="<?php echo esc_attr( nstarter_snapshot_token( $nstarter_post_id ) ); ?>"
		data-nstarter-restored-version="<?php echo esc_attr( $nstarter_restore_id ); ?>"
		data-nstarter-merge-conflicts="<?php echo esc_attr( wp_json_encode( $nstarter_merge['conflicts'] ) ); ?>"
		<?php endif; ?>>
		<?php echo nstarter_expand_live_sections( $nstarter_html, $nstarter_post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
