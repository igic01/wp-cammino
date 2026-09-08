<?php
/**
 * Snapshot Name: Domov – gradienty a zvýraznenia
 *
 * @package Cammino
 */

ob_start();
$cammino_home_variant = 'home-v2';
require __DIR__ . '/home.php';
$cammino_home_v2_html = (string) ob_get_clean();
$cammino_home_v2_html = str_replace(
	'<main id="main-content">',
	'<main id="main-content" class="home-v2">',
	$cammino_home_v2_html
);

echo $cammino_home_v2_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
