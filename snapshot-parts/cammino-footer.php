<?php
/**
 * Shared Cammino snapshot footer.
 *
 * Expected variables: $cammino_footer_id, $cammino_footer_email,
 * $cammino_footer_place, and $cammino_form_id.
 *
 * @package NStarter
 */

$cammino_footer_id    = isset( $cammino_footer_id ) ? (string) $cammino_footer_id : '';
$cammino_footer_email = isset( $cammino_footer_email ) ? (string) $cammino_footer_email : 'ahoj@cammino.sk';
$cammino_footer_place = isset( $cammino_footer_place ) ? (string) $cammino_footer_place : 'Bratislava, Slovensko';
$cammino_form_id      = isset( $cammino_form_id ) ? sanitize_html_class( $cammino_form_id ) : 'cammino-footer-email';
?>
<footer class="site-footer"<?php echo '' !== $cammino_footer_id ? ' id="' . esc_attr( $cammino_footer_id ) . '"' : ''; ?>>
	<div class="container footer-main">
		<div class="footer-brand">
			<a class="brand brand--footer" href="<?php echo esc_url( nstarter_cammino_page_url( 'index' ) ); ?>" aria-label="Cammino – domov">
				<img src="<?php echo esc_url( NSTARTER_URL . '/assets/cammino/logos/long_logo.svg' ); ?>" alt="Cammino" width="1666" height="297">
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
			<a href="<?php echo esc_url( nstarter_cammino_page_url( 'aboutus' ) ); ?>">O nás</a>
			<a href="<?php echo esc_url( nstarter_cammino_page_url( 'ss' ) ); ?>">Príbehy úspechov</a>
			<a href="<?php echo esc_url( nstarter_cammino_page_url( 'news', '#events' ) ); ?>">Podujatia</a>
			<a href="<?php echo esc_url( nstarter_cammino_page_url( 'news' ) ); ?>">Novinky</a>
		</div>
		<div class="footer-links">
			<h2>Zapojte sa</h2>
			<a href="<?php echo esc_url( nstarter_cammino_page_url( 'donate' ) ); ?>">Podporte nás</a>
			<a href="<?php echo esc_url( nstarter_cammino_page_url( 'contact', '?tema=firmy' ) ); ?>">Pre firmy</a>
			<a href="<?php echo esc_url( nstarter_cammino_page_url( 'contact', '?tema=cas' ) ); ?>">Dobrovoľníctvo</a>
			<a href="<?php echo esc_url( nstarter_cammino_page_url( 'contact' ) ); ?>">Pošlite nám príbeh</a>
		</div>
		<div class="footer-contact">
			<h2>Zostaňme v kontakte</h2>
			<a href="mailto:<?php echo esc_attr( sanitize_email( $cammino_footer_email ) ); ?>"><?php echo esc_html( $cammino_footer_email ); ?></a>
			<p><?php echo esc_html( $cammino_footer_place ); ?></p>
			<form class="newsletter" action="#" method="post">
				<label class="sr-only" for="<?php echo esc_attr( $cammino_form_id ); ?>">Váš e-mail</label>
				<input id="<?php echo esc_attr( $cammino_form_id ); ?>" type="email" name="email" placeholder="Váš e-mail" required>
				<button type="submit" aria-label="Prihlásiť sa na odber"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
			</form>
		</div>
	</div>
	<div class="container footer-bottom">
		<p>© <span data-year><?php echo esc_html( wp_date( 'Y' ) ); ?></span> Cammino. Každý krok má zmysel.</p>
		<div><a href="#">Ochrana súkromia</a><a href="#">Cookies</a></div>
	</div>
</footer>
