<?php
/**
 * Snapshot Name: Podporte nás
 *
 * @package Cammino
 */

$cammino_detail_url    = nstarter_get_source_page_url( 'donate-detail', '/podporte-nas/detail/' );
$cammino_donate_us_url = nstarter_get_source_page_url( 'donate-us', '/podporte-nas/dar/' );
$cammino_placeholder   = NSTARTER_URL . '/assets/images/placeholder.webp';
?>
<main id="main-content">
	<section class="donation-options section" id="donation-options" aria-labelledby="options-title">
		<div class="container">
			<div class="donation-options__heading" data-donate-reveal="up">
				<h1 id="options-title">Vyberte si, čo chcete <em>podporiť</em></h1>
				<p>Každá možnosť pomáha konkrétnej oblasti našej práce. Vyberte si tú, ktorá je vám najbližšia.</p>
			</div>

			<div class="donation-grid">
				<article class="donation-card donation-card--sage" data-donate-reveal="up">
					<a class="donation-card__image" href="<?php echo esc_url( add_query_arg( 'cause', 'education', $cammino_detail_url ) ); ?>" aria-label="Viac o podpore vzdelávania">
						<img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="Mladí ľudia počas vzdelávacieho programu" width="900" height="620">
						<span aria-hidden="true"><i class="fa-solid fa-graduation-cap"></i></span>
					</a>
					<div class="donation-card__body">
						<h2>Vzdelávanie a mentoring</h2>
						<p>Podporte kurzy, študijné materiály a osobné vedenie mladých ľudí pri ich ďalšom kroku.</p>
						<a class="button button--coral" href="<?php echo esc_url( add_query_arg( 'cause', 'education', $cammino_detail_url ) ); ?>">Chcem podporiť <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></a>
					</div>
				</article>

				<article class="donation-card donation-card--apricot" data-donate-reveal="up" data-delay="80">
					<a class="donation-card__image" href="<?php echo esc_url( add_query_arg( 'cause', 'workshops', $cammino_detail_url ) ); ?>" aria-label="Viac o podpore praktických dielní">
						<img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="Praktická tvorivá dielňa Cammino" width="900" height="620" loading="lazy">
						<span aria-hidden="true"><i class="fa-solid fa-screwdriver-wrench"></i></span>
					</a>
					<div class="donation-card__body">
						<h2>Praktické dielne</h2>
						<p>Pomôžte zabezpečiť techniku, tvorivé potreby a vybavenie pre workshopy nových zručností.</p>
						<a class="button button--coral" href="<?php echo esc_url( add_query_arg( 'cause', 'workshops', $cammino_detail_url ) ); ?>">Chcem podporiť <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></a>
					</div>
				</article>

				<article class="donation-card donation-card--cream" data-donate-reveal="up" data-delay="160">
					<a class="donation-card__image" href="<?php echo esc_url( add_query_arg( 'cause', 'community', $cammino_detail_url ) ); ?>" aria-label="Viac o podpore komunitných aktivít">
						<img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="Účastníci komunitného podujatia" width="900" height="620" loading="lazy">
						<span aria-hidden="true"><i class="fa-solid fa-people-group"></i></span>
					</a>
					<div class="donation-card__body">
						<h2>Komunitné aktivity</h2>
						<p>Prispejte na otvorené stretnutia a bezpečné priestory, kde sa môžu spájať celé komunity.</p>
						<a class="button button--coral" href="<?php echo esc_url( add_query_arg( 'cause', 'community', $cammino_detail_url ) ); ?>">Chcem podporiť <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></a>
					</div>
				</article>

				<article class="donation-card donation-card--coral" data-donate-reveal="up" data-delay="240">
					<a class="donation-card__image" href="<?php echo esc_url( add_query_arg( 'cause', 'direct', $cammino_detail_url ) ); ?>" aria-label="Viac o priamej pomoci mladým ľuďom">
						<img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="Individuálna pomoc mladému človeku" width="900" height="620" loading="lazy">
						<span aria-hidden="true"><i class="fa-solid fa-hands-holding-child"></i></span>
					</a>
					<div class="donation-card__body">
						<h2>Priama pomoc</h2>
						<p>Pomôžte mladým ľuďom prekonať náročné obdobie a získať podporu, ktorú práve potrebujú.</p>
						<a class="button button--coral" href="<?php echo esc_url( add_query_arg( 'cause', 'direct', $cammino_detail_url ) ); ?>">Chcem podporiť <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></a>
					</div>
				</article>
			</div>

			<div class="open-donation-card" data-donate-reveal="scale">
				<span class="open-donation-card__icon" aria-hidden="true"><i class="fa-solid fa-hand-holding-heart"></i></span>
				<div>
					<h2>Nechajte rozhodnutie na nás</h2>
					<p>Prispejte bez určenia konkrétnej oblasti. Vašu pomoc nasmerujeme tam, kde je v danej chvíli najviac potrebná, a postaráme sa, aby skončila v správnych rukách.</p>
				</div>
				<a class="button button--cream" href="<?php echo esc_url( $cammino_donate_us_url ); ?>">Chcem prispieť <i class="fa-solid fa-heart" aria-hidden="true"></i></a>
			</div>
		</div>
	</section>
</main>
