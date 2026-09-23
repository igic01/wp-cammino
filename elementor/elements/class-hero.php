<?php
/**
 * Native Elementor element tree for the homepage Hero.
 *
 * This is deliberately not a custom Widget_Base class. Every returned child is
 * an Elementor Free container or core widget and can therefore be selected,
 * moved, duplicated, styled, or removed independently in the editor.
 *
 * @package Cammino
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'CAMMINO_ELEMENTOR_BUILD' ) ) {
	exit;
}

/**
 * Return a native Elementor Hero element.
 *
 * @param string $theme_url Public URL of this theme, without a trailing slash.
 * @return array<string,mixed>
 */
function cammino_elementor_native_hero( string $theme_url ): array {
	$theme_url = untrailingslashit( $theme_url );

	$widget = static function ( string $id, string $type, array $settings ): array {
		return array(
			'id'         => $id,
			'elType'     => 'widget',
			'widgetType' => $type,
			'isInner'    => false,
			'settings'   => $settings,
			'elements'   => array(),
		);
	};

	$container = static function ( string $id, array $settings, array $elements = array(), bool $inner = true ): array {
		return array(
			'id'       => $id,
			'elType'   => 'container',
			'isInner'  => $inner,
			'settings' => $settings,
			'elements' => $elements,
		);
	};

	$primary_button = $widget(
		'a1000006',
		'button',
		array(
			'text'          => 'Podporte naše aktivity',
			'link'          => array( 'url' => CAMMINO_DONATE_URL, 'is_external' => true, 'nofollow' => false ),
			'selected_icon' => array( 'value' => 'fas fa-arrow-right-long', 'library' => 'fa-solid' ),
			'icon_align'    => 'right',
			'_css_classes'  => 'cammino-native-button button--coral',
		)
	);

	$secondary_button = $widget(
		'a1000007',
		'button',
		array(
			'text'          => 'Spoznajte Cammino',
			'link'          => array( 'url' => '#about', 'is_external' => false, 'nofollow' => false ),
			'selected_icon' => array( 'value' => 'fas fa-arrow-right', 'library' => 'fa-solid' ),
			'icon_align'    => 'right',
			'_css_classes'  => 'cammino-native-text-link',
		)
	);

	$copy = $container(
		'a1000003',
		array( 'flex_direction' => 'column', '_css_classes' => 'hero-copy' ),
		array(
			$widget( 'a1000004', 'heading', array( 'title' => 'Priestor, kde sa ľudia spájajú pre <em>pozitívnu zmenu</em>', 'header_size' => 'h1' ) ),
			$widget( 'a1000005', 'text-editor', array( 'editor' => '<p>OZ Cammino prepája vzdelávanie, osobný rozvoj a komunitnú spoluprácu. Vytvárame príležitosti, vďaka ktorým môžu mladí ľudia rozvíjať svoj potenciál a aktívne meniť svoje okolie.</p>', '_css_classes' => 'hero-lead' ) ),
			$container( 'a1000008', array( 'flex_direction' => 'row', 'flex_wrap' => 'wrap', '_css_classes' => 'hero-actions' ), array( $primary_button, $secondary_button ) ),
		)
	);

	$hero_image = $container(
		'a1000010',
		array( 'flex_direction' => 'column', '_css_classes' => 'hero-image-wrap' ),
		array(
			$widget( 'a1000011', 'image', array( 'image' => array( 'url' => $theme_url . '/assets/images/placeholder.webp', 'id' => '' ), 'image_size' => 'full' ) ),
			$container( 'a1000012', array( '_css_classes' => 'image-tint' ) ),
		)
	);

	$note_top = $container(
		'a1000013',
		array( 'flex_direction' => 'row', 'align_items' => 'center', '_css_classes' => 'hero-note hero-note--top' ),
		array(
			$widget( 'a1000014', 'image', array( 'image' => array( 'url' => $theme_url . '/assets/logos/new_logo.svg', 'id' => '' ), 'image_size' => 'full' ) ),
			$widget( 'a1000015', 'text-editor', array( 'editor' => '<p><strong>Rozvíjame potenciál</strong> ľudí aj komunít</p>' ) ),
		)
	);

	$note_bottom = $container(
		'a1000016',
		array( 'flex_direction' => 'row', 'align_items' => 'center', '_css_classes' => 'hero-note hero-note--bottom' ),
		array(
			$widget( 'a1000017', 'image', array( 'image' => array( 'url' => $theme_url . '/assets/logos/new_logo.svg', 'id' => '' ), 'image_size' => 'full' ) ),
			$widget( 'a1000018', 'text-editor', array( 'editor' => '<p><strong>Krok za krokom</strong> Spoločne</p>' ) ),
		)
	);

	$visual = $container(
		'a1000009',
		array( 'flex_direction' => 'column', '_css_classes' => 'hero-visual is-visible' ),
		array(
			$hero_image,
			$note_top,
			$note_bottom,
			$widget( 'a1000019', 'html', array( 'html' => '<svg class="hero-path" viewBox="0 0 170 120" aria-hidden="true"><path d="M8 104C35 110 37 63 70 69C105 75 97 18 154 14" pathLength="1"></path><path class="path-arrow" d="m145 5 11 9-12 7" pathLength="1"></path></svg>', '_css_classes' => 'hero-path-widget' ) ),
		)
	);

	return $container(
		'a1000001',
		array( 'content_width' => 'full', 'flex_direction' => 'column', 'html_tag' => 'section', '_css_classes' => 'hero section cammino-native-hero' ),
		array(
			$container( 'a1000002', array( 'content_width' => 'full', 'flex_direction' => 'row', 'flex_direction_mobile' => 'column', '_css_classes' => 'container hero-grid' ), array( $copy, $visual ) ),
		),
		false
	);
}
