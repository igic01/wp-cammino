<?php
/** Run with `php tests/page-top-spacing-workflow.php`. No WordPress/database changes. */

$root       = dirname( __DIR__ );
$base       = file_get_contents( $root . '/assets/css/cammino-base.css' );
$events     = file_get_contents( $root . '/assets/css/pages/events.css' );
$projects   = file_get_contents( $root . '/assets/css/pages/projects.css' );
$articles   = file_get_contents( $root . '/assets/css/pages/article.css' );
$checks     = 0;

function page_top_expect( $condition, $message ) {
	global $checks;
	++$checks;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

page_top_expect( str_contains( $base, '--page-start-space: clamp(1.25rem, 2.5vw, 2rem);' ), 'The design system defines one compact page-start gap.' );
page_top_expect( str_contains( $base, 'main#main-content > :first-child' ) && str_contains( $base, 'padding-top: var(--page-start-space);' ), 'Every visual page applies the shared gap to its first section.' );
page_top_expect( str_contains( $base, ':not(.donate-detail-page)' ), 'The non-hero donation detail layout is excluded.' );
page_top_expect( ! str_contains( $events, 'main#main-content { padding-top: 0; }' ), 'Events no longer remove the shared header offset.' );
page_top_expect( ! str_contains( $projects, 'main#main-content { padding-top: 0; }' ), 'Projects no longer remove the shared header offset.' );
page_top_expect( str_contains( $base, 'article:first-child > .article-hero:first-child' ), 'Post heroes use the same page-start gap.' );
page_top_expect( ! preg_match( '/\.article-hero\s*\{[^}]*padding-top:/s', $articles ), 'Article styles no longer add a second hero offset.' );

echo "Passed $checks shared page-top spacing checks.\n";
