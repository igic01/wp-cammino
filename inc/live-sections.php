<?php
/**
 * Runtime-rendered sections excluded from saved visual snapshots.
 *
 * @package Cammino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register a live-section renderer.
 */
function nstarter_register_live_section( string $id, callable $renderer ): void {
	global $nstarter_live_sections;

	if ( ! is_array( $nstarter_live_sections ) ) {
		$nstarter_live_sections = array();
	}

	$nstarter_live_sections[ sanitize_key( $id ) ] = $renderer;
}

/**
 * Print an empty marker that can safely remain in a saved snapshot.
 */
function nstarter_live_section( string $id, array $args = array() ): void {
	$encoded_args = base64_encode( (string) wp_json_encode( $args ) );

	echo '<div class="nstarter-live-section" data-nstarter-live-section="' . esc_attr( sanitize_key( $id ) ) . '" data-nstarter-live-args="' . esc_attr( $encoded_args ) . '"></div>';
}

/**
 * Expand saved markers with fresh runtime HTML.
 */
function nstarter_expand_live_sections( string $html, int $post_id ): string {
	global $nstarter_live_sections;

	$renderers = is_array( $nstarter_live_sections ) ? $nstarter_live_sections : array();
	$pattern   = '#<div([^>]*data-nstarter-live-section=["\']([^"\']+)["\'][^>]*)>\s*</div>#i';

	return (string) preg_replace_callback(
		$pattern,
		static function ( array $match ) use ( $post_id, $renderers ): string {
			$attributes = $match[1];
			$id         = sanitize_key( $match[2] );
			$args       = array();

			if ( preg_match( '#data-nstarter-live-args=["\']([^"\']*)["\']#i', $attributes, $args_match ) ) {
				$decoded = json_decode( (string) base64_decode( $args_match[1], true ), true );
				$args    = is_array( $decoded ) ? $decoded : array();
			}

			$content = isset( $renderers[ $id ] ) && is_callable( $renderers[ $id ] )
				? (string) call_user_func( $renderers[ $id ], $args, $post_id )
				: '<p class="cammino-live-section-error">' . esc_html__( 'This live section is unavailable.', 'cammino' ) . '</p>';

			$attributes = preg_replace( '#\scontenteditable=["\'][^"\']*["\']#i', '', $attributes );

			return '<div' . $attributes . ' contenteditable="false">' . $content . '</div>';
		},
		$html
	);
}
