<?php
/** Run with `php tests/elementor-home-workflow.php`. No WordPress/database changes. */

$root      = dirname( __DIR__ );
$bootstrap = file_get_contents( $root . '/elementor/bootstrap.php' );
$base      = file_get_contents( $root . '/elementor/elements/class-home-widget.php' );
$template  = json_decode( file_get_contents( $root . '/elementor/templates/home-elementor.json' ), true, 512, JSON_THROW_ON_ERROR );
$checks    = 0;

function elementor_home_expect( $condition, $message ) {
	global $checks;
	++$checks;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$expected = array(
	'cammino-home-hero',
	'cammino-home-about',
	'cammino-home-projects',
	'cammino-home-events',
	'cammino-home-story',
	'cammino-home-main-project',
	'cammino-home-involvement',
	'cammino-home-donate',
	'cammino-home-partners',
	'cammino-home-newsletter',
);

$widgets = $template['content'][0]['elements'][0]['elements'] ?? array();
$actual  = array_column( $widgets, 'widgetType' );

elementor_home_expect( 'Cammino - Home Elementor' === $template['title'], 'The importable page has a recognizable title.' );
elementor_home_expect( 'page' === $template['type'] && '0.4' === $template['version'], 'The file uses Elementor page-template format.' );
elementor_home_expect( $expected === $actual, 'All homepage sections appear exactly once and in source order.' );
elementor_home_expect( 'main-content' === $template['content'][0]['elements'][0]['settings']['_element_id'], 'The section stack retains the Home CSS main-content scope.' );
elementor_home_expect( str_contains( $bootstrap, "add_action( 'elementor/widgets/register'" ), 'The theme registers widgets through the current Elementor hook.' );
elementor_home_expect( str_contains( $bootstrap, "str_contains( \$data, 'cammino-home-' )" ), 'Design body classes are limited to pages using these widgets.' );
elementor_home_expect( str_contains( $base, "return array( 'cammino-home' )" ), 'All widgets share the dedicated Cammino Home category.' );
elementor_home_expect( str_contains( $base, 'add_inline_editing_attributes' ), 'The shared renderer enables on-canvas inline text editing.' );

foreach ( $expected as $widget_type ) {
	$file = $root . '/elementor/elements/class-' . substr( $widget_type, strlen( 'cammino-home-' ) ) . '.php';
	elementor_home_expect( is_file( $file ), sprintf( 'Widget source exists for %s.', $widget_type ) );
	elementor_home_expect( str_contains( file_get_contents( $file ), "return '" . $widget_type . "'" ), sprintf( 'Widget %s exposes the imported type name.', $widget_type ) );
}

echo "Passed {$checks} Elementor Home checks.\n";

