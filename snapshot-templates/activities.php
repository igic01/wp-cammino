<?php
/**
 * Snapshot Name: Naše aktivity
 *
 * @package Cammino
 */

$cammino_placeholder = NSTARTER_URL . '/assets/images/placeholder.webp';
$cammino_logo        = NSTARTER_URL . '/assets/logos/new_logo.svg';
$cammino_contact_url = nstarter_get_source_page_url( 'contact', '/kontakt/' );
$cammino_news_url    = nstarter_get_source_page_url( 'news', '/novinky/' );
$cammino_stories_url = nstarter_get_source_page_url( 'ss', '/pribehy/' );
?>
<main id="main-content" class="activities-main">
  <section class="activities-hero" aria-labelledby="activities-title">
    <div class="container activities-hero__grid">
      <div class="activities-hero__copy" data-activities-reveal="left">
        <p class="activities-eyebrow"><span></span> Naše aktivity</p>
        <h1 id="activities-title">Malé kroky.<br>Nové možnosti.<br><em>Spoločná cesta.</em></h1>
        <p class="activities-lead">Od prvého workshopu po medzinárodnú spoluprácu. Pomáhame mladým ľuďom rozvíjať potenciál a spájame komunity, ktoré chcú meniť svet okolo seba.</p>
        <a class="button button--coral" href="#vzdelavanie">Objavte, čo robíme <span class="button-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-down"></i></span></a>
      </div>

      <div class="activities-garden" data-activities-reveal="scale" data-delay="120">
        <svg class="activities-garden__path" viewBox="0 0 560 540" fill="none" aria-hidden="true">
          <path d="M75 100C170 0 510 70 483 225S104 220 80 372 330 570 470 442" pathLength="1" />
        </svg>
        <div class="activities-petal activities-petal--sage">
          <i class="fa-solid fa-seedling" aria-hidden="true"></i>
          <span>Priestor rásť</span>
        </div>
        <div class="activities-petal activities-petal--apricot">
          <i class="fa-solid fa-earth-europe" aria-hidden="true"></i>
          <span>Svet možností</span>
        </div>
        <div class="activities-petal activities-petal--plum">
          <i class="fa-solid fa-comments" aria-hidden="true"></i>
          <span>Hlas mladých</span>
        </div>
        <div class="activities-petal activities-petal--coral">
          <i class="fa-solid fa-hand-holding-heart" aria-hidden="true"></i>
          <span>Pomoc, ktorá spája</span>
        </div>
        <div class="activities-garden__center"><img src="<?php echo esc_url( $cammino_logo ); ?>" alt="Cammino" width="526" height="526"></div>
        <span class="activities-garden__spark" aria-hidden="true">✳</span>
        <span class="activities-garden__note">Každý krok má zmysel.</span>
      </div>
    </div>

    <nav class="container activities-nav" aria-label="Oblasti našich aktivít">
      <a href="#vzdelavanie"><span>01</span> Vzdelávanie mladých <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
      <a href="#medzinarodne-projekty"><span>02</span> Medzinárodné projekty <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
      <a href="#advokacia"><span>03</span> Advokačné aktivity <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
      <a href="#komunitna-pomoc"><span>04</span> Komunitná pomoc <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
    </nav>
  </section>

  <section class="section activities-education" id="vzdelavanie" aria-labelledby="education-title">
    <div class="container">
      <div class="activities-split">
        <div data-activities-reveal="left">
          <p class="activities-eyebrow"><span>01</span> Vzdelávanie mladých</p>
          <h2 id="education-title">Každý v sebe má<br><em>niečo výnimočné.</em></h2>
          <p class="activities-lead">Pomáhame mladým ľuďom objaviť silné stránky a nájsť odvahu urobiť ďalší krok.</p>
          <p>Prostredníctvom workshopov, diskusií a vzdelávacích programov rozvíjame zručnosti, sebavedomie a kritické myslenie. Prepájame učenie s praktickými situáciami, ktoré prináša každodenný život.</p>
          <a class="text-link" href="<?php echo esc_url( $cammino_news_url . '#events' ); ?>">Spoznajte naše podujatia <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="activities-photo-composition" data-activities-reveal="right" data-delay="100">
          <div class="activities-photo activities-photo--arch">
            <img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="" width="1200" height="800" loading="lazy" decoding="async">
          </div>
          <span class="activities-photo-stamp" aria-hidden="true"><i class="fa-solid fa-lightbulb"></i></span>
          <div class="activities-photo-note"><i class="fa-solid fa-seedling" aria-hidden="true"></i> Učíme sa. Skúšame. Rastieme.</div>
        </div>
      </div>
      <div class="activities-learning-grid">
        <article class="activities-learning-card" data-activities-reveal="up">
          <span class="activities-card-icon" aria-hidden="true"><i class="fa-solid fa-sun"></i></span>
          <h3>Osobný rast</h3>
          <p>Sebapoznanie, zdravé sebavedomie a rozvoj talentov. Priestor objaviť, v čom sme dobrí a kam chceme smerovať.</p>
          <span class="activities-card-foot">Odvaha byť sebou</span>
        </article>
        <article class="activities-learning-card" data-activities-reveal="up" data-delay="90">
          <span class="activities-card-icon" aria-hidden="true"><i class="fa-solid fa-compass"></i></span>
          <h3>Gramotnosti pre dnešný svet</h3>
          <p>Financie, médiá, digitálna bezpečnosť aj umelá inteligencia. Zručnosti, ktoré pomáhajú robiť informované rozhodnutia.</p>
          <span class="activities-card-foot">Istota v meniacom sa svete</span>
        </article>
        <article class="activities-learning-card" data-activities-reveal="up" data-delay="180">
          <span class="activities-card-icon" aria-hidden="true"><i class="fa-solid fa-arrow-trend-up"></i></span>
          <h3>Štúdium a pracovný život</h3>
          <p>Orientácia v možnostiach vzdelávania a práce. Stretnutia s odborníkmi, praktické rady a príprava na samostatnosť.</p>
          <span class="activities-card-foot">Pripravení na ďalší krok</span>
        </article>
      </div>
    </div>
  </section>

  <section class="section activities-international" id="medzinarodne-projekty" aria-labelledby="international-title">
    <div class="container activities-split">
      <div class="activities-world" data-activities-reveal="scale" aria-hidden="true">
        <div class="activities-world__orbit activities-world__orbit--outer"></div>
        <div class="activities-world__orbit activities-world__orbit--inner"></div>
        <div class="activities-world__globe"><i class="fa-solid fa-earth-europe"></i></div>
        <span class="activities-world__label activities-world__label--one"><i class="fa-solid fa-lightbulb"></i> Nové pohľady</span>
        <span class="activities-world__label activities-world__label--two"><i class="fa-solid fa-people-group"></i> Spoločné skúsenosti</span>
        <span class="activities-world__label activities-world__label--three"><i class="fa-solid fa-heart"></i> Bližšie k sebe</span>
        <span class="activities-world__star">✳</span>
      </div>
      <div data-activities-reveal="right" data-delay="100">
        <p class="activities-eyebrow"><span>02</span> Medzinárodné projekty</p>
        <h2 id="international-title">Príležitosti<br><em>bez hraníc.</em></h2>
        <p class="activities-lead">Keď prepájame ľudí z rôznych krajín, otvárame priestor novým nápadom aj priateľstvám.</p>
        <p>V medzinárodných projektoch si vymieňame skúsenosti, učíme sa od partnerov a hľadáme odpovede na výzvy, ktoré máme spoločné.</p>
        <ul class="activities-topics" aria-label="Témy medzinárodných projektov">
          <li>Duševné zdravie</li><li>Finančná gramotnosť</li><li>Mediálna gramotnosť</li><li>Digitálna bezpečnosť</li><li>Inklúzia mladých</li><li>Medzigeneračné učenie</li>
        </ul>
        <a class="text-link" href="<?php echo esc_url( $cammino_news_url ); ?>">Novinky z našich projektov <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
      </div>
    </div>
  </section>

  <section class="section activities-advocacy" id="advokacia" aria-labelledby="advocacy-title">
    <div class="container activities-split">
      <div data-activities-reveal="left">
        <p class="activities-eyebrow"><span>03</span> Advokačné aktivity</p>
        <h2 id="advocacy-title">Aby bolo počuť<br><em>každý hlas.</em></h2>
        <p class="activities-lead">Zastávame sa mladých ľudí v zraniteľných situáciách a prinášame ich skúsenosti do verejnej diskusie.</p>
        <p>Osobitnú pozornosť venujeme mladým ľuďom z Ukrajiny, ktorí prišli na Slovensko bez rodičov alebo zákonných zástupcov. Podporujeme dialóg o ich bezpečí, integrácii a prístupe k vzdelávaniu.</p>
        <a class="text-link" href="<?php echo esc_url( $cammino_contact_url ); ?>">Spojme sa pre dobrú vec <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
      </div>
      <div class="activities-voice" data-activities-reveal="scale" data-delay="120">
        <span class="activities-voice__icon" aria-hidden="true"><i class="fa-solid fa-comments"></i></span>
        <p class="activities-voice__statement">Byť vypočutý.<br>Patriť niekam.<br><em>Mať príležitosť.</em></p>
        <div class="activities-voice__footer"><span></span> Na tom záleží.</div>
        <svg class="activities-voice__path" viewBox="0 0 180 80" fill="none" aria-hidden="true"><path d="M8 58C50 90 63 2 108 30S144 58 165 14M150 16l16-4 2 17" pathLength="1" /></svg>
      </div>
    </div>
  </section>

  <section class="section activities-community" id="komunitna-pomoc" aria-labelledby="community-title">
    <div class="container activities-split">
      <div class="activities-community__visual" data-activities-reveal="left">
        <div class="activities-photo activities-photo--organic"><img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="" width="1200" height="800" loading="lazy" decoding="async"></div>
        <div class="activities-community__badge"><i class="fa-solid fa-heart" aria-hidden="true"></i><span>Malé gesto.<br>Veľká radosť.</span></div>
      </div>
      <div data-activities-reveal="right" data-delay="120">
        <p class="activities-eyebrow"><span>04</span> Komunitná pomoc</p>
        <h2 id="community-title">Spolu dokážeme<br><em>darovať úsmev.</em></h2>
        <p class="activities-lead">Spájame dobrovoľníkov, partnerov a komunity, aby pomoc našla cestu k deťom a rodinám, ktoré ju potrebujú.</p>
        <p>Našu komunitnú prácu stelesňuje iniciatíva <strong>Darujme úsmev</strong>. Vďaka ľuďom z celého Slovenska prináša konkrétnu pomoc aj pocit, že na náročnú životnú situáciu nikto nemusí zostať sám.</p>
        <a class="button button--cream" href="https://www.exallievi.sk/darujmeusmev/">Spoznajte Darujme úsmev <span class="button-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right-long icon-diagonal"></i></span></a>
      </div>
    </div>
  </section>

  <section class="section activities-impact" aria-labelledby="impact-title">
    <div class="container">
      <div class="activities-section-heading" data-activities-reveal="up">
        <p class="activities-eyebrow">Zmysel našej práce</p>
        <h2 id="impact-title">Čo spolu <em>vytvárame</em></h2>
        <p>Za každou aktivitou je človek a jeho cesta vpred.</p>
      </div>
      <!-- Add numerical impact only once values and reporting periods are confirmed. -->
      <div class="activities-impact__grid">
        <article data-activities-reveal="up"><span aria-hidden="true"><i class="fa-solid fa-book-open-reader"></i></span><h3>Priestor na učenie</h3><p>Nové zručnosti pre štúdium, prácu aj každodenný život.</p></article>
        <article data-activities-reveal="up" data-delay="100"><span aria-hidden="true"><i class="fa-solid fa-people-arrows"></i></span><h3>Nové prepojenia</h3><p>Stretnutia ľudí, komunít a partnerov naprieč hranicami.</p></article>
        <article data-activities-reveal="up" data-delay="200"><span aria-hidden="true"><i class="fa-solid fa-hand-holding-heart"></i></span><h3>Vzájomnú podporu</h3><p>Solidaritu a konkrétnu pomoc v náročných situáciách.</p></article>
      </div>
      <a class="text-link" href="<?php echo esc_url( $cammino_stories_url ); ?>">Spoznajte príbehy za našou prácou <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
    </div>
  </section>

  <section class="activities-invitation" aria-labelledby="invitation-title">
    <div class="container">
      <div class="activities-invitation__card" data-activities-reveal="up">
        <span class="activities-invitation__flower" aria-hidden="true">✳</span>
        <div>
          <p class="activities-eyebrow">Ďalší krok môžeme urobiť spolu</p>
          <h2 id="invitation-title">Na tejto ceste<br>je miesto <em>aj pre vás.</em></h2>
          <p>Darujte svoj čas, podeľte sa o skúsenosti alebo podporte naše aktivity. Každý z nás môže prispieť.</p>
          <div class="activities-invitation__actions">
            <a class="button button--cream" href="<?php echo esc_url( $cammino_contact_url ); ?>">Chcem sa zapojiť <span class="button-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right-long icon-diagonal"></i></span></a>
            <a class="text-link" href="<?php echo esc_url( CAMMINO_DONATE_URL ); ?>">Podporiť aktivity <i class="fa-solid fa-heart" aria-hidden="true"></i></a>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>
