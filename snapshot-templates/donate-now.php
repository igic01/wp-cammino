<?php
/**
 * Snapshot Name: donate now
 *
 * @package Cammino
 */

$cammino_asset_url   = NSTARTER_URL . '/assets';
$cammino_placeholder = $cammino_asset_url . '/images/placeholder.webp';
?>
  <a class="skip-link" href="#main-content">Preskočiť na obsah</a>

  <header class="site-header" data-header>
    <div class="container header-inner">
      <a class="brand" href="index.html" aria-label="Cammino – domov">
        <img src="<?php echo esc_url( $cammino_asset_url . '/logos/long_logo.svg' ); ?>" alt="Cammino" width="1666" height="297">
      </a>

      <button class="nav-toggle" type="button" aria-label="Otvoriť menu" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
      </button>

      <nav class="site-nav" id="site-nav" aria-label="Hlavná navigácia" data-nav>
        <a href="index.html">Domov</a>
        <a href="aboutus.html">O nás</a>
        <a href="ss.html">Príbehy</a>
        <a href="news.html#events">Podujatia</a>
        <a href="news.html">Novinky</a>
        <a class="is-active" href="contact.html" aria-current="page">Kontakt</a>
        <a class="language-link" href="#" lang="en" aria-label="Switch to English">EN</a>
        <a class="button button--small button--coral nav-donate" href="donate.html">Prispieť <i class="fa-solid fa-heart" aria-hidden="true"></i></a>
      </nav>
    </div>
  </header>

  <main id="main-content">
    <section class="contact-hero" aria-labelledby="contact-title">
      <div class="container contact-hero__grid">
        <div class="contact-intro" data-contact-reveal="left">
          <h1 id="contact-title">Poďme urobiť <em>ďalší krok spolu</em></h1>
          <p class="contact-lead">Máte otázku, nápad na spoluprácu alebo chcete vedieť viac o našich aktivitách? Vyberte si správny kontakt alebo nám pošlite správu.</p>

          <div class="contact-people">
            <article class="person-card" data-contact-reveal="up" data-delay="80">
              <div class="person-card__icon" aria-hidden="true" data-contact-pop><i class="fa-solid fa-user-tie"></i></div>
              <div>
                <span>Štatutár</span>
                <h2>Róbert Mruk</h2>
                <a href="mailto:management@ozcammino.sk">management@ozcammino.sk <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
              </div>
            </article>

            <article class="person-card" data-contact-reveal="up" data-delay="160">
              <div class="person-card__icon" aria-hidden="true" data-contact-pop><i class="fa-solid fa-folder-open"></i></div>
              <div>
                <span>Projektový manažér</span>
                <h2>Alexandra Mruk Papaianopol</h2>
                <a href="mailto:projekty@ozcammino.sk">projekty@ozcammino.sk <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
              </div>
            </article>
          </div>
        </div>

        <!-- here -->
         <article class="support-contact-card" data-contact-reveal="right" data-delay="120">
            <div class="support-copy">
              <div class="support-heart" aria-hidden="true" data-contact-pop><span class="detail-icon__badge"><i class="fa-solid fa-heart"></i></span></div>
              <span>Podporte nás</span>
              <h3>Pomôžte dobrým veciam napredovať</h3>
              <p>Každá podpora nám umožňuje vytvárať ďalšie príležitosti pre mladých ľudí a komunity.</p>
              <a class="button button--cream" href="donate.html">Chcem pomôcť <span class="button-arrow"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span></a>
            </div>

            <div class="qr-wrap" aria-label="QR kód pre podporu OZ Cammino">
              <div class="qr-placeholder" role="button" tabindex="0" aria-pressed="false" aria-label="Zväčšiť QR kód" data-qr-toggle>
                <img src="<?php echo esc_url( $cammino_placeholder ); ?>" alt="Miesto pre QR kód na podporu OZ Cammino" width="800" height="800" loading="lazy">
              </div>
              <small class="qr-hint"><i class="fa-solid fa-hand-pointer" aria-hidden="true"></i> Kliknutím zväčšíte QR</small>
            </div>
          </article>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container footer-main">
      <div class="footer-brand">
        <a class="brand brand--footer" href="index.html" aria-label="Cammino – domov">
          <img src="<?php echo esc_url( $cammino_asset_url . '/logos/long_logo.svg' ); ?>" alt="Cammino" width="1666" height="297">
        </a>
        <p>Pomáhame mladým ľuďom nájsť cestu k vzdelaniu, práci a samostatnej budúcnosti.</p>
        <div class="social-links" aria-label="Sociálne siete">
          <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram" aria-hidden="true"></i></a>
          <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a>
          <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in" aria-hidden="true"></i></a>
        </div>
      </div>
      <div class="footer-links">
        <h2>Cammino</h2>
        <a href="aboutus.html">O nás</a>
        <a href="ss.html">Príbehy úspechov</a>
        <a href="index.html#events">Podujatia</a>
        <a href="news.html">Novinky</a>
      </div>
      <div class="footer-links">
        <h2>Zapojte sa</h2>
        <a href="donate.html">Podporte nás</a>
        <a href="#">Pre firmy</a>
        <a href="#">Dobrovoľníctvo</a>
        <a href="#">Pošlite nám príbeh</a>
      </div>
      <div class="footer-contact">
        <h2>Zostaňme v kontakte</h2>
        <a href="mailto:management@ozcammino.sk">management@ozcammino.sk</a>
        <p>Miletičova 7, Bratislava</p>
        <form class="newsletter" action="#" method="post">
          <label class="sr-only" for="contact-footer-email">Váš e-mail</label>
          <input id="contact-footer-email" type="email" name="email" placeholder="Váš e-mail" required>
          <button type="submit" aria-label="Prihlásiť sa na odber"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
        </form>
      </div>
    </div>
    <div class="container footer-bottom">
      <p>© <span data-year></span> Cammino. Každý krok má zmysel.</p>
      <div><a href="#">Ochrana súkromia</a><a href="#">Cookies</a></div>
    </div>
  </footer>
