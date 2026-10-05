<?php
/** Shared private conversation view. @package Cammino */
defined( 'ABSPATH' ) || exit;
?>
<section id="conversation" class="cammino-conversation <?php echo $admin ? 'cammino-admin-tips__panel' : 'cammino-tipsters__card'; ?>" aria-labelledby="conversation-title" data-endpoint="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-tip="<?php echo esc_attr( $id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'cammino_conversation_' . $id ) ); ?>" data-cursor="<?php echo esc_attr( $cursor ); ?>">
	<div class="cammino-conversation__heading"><h2 id="conversation-title"><?php esc_html_e( 'Komunikácia', 'cammino' ); ?></h2><span data-chat-state><?php echo esc_html( cammino_tipsters_can_message( $id ) ? __( 'Otvorená', 'cammino' ) : __( 'Uzamknutá', 'cammino' ) ); ?></span></div>
	<p><?php esc_html_e( 'Správy sa ukladajú s odosielateľom a časom. Odoslané správy nie je možné upraviť ani odstrániť. Nové správy sa zobrazujú automaticky.', 'cammino' ); ?></p>
	<noscript><p><?php esc_html_e( 'Bez JavaScriptu zobrazíte nové správy obnovením stránky.', 'cammino' ); ?></p></noscript>
	<?php if ( $error instanceof WP_Error ) : ?><p class="cammino-tipsters__error" role="alert"><?php echo esc_html( $error->get_error_message() ); ?></p><?php endif; ?>
	<div class="cammino-conversation__viewport">
	<p class="cammino-conversation__feedback" data-live-status role="status" aria-live="polite"></p>
	<div class="cammino-conversation__scroll" data-chat-scroll data-latest="<?php echo $page >= max( 1, (int) $query->max_num_pages ) ? 'yes' : 'no'; ?>" tabindex="0" role="region" aria-label="<?php esc_attr_e( 'Správy v konverzácii', 'cammino' ); ?>">
	<?php if ( ! $query->found_posts ) : ?><p data-empty><?php esc_html_e( 'Zatiaľ bez správ.', 'cammino' ); ?></p><?php endif; ?>
	<ol class="cammino-conversation__messages">
		<?php foreach ( $query->posts as $message ) : ?>
			<li class="<?php echo (int) $message->post_author === get_current_user_id() ? 'is-own' : 'is-other'; ?>" data-message-id="<?php echo esc_attr( $message->ID ); ?>"><strong><?php echo esc_html( ( $message->post_title ?: __( 'Odosielateľ', 'cammino' ) ) . ' · ' . ( (int) $message->post_author === (int) get_post( $id )->post_author ? __( 'Tipster', 'cammino' ) : __( 'Administrátor', 'cammino' ) ) ); ?></strong>
				<time datetime="<?php echo esc_attr( str_replace( ' ', 'T', $message->post_date_gmt ) . 'Z' ); ?>"><?php echo esc_html( get_post_time( get_option( 'date_format' ) . ' H:i:s', false, $message, true ) ); ?></time>
				<div class="cammino-conversation__text"><?php echo esc_html( $message->post_content ); ?></div>
			</li>
		<?php endforeach; ?>
	</ol>
	<?php $links = paginate_links( array( 'base' => add_query_arg( 'messages_page', '%#%', $url ) . '#conversation', 'format' => '', 'current' => $page, 'total' => (int) $query->max_num_pages ) ); ?>
	<?php if ( $links ) : ?><nav aria-label="<?php esc_attr_e( 'Stránky komunikácie', 'cammino' ); ?>"><?php echo wp_kses_post( $links ); ?></nav><?php endif; ?>
	<div data-live-messages hidden><h3><?php esc_html_e( 'Nové správy', 'cammino' ); ?></h3><ol class="cammino-conversation__messages" aria-live="polite" aria-relevant="additions"></ol></div>
	</div>
	<button type="button" class="cammino-conversation__unread" data-new-messages hidden><?php esc_html_e( 'Prejsť na nové správy', 'cammino' ); ?></button>
	</div>
	<?php $can_send = cammino_tipsters_can_message( $id ); ?>
		<form data-message-form <?php echo $can_send ? '' : 'hidden'; ?> method="post" action="<?php echo esc_url( $admin ? admin_url( 'admin-post.php' ) : $url ); ?>">
			<?php wp_nonce_field( 'cammino_send_message_' . $id ); ?>
			<input type="hidden" name="operation" value="<?php echo $can_send ? 'send_message' : ''; ?>"><input type="hidden" name="message_token" value="<?php echo esc_attr( $token ); ?>">
			<?php if ( $admin ) : ?><input type="hidden" name="action" value="cammino_tip_message"><input type="hidden" name="tip_id" value="<?php echo esc_attr( $id ); ?>"><?php endif; ?>
			<label for="message-body"><?php esc_html_e( 'Nová správa', 'cammino' ); ?></label>
			<div class="cammino-conversation__composer">
				<textarea id="message-body" name="message_body" rows="2" maxlength="<?php echo esc_attr( CAMMINO_MESSAGE_MAX_LENGTH ); ?>" required aria-describedby="message-help"><?php echo esc_textarea( $body ); ?></textarea>
				<button type="submit" class="<?php echo $admin ? 'button button-primary' : 'button button--coral'; ?>"><?php esc_html_e( 'Odoslať', 'cammino' ); ?></button>
			</div>
			<p id="message-help"><?php echo esc_html( sprintf( __( 'Najviac %d znakov.', 'cammino' ), CAMMINO_MESSAGE_MAX_LENGTH ) ); ?> <span data-keyboard-help hidden><?php esc_html_e( 'Enter odošle správu, Shift+Enter pridá nový riadok.', 'cammino' ); ?></span></p>
		</form>
	<p data-closed <?php echo $can_send ? 'hidden' : ''; ?>><?php esc_html_e( 'Komunikácia je otvorená iba v diskusii a pri schválenom tipe s aktívnym účtom. História zostáva uložená.', 'cammino' ); ?></p>
</section>
