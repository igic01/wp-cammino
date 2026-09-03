<?php
/**
 * Snapshot Name: Domov
 *
 * @package Cammino
 */

$cammino_about_url   = nstarter_get_source_page_url( 'about-us', '/o-nas/' );
$cammino_donate_url  = nstarter_get_source_page_url( 'donate', '/podporte-nas/' );
$cammino_news_url    = nstarter_get_source_page_url( 'news', '/novinky/' );
$cammino_stories_url = nstarter_get_source_page_url( 'ss', '/pribehy/' );
$cammino_events_url  = $cammino_news_url . '#events';
$cammino_placeholder = NSTARTER_URL . '/assets/images/placeholder.webp';
$cammino_logo        = NSTARTER_URL . '/assets/logos/logo.svg';
?>
<main id="main-content">
    <section class="hero section" aria-labelledby="hero-title">
      <div class="container hero-grid">
        <div class="hero-copy" data-reveal="left">
          <h1 id="hero-title">Priestor, kde sa ľudia spájajú pre <em>pozitívnu zmenu</em></h1>
          <p class="hero-lead">OZ Cammino prepája vzdelávanie, osobný rozvoj a komunitnú spoluprácu. Vytvárame príležitosti, vďaka ktorým môžu mladí ľudia rozvíjať svoj potenciál a aktívne meniť svoje okolie.</p>
          <div class="hero-actions">
            <a class="button button--coral" href="<?php echo esc_url( $cammino_donate_url ); ?>">Podporte naše aktivity <span class="button-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right-long icon-diagonal"></i></span></a>
            <a class="text-link" href="#about">Spoznajte Cammino <span aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span></a>
          </div>
        </div>

        <div class="hero-visual" data-hero-reveal data-delay="140">
          <div class="hero-image-wrap">
            <img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="Ľudia spolupracujú počas komunitnej aktivity OZ Cammino" width="1200" height="800" decoding="async" fetchpriority="high">
            <div class="image-tint" aria-hidden="true"></div>
          </div>
          <div class="hero-note hero-note--top" aria-hidden="true">
            <span class="note-icon"><i class="fa-solid fa-star" aria-hidden="true"></i></span>
            <span><strong>Rozvíjame potenciál</strong>ľudí aj komunít</span>
          </div>
          <div class="hero-note hero-note--bottom">
            <img src="<?php echo esc_url( $cammino_logo ); ?>" alt="" width="526" height="526">
            <span><strong>Krok za krokom</strong>Spoločne</span>
          </div>
          <svg class="hero-path" viewBox="0 0 170 120" aria-hidden="true">
            <path d="M8 104C35 110 37 63 70 69C105 75 97 18 154 14" pathLength="1" />
            <path class="path-arrow" d="m145 5 11 9-12 7" pathLength="1" />
          </svg>
        </div>
      </div>

    </section>

    <section class="section story-section smile-impact" aria-labelledby="smile-impact-title">
      <div class="story-blob" aria-hidden="true"></div>
      <div class="container story-grid">
        <div class="story-visual" data-reveal="left">
          <div class="story-image">
            <img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="Komunitná iniciatíva Darujme úsmev" width="1200" height="800" loading="lazy">
          </div>
          <div class="quote-mark smile-impact-mark" aria-hidden="true"><i class="fa-solid fa-people-group"></i></div>
          <div class="story-tag"><span aria-hidden="true"><i class="fa-solid fa-hand-holding-heart"></i></span> Pomoc, ktorá spája</div>
        </div>

        <div class="story-copy" data-reveal="right" data-delay="160">
          <h2 id="smile-impact-title">Darujme <em>úsmev</em></h2>
          <p>Komunitná iniciatíva, ktorá spája ľudí z celého Slovenska, aby prinášali radosť a pomoc deťom a rodinám v náročných situáciách.</p>

          <div class="smile-impact-stats" aria-label="Dopad iniciatívy Darujme úsmev">
            <div class="smile-impact-stat"<?php
            nstarter_variable_section_attributes(
              'darujme_usmev_deti',
              array(
                'label'   => 'Počet detí',
                'type'    => 'number',
                'control' => 'text',
                'value'   => 5361,
                'min'     => 0,
                'max'     => 9999999,
                'step'    => 1,
              )
            );
            ?>>
              <span>DETÍ</span>
              <strong>
                <span data-impact-counter aria-hidden="true">5,361</span>
                <span class="sr-only" data-nstarter-variable-output>5361</span>
              </strong>
            </div>

            <div class="smile-impact-stat"<?php
            nstarter_variable_section_attributes(
              'darujme_usmev_rodiny',
              array(
                'label'   => 'Počet rodín',
                'type'    => 'number',
                'control' => 'text',
                'value'   => 1562,
                'min'     => 0,
                'max'     => 9999999,
                'step'    => 1,
              )
            );
            ?>>
              <span>RODÍN</span>
              <strong>
                <span data-impact-counter aria-hidden="true">1,562</span>
                <span class="sr-only" data-nstarter-variable-output>1562</span>
              </strong>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section about" id="about" aria-labelledby="about-title">
      <div class="container about-grid">
        <div class="section-heading" data-reveal="left">
          <h2 id="about-title">Cammino znamená<br><em>cesta</em></h2>
        </div>
        <div class="about-copy" data-reveal="right" data-delay="100">
          <p>Sme občianske združenie zamerané na vzdelávanie, osobnostný rozvoj a solidaritu. Prostredníctvom projektov, workshopov a komunitných aktivít spájame ľudí rôzneho veku a pomáhame im aktívne prispievať k pozitívnej zmene.</p>
          <a class="text-link" href="<?php echo esc_url( $cammino_about_url ); ?>">Viac o našom poslaní <span aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span></a>
        </div>
      </div>

      <div class="container support-grid">
        <article class="support-card support-card--sage" data-reveal="up">
          <span class="support-number">01</span>
          <div class="support-icon" aria-hidden="true"><i class="fa-solid fa-shield-heart"></i></div>
          <h3>Inklúzia</h3>
          <p>Vytvárame otvorený priestor a podporujeme rovnaké príležitosti pre všetkých mladých ľudí.</p>
          <a href="<?php echo esc_url( $cammino_about_url ); ?>" aria-label="Viac o inklúzii v OZ Cammino">Objaviť viac <span aria-hidden="true"><i class="fa-solid fa-arrow-right-long icon-diagonal"></i></span></a>
        </article>
        <article class="support-card support-card--apricot" data-reveal="up" data-delay="120">
          <span class="support-number">02</span>
          <div class="support-icon" aria-hidden="true"><i class="fa-solid fa-lightbulb"></i></div>
          <h3>Vzdelávanie</h3>
          <p>Pripravujeme projekty, workshopy a príležitosti, ktoré podporujú osobný rozvoj a praktické zručnosti.</p>
          <a href="#events" aria-label="Viac o vzdelávacích aktivitách">Objaviť viac <span aria-hidden="true"><i class="fa-solid fa-arrow-right-long icon-diagonal"></i></span></a>
        </article>
        <article class="support-card support-card--cream" data-reveal="up" data-delay="240">
          <span class="support-number">03</span>
          <div class="support-icon" aria-hidden="true"><i class="fa-solid fa-seedling"></i></div>
          <h3>Spolupráca</h3>
          <p>Prepájame ľudí, organizácie a komunity doma aj v zahraničí, aby mohli spoločne tvoriť pozitívne zmeny.</p>
          <a href="#stories" aria-label="Viac o výsledkoch našej spolupráce">Objaviť viac <span aria-hidden="true"><i class="fa-solid fa-arrow-right-long icon-diagonal"></i></span></a>
        </article>
      </div>
    </section>

    <section class="section news-subscribe home-subscribe" aria-labelledby="subscribe-title">
      <div class="container">
        <div class="subscribe-card" data-reveal="scale">
          <div class="subscribe-card__icon" aria-hidden="true"><i class="fa-solid fa-envelope-open-text"></i></div>
          <div class="subscribe-card__copy">
            <h2 id="subscribe-title">Dobré správy rovno do vašej schránky</h2>
            <p>Raz za mesiac pošleme výber príbehov, príležitostí a noviniek z Cammina.</p>
          </div>
          <form class="subscribe-form" action="#" method="post" data-newsletter-placeholder>
            <label class="sr-only" for="cammino-home-subscribe-email">Váš e-mail</label>
            <input id="cammino-home-subscribe-email" type="email" name="email" placeholder="vas@email.sk" required>
            <button class="button button--cream" type="submit" aria-label="Prihlásiť sa na odber"><span>Chcem novinky</span> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
            <span class="newsletter-status" role="status" data-newsletter-status></span>
          </form>
        </div>
      </div>
    </section>

    <section class="section community-cta" aria-labelledby="community-cta-title">
      <div class="container">
        <div class="community-cta-card">
          <div class="community-cta-media" data-reveal="left">
            <img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="Ľudia spojení komunitnými aktivitami OZ Cammino" width="1200" height="800" loading="lazy">
          </div>
          <div class="community-cta-copy" data-reveal="right" data-delay="120">
            <span class="community-cta-kicker">Každý krok má zmysel</span>
            <h2 id="community-cta-title"><span>Pridajte sa k nám</span><em>a tvorme zmenu spoločne</em></h2>
            <a class="button button--coral" href="<?php echo esc_url( $cammino_donate_url ); ?>">Chcem pomôcť <span class="button-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right-long icon-diagonal"></i></span></a>
          </div>
          <div class="community-cta-shape" aria-hidden="true"></div>
        </div>
      </div>
    </section>

    <section class="section events" id="events" aria-labelledby="events-title">
      <div class="container">
        <div class="section-topline" data-reveal="up">
          <div>
            <h2 id="events-title">Stretnutia, ktoré nás <em>spájajú</em></h2>
          </div>
          <a class="text-link" href="<?php echo esc_url( $cammino_events_url ); ?>">Všetky podujatia <span aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span></a>
        </div>

        <div class="event-grid">
          <article class="event-card event-card--featured" data-reveal="left">
            <a class="event-image" href="<?php echo esc_url( $cammino_events_url ); ?>" aria-label="Detail workshopu Objav svoj potenciál">
              <img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="Skupina ľudí na vzdelávacom workshope" width="1200" height="800" loading="lazy">
              <span class="event-type">Workshop</span>
            </a>
            <div class="event-content">
              <div class="event-date"><strong>24</strong><span>SEP</span></div>
              <div>
                <p class="event-meta">Bratislava · 16:00</p>
                <h3><a href="<?php echo esc_url( $cammino_events_url ); ?>">Objav svoj potenciál: workshop osobného rozvoja</a></h3>
                <p>Praktické popoludnie plné rozhovorov, tvorivých úloh a podnetov, ktoré pomáhajú lepšie spoznať svoje silné stránky.</p>
              </div>
            </div>
          </article>

          <div class="event-list" data-reveal="right" data-delay="140">
            <article class="event-row">
              <div class="event-date"><strong>02</strong><span>OKT</span></div>
              <div>
                <p class="event-meta">Online · 18:00</p>
                <h3><a href="<?php echo esc_url( $cammino_events_url ); ?>">Komunitné nápady pre naše okolie</a></h3>
                <span class="event-type event-type--inline">Webinár</span>
              </div>
              <a class="circle-link" href="<?php echo esc_url( $cammino_events_url ); ?>" aria-label="Detail webinára"><i class="fa-solid fa-arrow-right-long icon-diagonal" aria-hidden="true"></i></a>
            </article>
            <article class="event-row">
              <div class="event-date"><strong>11</strong><span>OKT</span></div>
              <div>
                <p class="event-meta">Košice · 14:00</p>
                <h3><a href="<?php echo esc_url( $cammino_events_url ); ?>">Komunitný deň s Camminom</a></h3>
                <span class="event-type event-type--inline">Stretnutie</span>
              </div>
              <a class="circle-link" href="<?php echo esc_url( $cammino_events_url ); ?>" aria-label="Detail komunitného dňa"><i class="fa-solid fa-arrow-right-long icon-diagonal" aria-hidden="true"></i></a>
            </article>
            <article class="event-row">
              <div class="event-date"><strong>19</strong><span>OKT</span></div>
              <div>
                <p class="event-meta">Žilina · 15:30</p>
                <h3><a href="<?php echo esc_url( $cammino_events_url ); ?>">Vzdelávanie bez hraníc</a></h3>
                <span class="event-type event-type--inline">Workshop</span>
              </div>
              <a class="circle-link" href="<?php echo esc_url( $cammino_events_url ); ?>" aria-label="Detail tvorivej dielne"><i class="fa-solid fa-arrow-right-long icon-diagonal" aria-hidden="true"></i></a>
            </article>
          </div>
        </div>
      </div>
    </section>

    <section class="section donate" id="donate" aria-labelledby="donate-title">
      <div class="container">
        <div class="donate-card" data-reveal="scale">
          <div class="donate-step donate-step--one" aria-hidden="true"></div>
          <div class="donate-step donate-step--two" aria-hidden="true"></div>
          <div class="donate-copy">
            <h2 id="donate-title">Pomôžte nám meniť dobré nápady na <em>skutočné príležitosti</em></h2>
            <p>Váš príspevok podporí vzdelávanie, praktické dielne, komunitné aktivity a priamu pomoc tam, kde je práve najviac potrebná.</p>
          </div>
          <div class="donate-action">
            <a class="button button--cream" href="<?php echo esc_url( $cammino_donate_url ); ?>">Chcem pomôcť <span class="button-arrow" aria-hidden="true"><i class="fa-solid fa-heart"></i></span></a>
            <small>Podpora vzdelávania, komunít a solidarity</small>
          </div>
        </div>
      </div>
    </section>

    <section class="section story-section" id="stories" aria-labelledby="story-title">
      <div class="story-blob" aria-hidden="true"></div>
      <div class="container story-grid">
        <div class="story-visual" data-reveal="left">
          <div class="story-image">
            <img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="Nina pri práci na svojom tvorivom projekte" width="1200" height="800" loading="lazy">
          </div>
          <div class="quote-mark" aria-hidden="true"><i class="fa-solid fa-quote-left"></i></div>
          <div class="story-tag"><span aria-hidden="true"><i class="fa-solid fa-star"></i></span> Príbeh so skutočným dopadom</div>
        </div>
        <div class="story-copy" data-reveal="right" data-delay="160">
          <h2 id="story-title">Zo skicára vznikla <em>prvá vlastná výstava</em></h2>
          <p>Nina svoje kresby dlho nikomu neukazovala. Bezpečný priestor, trpezlivá mentorka a skupina rovesníkov jej pomohli veriť vlastnému pohľadu a ukázať svoj talent.</p>
          <div class="story-person">
            <span>N</span>
            <p><strong>Nina</strong><br>príbeh odvahy ukázať svoj talent</p>
          </div>
          <a class="button button--cream" href="<?php echo esc_url( $cammino_stories_url ); ?>">Prečítať celý príbeh <span class="button-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span></a>
        </div>
      </div>
    </section>
  </main>
