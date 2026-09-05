<?php
/** Run with `php tests/post-workflow.php`. No WordPress/database changes. */
require __DIR__ . '/post-fixtures.php';
$checks = 0;
function expect( $condition, $message ) { global $checks; ++$checks; if ( ! $condition ) { throw new RuntimeException( $message ); } }
function ids( $posts ) { return array_map( static fn( $p ) => $p->ID, $posts ); }
expect( array_keys( cammino_get_post_placements() ) === array( 'event', 'project', 'impact-story' ), 'Three selectable types' );
expect( 'article' === cammino_get_post_placement(4), 'Legacy articles retain their type' );
expect( 'impact-story' === cammino_get_post_placement(8), 'Unstored metadata uses registered default' );
expect( ids( cammino_get_post_collection_posts( array( 'type' => 'impact-story' ), 1 ) ) === array(8,3), 'Default type matches typed queries' );
cammino_migrate_post_placements_from_slugs();
expect( 'article' === cammino_get_post_placement(8) && 'event' === cammino_get_post_placement(1), 'Upgrade preserves old untyped articles and events' );
$GLOBALS['test_meta'][8][CAMMINO_POST_PLACEMENT_META] = 'project';
cammino_migrate_post_placements_from_slugs();
expect( 'project' === cammino_get_post_placement(8), 'Migration is idempotent' );
expect( ids( cammino_get_post_collection_posts( array( 'type'=>'project' ), 8 ) ) === array(2), 'Latest query excludes current, drafts, private and password posts' );
$before = count($GLOBALS['test_queries']);
expect( cammino_get_post_collection_posts( array('mode'=>'selected','ids'=>array()), 1 ) === array(), 'Empty manual selection is empty' );
expect( count($GLOBALS['test_queries']) === $before, 'Empty selection never issues an unbounded post__in query' );
expect( ids(cammino_get_post_collection_posts(array('mode'=>'selected','ids'=>array(3,1,2,5,6,7)),1)) === array(3,2), 'Manual order and visibility are preserved' );
$args = cammino_sanitize_post_collection(array('title'=>'<b>Úsmev</b>','mode'=>'bad','type'=>'bad','limit'=>999,'ids'=>array(2,2,0,'bad',array(),3)));
expect( $args['title']==='Úsmev' && $args['type']==='all' && $args['mode']==='latest' && $args['limit']===6 && $args['ids']===array(2,3), 'Malformed configuration is normalized' );
expect( '' === cammino_render_post_collection(array('mode'=>'selected','ids'=>array()),1), 'Empty section hidden publicly' );
expect( str_contains(cammino_render_post_collection(array('mode'=>'selected','ids'=>array()),1,true),'cammino-collection-empty'), 'Editor explains empty selection' );
$marker = cammino_get_post_collection_block(array('title'=>'Súvisiace príbehy','mode'=>'selected','ids'=>array(3)));
$html = cammino_expand_post_live_content($marker.cammino_get_post_collection_template(),1);
expect( str_contains($html,'Príbeh Gama'), 'Live block renders selected post' );
$inert = substr($html,strpos($html,'<template'));
expect( ! str_contains($inert,'related-card'), 'Builder template stays inert and contains no stale cards' );
$GLOBALS['test_posts'][3]->post_title = 'Aktualizovaný príbeh';
expect( str_contains(cammino_expand_post_live_content($marker,1),'Aktualizovaný príbeh'), 'Published title changes appear without resaving host post' );
$GLOBALS['test_posts'][3]->post_status = 'draft';
expect( ! str_contains(cammino_expand_post_live_content($marker,1),'Aktualizovaný príbeh'), 'Unpublished selections disappear' );
$GLOBALS['test_posts'][3]->post_status = 'publish';
$saved = '<p data-nstarter-content-item data-nstarter-content-type="paragraph">Zachovať moje úpravy.</p>';
cammino_update_post_visual_content(4,$saved);
$upgraded = cammino_get_post_visual_content(4);
expect( str_contains($upgraded,$saved) && str_contains($upgraded,'data-nstarter-content-template="posts"'), 'Saved body gains tools without regeneration' );
expect( strpos($upgraded,'data-nstarter-content-type="posts"') < strpos($upgraded,'<template'), 'Default collection precedes inert templates so new items append in order' );
cammino_update_post_visual_content(4,$upgraded);
expect( cammino_get_post_visual_content(4)===$upgraded, 'Upgrade is stable after save/reload' );
cammino_update_post_visual_content(4,$saved.'<!-- cammino-post-collections-v1 -->'.cammino_get_post_collection_template());
expect( substr_count(cammino_get_post_visual_content(4),'data-nstarter-content-type="posts"')===1, 'Removed default block does not respawn; only template remains' );
foreach(array(1=>'O podujatí',2=>'O projekte',3=>'Čo sa zmenilo') as $id=>$heading) {
	expect(str_contains(cammino_render_post_visual_content($id),$heading),'New body fits type '.$id);
}
$_POST = array('cammino_post_settings_nonce'=>'test-nonce','cammino_post_placement'=>'project','cammino_project_period'=>'2026–2027');
cammino_save_post_settings(4);
expect(get_post_meta(4,CAMMINO_POST_SNAPSHOT_META,true)===$saved.'<!-- cammino-post-collections-v1 -->'.cammino_get_post_collection_template(),'Type changes preserve visual snapshot');
expect(cammino_get_post_placement(4)==='project' && get_post_meta(4,'_cammino_project_period',true)==='2026–2027','WordPress settings save');
$GLOBALS['test_can_edit']=false;
$_POST['cammino_post_placement']='event';
cammino_save_post_settings(4);
expect(cammino_get_post_placement(4)==='project','Unauthorized metadata update refused');
$_POST=array('post_id'=>1,'nonce'=>'test-nonce','settings'=>'{}');
try { cammino_ajax_post_collection(); } catch(TestJsonResponse $r) { expect($r->status===403,'Unauthorized AJAX refused'); }
$GLOBALS['test_can_edit']=true;
$_POST['nonce']='invalid';
try { cammino_ajax_post_collection(); } catch(TestJsonResponse $r) { expect($r->status===403,'Invalid nonce refused'); }
$_POST=array('post_id'=>1,'nonce'=>'test-nonce','settings'=>'{"type":"project"}','search'=>'Beta');
try { cammino_ajax_post_collection(); } catch(TestJsonResponse $r) { expect(array_column($r->payload['data']['posts'],'id')===array(2),'Authenticated search returns matching published posts'); }
echo "Passed $checks post workflow checks.\n";
