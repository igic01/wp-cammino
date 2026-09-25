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
	<section class="section info-section contact2-team-section" aria-labelledby="contact2-team-title">
		<div class="container">
			<div class="info-card" data-contact2-reveal="left" <?php nstarter_variable_section_attributes( 'contact2_people_count', array( 'label' => 'Počet členov tímu', 'type' => 'number', 'control' => 'repeat', 'value' => 2, 'min' => 0, 'step' => 1, 'token' => 'person' ) ); ?>>
				<div class="info-intro">
					<h2 id="contact2-team-title">Náš tím</h2>
				</div>
				<div class="contact-people" data-nstarter-variable-items>
					<article class="contact-person" data-nstarter-variable-item>
						<div class="contact-person__icon" aria-hidden="true"><i class="fa-solid fa-user-tie"></i></div>
						<div><span>Štatutár</span><h3>Robert Mruk</h3><a href="mailto:management@ozcammino.sk">management@ozcammino.sk <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
					</article>
					<article class="contact-person" data-nstarter-variable-item>
						<div class="contact-person__icon" aria-hidden="true"><i class="fa-solid fa-folder-open"></i></div>
						<div><span>Projektový manažér</span><h3>Alexandra Mruk Papaianopol</h3><a href="mailto:projekty@ozcammino.sk">projekty@ozcammino.sk <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
					</article>
				</div>
				<template data-nstarter-variable-template>
					<article class="contact-person" data-nstarter-variable-item>
						<div class="contact-person__icon" aria-hidden="true"><i class="fa-solid fa-user"></i></div>
						<div><span>Pozícia</span><h3>Nový člen tímu {{person}}</h3><a href="mailto:kontakt@ozcammino.sk">kontakt@ozcammino.sk <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
					</article>
				</template>
			</div>
		</div>
	</section>
</main>
