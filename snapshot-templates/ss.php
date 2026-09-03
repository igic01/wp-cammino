<?php
/**
 * Snapshot Name: Príbehy úspechov
 *
 * @package Cammino
 */

$cammino_story_image = NSTARTER_URL . '/assets/images/placeholder.webp';
$cammino_story_url   = home_url( '/pribeh/' );
$cammino_stories     = array(
	array(
		'date'        => '10. august 2026',
		'datetime'    => '2026-08-10',
		'label'       => 'Nina',
		'title'       => 'Nina našla odvahu ukázať svoj <em>talent</em>',
		'description' => 'Zo skicára, ktorý nikomu neukazovala, vznikla prvá vlastná výstava. Bezpečný priestor, trpezlivá mentorka a skupina rovesníkov jej pomohli veriť vlastnému pohľadu.',
		'result'      => '<strong>12 diel</strong> vystavených v komunitnom centre',
		'icon'        => 'fa-palette',
		'photos'      => 4,
	),
	array(
		'date'        => '27. jún 2026',
		'datetime'    => '2026-06-27',
		'label'       => 'Komunita',
		'title'       => 'Komunitný deň spojil celú <em>štvrť</em>',
		'description' => 'Jeden dvor sa na celý deň zmenil na priestor pre hudbu, tvorivé dielne a nové priateľstvá. Program pripravovali mladí ľudia spolu so susedmi a dobrovoľníkmi.',
		'result'      => '<strong>120+ ľudí</strong> sa stretlo na jednom mieste',
		'icon'        => 'fa-people-group',
		'photos'      => 4,
	),
	array(
		'date'        => '18. máj 2026',
		'datetime'    => '2026-05-18',
		'label'       => 'Nový smer',
		'title'       => 'Nový smer otvoril dvere k prvej <em>práci</em>',
		'description' => 'Šesťtýždňový program spojil praktické workshopy, individuálny mentoring a stretnutia so zamestnávateľmi. Tento príbeh nemá jednu fotografiu — jeho obraz tvoria konkrétne výsledky celej skupiny.',
		'result'      => '<strong>Každý účastník</strong> odišiel s vlastným ďalším krokom',
		'icon'        => 'fa-briefcase',
		'photos'      => 0,
	),
	array(
		'date'        => '11. apríl 2026',
		'datetime'    => '2026-04-11',
		'label'       => 'Dielňa',
		'title'       => 'Digitálna dielňa premenila nápady na <em>projekty</em>',
		'description' => 'Účastníci začínali s prázdnou obrazovkou. Počas štyroch sobôt vytvorili prvé weby, vizuálne identity a prototypy, ktoré dnes ďalej rozvíjajú.',
		'result'      => '<strong>16 projektov</strong> vzniklo za štyri spoločné soboty',
		'icon'        => 'fa-laptop-code',
		'photos'      => 4,
	),
);
?>
<a class="skip-link" href="#main-content">Preskočiť na obsah</a>

<main id="main-content">
	<section class="stories-hero" aria-labelledby="stories-title">
		<div class="container stories-hero__inner">
			<div data-story-reveal="up">
				<span class="stories-hero__mark" aria-hidden="true"><i class="fa-solid fa-face-smile"></i></span>
				<h1 id="stories-title">Príbehy, ktoré zanechali <em>úsmev</em></h1>
				<p>Skutoční ľudia, spoločné projekty a výsledky, ktoré ukazujú, že aj malý krok môže rozbehnúť veľkú zmenu.</p>
			</div>
			<nav class="story-jump" aria-label="Prejsť na príbeh" data-story-jump data-story-reveal="up" data-delay="140"></nav>
		</div>
	</section>

	<div class="stories-collection"<?php
	nstarter_variable_section_attributes(
		'story_count',
		array(
			'label'   => 'Počet príbehov',
			'type'    => 'number',
			'control' => 'repeat',
			'value'   => count( $cammino_stories ),
			'min'     => 0,
			'max'     => 20,
			'step'    => 1,
			'token'   => 'story',
		)
	);
	?>>
		<div class="stories-empty" data-nstarter-variable-empty-state>
			<strong>Zatiaľ tu nie sú žiadne príbehy.</strong>
			<span>Pomocou fialového pera nastavte počet kariet.</span>
		</div>

		<div class="stories-items" data-nstarter-variable-items>
			<?php foreach ( $cammino_stories as $cammino_story_offset => $cammino_story ) : ?>
				<?php $cammino_story_index = $cammino_story_offset + 1; ?>
				<section class="success-story" id="story-<?php echo esc_attr( (string) $cammino_story_index ); ?>" aria-labelledby="story-<?php echo esc_attr( (string) $cammino_story_index ); ?>-title" data-story-nav-label="<?php echo esc_attr( $cammino_story['label'] ); ?>" data-nstarter-variable-item>
					<div class="container">
						<header class="success-story__header" data-story-reveal="up">
							<div class="success-story__meta">
								<span class="story-index"><?php echo esc_html( str_pad( (string) $cammino_story_index, 2, '0', STR_PAD_LEFT ) ); ?></span>
								<time datetime="<?php echo esc_attr( $cammino_story['datetime'] ); ?>"><?php echo esc_html( $cammino_story['date'] ); ?></time>
							</div>
							<div class="success-story__intro">
								<h2 id="story-<?php echo esc_attr( (string) $cammino_story_index ); ?>-title"><?php echo wp_kses( $cammino_story['title'], array( 'em' => array() ) ); ?></h2>
								<p><?php echo esc_html( $cammino_story['description'] ); ?></p>
							</div>
						</header>

						<div class="story-photo-section"<?php
						nstarter_variable_section_attributes(
							'story_photos_' . $cammino_story_index,
							array(
								'label'   => sprintf( 'Počet fotografií — príbeh %02d', $cammino_story_index ),
								'type'    => 'number',
								'control' => 'repeat',
								'value'   => $cammino_story['photos'],
								'min'     => 0,
								'max'     => 4,
								'step'    => 1,
								'token'   => 'photo',
							)
						);
						?>>
							<div class="story-photo-empty" data-nstarter-variable-empty-state>Príbeh je momentálne bez fotografií.</div>
							<div class="success-story__media" data-nstarter-variable-items data-story-reveal="scale">
								<?php for ( $cammino_photo_index = 1; $cammino_photo_index <= $cammino_story['photos']; $cammino_photo_index++ ) : ?>
									<figure class="story-photo" data-nstarter-variable-item>
										<img src="<?php echo esc_url( $cammino_story_image ); ?>" alt="Fotografia <?php echo esc_attr( (string) $cammino_photo_index ); ?> k príbehu <?php echo esc_attr( (string) $cammino_story_index ); ?>" width="1400" height="900"<?php echo 1 < $cammino_photo_index ? ' loading="lazy"' : ''; ?>>
									</figure>
								<?php endfor; ?>
							</div>
							<template data-nstarter-variable-template>
								<figure class="story-photo" data-nstarter-variable-item>
									<img src="<?php echo esc_url( $cammino_story_image ); ?>" alt="Fotografia {{photo}} k príbehu <?php echo esc_attr( (string) $cammino_story_index ); ?>" width="1400" height="900" loading="lazy">
								</figure>
							</template>
						</div>

						<footer class="success-story__result story-link-variable" data-story-reveal="up"<?php
						nstarter_variable_section_attributes(
							'story_link_' . $cammino_story_index,
							array(
								'label'   => sprintf( 'Odkaz na celý príbeh %02d', $cammino_story_index ),
								'type'    => 'text',
								'control' => 'text',
								'value'   => $cammino_story_url,
							)
						);
						?>>
							<span aria-hidden="true"><i class="fa-solid <?php echo esc_attr( $cammino_story['icon'] ); ?>"></i></span>
							<p><?php echo wp_kses( $cammino_story['result'], array( 'strong' => array() ) ); ?></p>
							<a href="<?php echo esc_url( $cammino_story_url ); ?>" data-nstarter-variable-output data-nstarter-variable-output-attribute="href">Celý príbeh <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></a>
						</footer>
					</div>
				</section>
			<?php endforeach; ?>
		</div>

		<template data-nstarter-variable-template>
			<section class="success-story" id="story-{{story}}" aria-labelledby="story-{{story}}-title" data-story-nav-label="Nový príbeh {{story}}" data-nstarter-variable-item>
				<div class="container">
					<header class="success-story__header" data-story-reveal="up">
						<div class="success-story__meta">
							<span class="story-index">{{story_padded}}</span>
							<time datetime="2026-01-01">1. január 2026</time>
						</div>
						<div class="success-story__intro">
							<h2 id="story-{{story}}-title">Napíšte názov nového <em>príbehu</em></h2>
							<p>Sem doplňte krátky úvod príbehu. Text môžete upraviť priamo v textovom režime.</p>
						</div>
					</header>

					<div class="story-photo-section" data-nstarter-variable-section="story_photos_{{story}}" data-nstarter-variable-label="Počet fotografií — príbeh {{story_padded}}" data-nstarter-variable-type="number" data-nstarter-variable-control="repeat" data-nstarter-variable-value="0" data-nstarter-variable-min="0" data-nstarter-variable-max="4" data-nstarter-variable-step="1" data-nstarter-variable-token="photo">
						<div class="story-photo-empty" data-nstarter-variable-empty-state>Príbeh je momentálne bez fotografií.</div>
						<div class="success-story__media" data-nstarter-variable-items data-story-reveal="scale"></div>
						<template data-nstarter-variable-template>
							<figure class="story-photo" data-nstarter-variable-item>
								<img src="<?php echo esc_url( $cammino_story_image ); ?>" alt="Fotografia {{photo}} k príbehu {{story}}" width="1400" height="900" loading="lazy">
							</figure>
						</template>
					</div>

					<footer class="success-story__result story-link-variable" data-story-reveal="up" data-nstarter-variable-section="story_link_{{story}}" data-nstarter-variable-label="Odkaz na celý príbeh {{story_padded}}" data-nstarter-variable-type="text" data-nstarter-variable-control="text" data-nstarter-variable-value="<?php echo esc_attr( $cammino_story_url ); ?>">
						<span aria-hidden="true"><i class="fa-solid fa-hand-holding-heart"></i></span>
						<p><strong>Výsledok príbehu</strong> doplňte konkrétny prínos alebo úspech</p>
						<a href="<?php echo esc_url( $cammino_story_url ); ?>" data-nstarter-variable-output data-nstarter-variable-output-attribute="href">Celý príbeh <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></a>
					</footer>
				</div>
			</section>
		</template>
	</div>
</main>
