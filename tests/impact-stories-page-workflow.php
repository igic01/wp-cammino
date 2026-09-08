<?php
/** Run with `php tests/impact-stories-page-workflow.php`. No WordPress/database changes. */

$root      = dirname( __DIR__ );
$template  = file_get_contents( $root . '/snapshot-templates/impact-stories.php' );
$styles    = file_get_contents( $root . '/assets/css/pages/impact-stories.css' );
$script    = file_get_contents( $root . '/assets/js/pages/impact-stories.js' );
$theme     = file_get_contents( $root . '/functions.php' );
$checks    = 0;

function impact_stories_expect( $condition, $message ) {
	global $checks;
	++$checks;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

impact_stories_expect( str_contains( $template, 'Snapshot Name: Príbehy s dopadom' ), 'The impact-stories source is registered as a native snapshot.' );
impact_stories_expect( str_contains( $template, 'class="impact-stories-hero"' ), 'The page has a dedicated hero.' );
impact_stories_expect( str_contains( $template, '<h1 id="impact-stories-title">' ) && str_contains( $template, 'impact-stories-hero__copy' ), 'The hero contains an editable title and description.' );
impact_stories_expect( str_contains( $template, 'impact-stories-hero__media' ) && str_contains( $template, '<img ' ), 'The hero contains an editable image.' );
impact_stories_expect( str_contains( $template, "'impact_stories_count'" ) && str_contains( $template, "'control' => 'repeat'" ), 'The story collection uses a repeatable visual-editor variable.' );
impact_stories_expect( str_contains( $template, "'max'     => 100" ), 'The collection supports a large number of stories.' );
impact_stories_expect( str_contains( $template, 'data-nstarter-variable-items' ) && str_contains( $template, 'data-nstarter-variable-template' ), 'The variable collection has an item container and clone template.' );
impact_stories_expect( preg_match( '/<article class="impact-story"[^>]*data-nstarter-variable-item[^>]*>\s*<h2>.*?<\/h2>\s*<p>.*?<\/p>\s*<\/article>/s', $template ), 'Every story item consists of exactly a title and paragraph.' );
impact_stories_expect( str_contains( $styles, '.impact-stories-hero__grid' ) && str_contains( $styles, '.impact-stories-grid' ), 'The hero and repeatable story grid are styled.' );
impact_stories_expect( str_contains( $styles, '.impact-stories-list[data-nstarter-variable-value="0"]' ), 'An empty public story collection is hidden.' );
impact_stories_expect( str_contains( $script, 'IntersectionObserver' ) && str_contains( $script, 'editorPreview' ), 'Reveal animation is progressive and editor-safe.' );
impact_stories_expect( str_contains( $theme, "'impact-stories' => array(" ) && str_contains( $theme, '/assets/css/pages/impact-stories.css' ) && str_contains( $theme, '/assets/js/pages/impact-stories.js' ), 'The theme registers the new page assets.' );
impact_stories_expect( str_contains( $theme, "'impact-stories' => array( 'impact-stories-page' )" ), 'The template receives its page body class.' );

echo "Passed $checks impact stories page checks.\n";
