<?php
/** Live tipster portal; never captured by the visual snapshot editor. @package Cammino */
defined( 'ABSPATH' ) || exit;
get_header();
$login = 'login' === cammino_tipsters_route();
$user = wp_get_current_user();
$titles = array( 'login' => __( 'Prihlásenie tipstera', 'cammino' ), 'dashboard' => __( 'Môj účet', 'cammino' ), 'new' => __( 'Nový tip', 'cammino' ), 'tip' => __( 'Detail tipu', 'cammino' ) );
?>
<main id="main-content" class="cammino-tipsters<?php echo $login ? ' cammino-tipsters--login' : ''; ?>">
	<div class="container">
		<div class="cammino-tipsters__topbar">
			<div class="cammino-tipsters__heading">
				<p class="cammino-tipsters__eyebrow"><?php esc_html_e( 'Cammino · Tipsteri', 'cammino' ); ?></p>
				<h1><?php echo esc_html( $titles[ cammino_tipsters_route() ] ?? __( 'Môj účet', 'cammino' ) ); ?></h1>
				<p><?php echo esc_html( $login ? __( 'Prihláste sa údajmi, ktoré vám poskytol administrátor.', 'cammino' ) : sprintf( __( 'Vitajte, %s.', 'cammino' ), $user->display_name ) ); ?></p>
			</div>
		<?php if ( ! $login ) : ?>
			<form class="cammino-tipsters__logout" method="post" action="<?php echo esc_url( cammino_tipsters_url() ); ?>">
				<?php wp_nonce_field( 'cammino_tipster_logout' ); ?>
				<input type="hidden" name="operation" value="logout">
				<button type="submit" class="button"><?php esc_html_e( 'Odhlásiť sa', 'cammino' ); ?></button>
			</form>
		<?php endif; ?>
		</div>
		<?php if ( $login ) : ?>
			<div class="cammino-tipsters__card cammino-tipsters__login">
				<?php if ( ! empty( $GLOBALS['cammino_tipsters_login_error'] ) ) : ?>
					<p class="cammino-tipsters__error" role="alert"><?php echo esc_html( $GLOBALS['cammino_tipsters_login_error'] ); ?></p>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( cammino_tipsters_url( 'login' ) ); ?>">
					<?php wp_nonce_field( 'cammino_tipster_login' ); ?>
					<input type="hidden" name="cammino_login_token" value="<?php echo esc_attr( $GLOBALS['cammino_tipsters_login_token'] ?? '' ); ?>">
					<label for="tipster-username"><?php esc_html_e( 'Používateľské meno', 'cammino' ); ?></label>
					<input id="tipster-username" name="username" type="text" maxlength="60" autocomplete="username" required value="<?php echo esc_attr( cammino_tipsters_input( 'username' ) ); ?>">
					<label for="tipster-password"><?php esc_html_e( 'Heslo', 'cammino' ); ?></label>
					<input id="tipster-password" name="password" type="password" maxlength="4096" autocomplete="current-password" required>
					<button type="button" class="cammino-tipsters__password-toggle" aria-controls="tipster-password" aria-pressed="false" data-password-toggle hidden><?php esc_html_e( 'Zobraziť heslo', 'cammino' ); ?></button>
					<button type="submit" class="button button--coral"><?php esc_html_e( 'Prihlásiť sa', 'cammino' ); ?></button>
				</form>
				<p class="cammino-tipsters__help"><?php esc_html_e( 'Ak nemáte prístupové údaje alebo potrebujete nové heslo, kontaktujte administrátora. Účty a heslá spravuje administrátor.', 'cammino' ); ?></p>
			</div>
		<?php else : ?>
			<?php require __DIR__ . '/' . ( 'new' === cammino_tipsters_route() ? 'new-tip' : ( 'tip' === cammino_tipsters_route() ? 'tip-detail' : 'dashboard' ) ) . '.php'; ?>
		<?php endif; ?>
	</div>
</main>
<?php get_footer(); ?>
