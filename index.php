<?php
/**
 * Delegate the default template fallback to the Astra parent theme.
 *
 * @package NStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require get_template_directory() . '/index.php';
