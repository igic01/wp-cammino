<?php
/** Dedicated administrator account screens. @package Cammino */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', static function (): void {
	if ( cammino_tipsters_can_manage() ) {
		add_menu_page( __( 'Tipsteri', 'cammino' ), __( 'Tipsteri', 'cammino' ), CAMMINO_TIPSTERS_CAP, 'cammino-tipsters', 'cammino_tipsters_admin_page', 'dashicons-groups', 58 );
		add_submenu_page( 'cammino-tipsters', __( 'Tipsteri', 'cammino' ), __( 'Všetci tipsteri', 'cammino' ), CAMMINO_TIPSTERS_CAP, 'cammino-tipsters', 'cammino_tipsters_admin_page' );
		add_submenu_page( 'cammino-tipsters', __( 'Pridať tipstera', 'cammino' ), __( 'Pridať tipstera', 'cammino' ), CAMMINO_TIPSTERS_CAP, 'cammino-tipsters-new', 'cammino_tipsters_admin_page' );
	}
} );

function cammino_tipsters_admin_url( array $args = array() ): string {
	$page = 'cammino-tipsters';
	if ( isset( $args['view'] ) && 'create' === $args['view'] ) {
		$page = 'cammino-tipsters-new';
		unset( $args['view'] );
	}
	return add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
}

function cammino_tipsters_admin_form_start( string $operation, int $id = 0 ): void {
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="cammino_tipster_account">
		<input type="hidden" name="operation" value="<?php echo esc_attr( $operation ); ?>">
		<input type="hidden" name="account_id" value="<?php echo esc_attr( (string) $id ); ?>">
		<?php wp_nonce_field( 'cammino_tipster_' . $operation . '_' . $id ); ?>
	<?php
}

add_action( 'admin_post_cammino_tipster_account', 'cammino_tipsters_admin_submit' );
function cammino_tipsters_admin_submit(): void {
	if ( ! cammino_tipsters_can_manage() || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		wp_die( esc_html__( 'Nemáte oprávnenie spravovať tipsterov.', 'cammino' ), '', array( 'response' => 403 ) );
	}
	$operation = cammino_tipsters_input( 'operation' );
	$id = absint( cammino_tipsters_input( 'account_id' ) );
	if ( ! in_array( $operation, array( 'create', 'update', 'disable', 'enable', 'delete' ), true ) ) {
		wp_die( esc_html__( 'Neplatná operácia.', 'cammino' ), '', array( 'response' => 400 ) );
	}
	check_admin_referer( 'cammino_tipster_' . $operation . '_' . $id );
	if ( 'create' !== $operation && ! cammino_tipsters_managed_account( $id ) ) {
		wp_die( esc_html__( 'Účet tipstera neexistuje alebo ho nemožno spravovať na tejto stránke.', 'cammino' ), '', array( 'response' => 403 ) );
	}
	$messages = array(
		'create' => __( 'Účet tipstera bol vytvorený. Prihlasovacie údaje odovzdajte tipsterovi.', 'cammino' ),
		'update' => __( 'Účet bol aktualizovaný.', 'cammino' ),
		'disable' => __( 'Účet bol deaktivovaný. Jeho záznamy zostali zachované.', 'cammino' ),
		'enable' => __( 'Účet bol aktivovaný.', 'cammino' ),
		'delete' => __( 'Účet aj všetky jeho súvisiace záznamy a súbory boli odstránené.', 'cammino' ),
	);
	if ( 'create' === $operation ) {
		$result = cammino_tipsters_create_account( cammino_tipsters_input( 'username' ), cammino_tipsters_input( 'password' ), cammino_tipsters_input( 'display_name' ) );
		if ( ! is_wp_error( $result ) ) {
			$id = (int) $result;
		}
	} elseif ( 'update' === $operation ) {
		$result = cammino_tipsters_update_account( $id, cammino_tipsters_input( 'display_name' ), cammino_tipsters_input( 'password' ) );
	} elseif ( 'delete' === $operation ) {
		$account = cammino_tipsters_managed_account( $id );
		$result = 'yes' === cammino_tipsters_input( 'confirm_delete' ) && hash_equals( $account->user_login, cammino_tipsters_input( 'confirm_username' ) )
			? cammino_tipsters_delete_account( $id )
			: new WP_Error( 'confirmation', __( 'Potvrďte odstránenie a zadajte presné používateľské meno.', 'cammino' ) );
	} else {
		$result = cammino_tipsters_set_enabled( $id, 'enable' === $operation );
	}
	$error = is_wp_error( $result );
	set_transient( 'cammino_tipster_notice_' . get_current_user_id(), array(
		'error' => $error, 'message' => $error ? $result->get_error_message() : $messages[ $operation ],
		// Never store the submitted password in notices, URLs, or logs.
		'username' => $error ? sanitize_text_field( cammino_tipsters_input( 'username' ) ) : '',
		'display_name' => $error ? sanitize_text_field( cammino_tipsters_input( 'display_name' ) ) : '',
	), MINUTE_IN_SECONDS );
	$args = array();
	if ( 'delete' !== $operation || $error ) {
		$args = $id ? array( 'account_id' => $id, 'view' => 'delete' === $operation ? 'delete' : 'edit' ) : array( 'view' => 'create' );
	}
	wp_safe_redirect( cammino_tipsters_admin_url( $args ) );
	exit;
}

function cammino_tipsters_admin_page(): void {
	if ( ! cammino_tipsters_can_manage() ) {
		wp_die( esc_html__( 'Nemáte oprávnenie spravovať tipsterov.', 'cammino' ), '', array( 'response' => 403 ) );
	}
	$notice = get_transient( 'cammino_tipster_notice_' . get_current_user_id() );
	delete_transient( 'cammino_tipster_notice_' . get_current_user_id() );
	$view = isset( $_GET['view'] ) && is_string( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';
	if ( isset( $_GET['page'] ) && 'cammino-tipsters-new' === $_GET['page'] ) {
		$view = 'create';
	}
	$id = isset( $_GET['account_id'] ) && is_scalar( $_GET['account_id'] ) ? absint( $_GET['account_id'] ) : 0;
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Tipsteri', 'cammino' ); ?> <a class="page-title-action" href="<?php echo esc_url( cammino_tipsters_admin_url( array( 'view' => 'create' ) ) ); ?>"><?php esc_html_e( 'Pridať tipstera', 'cammino' ); ?></a></h1>
		<?php if ( is_array( $notice ) ) : ?>
			<div class="notice <?php echo esc_attr( $notice['error'] ? 'notice-error' : 'notice-success' ); ?>"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
		<?php endif; ?>
		<?php
		if ( 'create' === $view ) {
			cammino_tipsters_admin_edit( false, is_array( $notice ) ? $notice : array() );
		} elseif ( $id ) {
			$account = cammino_tipsters_managed_account( $id );
			if ( ! $account ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Účet tipstera neexistuje alebo ho nemožno spravovať na tejto stránke.', 'cammino' ) . '</p></div>';
			} elseif ( 'delete' === $view ) {
				cammino_tipsters_admin_delete( $account );
			} else {
				cammino_tipsters_admin_edit( $account, is_array( $notice ) ? $notice : array() );
			}
		} else {
			cammino_tipsters_admin_list();
		}
		?>
	</div>
	<?php
}

function cammino_tipsters_admin_list(): void {
	$search = isset( $_GET['s'] ) && is_string( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$page = isset( $_GET['paged'] ) && is_scalar( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
	$args = array( 'role' => CAMMINO_TIPSTER_ROLE, 'role__not_in' => cammino_tipsters_other_roles(), 'number' => 20, 'paged' => $page, 'orderby' => 'registered', 'order' => 'DESC' );
	if ( '' !== $search ) {
		$args['search'] = '*' . $search . '*';
		$args['search_columns'] = array( 'user_login', 'display_name' );
	}
	$query = new WP_User_Query( $args );
	?>
	<p><?php esc_html_e( 'Spravujte prístup tipsterov. Deaktivácia zachová záznamy; odstránenie účtu ich natrvalo vymaže.', 'cammino' ); ?></p>
	<p><a href="<?php echo esc_url( cammino_tipsters_url( 'login' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Otvoriť prihlasovaciu stránku', 'cammino' ); ?></a></p>
	<form method="get">
		<input type="hidden" name="page" value="cammino-tipsters">
		<p class="search-box">
			<label class="screen-reader-text" for="tipster-search"><?php esc_html_e( 'Hľadať tipstera', 'cammino' ); ?></label>
			<input type="search" id="tipster-search" name="s" value="<?php echo esc_attr( $search ); ?>">
			<?php submit_button( __( 'Hľadať', 'cammino' ), 'secondary', '', false ); ?>
		</p>
	</form>
	<table class="wp-list-table widefat fixed striped">
		<thead><tr><th scope="col"><?php esc_html_e( 'Používateľské meno', 'cammino' ); ?></th><th scope="col"><?php esc_html_e( 'Zobrazované meno', 'cammino' ); ?></th><th scope="col"><?php esc_html_e( 'Stav účtu', 'cammino' ); ?></th><th scope="col"><?php esc_html_e( 'Vytvorený', 'cammino' ); ?></th></tr></thead>
		<tbody>
		<?php foreach ( $query->get_results() as $account ) : ?>
			<tr>
				<td><strong><a href="<?php echo esc_url( cammino_tipsters_admin_url( array( 'account_id' => $account->ID ) ) ); ?>"><?php echo esc_html( $account->user_login ); ?></a></strong></td>
				<td><?php echo esc_html( $account->display_name ); ?></td>
				<td><?php echo esc_html( cammino_tipsters_admin_state_label( (int) $account->ID ) ); ?></td>
				<td><?php echo esc_html( get_date_from_gmt( $account->user_registered, get_option( 'date_format' ) ) ); ?></td>
			</tr>
		<?php endforeach; ?>
		<?php if ( ! $query->get_results() ) : ?><tr><td colspan="4"><?php esc_html_e( 'Nenašli sa žiadni tipsteri.', 'cammino' ); ?></td></tr><?php endif; ?>
		</tbody>
	</table>
	<?php
	$links = paginate_links( array(
		'base' => add_query_arg( 'paged', '%#%', cammino_tipsters_admin_url( array( 's' => $search ) ) ),
		'format' => '', 'current' => $page, 'total' => (int) ceil( $query->get_total() / 20 ),
	) );
	if ( $links ) {
		echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( $links ) . '</div></div>';
	}
}

function cammino_tipsters_admin_state_label( int $id ): string {
	if ( 'deleting' === get_user_meta( $id, CAMMINO_TIPSTER_STATE_META, true ) ) {
		return __( 'Mazanie nedokončené', 'cammino' );
	}
	return cammino_tipsters_account_enabled( $id ) ? __( 'Aktívny', 'cammino' ) : __( 'Deaktivovaný', 'cammino' );
}

function cammino_tipsters_admin_edit( $account, array $notice ): void {
	$id = $account ? (int) $account->ID : 0;
	?>
	<p><a href="<?php echo esc_url( cammino_tipsters_admin_url() ); ?>"><?php esc_html_e( 'Späť na zoznam', 'cammino' ); ?></a></p>
	<h2><?php echo esc_html( $account ? __( 'Upraviť účet tipstera', 'cammino' ) : __( 'Nový účet tipstera', 'cammino' ) ); ?></h2>
	<?php cammino_tipsters_admin_form_start( $account ? 'update' : 'create', $id ); ?>
		<table class="form-table" role="presentation">
			<tr><th scope="row"><label for="tipster-username"><?php esc_html_e( 'Používateľské meno', 'cammino' ); ?></label></th><td><input class="regular-text" id="tipster-username" name="username" maxlength="60" autocomplete="off" required <?php echo $account ? 'readonly' : ''; ?> value="<?php echo esc_attr( $account ? $account->user_login : ( $notice['username'] ?? '' ) ); ?>"><p class="description"><?php esc_html_e( 'Prihlasovacie meno je po vytvorení nemenné.', 'cammino' ); ?></p></td></tr>
			<tr><th scope="row"><label for="tipster-name"><?php esc_html_e( 'Zobrazované meno', 'cammino' ); ?></label></th><td><input class="regular-text" id="tipster-name" name="display_name" maxlength="250" <?php echo $account ? 'required' : ''; ?> value="<?php echo esc_attr( $account ? $account->display_name : ( $notice['display_name'] ?? '' ) ); ?>"><p class="description"><?php esc_html_e( 'Pri novom účte môže zostať prázdne; použije sa používateľské meno.', 'cammino' ); ?></p></td></tr>
			<tr><th scope="row"><label for="tipster-password"><?php echo esc_html( $account ? __( 'Nové heslo', 'cammino' ) : __( 'Heslo', 'cammino' ) ); ?></label></th><td><input type="password" class="regular-text" id="tipster-password" name="password" minlength="8" maxlength="4096" autocomplete="new-password" <?php echo $account ? '' : 'required'; ?>><p class="description"><?php esc_html_e( 'Aspoň 8 znakov. Heslo odovzdajte tipsterovi; sám si ho nemôže zmeniť. Zmena hesla ukončí jeho existujúce prihlásenia.', 'cammino' ); ?> <?php if ( $account ) { esc_html_e( 'Prázdne pole zachová aktuálne heslo.', 'cammino' ); } ?></p></td></tr>
		</table>
		<?php submit_button( $account ? __( 'Uložiť zmeny', 'cammino' ) : __( 'Vytvoriť účet', 'cammino' ) ); ?>
	</form>
	<?php if ( $account ) : ?>
		<p><a class="button" href="<?php echo esc_url( cammino_tipsters_admin_tips_url( array( 'owner' => $id ) ) ); ?>"><?php esc_html_e( 'Zobraziť tipy tohto tipstera', 'cammino' ); ?></a></p>
		<h2><?php esc_html_e( 'Prístup k účtu', 'cammino' ); ?></h2>
		<p><?php echo esc_html( cammino_tipsters_admin_state_label( $id ) ); ?></p>
		<?php if ( 'deleting' !== get_user_meta( $id, CAMMINO_TIPSTER_STATE_META, true ) ) : ?>
			<?php cammino_tipsters_admin_form_start( cammino_tipsters_account_enabled( $id ) ? 'disable' : 'enable', $id ); ?>
				<?php submit_button( cammino_tipsters_account_enabled( $id ) ? __( 'Deaktivovať účet', 'cammino' ) : __( 'Aktivovať účet', 'cammino' ), 'secondary' ); ?>
			</form>
		<?php endif; ?>
		<p><a class="button" href="<?php echo esc_url( cammino_tipsters_admin_url( array( 'account_id' => $id, 'view' => 'delete' ) ) ); ?>"><?php esc_html_e( 'Natrvalo odstrániť účet…', 'cammino' ); ?></a></p>
	<?php endif; ?>
	<?php
}

function cammino_tipsters_admin_delete( WP_User $account ): void {
	$records = cammino_tipsters_account_records( (int) $account->ID );
	?>
	<h2><?php esc_html_e( 'Natrvalo odstrániť účet', 'cammino' ); ?></h2>
	<div class="notice notice-warning inline"><p><?php esc_html_e( 'Odstránenie je nezvratné. Vymaže účet aj všetky jeho tipy, súbory, správy a súvisiacu históriu vrátane tipov označených „Deleted by tipster“. Deaktivácia účtu zachová všetky záznamy.', 'cammino' ); ?></p></div>
	<p><?php echo esc_html( sprintf( __( 'Účet: %1$s. Tipy: %2$d. Súbory: %3$d. Správy: %4$d.', 'cammino' ), $account->user_login, count( $records['tips'] ), count( $records['files'] ), count( $records['messages'] ) ) ); ?></p>
	<?php cammino_tipsters_admin_form_start( 'delete', (int) $account->ID ); ?>
		<p><label for="confirm-tipster"><?php esc_html_e( 'Pre potvrdenie zadajte používateľské meno:', 'cammino' ); ?></label> <strong><?php echo esc_html( $account->user_login ); ?></strong></p>
		<p><input type="text" class="regular-text" id="confirm-tipster" name="confirm_username" autocomplete="off" required></p>
		<p><label><input type="checkbox" name="confirm_delete" value="yes" required> <?php esc_html_e( 'Rozumiem, že účet aj všetky jeho záznamy budú natrvalo odstránené.', 'cammino' ); ?></label></p>
		<?php submit_button( __( 'Odstrániť účet a všetky záznamy', 'cammino' ), 'delete' ); ?>
	</form>
	<p><a href="<?php echo esc_url( cammino_tipsters_admin_url( array( 'account_id' => $account->ID ) ) ); ?>"><?php esc_html_e( 'Zrušiť a vrátiť sa na účet', 'cammino' ); ?></a></p>
	<?php
}
