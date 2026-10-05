<?php
/** Separate admin Tips tab with private review, filters, and status history. @package Cammino */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', static function (): void {
	if ( cammino_tipsters_can_manage() ) {
		$hook = add_menu_page( __( 'Tipy', 'cammino' ), __( 'Tipy', 'cammino' ), CAMMINO_TIPSTERS_CAP, 'cammino-tips', 'cammino_tipsters_admin_tips_page', 'dashicons-lightbulb', 59 );
		add_action( 'load-' . $hook, static function (): void {
			if ( ! cammino_tipsters_can_manage() ) { wp_die( esc_html__( 'Nemáte oprávnenie spravovať tipy.', 'cammino' ), '', array( 'response' => 403 ) ); }
			$id = isset( $_GET['tip_id'] ) && is_scalar( $_GET['tip_id'] ) ? absint( $_GET['tip_id'] ) : 0;
			if ( $id && ( ! cammino_tipsters_can_read_tip( $id ) || ! cammino_tipsters_tip_ready( $id ) ) ) { wp_die( esc_html__( 'Tip nie je dostupný.', 'cammino' ), '', array( 'response' => 404 ) ); }
			nocache_headers(); header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0' );
		} );
	}
} );

function cammino_tipsters_admin_tips_url( array $args = array() ): string {
	return add_query_arg( array_merge( array( 'page' => 'cammino-tips' ), $args ), admin_url( 'admin.php' ) );
}

function cammino_tipsters_admin_tips_query( string $status = '', string $search = '', int $owner = 0, int $page = 1 ): WP_Query {
	$statuses = array_keys( cammino_tipsters_status_labels() );
	return new WP_Query( array(
		'post_type' => CAMMINO_TIP_POST_TYPE, 'post_status' => 'private', 'posts_per_page' => 20,
		'paged' => max( 1, $page ), 's' => $search, 'author' => $owner ?: '', 'orderby' => array( 'date' => 'DESC', 'ID' => 'DESC' ),
		'post__in' => cammino_tipsters_can_manage() ? array() : array( 0 ),
		'meta_query' => array( array( 'key' => '_cammino_tip_status', 'value' => in_array( $status, $statuses, true ) ? array( $status ) : $statuses, 'compare' => 'IN' ) ),
	) );
}

add_action( 'admin_post_cammino_tip_status', static function (): void {
	if ( ! cammino_tipsters_can_manage() || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { wp_die( esc_html__( 'Nemáte oprávnenie meniť stav tipu.', 'cammino' ), '', array( 'response' => 403 ) ); }
	$id = absint( cammino_tipsters_input( 'tip_id' ) );
	check_admin_referer( 'cammino_tip_status_' . $id );
	$result = cammino_tipsters_change_status( $id, cammino_tipsters_input( 'status' ), cammino_tipsters_input( 'tip_version' ), 'yes' === cammino_tipsters_input( 'confirm_reopen' ) );
	set_transient( 'cammino_tip_status_notice_' . get_current_user_id(), array( 'error' => is_wp_error( $result ), 'message' => is_wp_error( $result ) ? $result->get_error_message() : __( 'Stav tipu bol aktualizovaný.', 'cammino' ) ), MINUTE_IN_SECONDS );
	wp_safe_redirect( cammino_tipsters_admin_tips_url( array( 'tip_id' => $id ) ), 303 ); exit;
} );

function cammino_tipsters_admin_tips_page(): void {
	if ( ! cammino_tipsters_can_manage() ) { wp_die( esc_html__( 'Nemáte oprávnenie spravovať tipy.', 'cammino' ), '', array( 'response' => 403 ) ); }
	$id = isset( $_GET['tip_id'] ) && is_scalar( $_GET['tip_id'] ) ? absint( $_GET['tip_id'] ) : 0;
	if ( $id && ( ! cammino_tipsters_can_read_tip( $id ) || ! cammino_tipsters_tip_ready( $id ) ) ) { wp_die( esc_html__( 'Tip nie je dostupný.', 'cammino' ), '', array( 'response' => 404 ) ); }
	if ( $id ) {
		$key = 'cammino_message_draft_' . get_current_user_id() . '_' . $id;
		$draft = get_transient( $key ); delete_transient( $key );
		if ( is_array( $draft ) ) {
			$GLOBALS['cammino_tipsters_message_error'] = new WP_Error( 'message', $draft['error'] );
			$GLOBALS['cammino_tipsters_message_draft'] = $draft;
		}
	}
	$notice = get_transient( 'cammino_tip_status_notice_' . get_current_user_id() ); delete_transient( 'cammino_tip_status_notice_' . get_current_user_id() );
	echo '<div class="wrap cammino-admin-tips"><h1>' . esc_html__( 'Tipy', 'cammino' ) . '</h1>';
	if ( is_array( $notice ) ) { echo '<div class="notice ' . esc_attr( $notice['error'] ? 'notice-error' : 'notice-success' ) . '"><p>' . esc_html( $notice['message'] ) . '</p></div>'; }
	if ( $id ) { cammino_tipsters_admin_tip_detail( $id ); } else { cammino_tipsters_admin_tips_list(); }
	echo '</div>';
}

function cammino_tipsters_admin_tips_list(): void {
	$status = isset( $_GET['status'] ) && is_string( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
	$search = isset( $_GET['s'] ) && is_string( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$owner = isset( $_GET['owner'] ) && is_scalar( $_GET['owner'] ) ? absint( $_GET['owner'] ) : 0;
	$page = isset( $_GET['paged'] ) && is_scalar( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$query = cammino_tipsters_admin_tips_query( $status, $search, $owner, $page ); $labels = cammino_tipsters_status_labels();
	?>
	<p><?php esc_html_e( 'Otvorte tip, skontrolujte jeho obsah a prílohy a zmeňte stav. Tipy odstránené tipsterom zostávajú dostupné administrátorom.', 'cammino' ); ?></p>
	<form method="get" class="cammino-admin-tips__filters">
		<input type="hidden" name="page" value="cammino-tips">
		<?php if ( $owner ) : ?><input type="hidden" name="owner" value="<?php echo esc_attr( $owner ); ?>"><?php endif; ?>
		<label for="tip-status-filter"><?php esc_html_e( 'Stav', 'cammino' ); ?></label>
		<select name="status" id="tip-status-filter"><option value=""><?php esc_html_e( 'Všetky stavy', 'cammino' ); ?></option><?php foreach ( $labels as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
		<label for="tip-search"><?php esc_html_e( 'Hľadať v tipoch', 'cammino' ); ?></label>
		<input type="search" id="tip-search" name="s" value="<?php echo esc_attr( $search ); ?>">
		<?php submit_button( __( 'Filtrovať', 'cammino' ), 'secondary', '', false ); ?>
		<a href="<?php echo esc_url( cammino_tipsters_admin_tips_url() ); ?>"><?php esc_html_e( 'Zrušiť filtre', 'cammino' ); ?></a>
	</form>
	<?php if ( $owner && get_userdata( $owner ) ) : ?><p><?php echo esc_html( sprintf( __( 'Tipster: %s', 'cammino' ), get_userdata( $owner )->user_login ) ); ?></p><?php endif; ?>
	<table class="wp-list-table widefat fixed striped"><thead><tr><th><?php esc_html_e( 'Názov', 'cammino' ); ?></th><th><?php esc_html_e( 'Tipster', 'cammino' ); ?></th><th><?php esc_html_e( 'Stav', 'cammino' ); ?></th><th><?php esc_html_e( 'Odoslané', 'cammino' ); ?></th><th><?php esc_html_e( 'Aktualizované', 'cammino' ); ?></th></tr></thead><tbody>
		<?php foreach ( $query->posts as $tip ) : $record = cammino_tipsters_tip_record( $tip->ID ); $account = get_userdata( (int) $tip->post_author ); ?>
			<tr><td><strong><a href="<?php echo esc_url( cammino_tipsters_admin_tips_url( array( 'tip_id' => $tip->ID ) ) ); ?>"><?php echo esc_html( $record['title'] ); ?></a></strong></td>
			<td><?php if ( $account ) : ?><a href="<?php echo esc_url( cammino_tipsters_admin_tips_url( array( 'owner' => $account->ID ) ) ); ?>"><?php echo esc_html( $account->display_name . ' (' . $account->user_login . ')' ); ?></a><?php else : ?>—<?php endif; ?></td>
			<td><?php echo esc_html( $labels[ $record['status'] ] ?? $record['status'] ); ?></td><td><?php echo esc_html( get_post_time( get_option( 'date_format' ) . ' H:i', false, $tip, true ) ); ?></td><td><?php echo esc_html( get_date_from_gmt( $record['updated_at'], get_option( 'date_format' ) . ' H:i' ) ); ?></td></tr>
		<?php endforeach; ?>
		<?php if ( ! $query->posts ) : ?><tr><td colspan="5"><?php esc_html_e( 'Nenašli sa žiadne tipy.', 'cammino' ); ?></td></tr><?php endif; ?>
	</tbody></table>
	<?php
	$links = paginate_links( array( 'base' => add_query_arg( 'paged', '%#%', cammino_tipsters_admin_tips_url( array( 'status' => $status, 's' => $search, 'owner' => $owner ) ) ), 'format' => '', 'current' => $page, 'total' => (int) $query->max_num_pages ) );
	if ( $links ) { echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( $links ) . '</div></div>'; }
}

function cammino_tipsters_admin_file_list( int $id, array $files ): void {
	echo '<ul>';
	foreach ( $files as $file ) {
		if ( empty( $file['id'] ) ) { continue; }
		echo '<li><a href="' . esc_url( cammino_tipsters_url( 'download', $id, $file['id'] ) ) . '">' . esc_html( $file['name'] ) . '</a> (' . esc_html( size_format( $file['size'] ) ) . ')</li>';
	}
	if ( ! $files ) { echo '<li>' . esc_html__( 'Bez príloh.', 'cammino' ) . '</li>'; }
	echo '</ul>';
}

function cammino_tipsters_admin_tip_detail( int $id ): void {
	$record = cammino_tipsters_tip_record( $id ); $post = get_post( $id ); $labels = cammino_tipsters_status_labels(); $owner = get_userdata( (int) $post->post_author );
	?>
	<p><a href="<?php echo esc_url( cammino_tipsters_admin_tips_url() ); ?>">&larr; <?php esc_html_e( 'Všetky tipy', 'cammino' ); ?></a></p>
	<div class="cammino-admin-tips__panel">
		<h2><?php echo esc_html( $record['title'] ); ?></h2>
		<p><strong data-tip-status><?php echo esc_html( $labels[ $record['status'] ] ); ?></strong> · <?php if ( $owner ) : ?><a href="<?php echo esc_url( cammino_tipsters_admin_url( array( 'account_id' => $owner->ID ) ) ); ?>"><?php echo esc_html( $owner->display_name . ' (' . $owner->user_login . ')' ); ?></a><?php endif; ?></p>
		<p><?php echo esc_html( sprintf( __( 'Odoslané: %1$s · Aktualizované: %2$s', 'cammino' ), get_post_time( get_option( 'date_format' ) . ' H:i', false, $post, true ), get_date_from_gmt( $record['updated_at'], get_option( 'date_format' ) . ' H:i' ) ) ); ?></p>
		<h3><?php esc_html_e( 'Krátky popis', 'cammino' ); ?></h3><div class="cammino-admin-tips__text"><?php echo esc_html( $record['short_description'] ); ?></div>
		<h3><?php esc_html_e( 'Podrobný popis', 'cammino' ); ?></h3><div class="cammino-admin-tips__text"><?php echo esc_html( $record['long_description'] ); ?></div>
		<h3><?php esc_html_e( 'Odkaz na súbory', 'cammino' ); ?></h3>
		<?php if ( ! empty( $record['file_link'] ) ) : ?><p><a href="<?php echo esc_url( $record['file_link'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $record['file_link'] ); ?></a></p><?php else : ?><p><?php esc_html_e( 'Bez odkazu.', 'cammino' ); ?></p><?php endif; ?>
		<?php if ( $record['files'] ) : ?><h3><?php esc_html_e( 'Staršie prílohy', 'cammino' ); ?></h3><?php cammino_tipsters_admin_file_list( $id, $record['files'] ); ?><?php endif; ?>
		<?php if ( $record['retained_files'] ) : ?><h3><?php esc_html_e( 'Prílohy odstránené z formulára', 'cammino' ); ?></h3><p><?php esc_html_e( 'Zachované pre administrátora; tipster ich už nemôže stiahnuť.', 'cammino' ); ?></p><?php cammino_tipsters_admin_file_list( $id, $record['retained_files'] ); ?><?php endif; ?>
	</div>
	<div class="cammino-admin-tips__panel"><h2><?php esc_html_e( 'Zmeniť stav', 'cammino' ); ?></h2>
		<?php if ( get_post_meta( $id, '_cammino_tip_purging', true ) ) : ?>
			<p class="notice notice-error"><?php esc_html_e( 'Mazanie tipu nie je dokončené. Tipster nemá prístup. Zopakujte trvalé odstránenie nižšie.', 'cammino' ); ?></p>
		<?php elseif ( $record['deleted'] ) : $actor = get_userdata( $record['deleted']['actor'] ); ?>
			<p><?php echo esc_html( sprintf( __( 'Odstránil: %1$s · Čas: %2$s · Predchádzajúci stav: %3$s', 'cammino' ), $actor ? $actor->display_name : __( 'Tipster', 'cammino' ), get_date_from_gmt( $record['deleted']['time'], get_option( 'date_format' ) . ' H:i' ), $labels[ $record['deleted']['previous_status'] ] ) ); ?></p>
			<p><?php esc_html_e( 'Tip zostáva zachovaný. Obnovenie ani nové zmeny nie sú povolené.', 'cammino' ); ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'Diskusia umožní úpravy a komunikáciu. Schválenie uzamkne formulár. Zamietnutie uzamkne formulár aj komunikáciu.', 'cammino' ); ?></p>
			<?php foreach ( cammino_tipsters_tip_transitions( $record['status'] ) as $target ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cammino-admin-tips__status-form">
					<input type="hidden" name="action" value="cammino_tip_status"><input type="hidden" name="tip_id" value="<?php echo esc_attr( $id ); ?>"><input type="hidden" name="tip_version" value="<?php echo esc_attr( $record['version'] ); ?>"><input type="hidden" name="status" value="<?php echo esc_attr( $target ); ?>">
					<?php wp_nonce_field( 'cammino_tip_status_' . $id ); ?>
					<?php if ( 'discussion' === $target && in_array( $record['status'], array( 'approved', 'rejected' ), true ) ) : ?><p><label><input type="checkbox" name="confirm_reopen" value="yes" required> <?php esc_html_e( 'Potvrdzujem opätovné otvorenie a povolenie úprav tipsterovi.', 'cammino' ); ?></label></p><?php endif; ?>
					<?php submit_button( 'rejected' === $target ? __( 'Zamietnuť tip', 'cammino' ) : ( 'approved' === $target ? __( 'Schváliť tip', 'cammino' ) : ( in_array( $record['status'], array( 'approved', 'rejected' ), true ) ? __( 'Znovu otvoriť diskusiu', 'cammino' ) : __( 'Otvoriť diskusiu', 'cammino' ) ) ), 'rejected' === $target ? 'secondary' : 'primary', '', false ); ?>
				</form>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<?php cammino_tipsters_render_conversation( $id, true ); ?>
	<div class="cammino-admin-tips__panel"><h2><?php esc_html_e( 'História zmien', 'cammino' ); ?></h2>
		<p><?php echo esc_html( sprintf( __( 'Tip odoslaný: %s.', 'cammino' ), get_post_time( get_option( 'date_format' ) . ' H:i', false, $post, true ) ) ); ?></p>
		<ol><?php foreach ( array_reverse( $record['history'] ) as $event ) : $actor = get_userdata( $event['actor'] ); ?>
			<li><strong><?php echo esc_html( get_date_from_gmt( $event['time'], get_option( 'date_format' ) . ' H:i:s' ) . ' · ' . ( $actor ? $actor->display_name . ' (' . $actor->user_login . ')' : __( 'Odstránený účet', 'cammino' ) ) ); ?></strong>
				<?php if ( 'edited' === $event['type'] ) : ?>
					<p><?php esc_html_e( 'Tipster upravil formulár.', 'cammino' ); ?> <?php echo esc_html( implode( ', ', array_intersect_key( array( 'title' => __( 'Názov', 'cammino' ), 'short_description' => __( 'Krátky popis', 'cammino' ), 'long_description' => __( 'Podrobný popis', 'cammino' ), 'file_link' => __( 'Odkaz na súbory', 'cammino' ) ), array_flip( $event['fields'] ) ) ) ); ?></p>
					<?php if ( $event['added'] ) : ?><p><?php echo esc_html( __( 'Pridané prílohy: ', 'cammino' ) . implode( ', ', $event['added'] ) ); ?></p><?php endif; ?>
					<?php if ( $event['removed'] ) : ?><p><?php echo esc_html( __( 'Odstránené prílohy: ', 'cammino' ) . implode( ', ', $event['removed'] ) ); ?></p><?php endif; ?>
				<?php else : ?><p><?php echo esc_html( ( $labels[ $event['from'] ] ?? $event['from'] ) . ' → ' . ( $labels[ $event['to'] ] ?? $event['to'] ) ); ?></p><?php endif; ?>
			</li>
		<?php endforeach; ?></ol>
	</div>
	<?php
	cammino_tipsters_admin_tip_delete_form( $id, $record );
}

function cammino_tipsters_admin_tip_delete_form( int $id, array $record ): void {
	?>
	<details class="cammino-admin-tips__panel cammino-admin-tips__danger"><summary><?php esc_html_e( 'Natrvalo odstrániť tip', 'cammino' ); ?></summary>
		<p><?php esc_html_e( 'Odstráni sa tento tip, celá komunikácia, história a staršie nahrané prílohy. Operáciu nie je možné vrátiť. Účet tipstera, ostatné tipy a súbory v externom úložisku zostanú zachované.', 'cammino' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'cammino_admin_delete_tip_' . $id ); ?>
			<input type="hidden" name="action" value="cammino_admin_delete_tip"><input type="hidden" name="tip_id" value="<?php echo esc_attr( $id ); ?>"><input type="hidden" name="tip_version" value="<?php echo esc_attr( $record['version'] ); ?>">
			<label for="confirm-tip-title"><?php echo esc_html( sprintf( __( 'Zadajte presný názov: %s', 'cammino' ), $record['title'] ) ); ?></label>
			<input type="text" id="confirm-tip-title" name="confirm_title" required maxlength="200" autocomplete="off">
			<p><label><input type="checkbox" name="confirm_delete" value="yes" required> <?php esc_html_e( 'Potvrdzujem trvalé odstránenie tipu a celej komunikácie.', 'cammino' ); ?></label></p>
			<?php submit_button( __( 'Natrvalo odstrániť tip', 'cammino' ), 'delete', '', false ); ?>
		</form>
	</details>
	<?php
}

add_action( 'admin_post_cammino_admin_delete_tip', static function (): void {
	if ( ! cammino_tipsters_can_manage() || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { wp_die( esc_html__( 'Nemáte oprávnenie odstrániť tip.', 'cammino' ), '', array( 'response' => 403 ) ); }
	$id = absint( cammino_tipsters_input( 'tip_id' ) ); check_admin_referer( 'cammino_admin_delete_tip_' . $id );
	$result = cammino_tipsters_admin_delete_tip( $id, cammino_tipsters_input( 'tip_version' ), cammino_tipsters_input( 'confirm_title' ), 'yes' === cammino_tipsters_input( 'confirm_delete' ) );
	set_transient( 'cammino_tip_status_notice_' . get_current_user_id(), array( 'error' => is_wp_error( $result ), 'message' => is_wp_error( $result ) ? $result->get_error_message() : __( 'Tip a všetky jeho záznamy boli odstránené.', 'cammino' ) ), MINUTE_IN_SECONDS );
	wp_safe_redirect( cammino_tipsters_admin_tips_url( is_wp_error( $result ) && get_post( $id ) ? array( 'tip_id' => $id ) : array() ), 303 ); exit;
} );

add_action( 'admin_enqueue_scripts', static function (): void {
	if ( isset( $_GET['page'] ) && 'cammino-tips' === $_GET['page'] && cammino_tipsters_can_manage() ) {
		wp_enqueue_style( 'cammino-admin-tips', get_template_directory_uri() . '/tipsters/assets/admin-tips.css', array(), CAMMINO_TIPSTERS_VERSION );
	}
} );

add_action( 'admin_post_cammino_tip_message', static function (): void {
	if ( ! cammino_tipsters_can_manage() || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { wp_die( esc_html__( 'Nemáte oprávnenie odoslať správu.', 'cammino' ), '', array( 'response' => 403 ) ); }
	$id = absint( cammino_tipsters_input( 'tip_id' ) );
	check_admin_referer( 'cammino_send_message_' . $id );
	$result = cammino_tipsters_send_message( $id, cammino_tipsters_input( 'message_body' ), cammino_tipsters_input( 'message_token' ) );
	if ( ! is_wp_error( $result ) ) { wp_safe_redirect( cammino_tipsters_message_redirect_url( $id, true ), 303 ); exit; }
	if ( ! cammino_tipsters_can_read_tip( $id ) || ! cammino_tipsters_tip_ready( $id ) ) { wp_die( esc_html__( 'Tip nie je dostupný.', 'cammino' ), '', array( 'response' => 404 ) ); }
	nocache_headers(); header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0' );
	$tip = get_post( $id ); $owner = (int) $tip->post_author;
	$lock = cammino_tipsters_write_lock( $owner );
	if ( is_wp_error( $lock ) ) { wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 409, 'back_link' => true ) ); }
	try {
		clean_post_cache( $id ); wp_cache_delete( $owner, 'user_meta' );
		if ( ! get_post( $id ) || 'deleting' === get_user_meta( $owner, CAMMINO_TIPSTER_STATE_META, true ) ) { wp_die( esc_html__( 'Tip nie je dostupný.', 'cammino' ), '', array( 'response' => 404 ) ); }
		// Track temporary drafts so a complete account purge clears them, including cache-backed transients.
		$users = array_map( 'intval', get_post_meta( $id, '_cammino_message_draft_user' ) );
		if ( ! in_array( get_current_user_id(), $users, true ) && ! add_post_meta( $id, '_cammino_message_draft_user', get_current_user_id() ) ) {
			wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 500, 'back_link' => true ) );
		}
		set_transient( 'cammino_message_draft_' . get_current_user_id() . '_' . $id, array(
		'error' => $result->get_error_message(), 'body' => mb_substr( cammino_tipsters_input( 'message_body' ), 0, CAMMINO_MESSAGE_MAX_LENGTH, 'UTF-8' ),
		'token' => cammino_tipsters_input( 'message_token' ),
		), 5 * MINUTE_IN_SECONDS );
	} finally { cammino_tipsters_release_lock( $lock ); }
	wp_safe_redirect( cammino_tipsters_admin_tips_url( array( 'tip_id' => $id ) ) . '#conversation', 303 ); exit;
} );
