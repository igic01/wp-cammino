<?php
/**
 * Internal visual snapshot wrapper.
 *
 * @package NStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$nstarter_post_id = get_queried_object_id();
$nstarter_html    = nstarter_get_snapshot_html( $nstarter_post_id );

if ( '' === trim( $nstarter_html ) ) {
	// First-use preview. The snapshot is persisted when Save or Regenerate is used.
	$nstarter_html = nstarter_render_source_template( $nstarter_post_id );
}
?>
<div id="nstarter-snapshot" data-nstarter-snapshot-root>
	<?php echo nstarter_expand_live_sections( $nstarter_html, $nstarter_post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
<?php
get_footer();
