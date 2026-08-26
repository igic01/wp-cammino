<?php
/**
 * Snapshot Name: Detail podpory
 *
 * @package Cammino
 */

$cammino_donate_url = nstarter_get_source_page_url( 'donate', '/podporte-nas/' );
$cammino_detail_url = nstarter_get_source_page_url( 'donate-detail', '/podporte-nas/detail/' );
$cammino_placeholder = NSTARTER_URL . '/assets/images/placeholder.webp';
?>
<main id="main-content">
	<article>
		<div class="container article-layout donation-article-layout">
			<aside class="article-share" aria-label="Zdieľať túto výzvu" data-detail-reveal="up">
				<span>Zdieľať</span>
				<button type="button" data-share="facebook" aria-label="Zdieľať na Facebooku"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></button>
				<button type="button" data-share="linkedin" aria-label="Zdieľať na LinkedIn"><i class="fa-brands fa-linkedin-in" aria-hidden="true"></i></button>
				<button type="button" data-share="copy" aria-label="Kopírovať odkaz"><i class="fa-solid fa-link" aria-hidden="true"></i></button>
				<span class="copy-feedback" role="status" aria-live="polite" data-copy-feedback></span>
			</aside>

			<div class="article-content">
				<p class="article-lead" data-content-lead data-detail-reveal="up">Mladí ľudia často nepotrebujú hotové odpovede. Potrebujú bezpečný priestor, správne otázky a človeka, ktorý pri nich zostane dostatočne dlho.</p>
				<section data-detail-reveal="up"><h2 data-content-title>Prečo je táto pomoc dôležitá</h2><p data-content-body>Vďaka podpore môžeme pripravovať pravidelné stretnutia, zabezpečiť potrebné materiály a prispôsobiť pomoc konkrétnej situácii každého účastníka. Príspevok tak nepokrýva iba jednu aktivitu, ale vytvára podmienky pre dlhodobejšiu zmenu.</p></section>
				<blockquote data-detail-reveal="scale"><span aria-hidden="true"><i class="fa-solid fa-quote-left"></i></span><p data-content-quote>Najväčšiu zmenu často neprinesie jedna veľká vec, ale človek, ktorý nám dovolí urobiť ďalší krok.</p><cite>Cammino</cite></blockquote>
				<figure class="article-inline-image" data-detail-reveal="scale"><img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="Účastníci počas podporovaného programu" width="1200" height="800" loading="lazy" data-content-image><figcaption data-content-caption>Podpora vytvára priestor skúšať, učiť sa a rásť vlastným tempom</figcaption></figure>
				<section data-detail-reveal="up"><h2 data-content-secondary-title>Ako vašu pomoc využijeme</h2><p data-content-secondary-body>Každý príspevok premieňame na konkrétnu podporu programu a ľudí, ktorí ho potrebujú. Rozhodnutia robíme podľa aktuálnej situácie a výsledky našej práce komunikujeme otvorene.</p></section>
			</div>
		</div>
	</article>

	<section class="donation-checkout-section section" aria-labelledby="checkout-title">
		<div class="container">
			<a class="article-back checkout-back" href="<?php echo esc_url( $cammino_donate_url ); ?>" data-detail-reveal="up"><i class="fa-solid fa-arrow-left-long" aria-hidden="true"></i> Späť na možnosti podpory</a>

			<div class="checkout-grid">
				<div class="checkout-cause" data-detail-reveal="up">
					<h1 id="checkout-title" data-cause-title>Vzdelávanie a mentoring</h1>
					<figure class="checkout-cause__image"><img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="Podporovaný program Cammino" width="1100" height="760" data-cause-image><span aria-hidden="true"><i class="fa-solid fa-graduation-cap" data-cause-icon></i></span></figure>
					<p data-cause-description>Podporte kurzy, študijné materiály a osobné vedenie mladých ľudí pri ich ďalšom kroku.</p>
				</div>

				<aside class="donation-form-card" data-detail-reveal="up" data-delay="100">
					<form data-donation-form>
						<div class="donation-form-card__heading"><span aria-hidden="true"><i class="fa-solid fa-heart"></i></span><div><small>Váš príspevok</small><h2>Vyberte výšku daru</h2></div></div>
						<div class="frequency-switch" role="group" aria-label="Frekvencia príspevku"><button class="is-active" type="button" data-frequency="once" aria-pressed="true">Jednorazovo</button><button type="button" data-frequency="monthly" aria-pressed="false">Mesačne</button></div>
						<fieldset class="amount-fieldset">
							<legend>Suma príspevku</legend>
							<div class="amount-options"><button type="button" data-amount="15">15 €</button><button class="is-active" type="button" data-amount="30">30 €</button><button type="button" data-amount="50">50 €</button><button type="button" data-amount="100">100 €</button></div>
							<label class="custom-amount"><span>Vlastná suma</span><span><input type="number" min="1" step="1" inputmode="numeric" placeholder="Iná suma" data-custom-amount><b>€</b></span></label>
						</fieldset>
						<div class="donor-fields">
							<label><span>Vaše meno</span><span class="form-input"><i class="fa-solid fa-user" aria-hidden="true"></i><input type="text" name="name" autocomplete="name" placeholder="Meno a priezvisko" required></span></label>
							<label><span>Váš e-mail</span><span class="form-input"><i class="fa-solid fa-envelope" aria-hidden="true"></i><input type="email" name="email" autocomplete="email" placeholder="vas@email.sk" required></span></label>
						</div>
						<label class="consent-check"><input type="checkbox" required><span>Súhlasím so spracovaním údajov potrebných na vybavenie daru.</span></label>
						<div class="donation-summary"><span data-summary-frequency>Jednorazový dar</span><strong data-summary-amount>30 €</strong></div>
						<button class="button button--coral donation-submit" type="submit">Pokračovať k darovaniu <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></button>
						<p class="secure-note"><i class="fa-solid fa-lock" aria-hidden="true"></i> Toto je ukážka formulára. Platba zatiaľ nebude vykonaná.</p>
					</form>
					<div class="donation-success" hidden data-donation-success><span><i class="fa-solid fa-heart" aria-hidden="true"></i></span><h2>Ďakujeme za vaše rozhodnutie</h2><p>V ostrej verzii by teraz nasledovala bezpečná platobná brána.</p><button class="button button--cream" type="button" data-form-reset>Upraviť dar</button></div>
				</aside>
			</div>
		</div>
	</section>

	<section class="related-section section" aria-labelledby="other-donations-title">
		<div class="container">
			<div class="related-heading" data-detail-reveal="up"><div><span>Podporte ďalší krok</span><h2 id="other-donations-title">Ďalšie možnosti pomoci</h2></div><a href="<?php echo esc_url( $cammino_donate_url ); ?>">Všetky možnosti <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></a></div>
			<div class="related-grid">
				<a class="related-card related-card--sage" href="<?php echo esc_url( add_query_arg( 'cause', 'education', $cammino_detail_url ) ); ?>" data-donation-option="education" data-detail-reveal="up"><div class="related-card__image"><img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="Vzdelávanie a mentoring" width="700" height="480" loading="lazy"><span>Vzdelávanie</span></div><div class="related-card__body"><small>Kurzy a osobné vedenie</small><h3>Vzdelávanie a mentoring</h3><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></div></a>
				<a class="related-card related-card--apricot" href="<?php echo esc_url( add_query_arg( 'cause', 'workshops', $cammino_detail_url ) ); ?>" data-donation-option="workshops" data-detail-reveal="up" data-delay="100"><div class="related-card__image"><img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="Praktické dielne" width="700" height="480" loading="lazy"><span>Dielne</span></div><div class="related-card__body"><small>Materiály a nové zručnosti</small><h3>Praktické dielne</h3><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></div></a>
				<a class="related-card related-card--coral" href="<?php echo esc_url( add_query_arg( 'cause', 'community', $cammino_detail_url ) ); ?>" data-donation-option="community" data-detail-reveal="up" data-delay="200"><div class="related-card__image"><img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="Komunitné aktivity" width="700" height="480" loading="lazy"><span>Komunita</span></div><div class="related-card__body"><small>Otvorené a bezpečné priestory</small><h3>Komunitné aktivity</h3><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></div></a>
				<a class="related-card related-card--sage" href="<?php echo esc_url( add_query_arg( 'cause', 'direct', $cammino_detail_url ) ); ?>" data-donation-option="direct" data-detail-reveal="up" data-delay="300"><div class="related-card__image"><img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="Priama pomoc" width="700" height="480" loading="lazy"><span>Priama pomoc</span></div><div class="related-card__body"><small>Podpora v správnej chvíli</small><h3>Priama pomoc mladým ľuďom</h3><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></div></a>
			</div>
		</div>
	</section>
</main>
