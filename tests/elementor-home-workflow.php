<?php
/** Run with `php tests/elementor-home-workflow.php`. No WordPress/database changes. */

$root      = dirname( __DIR__ );
$bootstrap = file_get_contents( $root . '/elementor/bootstrap.php' );
$base      = file_get_contents( $root . '/elementor/elements/class-home-widget.php' );
$hero_php  = file_get_contents( $root . '/elementor/elements/class-hero.php' );
$template  = json_decode( file_get_contents( $root . '/elementor/templates/home-elementor.json' ), true, 512, JSON_THROW_ON_ERROR );
$hero_template = json_decode( file_get_contents( $root . '/elementor/templates/hero-elementor.json' ), true, 512, JSON_THROW_ON_ERROR );
$checks    = 0;

function elementor_home_expect( $condition, $message ) {
	global $checks;
	++$checks;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$expected = array(
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

$elements = $template['content'][0]['elements'][0]['elements'] ?? array();
$hero     = array_shift( $elements );
$actual   = array_column( $elements, 'widgetType' );

elementor_home_expect( 'Cammino - Home Elementor' === $template['title'], 'The importable page has a recognizable title.' );
elementor_home_expect( 'page' === $template['type'] && '0.4' === $template['version'], 'The file uses Elementor page-template format.' );
elementor_home_expect( str_contains( $hero['settings']['_css_classes'] ?? '', 'cammino-native-hero' ), 'The first Home section is the native Hero container.' );
elementor_home_expect( $expected === $actual, 'The remaining custom sections appear exactly once and in source order.' );
elementor_home_expect( 'main-content' === $template['content'][0]['elements'][0]['settings']['_element_id'], 'The section stack retains the Home CSS main-content scope.' );
elementor_home_expect( str_contains( $bootstrap, "add_action( 'elementor/widgets/register'" ), 'The theme registers widgets through the current Elementor hook.' );
elementor_home_expect( str_contains( $bootstrap, "str_contains( \$data, 'cammino-home-' )" ), 'Design body classes are limited to pages using these widgets.' );
elementor_home_expect( str_contains( $base, "return array( 'cammino-home' )" ), 'All widgets share the dedicated Cammino Home category.' );
elementor_home_expect( str_contains( $base, 'add_inline_editing_attributes' ), 'The shared renderer enables on-canvas inline text editing.' );
elementor_home_expect( str_contains( $hero_php, 'function cammino_elementor_native_hero' ) && ! str_contains( $hero_php, 'extends Cammino_Elementor_Home_Widget' ), 'The Hero source is a native element tree rather than a custom widget.' );
elementor_home_expect( $hero === $hero_template['content'][0], 'The standalone and full-page templates use the same native Hero tree.' );

$native_types = array();
$walk = static function ( array $element ) use ( &$walk, &$native_types ): void {
	if ( 'widget' === ( $element['elType'] ?? '' ) ) {
		$native_types[] = $element['widgetType'] ?? '';
	}
	foreach ( $element['elements'] ?? array() as $child ) {
		$walk( $child );
	}
};
$walk( $hero );
elementor_home_expect( array() === array_diff( $native_types, array( 'heading', 'text-editor', 'button', 'image', 'html' ) ), 'The Hero uses only Elementor Free core widgets.' );
elementor_home_expect( 2 === count( array_filter( $native_types, static fn( string $type ): bool => 'button' === $type ) ), 'Both Hero actions are independently editable Button widgets.' );
elementor_home_expect( 3 === count( array_filter( $native_types, static fn( string $type ): bool => 'image' === $type ) ), 'The Hero image and two note logos are independent Image widgets.' );

foreach ( $expected as $widget_type ) {
	$file = $root . '/elementor/elements/class-' . substr( $widget_type, strlen( 'cammino-home-' ) ) . '.php';
	elementor_home_expect( is_file( $file ), sprintf( 'Widget source exists for %s.', $widget_type ) );
	elementor_home_expect( str_contains( file_get_contents( $file ), "return '" . $widget_type . "'" ), sprintf( 'Widget %s exposes the imported type name.', $widget_type ) );
}

echo "Passed {$checks} Elementor Home checks.\n";
