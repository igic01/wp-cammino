<?php
/** Run with `php tests/template-merge-workflow.php`. No WordPress/database changes. */
require __DIR__ . '/post-fixtures.php';
function get_file_data( $file, $headers ) { return array( 'name' => basename( $file, '.php' ) ); }
function sanitize_file_name( $value ) { return preg_replace( '/[^a-zA-Z0-9_-]/', '', $value ); }
function get_page_template_slug( $id ) { return get_post_meta( $id, '_wp_page_template', true ); }
function metadata_exists( $type, $id, $key ) { return array_key_exists( $key, $GLOBALS['test_meta'][ $id ] ?? array() ); }
function home_url( $path = '' ) { return 'https://example.test' . $path; }
function add_query_arg( $args, $value = '', $url = '' ) {
	if ( ! is_array( $args ) ) { $args = array( $args => $value ); } else { $url = $value; }
	return $url . ( str_contains( $url, '?' ) ? '&' : '?' ) . http_build_query( $args );
}
require NSTARTER_PATH . '/inc/snapshots.php';
require NSTARTER_PATH . '/inc/html-merge.php';
require NSTARTER_PATH . '/inc/history.php';
$page = new WP_Post( 100, 'Test page' );
$page->post_type = 'page';
$GLOBALS['test_posts'][100] = $page;
$checks = 0;
function template_merge_expect( bool $condition, string $message ): void {
	global $checks;
	++$checks;
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}

foreach ( nstarter_get_source_templates() as $slug => $name ) {
	$GLOBALS['test_meta'][100]['_wp_page_template'] = nstarter_get_source_template_path( $slug );
	$html = nstarter_render_source_template( 100 );
	// Edit every text node. Decorative, live, and prototype nodes stay template-owned.
	$saved = preg_replace_callback( '/(?<=>)([^<]+)(?=<)/', static fn( array $match ): string => '' === trim( $match[1] ) ? $match[1] : 'CLIENT ' . $match[1], $html );
	$result = nstarter_merge_page_html( $html, $saved );
	template_merge_expect( ! $result['conflicts'], $slug . ': existing template snapshots merge without false conflicts: ' . implode( '; ', $result['conflicts'] ) );
	$doc = new DOMDocument();
	$previous = libxml_use_internal_errors( true );
	$doc->loadHTML( '<?xml encoding="UTF-8">' . $result['html'] );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );
	$xpath = new DOMXPath( $doc );
	foreach ( $xpath->query( '//text()[normalize-space(.) != ""]' ) as $text ) {
		$excluded = false;
		for ( $node = $text->parentNode; $node instanceof DOMElement; $node = $node->parentNode ) {
			if ( in_array( $node->tagName, array( 'script', 'style', 'svg', 'template', 'noscript' ), true ) || $node->hasAttribute( 'data-nstarter-live-section' ) || 'true' === $node->getAttribute( 'aria-hidden' ) || ( 'i' === $node->tagName && $node->hasAttribute( 'class' ) ) ) {
				$excluded = true;
				break;
			}
		}
		if ( ! $excluded ) {
			template_merge_expect( str_starts_with( $text->nodeValue, 'CLIENT ' ), $slug . ': edited text survives: ' . $text->nodeValue );
		}
	}
	template_merge_expect( ! str_contains( $result['html'], 'class="site-header"' ) && ! str_contains( $result['html'], 'class="site-footer"' ), $slug . ': the wrapper owns the shared shell.' );
}
// Simulate a deployed redesign of the dedicated feature-test template.
$GLOBALS['test_meta'][100]['_wp_page_template'] = nstarter_get_source_template_path( 'feature-test' );
$saved = str_replace(
    array( 'A page for testing saved content', 'First anonymous paragraph.', 'Second anonymous paragraph.', 'https://example.com/', 'Replace this test image', 'First test card' ),
    array( 'CLIENT heading', 'CLIENT first paragraph.', 'CLIENT second paragraph.', 'https://client.example/destination', 'CLIENT image description', 'CLIENT first card' ),
    nstarter_render_source_template( 100 )
);
$saved = str_replace( NSTARTER_URL . '/assets/images/placeholder.webp', 'https://client.example/photo.jpg', $saved );
define( 'CAMMINO_FEATURE_TEST_LAYOUT', 2 );
$result = nstarter_merge_page_html( nstarter_render_source_template( 100 ), $saved );
template_merge_expect( ! $result['conflicts'], 'Test design updates without ambiguous matches.' );
template_merge_expect( str_contains( $result['html'], 'feature-test--layout-2' ) && str_contains( $result['html'], 'feature-test__new-wrapper' ), 'The new layout and wrappers replace the old structure.' );
template_merge_expect( str_contains( $result['html'], '<h2 id="feature-test-title">CLIENT heading</h2>' ), 'The heading content survives a tag change.' );
template_merge_expect( str_contains( $result['html'], 'CLIENT first paragraph.' ) && str_contains( $result['html'], 'CLIENT second paragraph.' ), 'Anonymous paragraphs survive new wrappers in order.' );
template_merge_expect( str_contains( $result['html'], 'href="https://client.example/destination"' ), 'The saved link destination survives.' );
template_merge_expect( str_contains( $result['html'], 'src="https://client.example/photo.jpg"' ) && str_contains( $result['html'], 'alt="CLIENT image description"' ), 'The saved image and description survive.' );
template_merge_expect( str_contains( $result['html'], 'CLIENT first card' ), 'Repeatable card edits survive.' );
template_merge_expect( str_contains( $result['html'], 'New section from layout two' ), 'New sections retain template defaults.' );
echo "Passed $checks template merge checks.\n";
