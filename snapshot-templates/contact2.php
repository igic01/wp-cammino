<?php
/**
 * Snapshot Name: Zapojte sa
 *
 * @package Cammino
 */
?>
<main id="main-content" class="contact2-main">
	<section class="contact2-section" aria-labelledby="contact2-title">
		<div class="container">
			<div class="contact2-shell">
				<div class="contact2-copy" data-contact2-reveal="left">
					<h1 id="contact2-title">Zapojte sa do <em>našej cesty</em></h1>
					<p class="contact2-lead">Chcete venovať svoj čas, spojiť s nami svoju organizáciu alebo podporiť dobrú vec? Napíšte nám, akým spôsobom sa chcete zapojiť, a spoločne nájdeme ďalší krok.</p>

					<div class="contact2-options" aria-label="Možnosti zapojenia">
						<article>
							<i class="fa-solid fa-people-group" aria-hidden="true"></i>
							<div>
								<h2>Dobrovoľník</h2>
								<p>Pomôžte svojím časom, skúsenosťami alebo pri organizácii aktivít.</p>
							</div>
						</article>
						<article>
							<i class="fa-solid fa-handshake-angle" aria-hidden="true"></i>
							<div>
								<h2>Partner</h2>
								<p>Zapojte firmu, školu alebo organizáciu do zmysluplnej spolupráce.</p>
							</div>
						</article>
						<article>
							<i class="fa-solid fa-hand-holding-heart" aria-hidden="true"></i>
							<div>
								<h2>Podporovateľ</h2>
								<p>Prispejte finančnou alebo materiálnou pomocou tam, kde je potrebná.</p>
							</div>
						</article>
					</div>

					<p class="contact2-note"><i class="fa-solid fa-shield-heart" aria-hidden="true"></i> Vaše údaje použijeme iba na odpoveď a dohodnutie ďalšieho postupu.</p>
				</div>

				<div class="contact2-form-card" data-contact2-reveal="right" data-delay="100">
					<div class="contact2-form-heading">
						<h2>Povedzte nám, ako sa chcete zapojiť</h2>
						<p>Vyplňte krátky formulár. Ozveme sa vám a prejdeme si možnosti spolupráce.</p>
					</div>

					<div class="contact2-form-runtime">
						<?php nstarter_live_section( 'cammino_contact2_form' ); ?>
					</div>
				</div>
			</div>
		</div>
	</section>
</main>
