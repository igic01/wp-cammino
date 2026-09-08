<?php
/**
 * Snapshot Name: Príbehy s dopadom
 *
 * @package Cammino
 */

$cammino_impact_stories_image = NSTARTER_URL . '/assets/images/placeholder.webp';
?>
<main id="main-content" class="impact-stories-main">
	<section class="impact-stories-hero" aria-labelledby="impact-stories-title">
		<div class="container impact-stories-hero__grid">
			<div class="impact-stories-hero__copy" data-impact-stories-reveal="left">
				<h1 id="impact-stories-title">Príbehy, ktoré prinášajú <em>skutočnú zmenu</em></h1>
				<p>Za každým projektom je človek, odvaha a krok vpred. Spoznajte príbehy ľudí a komunít, ktorých cesta ukazuje, že pomoc má zmysel.</p>
			</div>

			<figure class="impact-stories-hero__media" data-impact-stories-reveal="right" data-delay="100">
				<img src="<?php echo esc_url( $cammino_impact_stories_image ); ?>" alt="Ľudia spojení príbehmi s pozitívnym dopadom" width="1200" height="900" decoding="async" fetchpriority="high">
			</figure>
		</div>
	</section>

	<section class="section impact-stories-list" aria-label="Zoznam príbehov s dopadom"<?php
	nstarter_variable_section_attributes(
		'impact_stories_count',
		array(
			'label'   => 'Počet príbehov',
			'type'    => 'number',
			'control' => 'repeat',
			'value'   => 3,
			'min'     => 0,
			'max'     => 100,
			'step'    => 1,
			'token'   => 'story',
		)
	);
	?>>
		<div class="container">
			<div class="impact-stories-grid" data-nstarter-variable-items>
				<article class="impact-story" data-nstarter-variable-item data-impact-stories-reveal="up">
					<h2>Keď podpora otvorí nové možnosti</h2>
					<p>Krátky opis príbehu, ktorý približuje človeka, jeho cestu a zmenu, ku ktorej pomohla spoločná podpora.</p>
				</article>
				<article class="impact-story" data-nstarter-variable-item data-impact-stories-reveal="up" data-delay="70">
					<h2>Od prvého kroku k vlastnému cieľu</h2>
					<p>Krátky opis príbehu o odvahe skúsiť niečo nové, prekonať neistotu a objaviť vlastný potenciál.</p>
				</article>
				<article class="impact-story" data-nstarter-variable-item data-impact-stories-reveal="up" data-delay="140">
					<h2>Komunita, ktorá drží spolu</h2>
					<p>Krátky opis príbehu o ľuďoch, ktorí spojili svoje schopnosti a vytvorili pozitívnu zmenu vo svojom okolí.</p>
				</article>
			</div>

			<template data-nstarter-variable-template>
				<article class="impact-story" data-nstarter-variable-item data-impact-stories-reveal="up">
					<h2>Názov príbehu {{story}}</h2>
					<p>Sem doplňte opis príbehu.</p>
				</article>
			</template>
		</div>
	</section>
</main>
