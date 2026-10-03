<?php
/** Keep tipster account administration in its dedicated area. @package Cammino */
defined( 'ABSPATH' ) || exit;

/** Other registered roles distinguish protected mixed-role users from tipsters. */
function cammino_tipsters_other_roles(): array {
	return array_values( array_diff( array_keys( wp_roles()->roles ), array( CAMMINO_TIPSTER_ROLE ) ) );
}

/** Filter only WordPress's native Users table, not authentication/user lookups. */
add_filter( 'users_list_table_query_args', 'cammino_tipsters_regular_users_args' );
function cammino_tipsters_regular_users_args( array $args ): array {
	if ( is_network_admin() ) {
		return $args;
	}
	global $wpdb;
	$key = $wpdb->get_blog_prefix( $args['blog_id'] ?? get_current_blog_id() ) . 'capabilities';
	$regular = array(
		'relation' => 'OR',
		array( 'key' => $key, 'value' => '"' . CAMMINO_TIPSTER_ROLE . '"', 'compare' => 'NOT LIKE' ),
	);
	// Mixed-role accounts remain in Users so their other roles can be repaired.
	foreach ( cammino_tipsters_other_roles() as $role ) {
		$regular[] = array( 'key' => $key, 'value' => '"' . $role . '"', 'compare' => 'LIKE' );
	}
	$meta = array( 'relation' => 'AND', $regular );
	if ( ! empty( $args['meta_query'] ) ) {
		$meta[] = $args['meta_query'];
	}
	$args['meta_query'] = $meta;
	return $args;
}

add_filter( 'views_users', static function ( array $views ): array {
	unset( $views[ CAMMINO_TIPSTER_ROLE ] );
	// Keep the native All count consistent with the filtered table/pagination.
	if ( isset( $views['all'] ) && false !== strpos( $views['all'], '<span class="count">' ) ) {
		$query = new WP_User_Query( cammino_tipsters_regular_users_args( array( 'number' => 1, 'fields' => 'ID', 'count_total' => true ) ) );
		$views['all'] = preg_replace( '~(<span class="count">)\([^<]*\)(</span>)~', '${1}(' . number_format_i18n( $query->get_total() ) . ')${2}', $views['all'] );
	}
	return $views;
} );

/** Native user creation and bulk role changes cannot assign the tipster role. */
add_filter( 'editable_roles', static function ( array $roles ): array {
	if ( ! is_network_admin() && in_array( $GLOBALS['pagenow'] ?? '', array( 'users.php', 'user-new.php', 'user-edit.php' ), true ) ) {
		unset( $roles[ CAMMINO_TIPSTER_ROLE ] );
	}
	return $roles;
} );

add_filter( 'option_default_role', static function ( $role ) {
	return CAMMINO_TIPSTER_ROLE === $role && 'user-new.php' === ( $GLOBALS['pagenow'] ?? '' ) && ! is_network_admin() ? 'subscriber' : $role;
} );

/** Return a safe destination without applying any native user mutation. */
function cammino_tipsters_native_admin_destination( string $page, array $request ): string {
	if ( ! cammino_tipsters_can_manage() || is_network_admin() ) {
		return '';
	}
	if ( 'user-edit.php' === $page ) {
		$id = isset( $request['user_id'] ) && is_scalar( $request['user_id'] ) ? absint( $request['user_id'] ) : 0;
		return cammino_tipsters_managed_account( $id ) ? cammino_tipsters_admin_url( array( 'account_id' => $id ) ) : '';
	}
	if ( 'users.php' !== $page ) {
		return '';
	}
	if ( isset( $request['role'] ) && CAMMINO_TIPSTER_ROLE === $request['role'] ) {
		return cammino_tipsters_admin_url();
	}
	$action = '';
	foreach ( array( 'action', 'action2' ) as $key ) {
		if ( ! empty( $request[ $key ] ) && is_string( $request[ $key ] ) && '-1' !== $request[ $key ] ) {
			$action = $request[ $key ];
			break;
		}
	}
	$ids = array_merge( (array) ( $request['users'] ?? array() ), (array) ( $request['user'] ?? array() ) );
	foreach ( $ids as $id ) {
		if ( is_scalar( $id ) && cammino_tipsters_managed_account( absint( $id ) ) ) {
			return cammino_tipsters_admin_url( array( 'account_id' => absint( $id ), 'view' => in_array( $action, array( 'delete', 'dodelete' ), true ) ? 'delete' : 'edit' ) );
		}
	}
	return '';
}

// admin_init precedes native user edit, delete, promotion, and reset handlers.
add_action( 'admin_init', static function (): void {
	$destination = cammino_tipsters_native_admin_destination( $GLOBALS['pagenow'] ?? '', $_REQUEST );
	if ( $destination ) {
		wp_safe_redirect( $destination );
		exit;
	}
}, 1 );
