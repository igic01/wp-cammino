<?php
/**
 * Snapshot Name: Kontakt
 *
 * @package Cammino
 */

$cammino_asset_url = NSTARTER_URL . '/assets';
?>
  <a class="skip-link" href="#main-content">Preskočiť na obsah</a>

  <main id="main-content">
    <section class="contact-hero" aria-labelledby="contact-title">
      <div class="container contact-hero__grid">
        <div class="contact-intro" data-contact-reveal="left">
          <p class="contact-label"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Kontakt</p>
          <h1 id="contact-title">Poďme urobiť <em>ďalší krok spolu</em></h1>
          <p class="contact-lead">Máte otázku, nápad na spoluprácu alebo chcete vedieť viac o našich aktivitách? Vyberte si správny kontakt alebo nám pošlite správu.</p>

        </div>

        <div class="contact-form-card" data-contact-reveal="scale" data-delay="100">
          <div class="form-heading">
            <div class="form-heading__icon" aria-hidden="true" data-contact-pop><span class="form-heading__badge"><i class="fa-solid fa-envelope-open-text"></i></span></div>
            <div>
              <span>Napíšte nám</span>
              <h2>Čo máte na mysli?</h2>
            </div>
          </div>

          <div class="contact-form-runtime">
            <?php nstarter_live_section( 'cammino_contact_form' ); ?>
          </div>
        </div>
      </div>
    </section>

    <section class="section info-section contact-team-section" aria-labelledby="contact-team-title">
      <div class="container">
        <div class="info-card" <?php nstarter_variable_section_attributes( 'contact_people_count', array( 'label' => 'Počet členov tímu', 'type' => 'number', 'control' => 'repeat', 'value' => 2, 'min' => 0, 'step' => 1, 'token' => 'person' ) ); ?>>
          <div class="info-intro"><h2 id="contact-team-title">Náš tím</h2></div>
          <div class="contact-people" data-nstarter-variable-items>
            <article class="contact-person" data-nstarter-variable-item>
              <div class="contact-person__icon" aria-hidden="true"><i class="fa-solid fa-user-tie"></i></div>
              <div><span>Štatutár</span><h3>Róbert Mruk</h3><a href="mailto:management@ozcammino.sk">management@ozcammino.sk <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
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

    <section class="section contact-details" aria-labelledby="details-title">
      <div class="container">
        <div class="details-heading" data-contact-reveal="up">
          <div>
            <span class="detail-number">01</span>
            <h2 id="details-title">Nájdite svoju cestu <em>k nám</em></h2>
          </div>
        </div>

        <div class="details-grid">
          <article class="address-card" data-contact-reveal="left">
            <div class="address-card__copy">
              <div class="address-marker" aria-hidden="true" data-contact-pop><span class="detail-icon__badge"><i class="fa-solid fa-location-dot"></i></span></div>
              <span>Adresa</span>
              <h3>Miletičova&nbsp;7<br>Bratislava<br>821 08<br>Slovenská Republika</h3>
              <a class="text-link" href="https://maps.google.com/?q=Miletičova+7+Bratislava" target="_blank" rel="noopener noreferrer">Otvoriť v mape <span aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span></a>
            </div>
            <div class="address-art" aria-hidden="true">
              <span class="map-ring map-ring--one"></span>
              <span class="map-ring map-ring--two"></span>
              <svg viewBox="0 0 260 190">
                <path d="M18 166 C55 152, 39 98, 92 105 S130 36, 191 52 S215 23, 244 17" pathLength="1"></path>
                <path class="map-arrow" d="M229 9 L245 17 L232 29" pathLength="1"></path>
              </svg>
            </div>
          </article>

          <article class="support-contact-card" data-contact-reveal="right" data-delay="120">
            <div class="support-copy">
              <div class="support-heart" aria-hidden="true" data-contact-pop><span class="detail-icon__badge"><i class="fa-solid fa-heart"></i></span></div>
              <span>Podporte nás</span>
              <h3>Pomôžte dobrým veciam napredovať</h3>
              <p>Každá podpora nám umožňuje vytvárať ďalšie príležitosti pre mladých ľudí a komunity.</p>
              <a class="button button--cream" href="<?php echo esc_url( CAMMINO_DONATE_URL ); ?>" target="_blank" rel="noopener noreferrer">Chcem pomôcť <span class="button-arrow"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span></a>
            </div>

            <div class="qr-wrap" aria-label="QR kód pre podporu OZ Cammino">
              <a class="qr-placeholder" href="https://cammino.darujme.sk/darujmeusmev/" target="_blank" rel="noopener noreferrer" aria-label="Podporiť OZ Cammino (otvorí sa na novej karte)">
                <img src="<?php echo esc_url( $cammino_asset_url . '/images/placeholder.webp' ); ?>" alt="Miesto pre QR kód na podporu OZ Cammino" width="800" height="800" loading="lazy">
              </a>
            </div>
          </article>
        </div>
      </div>
    </section>
  </main>
