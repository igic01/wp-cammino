<?php
/**
 * Snapshot Name: Aktuálna zbierka — Darujme úsmev
 *
 * @package Cammino
 */

$cammino_campaign_url = 'https://cammino.darujme.sk/darujmeusmev/';
$cammino_about_url    = nstarter_get_source_page_url( 'about-us', '/o-nas/' );
$cammino_placeholder  = NSTARTER_URL . '/assets/images/placeholder.webp';
?>
<a class="skip-link" href="#main-content">Preskočiť na obsah</a>

<main id="main-content">
	<section class="current-donation-hero" aria-labelledby="current-donation-title">
		<img class="current-donation-hero__photo" src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="" aria-hidden="true" width="1600" height="1000" decoding="async" fetchpriority="high">
		<div class="container current-donation-hero__grid">
			<div class="current-donation-copy" data-current-reveal="left">
				<p class="current-donation-eyebrow"><i class="fa-solid fa-heart" aria-hidden="true"></i> Aktuálna zbierka OZ Cammino</p>
				<h1 id="current-donation-title">Darujme úsmev.<br><em>Darujme nádej.</em></h1>
				<p class="current-donation-lead">Pomáhame deťom, mladým ľuďom a rodinám prekonať chvíle, keď musia voliť medzi základnými potrebami. Váš dar premieňame na konkrétnu pomoc počas Vianoc aj celého roka.</p>

				<div class="current-donation-actions">
					<a class="button button--coral current-donation-primary" href="<?php echo esc_url( $cammino_campaign_url ); ?>" target="_blank" rel="noopener noreferrer" data-donation-external>
						Chcem darovať cez DARUJME.sk
						<span class="button-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
					</a>
					<a class="current-donation-qr-jump" href="#qr-darovanie">Alebo naskenujte QR kód <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
				</div>

				<ul class="current-donation-trust" aria-label="Informácie o darovaní">
					<li><i class="fa-solid fa-shield-heart" aria-hidden="true"></i> Bezpečne cez DARUJME.sk</li>
					<li><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i> Jednorazovo alebo mesačne</li>
				</ul>
			</div>

			<aside class="current-donation-panel" id="qr-darovanie" data-current-reveal="scale" data-delay="120">
				<div class="current-donation-panel__heading">
					<span aria-hidden="true"><i class="fa-solid fa-mobile-screen-button"></i></span>
					<div><small>Rýchla cesta k pomoci</small><h2>Naskenujte QR kód</h2></div>
				</div>

				<a class="current-donation-qr" href="<?php echo esc_url( $cammino_campaign_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Otvoriť zbierku Darujme úsmev na DARUJME.sk" data-donation-external>
					<img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="Miesto pre QR kód zbierky Darujme úsmev" width="900" height="900">
					<span class="current-donation-qr__scan" aria-hidden="true"></span>
				</a>

				<p>QR obrázok môžete neskôr jednoducho nahradiť vo vizuálnom editore. Kliknutie naň už teraz otvorí bezpečnú darovaciu stránku.</p>
				<a class="current-donation-panel__link" href="<?php echo esc_url( $cammino_campaign_url ); ?>" target="_blank" rel="noopener noreferrer" data-donation-external>
					Otvoriť darovaciu stránku <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
				</a>
			</aside>
		</div>
	</section>

	<section class="section current-impact" aria-labelledby="current-impact-title">
		<div class="container">
			<div class="current-impact__heading" data-current-reveal="up">
				<div><span>Každý dar má konkrétny význam</span><h2 id="current-impact-title">Čo môže vaša pomoc <em>zmeniť</em></h2></div>
				<p>Aj jeden príspevok dokáže rodine uľahčiť náročné obdobie a mladému človeku pomôcť pokračovať v štúdiu.</p>
			</div>

			<div class="current-impact__grid">
				<article class="current-impact-card current-impact-card--sage" data-current-reveal="up">
					<span class="current-impact-card__amount">30 €</span>
					<div class="current-impact-card__icon" aria-hidden="true"><i class="fa-solid fa-pencil"></i></div>
					<h3>Školské pomôcky</h3>
					<p>Pomôžu dieťaťu pripraviť sa do školy bez toho, aby táto potreba zaťažila rodinný rozpočet.</p>
				</article>

				<article class="current-impact-card current-impact-card--apricot" data-current-reveal="up" data-delay="80">
					<span class="current-impact-card__amount">50 €</span>
					<div class="current-impact-card__icon" aria-hidden="true"><i class="fa-solid fa-mitten"></i></div>
					<h3>Zimné oblečenie</h3>
					<p>Dokáže zabezpečiť teplé oblečenie pre dieťa, ktoré ho práve potrebuje.</p>
				</article>

				<article class="current-impact-card current-impact-card--cream" data-current-reveal="up" data-delay="160">
					<span class="current-impact-card__amount">150 €</span>
					<div class="current-impact-card__icon" aria-hidden="true"><i class="fa-solid fa-graduation-cap"></i></div>
					<h3>Podpora pri štúdiu</h3>
					<p>Prispeje na školné, internát, cestovné alebo ďalšie náklady spojené so vzdelávaním.</p>
				</article>

				<article class="current-impact-card current-impact-card--coral" data-current-reveal="up" data-delay="240">
					<span class="current-impact-card__amount">200 €</span>
					<div class="current-impact-card__icon" aria-hidden="true"><i class="fa-solid fa-house-heart"></i></div>
					<h3>Pomoc domácnosti</h3>
					<p>Môže rodine pomôcť zabezpečiť potrebný spotrebič alebo inú nevyhnutnú vec.</p>
				</article>
			</div>
		</div>
	</section>

	<section class="current-help" aria-labelledby="current-help-title">
		<div class="container current-help__grid">
			<div class="current-help__visual" data-current-reveal="left">
				<img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="Pomoc deťom, mladým ľuďom a rodinám" width="1200" height="900" loading="lazy">
				<span><i class="fa-solid fa-gift" aria-hidden="true"></i> Pomoc počas celého roka</span>
			</div>

			<div class="current-help__copy" data-current-reveal="right" data-delay="100">
				<p class="current-help__label">Za každým darom je človek</p>
				<h2 id="current-help-title">Nie všetky sviatky začínajú <em>bez starostí</em></h2>
				<p>Niektoré rodiny riešia, či zaplatia kúrenie alebo kúpia deťom topánky. Vďaka darcom dokážeme priniesť adresnú pomoc: oblečenie, potraviny, školské potreby, cestovné aj podporu pri štúdiu.</p>
				<p>A často prinášame aj niečo rovnako dôležité — pocit, že na nich niekto myslí.</p>
				<a class="text-link" href="<?php echo esc_url( $cammino_about_url ); ?>">Spoznajte OZ Cammino <span aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span></a>
			</div>
		</div>
	</section>

	<section class="section current-donation-final" aria-labelledby="current-donation-final-title">
		<div class="container">
			<div class="current-donation-final__card" data-current-reveal="scale">
				<div class="current-donation-final__icon" aria-hidden="true"><i class="fa-solid fa-hand-holding-heart"></i></div>
				<div>
					<span>Spoločne môžeme meniť budúcnosť</span>
					<h2 id="current-donation-final-title">Darujte úsmev a možnosť <em>lepšieho života</em></h2>
					<p>Vyberte si jednorazový alebo pravidelný mesačný dar na zabezpečenej stránke DARUJME.sk.</p>
				</div>
				<a class="button button--cream current-donation-final__button" href="<?php echo esc_url( $cammino_campaign_url ); ?>" target="_blank" rel="noopener noreferrer" data-donation-external>
					Chcem darovať <span class="button-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-up-right-from-square"></i></span>
				</a>
			</div>
		</div>
	</section>
</main>
