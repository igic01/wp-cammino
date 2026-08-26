<?php
/**
 * Snapshot Name: O nás
 *
 * @package Cammino
 */

$cammino_asset_url = NSTARTER_URL . '/assets';
?>
  <a class="skip-link" href="#main-content">Preskočiť na obsah</a>

  <main id="main-content">
    <section class="about-hero" aria-labelledby="about-title">
      <div class="container about-hero__grid">
        <div class="about-hero__copy" data-about-reveal="left">
          <h1 id="about-title">Spoločne kráčame k <em>pozitívnej zmene</em></h1>
          <p>OZ Cammino je občianske združenie, ktoré vytvára priestor pre stretnutia, učenie a osobný rast. Prostredníctvom projektov, workshopov a komunitných aktivít podporujeme rozvoj zručností, solidarity a spolupráce medzi ľuďmi rôzneho veku.</p>
          <p>Našou víziou je spoločnosť, v ktorej majú mladí ľudia príležitosť rozvíjať svoj potenciál, zapájať sa do života komunity a aktívne prispievať k pozitívnej zmene vo svojom okolí.</p>
          <a class="button button--coral" href="#mission">Naša misia <span class="button-arrow"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></span></a>
        </div>

        <div class="about-hero__visual" data-about-reveal="scale" data-delay="120">
          <div class="about-hero__image">
            <img src="<?php echo esc_url( $cammino_asset_url . '/images/placeholder.webp' ); ?>" alt="Mladí ľudia spolupracujúci v komunite" width="1200" height="900">
          </div>
          <div class="floating-path floating-path--top">
            <span class="floating-path__icon"><i class="fa-solid fa-route" aria-hidden="true"></i></span>
            <span><strong>Cammino</strong> znamená cesta</span>
          </div>
          <div class="floating-path floating-path--bottom">
            <i class="fa-solid fa-people-group" aria-hidden="true"></i>
            <span>Rastieme spolu</span>
          </div>
          <svg class="hero-path-line" viewBox="0 0 180 90" aria-hidden="true">
            <path d="M8 74 C45 78, 45 27, 84 43 S128 17, 165 13" pathLength="1"></path>
            <path class="hero-path-arrow" d="M151 5 L166 13 L154 24" pathLength="1"></path>
          </svg>
        </div>
      </div>
    </section>

    <section class="section identity-section" aria-labelledby="identity-title">
      <div class="container identity-grid">
        <div class="identity-heading" data-about-reveal="left">
          <span class="identity-number">01</span>
          <h2 id="identity-title">Cesta s jasným <em>zmyslom</em></h2>
        </div>
        <div class="identity-copy" data-about-reveal="right" data-delay="100">
          <p>OZ Cammino je občianske združenie zamerané na podporu vzdelávania, osobnostného rozvoja a solidarity v spoločnosti. Naše aktivity prepájajú komunitnú prácu, vzdelávacie projekty a medzinárodnú spoluprácu.</p>
          <blockquote>
            <span aria-hidden="true"><i class="fa-solid fa-quote-left"></i></span>
            <p>Názov Cammino znamená „cesta“. Symbolizuje cestu osobného rastu, vzdelávania a zodpovednosti za spoločnosť okolo nás.</p>
          </blockquote>
        </div>
      </div>
    </section>

    <section class="section mission-section" id="mission" aria-labelledby="mission-title">
      <div class="mission-orbit mission-orbit--one" aria-hidden="true"></div>
      <div class="mission-orbit mission-orbit--two" aria-hidden="true"></div>
      <div class="container mission-inner" data-about-reveal="scale">
        <div class="mission-icon" aria-hidden="true"><i class="fa-solid fa-compass"></i></div>
        <p>Naša misia</p>
        <h2 id="mission-title" data-word-highlight>Podporovať mladých ľudí v rozvoji ich potenciálu, posilňovať solidaritu v komunitách a prispievať k otvorenej a inkluzívnej spoločnosti</h2>
        <div class="mission-steps" aria-hidden="true">
          <span></span><span></span><span></span><span></span>
        </div>
      </div>
    </section>

    <section class="section values-section" aria-labelledby="values-title">
      <div class="container">
        <div class="values-heading" data-about-reveal="up">
          <div>
            <span class="identity-number">02</span>
            <h2 id="values-title">Hodnoty, ktoré nás vedú <em>vpred</em></h2>
          </div>
          <p>Každý projekt staviame na štyroch pevných bodoch.</p>
        </div>

        <div class="values-grid">
          <article class="value-card value-card--sage" data-about-reveal="up">
            <span class="value-card__number">01</span>
            <div class="value-card__icon" aria-hidden="true"><i class="fa-solid fa-universal-access"></i></div>
            <span class="value-card__name">Inclusion</span>
            <h3>Inklúzia</h3>
            <p>Podporujeme rovnaké príležitosti pre všetkých mladých ľudí.</p>
          </article>
          <article class="value-card value-card--apricot" data-about-reveal="up" data-delay="90">
            <span class="value-card__number">02</span>
            <div class="value-card__icon" aria-hidden="true"><i class="fa-solid fa-book-open-reader"></i></div>
            <span class="value-card__name">Education</span>
            <h3>Vzdelávanie</h3>
            <p>Vzdelávanie je základom osobného rozvoja aj demokratickej spoločnosti.</p>
          </article>
          <article class="value-card value-card--cream" data-about-reveal="up" data-delay="180">
            <span class="value-card__number">03</span>
            <div class="value-card__icon" aria-hidden="true"><i class="fa-solid fa-handshake-angle"></i></div>
            <span class="value-card__name">Cooperation</span>
            <h3>Spolupráca</h3>
            <p>Spájame ľudí, organizácie a komunity s cieľom vytvárať pozitívne zmeny.</p>
          </article>
          <article class="value-card value-card--coral" data-about-reveal="up" data-delay="270">
            <span class="value-card__number">04</span>
            <div class="value-card__icon" aria-hidden="true"><i class="fa-solid fa-hand-holding-heart"></i></div>
            <span class="value-card__name">Solidarity</span>
            <h3>Solidarita</h3>
            <p>Veríme v silu pomoci a spolupráce medzi ľuďmi.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="section europe-section" aria-labelledby="europe-title">
      <div class="container europe-grid">
        <div class="europe-visual" data-about-reveal="left">
          <div class="europe-image">
            <img src="<?php echo esc_url( $cammino_asset_url . '/images/placeholder.webp' ); ?>" alt="Medzinárodná pracovná skupina pri spoločnom rokovaní" width="1200" height="900" loading="lazy">
          </div>
          <div class="europe-badge" aria-hidden="true" data-floating-control><i class="fa-solid fa-earth-europe"></i></div>
          <span class="europe-note" data-floating-control><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Slovensko ↔ Brusel</span>
        </div>

        <div class="europe-copy" data-about-reveal="right" data-delay="120">
          <span class="identity-number">03</span>
          <h2 id="europe-title">Náš európsky <em>rozmer</em></h2>
          <p>Naša činnosť presahuje hranice Slovenska a zapájame sa do medzinárodnej spolupráce. Zástupcovia OZ Cammino sa podieľajú napríklad na:</p>
          <ul class="europe-list">
            <li><span><i class="fa-solid fa-users" aria-hidden="true"></i></span> pracovných skupinách na Slovensku a v Bruseli</li>
            <li><span><i class="fa-solid fa-landmark" aria-hidden="true"></i></span> činnosti Monitorovacieho výboru programov Fondov pre oblasť vnútorných záležitostí</li>
            <li><span><i class="fa-solid fa-diagram-project" aria-hidden="true"></i></span> spolupráci na tvorbe európskych projektov</li>
          </ul>
          <p class="europe-summary">Tieto platformy umožňujú prepájať skúsenosti z praxe s tvorbou verejných politík v oblasti mobility, migrácie a integrácie.</p>
        </div>
      </div>
    </section>

    <section class="section info-section" id="info" aria-labelledby="info-title">
      <div class="container">
        <div class="info-card" data-about-reveal="scale">
          <div class="info-intro">
            <span class="identity-number">04</span>
            <h2 id="info-title">Poďme sa <em>spojiť</em></h2>
            <p>Máte nápad, otázku alebo chuť spolupracovať? Ozvite sa správnemu človeku priamo.</p>
            <div class="info-address">
              <span aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>
              <div><small>Adresa</small><strong>Miletičova 7, Bratislava</strong></div>
            </div>
          </div>

          <div class="contact-people">
            <article class="contact-person">
              <div class="contact-person__icon" aria-hidden="true"><i class="fa-solid fa-user-tie"></i></div>
              <div>
                <span>Štatutár</span>
                <h3>Robert Mruk</h3>
                <a href="mailto:management@ozcammino.sk">management@ozcammino.sk <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
              </div>
            </article>
            <article class="contact-person">
              <div class="contact-person__icon" aria-hidden="true"><i class="fa-solid fa-folder-open"></i></div>
              <div>
                <span>Projektový manažér</span>
                <h3>Alexandra Mruk Papaianopol</h3>
                <a href="mailto:projekty@ozcammino.sk">projekty@ozcammino.sk <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
              </div>
            </article>
          </div>
        </div>
      </div>
    </section>
  </main>
