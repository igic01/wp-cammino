<?php
/**
 * Snapshot Name: Projekty
 *
 * @package Cammino
 */

$cammino_projects_hero_image = NSTARTER_URL . '/assets/images/placeholder.webp';
$cammino_featured_project_url = nstarter_get_source_page_url( 'darujme-usmev', '/darujme-usmev/' );
?>
<main id="main-content" class="projects-directory-main">
	<section class="projects-feature" aria-labelledby="featured-project-title">
		<div class="container">
			<div class="projects-feature__panel">
				<div class="projects-feature__copy">
					<span class="projects-feature__eyebrow"><i class="fa-solid fa-star" aria-hidden="true"></i> Náš najväčší projekt</span>
					<h1 id="featured-project-title">Darujme <em>úsmev</em></h1>
					<p>Prepájame dobrovoľníkov, partnerov a darcov, aby sme spoločne prinášali konkrétnu pomoc a radosť rodinám s deťmi, ktoré čelia náročným životným situáciám.</p>
					<a class="button button--coral" href="<?php echo esc_url( $cammino_featured_project_url ); ?>">Viac o projekte <span class="button-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right-long icon-diagonal"></i></span></a>
				</div>
				<figure class="projects-feature__media">
					<img src="<?php echo esc_url( $cammino_projects_hero_image ); ?>" alt="Projekt Darujme úsmev" width="1200" height="800" loading="eager" decoding="async">
					<figcaption><i class="fa-solid fa-heart" aria-hidden="true"></i> Pomoc, ktorá má ľudskú tvár</figcaption>
				</figure>
			</div>
		</div>
	</section>

	<section class="projects-listing section" id="projekty" aria-labelledby="projects-title">
		<div class="container">
			<div class="projects-directory-shell">
				<header class="projects-heading">
					<div>
						<span>Naša práca</span>
						<h2 id="projects-title">Ďalšie projekty</h2>
					</div>
				</header>

				<?php nstarter_live_section( 'cammino_all_projects' ); ?>
			</div>
		</div>
	</section>
</main>
