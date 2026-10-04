<?php
/** Private tipster account module. @package Cammino */
defined( 'ABSPATH' ) || exit;

const CAMMINO_TIPSTERS_VERSION = '1.3.1';
const CAMMINO_TIPSTER_ROLE = 'cammino_tipster';
const CAMMINO_TIPSTERS_CAP = 'manage_cammino_tipsters';
const CAMMINO_TIP_POST_TYPE = 'cammino_tip';
const CAMMINO_MESSAGE_POST_TYPE = 'cammino_tip_message';
const CAMMINO_TIPSTER_STATE_META = '_cammino_tipster_state';
const CAMMINO_TIP_FILES_META = '_cammino_tip_files';
const CAMMINO_MESSAGE_TIP_META = '_cammino_tip_id';
const CAMMINO_TIP_WORKFLOW_META = '_cammino_tip_workflow';
const CAMMINO_TIP_PENDING_FILES_META = '_cammino_tip_pending_files';

require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/uploads.php';
require_once __DIR__ . '/tips.php';
require_once __DIR__ . '/workflow.php';
require_once __DIR__ . '/messages.php';
require_once __DIR__ . '/accounts.php';
require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/admin-tips.php';
require_once __DIR__ . '/admin-navigation.php';
require_once __DIR__ . '/frontend.php';

add_action( 'init', 'cammino_tipsters_initialize', 5 );
add_action( 'init', 'cammino_tipsters_register_models', 6 );

/** Runs on upgrades too, without replacing existing users or role definitions. */
function cammino_tipsters_initialize(): void {
	if ( CAMMINO_TIPSTERS_VERSION === get_option( 'cammino_tipsters_version' ) ) {
		return;
	}
	if ( ! get_role( CAMMINO_TIPSTER_ROLE ) ) {
		add_role( CAMMINO_TIPSTER_ROLE, __( 'Tipster', 'cammino' ), array( 'read' => true ) );
	}
	$administrator = get_role( 'administrator' );
	if ( $administrator ) {
		$administrator->add_cap( CAMMINO_TIPSTERS_CAP );
	}
	update_option( 'cammino_tipsters_version', CAMMINO_TIPSTERS_VERSION, false );
}

/** Business records have no native public routes, REST access, or editor UI. */
function cammino_tipsters_register_models(): void {
	$capabilities = array();
	foreach ( array( 'edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_private_posts', 'delete_published_posts', 'delete_others_posts', 'edit_private_posts', 'edit_published_posts', 'create_posts' ) as $cap ) {
		$capabilities[ $cap ] = CAMMINO_TIPSTERS_CAP;
	}
	// Custom handlers use the management/ownership helpers, never native editors.
	foreach ( array( 'edit_post', 'delete_post', 'create_posts' ) as $cap ) {
		$capabilities[ $cap ] = 'do_not_allow';
	}
	foreach ( array( CAMMINO_TIP_POST_TYPE, CAMMINO_MESSAGE_POST_TYPE ) as $type ) {
		register_post_type( $type, array(
			'public' => false, 'publicly_queryable' => false, 'show_ui' => false,
			'show_in_rest' => false, 'show_in_nav_menus' => false, 'show_in_admin_bar' => false,
			'exclude_from_search' => true, 'rewrite' => false, 'query_var' => false,
			'has_archive' => false, 'supports' => array(), 'can_export' => false,
			'capabilities' => $capabilities, 'map_meta_cap' => false,
			// Cleanup is explicit: messages include admin-authored replies.
			'delete_with_user' => false,
		) );
	}
}
