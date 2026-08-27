<?php
/**
 * Snapshot Name: Aktuálna zbierka — Darujme úsmev
 *
 * @package Cammino
 */

$cammino_campaign_url = 'https://cammino.darujme.sk/darujmeusmev/';
$cammino_placeholder  = NSTARTER_URL . '/assets/images/placeholder.webp';
?>
<a class="skip-link" href="#main-content">Preskočiť na obsah</a>

<main id="main-content">
	<section class="contact-hero" aria-labelledby="contact-title">
		<div class="container contact-hero__grid">
			<div class="contact-intro" data-contact-reveal="left">
				<p class="contact-label"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Kontakt</p>
				<h1 id="contact-title">Poďme urobiť <em>ďalší krok spolu</em></h1>
				<p class="contact-lead">Máte otázku, nápad na spoluprácu alebo chcete vedieť viac o našich aktivitách? Vyberte si správny kontakt alebo podporte našu aktuálnu zbierku.</p>

				<div class="contact-people">
					<article class="person-card" data-contact-reveal="up" data-delay="80">
						<div class="person-card__icon" aria-hidden="true" data-contact-pop><i class="fa-solid fa-user-tie"></i></div>
						<div>
							<span>Štatutár</span>
							<h2>Róbert Mruk</h2>
							<a href="mailto:management@ozcammino.sk">management@ozcammino.sk <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
						</div>
					</article>

					<article class="person-card" data-contact-reveal="up" data-delay="160">
						<div class="person-card__icon" aria-hidden="true" data-contact-pop><i class="fa-solid fa-folder-open"></i></div>
						<div>
							<span>Projektový manažér</span>
							<h2>Alexandra Mruk Papaianopol</h2>
							<a href="mailto:projekty@ozcammino.sk">projekty@ozcammino.sk <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
						</div>
					</article>
				</div>
			</div>

			<article class="support-contact-card" data-contact-reveal="right" data-delay="120">
				<div class="support-copy">
					<div class="support-heart" aria-hidden="true" data-contact-pop><span class="detail-icon__badge"><i class="fa-solid fa-heart"></i></span></div>
					<span>Podporte nás</span>
					<h3>Pomôžte dobrým veciam napredovať</h3>
					<p>Každá podpora nám umožňuje prinášať konkrétnu pomoc deťom, mladým ľuďom a rodinám, ktoré ju práve potrebujú.</p>
					<a class="button button--cream" href="<?php echo esc_url( $cammino_campaign_url ); ?>" target="_blank" rel="noopener noreferrer">Chcem pomôcť <span class="button-arrow"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span></a>
				</div>

				<div class="qr-wrap" aria-label="QR kód zbierky Darujme úsmev">
					<a class="qr-placeholder" href="<?php echo esc_url( $cammino_campaign_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Otvoriť zbierku Darujme úsmev na DARUJME.sk">
						<img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="Miesto pre QR kód zbierky Darujme úsmev" width="800" height="800">
						<span class="qr-scan" aria-hidden="true"></span>
					</a>
					<small class="qr-hint"><i class="fa-solid fa-hand-pointer" aria-hidden="true"></i> Kliknite alebo naskenujte QR</small>
				</div>
			</article>
		</div>
	</section>
</main>
