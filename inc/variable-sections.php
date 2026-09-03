<?php
/**
 * Snapshot-native section variables edited directly in the visual editor.
 *
 * @package Cammino
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print the data attributes that define one editable section variable.
 *
 * Repeat controls resize elements inside `data-nstarter-variable-items`.
 * Text controls update nodes marked with `data-nstarter-variable-output`.
 *
 * @param string              $id     Stable variable-section ID.
 * @param array<string,mixed> $config Variable configuration.
 */
function nstarter_variable_section_attributes( string $id, array $config = array() ): void {
	$id = sanitize_key( $id );

	if ( '' === $id ) {
		return;
	}

	$type    = isset( $config['type'] ) && in_array( $config['type'], array( 'text', 'boolean' ), true )
		? $config['type']
		: 'number';
	$control = isset( $config['control'] ) && 'text' === $config['control'] ? 'text' : 'repeat';
	$value   = isset( $config['value'] ) && is_scalar( $config['value'] ) ? (string) $config['value'] : '';
	$label   = isset( $config['label'] ) && is_scalar( $config['label'] )
		? (string) $config['label']
		: ucwords( str_replace( array( '-', '_' ), ' ', $id ) );
	$attributes = array(
		'data-nstarter-variable-section' => $id,
		'data-nstarter-variable-label'   => $label,
		'data-nstarter-variable-type'    => $type,
		'data-nstarter-variable-control' => $control,
		'data-nstarter-variable-value'   => $value,
	);

	if ( isset( $config['token'] ) && is_scalar( $config['token'] ) ) {
		$token = sanitize_key( (string) $config['token'] );
		if ( '' !== $token ) {
			$attributes['data-nstarter-variable-token'] = $token;
		}
	}

	foreach ( array( 'min', 'max', 'step' ) as $name ) {
		if ( isset( $config[ $name ] ) && is_numeric( $config[ $name ] ) ) {
			$attributes[ 'data-nstarter-variable-' . $name ] = (string) $config[ $name ];
		}
	}

	foreach ( $attributes as $name => $attribute_value ) {
		echo ' ' . esc_attr( $name ) . '="' . esc_attr( $attribute_value ) . '"';
	}
}
