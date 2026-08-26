<?php
/**
 * Snapshot Name: Dar pre Cammino
 *
 * @package Cammino
 */

$cammino_donate_url = nstarter_get_source_page_url( 'donate', '/podporte-nas/' );
$cammino_about_url  = nstarter_get_source_page_url( 'about-us', '/o-nas/' );
$cammino_placeholder = NSTARTER_URL . '/assets/images/placeholder.webp';
?>
<main id="main-content">
	<section class="support-hero" aria-labelledby="support-title">
		<img class="support-hero__photo" src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="" aria-hidden="true">
		<div class="container support-shell">
			<div class="support-opening">
				<a class="support-back" href="<?php echo esc_url( $cammino_donate_url ); ?>" data-us-reveal="up"><i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> Späť na možnosti podpory</a>

				<div class="support-intro" data-us-reveal="up">
					<span class="support-intro__icon" aria-hidden="true"><i class="fa-solid fa-hand-holding-heart"></i></span>
					<p>Dar pre Cammino</p>
					<h1 id="support-title">Pomôžte nám byť tam, kde nás potrebujú</h1>
					<p class="support-intro__description">Váš dar použijeme tam, kde môže mať v tejto chvíli najväčší význam. Zodpovedne ho premeníme na bezpečný priestor, príležitosť a konkrétny ďalší krok.</p>
				</div>

				<a class="donation-scroll-cue" href="#donation-form" aria-label="Prejsť k formuláru darovania" data-us-reveal="up" data-scroll-to-donation>
					<span>Prejsť k darovaniu</span>
					<svg viewBox="0 0 42 66" aria-hidden="true"><path class="donation-scroll-cue__line" d="M21 4V49"></path><path class="donation-scroll-cue__head" d="M9 39L21 52L33 39"></path></svg>
				</a>
			</div>

			<div class="support-form-card" id="donation-form">
				<form data-us-donation-form>
					<div class="support-form-card__heading"><span aria-hidden="true"><i class="fa-solid fa-heart"></i></span><div><small>Váš príspevok</small><h2>Vyberte výšku daru</h2></div></div>
					<div class="us-frequency-switch" role="group" aria-label="Frekvencia príspevku"><button class="is-active" type="button" data-us-frequency="once" aria-pressed="true">Jednorazovo</button><button type="button" data-us-frequency="monthly" aria-pressed="false">Mesačne</button></div>

					<fieldset class="us-amount-fieldset">
						<legend>Suma príspevku</legend>
						<div class="us-amount-options"><button type="button" data-us-amount="15">15 €</button><button class="is-active" type="button" data-us-amount="30">30 €</button><button type="button" data-us-amount="50">50 €</button><button type="button" data-us-amount="100">100 €</button></div>
						<label class="us-custom-amount"><span>Vlastná suma</span><span><input type="number" min="1" step="1" inputmode="numeric" placeholder="Iná suma" data-us-custom-amount><b>€</b></span></label>
					</fieldset>

					<div class="us-donor-fields">
						<label><span>Vaše meno</span><span class="us-form-input"><i class="fa-solid fa-user" aria-hidden="true"></i><input type="text" name="name" autocomplete="name" placeholder="Meno a priezvisko" required></span></label>
						<label><span>Váš e-mail</span><span class="us-form-input"><i class="fa-solid fa-envelope" aria-hidden="true"></i><input type="email" name="email" autocomplete="email" placeholder="vas@email.sk" required></span></label>
					</div>

					<label class="us-consent"><input type="checkbox" required><span>Súhlasím so spracovaním údajov potrebných na vybavenie daru.</span></label>
					<div class="us-summary"><span data-us-summary-frequency>Jednorazový dar</span><strong data-us-summary-amount>30 €</strong></div>
					<button class="button button--coral us-submit" type="submit">Pokračovať k darovaniu <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></button>
					<p class="us-secure-note"><i class="fa-solid fa-lock" aria-hidden="true"></i> Toto je ukážka formulára. Platba zatiaľ nebude vykonaná.</p>
				</form>

				<div class="us-success" hidden data-us-success><span><i class="fa-solid fa-heart" aria-hidden="true"></i></span><h2>Ďakujeme, že kráčate s nami</h2><p>V ostrej verzii by teraz nasledovala bezpečná platobná brána.</p><button class="button button--cream" type="button" data-us-form-reset>Upraviť dar</button></div>
			</div>

			<aside class="about-invite">
				<div class="about-invite__icon" aria-hidden="true"><i class="fa-solid fa-compass"></i></div>
				<div><small>Spoznajte našu cestu</small><h2>Chcete vedieť, komu zverujete svoju podporu</h2><p>Prečítajte si viac o našom poslaní, hodnotách a ľuďoch, ktorí tvoria Cammino.</p></div>
				<a class="button button--cream" href="<?php echo esc_url( $cammino_about_url ); ?>">O Camminu <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></a>
			</aside>
		</div>
	</section>
</main>
