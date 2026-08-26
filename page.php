<?php
/**
 * Delegate ordinary WordPress pages, including Elementor pages, to Astra.
 *
 * @package NStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require get_template_directory() . '/page.php';
