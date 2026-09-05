<?php
/**
 * Snapshot Name: Darujme úsmev
 *
 * @package Cammino
 */

$cammino_placeholder = NSTARTER_URL . '/assets/images/placeholder.webp';
$cammino_contact_url = nstarter_get_source_page_url( 'contact', '/kontakt/' );
$cammino_stories_url = nstarter_get_source_page_url( 'ss', '/pribehy/' );
$cammino_gallery_captions = array( 'Keď sa ľudia spoja', 'Pripravené s láskou', 'Radosť, ktorú zdieľame' );
?>
<main id="main-content" class="smile-main">
  <section class="smile-hero" aria-labelledby="smile-title">
    <div class="container smile-hero__grid">
      <div class="smile-hero__copy" data-smile-reveal="left">
        <p class="smile-eyebrow"><i class="fa-solid fa-heart" aria-hidden="true"></i> Malé gesto. Veľká radosť.</p>
        <h1 id="smile-title">Darujme<br><em>úsmev.</em></h1>
        <p class="smile-lead">Spájame ľudí z celého Slovenska, aby sme prinášali radosť a konkrétnu pomoc deťom a rodinám v náročných životných situáciách.</p>
        <div class="smile-actions">
          <a class="button button--coral" href="#zapojte-sa">Chcem pomôcť <span class="button-arrow" aria-hidden="true"><i class="fa-solid fa-heart"></i></span></a>
          <a class="text-link" href="#ako-to-funguje">Ako to funguje <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
        </div>
        <p class="smile-hero__footnote"><span></span> Pomoc, ktorá má ľudskú tvár.</p>
      </div>

      <div class="smile-gift-scene" data-smile-reveal="scale" data-delay="100">
        <div class="smile-gift-scene__halo" aria-hidden="true"></div>
        <div class="smile-face" aria-hidden="true"><span class="smile-face__eyes"></span><span class="smile-face__mouth"></span><span class="smile-face__cheek"></span></div>
        <div class="smile-gift" aria-hidden="true">
          <div class="smile-gift__box"><span></span></div>
          <div class="smile-gift__lid"><span class="smile-gift__bow smile-gift__bow--left"></span><span class="smile-gift__bow smile-gift__bow--right"></span></div>
        </div>
        <div class="smile-gift-scene__tag"><i class="fa-solid fa-heart" aria-hidden="true"></i><span>Pre niekoho<br><strong>veľmi dôležitého.</strong></span></div>
        <span class="smile-gift-scene__spark smile-gift-scene__spark--one" aria-hidden="true">✳</span>
        <span class="smile-gift-scene__spark smile-gift-scene__spark--two" aria-hidden="true">✦</span>
        <span class="smile-gift-scene__heart" aria-hidden="true"><i class="fa-solid fa-heart"></i></span>
        <svg class="smile-gift-scene__trail" viewBox="0 0 520 540" fill="none" aria-hidden="true"><path d="M50 155C-15 100 110 30 203 46S490 78 479 244 390 520 261 482" pathLength="1" /></svg>
        <span class="smile-gift-scene__note">Radosť sa násobí, keď ju darujeme.</span>
      </div>
    </div>
    <nav class="container smile-jump" aria-label="O iniciatíve Darujme úsmev">
      <a href="#o-projekte">O projekte <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
      <a href="#nas-dopad">Naša pomoc <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
      <a href="#ako-to-funguje">Ako to funguje <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
      <a href="#zapojte-sa">Zapojte sa <i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
    </nav>
  </section>

  <section class="section smile-about" id="o-projekte" aria-labelledby="smile-about-title">
    <div class="container smile-split">
      <div class="smile-photo-stack" data-smile-reveal="left">
        <div class="smile-photo-stack__back" aria-hidden="true"></div>
        <figure class="smile-photo-stack__front"><img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="" width="1200" height="800" loading="lazy" decoding="async"><figcaption>Od človeka k človeku. Od srdca k srdcu.</figcaption></figure>
        <span class="smile-photo-stack__seal" aria-hidden="true"><i class="fa-solid fa-hand-holding-heart"></i></span>
      </div>
      <div data-smile-reveal="right" data-delay="100">
        <p class="smile-eyebrow">O projekte</p>
        <h2 id="smile-about-title">V jednom balíčku<br>môže byť <em>oveľa viac.</em></h2>
        <p class="smile-lead">Potrebné veci. Malé prekvapenie. A veľký pocit, že na vás niekto myslí.</p>
        <p>Darujme úsmev je dlhodobá komunitná iniciatíva. Každý rok pred Vianocami prepájame dobrovoľníkov, partnerov a darcov, aby sme spoločne pripravili pomoc pre rodiny s deťmi, ktoré čelia sociálnym alebo ekonomickým výzvam.</p>
        <p>Veríme, že aj malé gesto solidarity môže priniesť nádej do každodenného života. Chceme, aby deti a mladí ľudia cítili, že na svojej ceste nie sú sami.</p>
      </div>
    </div>
  </section>

  <section class="section smile-impact" id="nas-dopad" aria-labelledby="smile-impact-title">
    <div class="container"<?php
    nstarter_variable_section_attributes(
      'smile_verified_results',
      array( 'label' => 'Overené výsledky — počet kariet (doplňte aj obdobie)', 'type' => 'number', 'control' => 'repeat', 'value' => 0, 'min' => 0, 'max' => 4, 'step' => 1, 'token' => 'result' )
    );
    ?>>
      <div class="smile-impact__heading" data-smile-reveal="up">
        <div><p class="smile-eyebrow">Náš dopad</p><h2 id="smile-impact-title">Spoločne prinášame<br><em>viac než úsmev.</em></h2></div>
        <p>Vďaka dobrovoľníkom, partnerom a darcom sa pomoc dostáva k deťom a rodinám v rôznych regiónoch Slovenska.</p>
      </div>
      <div class="smile-impact__grid">
        <article data-smile-reveal="up"><span aria-hidden="true"><i class="fa-solid fa-gift"></i></span><h3>Radosť pre deti</h3><p>Potrebné veci aj darčeky, ktoré dokážu rozjasniť sviatočné dni.</p></article>
        <article data-smile-reveal="up" data-delay="90"><span aria-hidden="true"><i class="fa-solid fa-house-chimney-window"></i></span><h3>Podpora pre rodiny</h3><p>Konkrétna pomoc v období, keď je zabezpečiť bežné potreby náročné.</p></article>
        <article data-smile-reveal="up" data-delay="180"><span aria-hidden="true"><i class="fa-solid fa-people-group"></i></span><h3>Silnejšie komunity</h3><p>Ľudia, ktorí sa spájajú a menia ochotu pomôcť na skutočné skutky.</p></article>
      </div>
      <!-- The draft totals, dates and overseas recipients require confirmation.
           Keep this collection empty until each result and period are verified. -->
      <div class="smile-results" data-nstarter-variable-items></div>
      <template data-nstarter-variable-template>
        <article class="smile-result" data-nstarter-variable-item><strong>—</strong><h3>Doplňte overený výsledok</h3><p>Doplňte rok alebo obdobie</p></article>
      </template>
    </div>
  </section>

  <section class="section smile-process" id="ako-to-funguje" aria-labelledby="smile-process-title">
    <div class="container">
      <div class="smile-section-heading" data-smile-reveal="up"><p class="smile-eyebrow">Ako projekt funguje</p><h2 id="smile-process-title">Od ochoty pomôcť<br>k <em>skutočnej radosti.</em></h2><p>Za každým balíčkom je spoločná cesta ľudí, ktorým záleží.</p></div>
      <div class="smile-process__steps" data-smile-reveal="up">
        <svg class="smile-process__path" viewBox="0 0 1000 100" fill="none" preserveAspectRatio="none" aria-hidden="true"><path d="M60 55C230 -30 300 125 495 55S770 -30 940 55" pathLength="1" /></svg>
        <article class="smile-step"><span class="smile-step__number">01</span><div class="smile-step__icon" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></div><h3>Nachádzame tých,<br>ktorí potrebujú pomoc</h3><p>Spolu so školami, komunitnými a sociálnymi pracovníkmi i partnerskými organizáciami identifikujeme rodiny a deti v náročnej situácii.</p></article>
        <article class="smile-step"><span class="smile-step__number">02</span><div class="smile-step__icon" aria-hidden="true"><i class="fa-solid fa-hands-holding-circle"></i></div><h3>Spájame ľudí<br>a pripravujeme pomoc</h3><p>Dobrovoľníci a partneri pomáhajú s organizáciou zbierky, prípravou balíčkov a koordináciou aktivít vo svojich komunitách.</p></article>
        <article class="smile-step"><span class="smile-step__number">03</span><div class="smile-step__icon" aria-hidden="true"><i class="fa-solid fa-face-smile"></i></div><h3>Prinášame pomoc<br>tam, kde má zmysel</h3><p>Pripravená pomoc putuje priamo k deťom a rodinám v rôznych regiónoch Slovenska a podľa možností aj k partnerom v zahraničí.</p></article>
      </div>
    </div>
  </section>

  <section class="section smile-story" aria-labelledby="smile-story-title">
    <div class="container smile-split">
      <div data-smile-reveal="left">
        <p class="smile-eyebrow">Za každým úsmevom je príbeh</p>
        <h2 id="smile-story-title">Ten najkrajší odkaz?<br><em>Nie ste na to sami.</em></h2>
        <!-- Replace with a verified, consented story before presenting a specific family. -->
        <p class="smile-lead">Pre rodiny v náročnej finančnej situácii môžu sviatky prinášať aj obavy a neistotu.</p>
        <p>Balíček s potrebnými vecami a darčekmi môže znamenať chvíľu radosti pre deti aj úľavu pre rodičov. Je to jednoduchý spôsob, ako dať niekomu najavo, že naňho myslíme.</p>
        <p>Takéto chvíle chceme vytvárať spoločne. Vďaka každému človeku, ktorý daruje svoj čas, skúsenosti alebo podporu.</p>
        <a class="text-link" href="<?php echo esc_url( $cammino_stories_url ); ?>">Spoznajte ďalšie príbehy pomoci <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
      </div>
      <div class="smile-story__visual" data-smile-reveal="right" data-delay="100">
        <div class="smile-story__image"><img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="" width="1200" height="800" loading="lazy" decoding="async"></div>
        <div class="smile-story__note"><i class="fa-solid fa-heart" aria-hidden="true"></i><span>Aj malé gesto<br><strong>má veľký zmysel.</strong></span></div>
        <span class="smile-story__spark" aria-hidden="true">✳</span>
      </div>
    </div>
  </section>

  <section class="section smile-involvement" id="zapojte-sa" aria-labelledby="smile-involvement-title">
    <div class="container">
      <div class="smile-section-heading" data-smile-reveal="up"><p class="smile-eyebrow">Ako sa môžete zapojiť</p><h2 id="smile-involvement-title">Každý môže darovať<br><em>kúsok radosti.</em></h2><p>Vyberte si spôsob pomoci, ktorý je vám blízky.</p></div>
      <div class="smile-help-grid">
        <article class="smile-help-card smile-help-card--sage" data-smile-reveal="up">
          <span class="smile-help-card__label">Váš čas</span><span class="smile-help-card__icon" aria-hidden="true"><i class="fa-solid fa-hand-holding-heart"></i></span>
          <h3>Staňte sa<br>dobrovoľníkom</h3><p>Pomôžte s organizovaním zbierky, prípravou balíčkov alebo koordináciou pomoci vo svojom regióne.</p>
          <a href="<?php echo esc_url( $cammino_contact_url ); ?>">Chcem dobrovoľníčiť <span aria-hidden="true"><i class="fa-solid fa-arrow-right-long icon-diagonal"></i></span></a>
        </article>
        <article class="smile-help-card smile-help-card--apricot" data-smile-reveal="up" data-delay="100">
          <span class="smile-help-card__label">Vaša spolupráca</span><span class="smile-help-card__icon" aria-hidden="true"><i class="fa-solid fa-handshake-angle"></i></span>
          <h3>Staňte sa<br>partnerom</h3><p>Zapojte svoju firmu, školu alebo organizáciu. Spoločne nájdeme spôsob, ako rozšíriť dosah iniciatívy.</p>
          <a href="<?php echo esc_url( $cammino_contact_url ); ?>">Mám záujem o partnerstvo <span aria-hidden="true"><i class="fa-solid fa-arrow-right-long icon-diagonal"></i></span></a>
        </article>
        <article class="smile-help-card smile-help-card--coral" data-smile-reveal="up" data-delay="200">
          <span class="smile-help-card__label">Vaša podpora</span><span class="smile-help-card__icon" aria-hidden="true"><i class="fa-solid fa-gift"></i></span>
          <h3>Podporte<br>Darujme úsmev</h3><p>Finančná alebo materiálna pomoc môže priniesť radosť ďalším deťom a rodinám. Ozvite sa nám a dohodneme jej podobu.</p>
          <a href="<?php echo esc_url( $cammino_contact_url ); ?>">Chcem podporiť projekt <span aria-hidden="true"><i class="fa-solid fa-arrow-right-long icon-diagonal"></i></span></a>
        </article>
      </div>
      <p class="smile-involvement__contact">Neviete, kde začať? <a href="<?php echo esc_url( $cammino_contact_url ); ?>">Napíšte nám. Radi vám pomôžeme.</a></p>
    </div>
  </section>

  <section class="section smile-community" aria-labelledby="smile-community-title">
    <div class="container">
      <div class="smile-community__heading" data-smile-reveal="up"><div><p class="smile-eyebrow">Ľudia za úsmevmi</p><h2 id="smile-community-title">Spolu to má<br><em>väčší zmysel.</em></h2></div><p>Ďakujeme dobrovoľníkom, darcom a partnerom. Za každý pripravený balíček, podanú ruku a chvíľu, ktorú ste venovali druhým.</p></div>
      <div class="smile-gallery"<?php
      nstarter_variable_section_attributes(
        'smile_gallery',
        array( 'label' => 'Počet fotografií v galérii', 'type' => 'number', 'control' => 'repeat', 'value' => 3, 'min' => 0, 'max' => 9, 'step' => 1, 'token' => 'photo' )
      );
      ?>>
        <div class="smile-gallery__grid" data-nstarter-variable-items>
          <?php foreach ( $cammino_gallery_captions as $cammino_caption ) : ?>
          <figure class="smile-gallery__photo" data-nstarter-variable-item><div><img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="" width="1200" height="800" loading="lazy" decoding="async"></div><figcaption><?php echo esc_html( $cammino_caption ); ?></figcaption></figure>
          <?php endforeach; ?>
        </div>
        <template data-nstarter-variable-template><figure class="smile-gallery__photo" data-nstarter-variable-item><div><img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="" width="1200" height="800" loading="lazy" decoding="async"></div><figcaption>Spoločné chvíle</figcaption></figure></template>
      </div>
      <div class="smile-partners"<?php
      nstarter_variable_section_attributes(
        'smile_partners',
        array( 'label' => 'Počet potvrdených partnerov — doplňte logo a názov', 'type' => 'number', 'control' => 'repeat', 'value' => 0, 'min' => 0, 'max' => 12, 'step' => 1, 'token' => 'partner' )
      );
      ?>>
        <p class="smile-partners__thanks"><i class="fa-solid fa-heart" aria-hidden="true"></i> Každý z vás je súčasťou tohto príbehu. Ďakujeme.</p>
        <div class="smile-partners__grid" data-nstarter-variable-items></div>
        <template data-nstarter-variable-template><figure class="smile-partner" data-nstarter-variable-item><img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="" width="400" height="200" loading="lazy"><figcaption>Doplňte názov partnera</figcaption></figure></template>
      </div>
    </div>
  </section>

  <section class="smile-closing" aria-labelledby="smile-closing-title">
    <div class="container">
      <div class="smile-closing__card" data-smile-reveal="scale">
        <div class="smile-closing__face" aria-hidden="true"><span></span><svg viewBox="0 0 180 100" fill="none"><path d="M15 15C22 107 158 107 165 15" pathLength="1" /></svg></div>
        <p class="smile-eyebrow">Jeden dobrý skutok môže byť začiatkom</p>
        <h2 id="smile-closing-title">Spoločne môžeme priniesť<br><em>viac úsmevov.</em></h2>
        <p>Každý dar, každý dobrovoľník a každý partner pomáha vytvárať príbeh solidarity. Staňte sa jeho súčasťou.</p>
        <a class="button button--cream" href="#zapojte-sa">Chcem pomôcť <span class="button-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right-long icon-diagonal"></i></span></a>
      </div>
    </div>
  </section>
</main>
