<?php
/**
 * Snapshot Name: Domov – farebné bloky
 *
 * @package Cammino
 */

ob_start();
$cammino_home_variant = 'home-v3';
require __DIR__ . '/home.php';
$cammino_home_v3_html = (string) ob_get_clean();
$cammino_home_v3_html = str_replace(
	'<main id="main-content">',
	'<main id="main-content" class="home-v3">',
	$cammino_home_v3_html
);

echo $cammino_home_v3_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
