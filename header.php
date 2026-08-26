<?php
/**
 * Delegate the ordinary site header to the Astra parent theme.
 *
 * @package NStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$parent_header = nstarter_get_parent_theme_file( 'header.php' );

if ( '' !== $parent_header ) {
	require $parent_header;
	return;
}

// Emergency fallback: render a valid document even when Astra is unavailable.
require NSTARTER_PATH . '/header-nstarter.php';
