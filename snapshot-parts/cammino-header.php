<?php
/**
 * Shared Cammino snapshot header.
 *
 * Expected variables: $cammino_active and $cammino_header_class.
 *
 * @package NStarter
 */

$cammino_active       = isset( $cammino_active ) ? (string) $cammino_active : '';
$cammino_header_class = isset( $cammino_header_class ) ? (string) $cammino_header_class : '';
$cammino_navigation   = array(
	'home'    => array( 'page' => 'index', 'label' => 'Domov' ),
	'about'   => array( 'page' => 'aboutus', 'label' => 'O nás' ),
	'stories' => array( 'page' => 'ss', 'label' => 'Príbehy' ),
	'events'  => array( 'page' => 'news', 'label' => 'Podujatia', 'suffix' => '#events' ),
	'news'    => array( 'page' => 'news', 'label' => 'Novinky' ),
	'contact' => array( 'page' => 'contact', 'label' => 'Kontakt' ),
);
?>
<header class="site-header<?php echo '' !== $cammino_header_class ? ' ' . esc_attr( $cammino_header_class ) : ''; ?>" data-header data-nstarter-transient-class="is-scrolled">
	<div class="container header-inner">
		<a class="brand" href="<?php echo esc_url( nstarter_cammino_page_url( 'index' ) ); ?>" aria-label="Cammino – domov">
			<img src="<?php echo esc_url( NSTARTER_URL . '/assets/cammino/logos/long_logo.svg' ); ?>" alt="Cammino" width="1666" height="297">
		</a>

		<button class="nav-toggle" type="button" aria-label="Otvoriť menu" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
			<i class="fa-solid fa-bars" aria-hidden="true" data-nstarter-transient-class="fa-bars fa-xmark"></i>
		</button>

		<nav class="site-nav" id="site-nav" aria-label="Hlavná navigácia" data-nav data-nstarter-transient-class="is-open">
			<?php foreach ( $cammino_navigation as $key => $item ) : ?>
				<?php
				$item_active = $key === $cammino_active || ( 'events' === $key && 'events' === $cammino_active );
				$item_url    = nstarter_cammino_page_url( $item['page'], $item['suffix'] ?? '' );
				?>
				<a<?php echo $item_active ? ' class="is-active" aria-current="page"' : ''; ?> href="<?php echo esc_url( $item_url ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
			<?php endforeach; ?>
			<a class="language-link" href="#" lang="en" aria-label="Switch to English">EN</a>
			<a class="button button--small button--coral nav-donate<?php echo 'donate' === $cammino_active ? ' is-active' : ''; ?>" href="<?php echo esc_url( nstarter_cammino_page_url( 'donate' ) ); ?>"<?php echo 'donate' === $cammino_active ? ' aria-current="page"' : ''; ?>>Prispieť <i class="fa-solid fa-heart" aria-hidden="true"></i></a>
		</nav>
	</div>
</header>
