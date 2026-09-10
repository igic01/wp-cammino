<?php
/**
 * Snapshot Name: Hlavný projekt
 *
 * @package Cammino
 */

$cammino_main_project_image = NSTARTER_URL . '/assets/images/placeholder.webp';
$cammino_donate_url         = CAMMINO_DONATE_URL;
$cammino_logo               = NSTARTER_URL . '/assets/logos/new_logo.svg';
?>
<main id="main-content" class="main-project-main">
	<section class="main-project-hero" aria-labelledby="main-project-title">
		<div class="container main-project-hero__grid">
			<div class="main-project-hero__copy" data-main-project-reveal="left">
				<h1 id="main-project-title">Darujme <em>úsmev</em></h1>
				<p>Spájame dobrovoľníkov, partnerov a darcov z celého Slovenska, aby sa konkrétna pomoc dostala k deťom a rodinám, ktoré ju najviac potrebujú.</p>
				<a class="button button--coral" href="https://www.exallievi.sk/darujmeusmev/" target="_blank" rel="noopener noreferrer">Spoznajte projekt <span class="button-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-down"></i></span></a>
			</div>

			<figure class="main-project-hero__media" data-main-project-reveal="right" data-delay="120">
				<img src="<?php echo esc_url( $cammino_main_project_image ); ?>" alt="Dobrovoľníci projektu Darujme úsmev pomáhajú rodinám s deťmi" width="1200" height="900" decoding="async" fetchpriority="high">
				<div class="main-project-hero__note"><img src="<?php echo esc_url( $cammino_logo ); ?>" alt="" width="627" height="523"><span>Pomoc, ktorá má ľudskú tvár</span></div>
			</figure>
		</div>
	</section>

	<section class="section main-project-about" id="o-projekte" aria-labelledby="main-project-about-title">
		<div class="container main-project-about__grid">
			<div data-main-project-reveal="left">
				<h2 id="main-project-about-title">O projekte</h2>
			</div>
			<div class="main-project-about__copy" data-main-project-reveal="right" data-delay="80">
				<p>Za viac ako 12 rokov sa iniciatíva Darujme úsmev stala symbolom solidarity a spolupráce ľudí z celého Slovenska. Vďaka dobrovoľníkom, partnerom a darcom prinášame pomoc tam, kde je najviac potrebná.</p>
				<p>Projekt nie je len o materiálnej pomoci. Je o ľudskosti, radosti a nádeji — o pocite, že deti a rodiny na svojej ceste nie sú samy.</p>
			</div>
		</div>
	</section>

	<section class="section main-project-impact" aria-labelledby="main-project-impact-title">
		<div class="container">
			<div class="main-project-heading" data-main-project-reveal="up">
				<h2 id="main-project-impact-title">Dopad projektu</h2>
				<p>Výsledky posledného ročníka ukazujú, čo dokáže spoločné úsilie.</p>
			</div>

			<div class="main-project-impact__grid">
				<article class="main-project-impact__card main-project-impact__card--coral" data-main-project-reveal="up">
					<strong data-main-project-counter>1500+</strong>
					<p>detí dostalo konkrétnu pomoc a podporu</p>
				</article>
				<article class="main-project-impact__card main-project-impact__card--sage" data-main-project-reveal="up" data-delay="60">
					<strong data-main-project-counter>300+</strong>
					<p>rodinám sme pomohli zvládnuť náročnú situáciu</p>
				</article>
				<article class="main-project-impact__card main-project-impact__card--apricot" data-main-project-reveal="up" data-delay="120">
					<strong>Celé Slovensko</strong>
					<p>dobrovoľníci a pomoc v regiónoch po celej krajine</p>
				</article>
				<article class="main-project-impact__card main-project-impact__card--cream" data-main-project-reveal="up" data-delay="180">
					<strong>6 domovov</strong>
					<p>pre deti na Ukrajine získalo našu podporu</p>
				</article>
				<article class="main-project-impact__card main-project-impact__card--plum" data-main-project-reveal="up" data-delay="240">
					<strong>Aj za hranicami</strong>
					<p>pomoc smerovala aj saleziánskym misiám v zahraničí</p>
				</article>
			</div>
		</div>
	</section>

	<section class="section main-project-process" aria-labelledby="main-project-process-title">
		<div class="container">
			<div class="main-project-heading main-project-heading--center" data-main-project-reveal="up">
				<h2 id="main-project-process-title">Ako projekt funguje</h2>
				<p>Pomoc pripravujeme spoločne a doručujeme ju priamo ľuďom, ktorí ju potrebujú.</p>
			</div>

			<div class="main-project-process__grid">
				<article class="main-project-process__step" data-main-project-reveal="up">
					<h3>Identifikácia rodín a detí</h3>
					<p>So školami, komunitnými a sociálnymi pracovníkmi i partnerskými organizáciami hľadáme rodiny a deti v náročnej životnej situácii.</p>
				</article>
				<article class="main-project-process__step" data-main-project-reveal="up" data-delay="90">
					<h3>Zapojenie dobrovoľníkov a partnerov</h3>
					<p>Ľudia z rôznych regiónov pomáhajú organizovať zbierky, pripravovať pomoc a koordinovať aktivity vo svojich komunitách.</p>
				</article>
				<article class="main-project-process__step" data-main-project-reveal="up" data-delay="180">
					<h3>Distribúcia pomoci</h3>
					<p>Pripravená pomoc smeruje priamo k rodinám a deťom na Slovensku, do detských domovov na Ukrajine aj k saleziánskym misiám.</p>
				</article>
			</div>
		</div>
	</section>

	<section class="section main-project-stories" aria-labelledby="main-project-stories-title"<?php
	nstarter_variable_section_attributes(
		'main_project_stories',
		array(
			'label'   => 'Počet príbehov',
			'type'    => 'number',
			'control' => 'repeat',
			'value'   => 0,
			'min'     => 0,
			'max'     => 20,
			'step'    => 1,
			'token'   => 'story',
		)
	);
	?>>
		<div class="container">
			<div class="main-project-heading" data-main-project-reveal="up">
				<h2 id="main-project-stories-title">Príbehy, ktoré píšeme spolu</h2>
				<p>Skutočné príbehy detí, rodín a ľudí, ktorí sa rozhodli pomôcť.</p>
			</div>

			<div class="main-project-stories__grid" data-nstarter-variable-items></div>
			<template data-nstarter-variable-template>
				<article class="main-project-story" data-nstarter-variable-item>
					<h3>Názov príbehu {{story}}</h3>
					<p>Sem doplňte krátky opis príbehu.</p>
				</article>
			</template>
		</div>
	</section>

	<section class="section main-project-join" aria-labelledby="main-project-join-title">
		<div class="container">
			<div class="main-project-heading" data-main-project-reveal="up">
				<h2 id="main-project-join-title">Ako sa môžete zapojiť</h2>
				<p>Každý môže prispieť k tomu, aby Darujme úsmev prinášal pomoc ďalším deťom a rodinám.</p>
			</div>

			<div class="main-project-join__grid">
				<article class="main-project-join__card" data-main-project-reveal="up">
					<h3>Staňte sa dobrovoľníkom</h3>
					<p>Pomôžte organizovať zbierky, koordinovať pomoc vo svojom regióne alebo podporiť aktivity projektu.</p>
					<i class="fa-solid fa-people-carry-box" aria-hidden="true"></i>
				</article>
				<article class="main-project-join__card" data-main-project-reveal="up" data-delay="80">
					<h3>Staňte sa partnerom</h3>
					<p>Zapojte svoju organizáciu, školu alebo firmu a pomôžte nám rozšíriť dosah iniciatívy.</p>
					<i class="fa-solid fa-handshake-angle" aria-hidden="true"></i>
				</article>
				<article class="main-project-join__card" data-main-project-reveal="up" data-delay="160">
					<h3>Podporte projekt</h3>
					<p>Finančná alebo materiálna pomoc prináša konkrétnu podporu deťom a rodinám v náročných situáciách.</p>
					<i class="fa-solid fa-heart-circle-plus" aria-hidden="true"></i>
				</article>
			</div>
		</div>
	</section>

	<section class="section donate" id="donate" aria-labelledby="donate-title">
		<div class="container">
			<div class="donate-card" data-main-project-reveal="scale">
				<div class="donate-step donate-step--one" aria-hidden="true"></div>
				<div class="donate-step donate-step--two" aria-hidden="true"></div>
				<div class="donate-copy">
					<h2 id="donate-title">Spoločne môžeme priniesť <em>viac úsmevov</em></h2>
					<p>Každý dar a každé zapojenie pomáha vytvárať príbeh solidarity, ktorý prináša radosť a nádej deťom a rodinám.</p>
				</div>
				<div class="donate-action">
					<a class="button button--cream" href="<?php echo esc_url( $cammino_donate_url ); ?>">Chcem pomôcť <span class="button-arrow" aria-hidden="true"><i class="fa-solid fa-heart"></i></span></a>
					<small>Pomoc deťom a rodinám tam, kde je potrebná</small>
				</div>
			</div>
		</div>
	</section>
</main>
