<?php
/**
 * CartFlows Admin.
 *
 * @package CartFlows
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Class Cartflows_Admin.
 */
class Cartflows_Admin {

	/**
	 * Instance
	 *
	 * @access private
	 * @var object Class object.
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Initiator
	 *
	 * @since 1.0.0
	 * @return object initialized object of class.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->init_hooks();
	}

	/**
	 * Init Hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init_hooks() {

		if ( ! is_admin() ) {
			return;
		}

		/* Add lite version class to body */
		add_action( 'admin_body_class', array( $this, 'add_admin_body_class' ) );

		add_filter( 'plugin_action_links_' . CARTFLOWS_BASE, array( $this, 'add_action_links' ) );

		add_action( 'in_admin_header', array( $this, 'embed_page_header' ) );

		add_action( 'admin_init', array( $this, 'flush_rules_after_save_permalinks' ) );

		add_filter( 'post_row_actions', array( $this, 'remove_flow_actions' ), 99, 2 );
	}

	/**
	 * Add the clone link to action list for flows row actions
	 *
	 * @param array  $actions Actions array.
	 * @param object $post Post object.
	 *
	 * @return array
	 */
	public function remove_flow_actions( $actions, $post ) {

		if ( current_user_can( 'edit_posts' ) && isset( $post ) && CARTFLOWS_FLOW_POST_TYPE === $post->post_type ) {

			if ( isset( $actions['duplicate'] ) ) { // Duplicate page plugin remove.
				unset( $actions['duplicate'] );
			}

			if ( isset( $actions['edit_as_new_draft'] ) ) { // Duplicate post plugin remove.
				unset( $actions['edit_as_new_draft'] );
			}
		}

		return $actions;
	}

	/**
	 *  After save of permalinks.
	 */
	public static function flush_rules_after_save_permalinks() {

		$has_saved_permalinks = get_option( 'cartflows_permalink_refresh' );
		if ( $has_saved_permalinks ) {
			flush_rewrite_rules();
			delete_option( 'cartflows_permalink_refresh' );
		}
	}

	/**
	 * Show action on plugin page.
	 *
	 * @param  array $links links.
	 * @return array
	 */
	public function add_action_links( $links ) {

		$default_url = add_query_arg(
			array(
				'page' => CARTFLOWS_SLUG,
				'path' => 'settings',
			),
			admin_url()
		);

		$mylinks = array(
			'<a href="' . $default_url . '">' . __( 'Settings', 'cartflows' ) . '</a>',
			'<a target="_blank" href="' . esc_url( 'https://cartflows.com/docs' ) . '">' . __( 'Docs', 'cartflows' ) . '</a>',
		);

		if ( ! _is_cartflows_pro() ) {
			array_push( $mylinks, '<a style="color: #39b54a; font-weight: 700;" target="_blank" href="' . esc_url( 'https://cartflows.com/pricing/' ) . '"> Go Pro </a>' );
		}

		return array_merge( $links, $mylinks );
	}

	/**
	 * Check is flow admin.
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function is_flow_edit_admin() {

		$current_screen = get_current_screen();

		if (
			is_object( $current_screen ) &&
			isset( $current_screen->post_type ) &&
			( CARTFLOWS_FLOW_POST_TYPE === $current_screen->post_type ) &&
			isset( $current_screen->base ) &&
			( 'post' === $current_screen->base )
		) {
			return true;
		}
		return false;
	}

	/**
	 * Admin body classes.
	 *
	 * Body classes to be added to <body> tag in admin page
	 *
	 * @param String $classes body classes returned from the filter.
	 * @return String body classes to be added to <body> tag in admin page
	 */
	public function add_admin_body_class( $classes ) {

		$classes .= ' cartflows-' . CARTFLOWS_VER;

		if ( isset( $_GET['action'] ) && in_array( sanitize_text_field( wp_unslash( $_GET['action'] ) ), array( 'wcf-log', 'wcf-license' ) ) ) { //phpcs:ignore
			$classes .= ' wcf-debug-page ';
		}

		return $classes;
	}

	/**
	 * Set up a div for the header embed to render into.
	 * The initial contents here are meant as a place loader for when the PHP page initialy loads.
	 */
	public function embed_page_header() {

		if ( ! $this->show_embed_header() ) {
			return;
		}

		wp_enqueue_style( 'cartflows-admin-embed-header', CARTFLOWS_URL . 'admin/assets/css/admin-embed-header.css', array(), CARTFLOWS_VER );

		include_once CARTFLOWS_DIR . 'includes/admin/cartflows-admin-header.php';
	}

	/**
	 * Show embed header.
	 *
	 * @since 1.0.0
	 */
	public function show_embed_header() {

		$current_screen = get_current_screen();

		if (
			is_object( $current_screen ) &&
			isset( $current_screen->post_type ) &&
			( CARTFLOWS_FLOW_POST_TYPE === $current_screen->post_type ) &&
			isset( $current_screen->base ) &&
			( 'post' === $current_screen->base || 'edit' === $current_screen->base )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Check allowed screen for notices.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function allowed_screen_for_notices() {

		$screen          = get_current_screen();
		$screen_id       = $screen ? $screen->id : '';
		$allowed_screens = array(
			'cartflows_page_cartflows_settings',
			'edit-cartflows_flow',
			'dashboard',
			'plugins',
		);

		if ( in_array( $screen_id, $allowed_screens, true ) ) {
			return true;
		}

		return false;
	}
}

Cartflows_Admin::get_instance();
