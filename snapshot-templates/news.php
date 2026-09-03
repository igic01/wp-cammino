<?php
/**
 * Snapshot Name: Novinky
 *
 * @package Cammino
 */
?>
<a class="skip-link" href="#main-content">Preskočiť na obsah</a>

<main id="main-content">
	<section class="news-events section" id="events" aria-labelledby="events-title">
		<div class="container">
			<div class="news-events__heading" data-news-reveal="up">
				<div>
					<h1 id="events-title">Podujatia <em>práve teraz</em></h1>
				</div>
			</div>

			<?php nstarter_live_section( 'cammino_news_events' ); ?>
		</div>
	</section>

	<section class="section articles-section" id="articles" aria-labelledby="articles-title">
		<div class="container">
			<div class="articles-heading" data-news-reveal="up">
				<h2 id="articles-title">Najnovšie z <em>Cammina</em></h2>
			</div>

			<?php nstarter_live_section( 'cammino_news_articles' ); ?>
		</div>
	</section>

	<section class="section news-subscribe" aria-labelledby="subscribe-title">
		<div class="container">
			<div class="subscribe-card" data-news-reveal="scale">
				<div class="subscribe-card__icon" aria-hidden="true"><i class="fa-solid fa-envelope-open-text"></i></div>
				<div class="subscribe-card__copy">
					<h2 id="subscribe-title">Dobré správy rovno do vašej schránky</h2>
					<p>Raz za mesiac pošleme výber príbehov, príležitostí a noviniek z Cammina.</p>
				</div>
				<form class="subscribe-form" action="#" method="post" data-newsletter-placeholder>
					<label class="sr-only" for="cammino-subscribe-email">Váš e-mail</label>
					<input id="cammino-subscribe-email" type="email" name="email" placeholder="vas@email.sk" required>
					<button class="button button--cream" type="submit" aria-label="Prihlásiť sa na odber"><span>Chcem novinky</span> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
					<span class="newsletter-status" role="status" data-newsletter-status></span>
				</form>
			</div>
		</div>
	</section>
</main>
