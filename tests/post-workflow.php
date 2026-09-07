<?php
/** Run with `php tests/post-workflow.php`. No WordPress/database changes. */
require __DIR__ . '/post-fixtures.php';

$checks = 0;
function expect( $condition, $message ) {
	global $checks;
	++$checks;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

expect( array_keys( cammino_get_post_placements() ) === array( 'event', 'project', 'impact-story' ), 'Three selectable types' );
expect( 'article' === cammino_get_post_placement( 4 ), 'Legacy articles retain their type' );
expect( 'impact-story' === cammino_get_post_placement( 8 ), 'Unstored metadata uses registered default' );

cammino_migrate_post_placements_from_slugs();
expect( 'article' === cammino_get_post_placement( 8 ) && 'event' === cammino_get_post_placement( 1 ), 'Upgrade preserves old untyped articles and events' );
cammino_migrate_event_categories();
$event_term_id = cammino_get_event_category_id( false );
expect( $event_term_id > 0 && in_array( $event_term_id, $GLOBALS['test_post_terms'][1], true ), 'Existing events move to the dedicated event category' );
cammino_sync_event_category( 1, 'project' );
expect( ! in_array( $event_term_id, $GLOBALS['test_post_terms'][1], true ), 'Changing an event type removes the dedicated event category' );
cammino_sync_event_category( 1, 'event' );
$GLOBALS['test_meta'][8][CAMMINO_POST_PLACEMENT_META] = 'project';
cammino_migrate_post_placements_from_slugs();
expect( 'project' === cammino_get_post_placement( 8 ), 'Migration is idempotent' );

expect( cammino_update_visual_post_details( 1, 'Nový názov podujatia', '2026-10-15T16:00', 'Bratislava', true, 'Workshop' ), 'Visual editor saves event title, date, location, type, and photo visibility' );
expect( get_the_title( 1 ) === 'Nový názov podujatia', 'Visual editor updates the WordPress post title' );
expect( get_post_meta( 1, CAMMINO_EVENT_DATE_META, true ) === '2026-10-15T16:00' && get_post_meta( 1, CAMMINO_EVENT_LOCATION_META, true ) === 'Bratislava', 'Visual editor updates event metadata' );
expect( get_post_meta( 1, CAMMINO_EVENT_TYPE_META, true ) === 'Workshop', 'Visual editor updates the optional event type' );
expect( get_post_meta( 1, CAMMINO_EVENT_HIDE_IMAGE_META, true ) === '1', 'Visual editor can hide the event photo' );
expect( cammino_update_visual_post_details( 1, 'Nový názov podujatia', '2026-10-15T16:00', 'Bratislava', true, '' ) && '' === get_post_meta( 1, CAMMINO_EVENT_TYPE_META, true ), 'Event type can be left empty' );
expect( ! cammino_update_visual_post_details( 1, '', 'not-a-date', 'Bratislava' ), 'Invalid visual event details are rejected' );

$saved = '<p data-nstarter-content-item data-nstarter-content-type="paragraph">Zachovať moje úpravy.</p>';
cammino_update_post_visual_content( 4, $saved );
expect( cammino_get_post_visual_content( 4 ) === $saved, 'Saved article content is preserved' );

$legacy_marker = '<div data-nstarter-live-section="cammino_post_collection" data-nstarter-live-args=""></div>';
$legacy_inline = '<div class="article-content-block article-content-block--posts" data-nstarter-content-item="" data-nstarter-content-type="posts">' . $legacy_marker . '</div>';
$legacy_bottom = '<div class="article-content-block article-content-block--posts" data-cammino-post-bottom data-nstarter-variable-section="cammino_related_posts">' . $legacy_marker . '</div>';
$legacy_template = '<template data-nstarter-content-template="posts">' . $legacy_inline . '</template>';
cammino_update_post_visual_content( 4, $saved . '<!-- cammino-post-collections-v1 -->' . $legacy_inline . $legacy_bottom . $legacy_template );
$cleaned = cammino_get_post_visual_content( 4 );
expect( str_contains( $cleaned, $saved ), 'Legacy cleanup keeps the article body' );
expect( ! str_contains( $cleaned, 'cammino_post_collection' ), 'Legacy Related Posts live markers are removed' );
expect( ! str_contains( $cleaned, 'data-cammino-post-bottom' ), 'Legacy bottom Related Posts elements are removed' );
expect( ! str_contains( $cleaned, 'data-nstarter-content-template="posts"' ), 'Legacy Related Posts templates are removed' );
expect( ! str_contains( $cleaned, 'cammino-post-collections-v1' ), 'Legacy collection migration comments are removed' );
cammino_update_post_visual_content( 4, $cleaned );
expect( cammino_get_post_visual_content( 4 ) === $cleaned, 'Related Posts cleanup is stable after save and reload' );

$fresh_body = cammino_render_post_visual_content( 1 );
$fresh_visible_body = preg_replace( '#<template\b[^>]*>.*?</template>#is', '', $fresh_body );
expect( ! str_contains( $fresh_visible_body, 'data-nstarter-content-item' ), 'New post body starts empty' );
expect( substr_count( $fresh_body, 'data-nstarter-content-template=' ) === 3, 'Inline builder provides title, paragraph and image templates' );
expect( str_contains( $fresh_body, '/assets/images/placeholder.webp' ), 'New image template uses the local placeholder' );
expect( ! str_contains( $fresh_body, 'content-template="posts"' ), 'Fresh post body has no Related Posts element' );

$_POST = array(
	'cammino_post_settings_nonce' => 'test-nonce',
	'cammino_post_placement'      => 'project',
	'cammino_project_period'      => '2026–2027',
);
cammino_save_post_settings( 4 );
expect( get_post_meta( 4, CAMMINO_POST_SNAPSHOT_META, true ) === $cleaned, 'Type changes preserve the cleaned visual snapshot' );
expect( cammino_get_post_placement( 4 ) === 'project' && get_post_meta( 4, '_cammino_project_period', true ) === '2026–2027', 'WordPress settings save' );

$GLOBALS['test_can_edit'] = false;
$_POST['cammino_post_placement'] = 'event';
cammino_save_post_settings( 4 );
expect( cammino_get_post_placement( 4 ) === 'project', 'Unauthorized metadata update refused' );

echo "Passed $checks post workflow checks.\n";
