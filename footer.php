<?php
/**
 * Delegate the ordinary site footer to the Astra parent theme.
 *
 * @package NStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$parent_footer = nstarter_get_parent_theme_file( 'footer.php' );

if ( '' !== $parent_footer ) {
	require $parent_footer;
	return;
}

// Emergency fallback: close the document even when Astra is unavailable.
require NSTARTER_PATH . '/footer-nstarter.php';
