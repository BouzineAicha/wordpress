<?php
/**
 * Checkout markup.
 *
 * @package CartFlows
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Checkout Markup
 *
 * @since 1.0.0
 */
class Cartflows_Checkout_Markup {


	/**
	 * Member Variable
	 *
	 * @var object instance
	 */
	private static $instance;

	/**
	 *  Initiator
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 *  Constructor
	 */
	public function __construct() {

		/* Set is checkout flag */
		add_filter( 'woocommerce_is_checkout', array( $this, 'woo_checkout_flag' ), 9999 );

		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_checkout_fields' ), 10, 2 );

		/* Show notice if cart is empty */
		add_action( 'cartflows_checkout_cart_empty', array( $this, 'display_woo_notices' ) );

		/* Checkout Shortcode */
		add_shortcode( 'cartflows_checkout', array( $this, 'checkout_shortcode_markup' ) );

		/* Preconfigured cart data */
		add_action( 'wp', array( $this, 'preconfigured_cart_data' ), 1 );

		/* Embed Checkout */
		add_action( 'wp', array( $this, 'shortcode_load_data' ), 999 );

		/* Ajax Endpoint */
		add_filter( 'woocommerce_ajax_get_endpoint', array( $this, 'get_ajax_endpoint' ), 10, 2 );

		add_filter( 'cartflows_add_before_main_section', array( $this, 'enable_logo_in_header' ) );

		add_filter( 'cartflows_primary_container_bottom', array( $this, 'show_cartflows_copyright_message' ) );

		add_filter( 'woocommerce_login_redirect', array( $this, 'after_login_redirect' ), 9999, 2 );

		add_action( 'wp_ajax_wcf_woo_apply_coupon', array( $this, 'apply_coupon' ) );
		add_action( 'wp_ajax_nopriv_wcf_woo_apply_coupon', array( $this, 'apply_coupon' ) );

		add_filter( 'global_cartflows_js_localize', array( $this, 'add_localize_vars' ) );

		add_action( 'wp_ajax_wcf_woo_remove_coupon', array( $this, 'remove_coupon' ) );
		add_action( 'wp_ajax_nopriv_wcf_woo_remove_coupon', array( $this, 'remove_coupon' ) );

		add_action( 'wp_ajax_wcf_woo_remove_cart_product', array( $this, 'wcf_woo_remove_cart_product' ) );
		add_action( 'wp_ajax_nopriv_wcf_woo_remove_cart_product', array( $this, 'wcf_woo_remove_cart_product' ) );

		add_action( 'wp_ajax_nopriv_wcf_check_email_exists', array( $this, 'check_email_exists' ) );

		add_action( 'wp_ajax_nopriv_wcf_woocommerce_login', array( $this, 'woocommerce_user_login' ) );

		add_filter( 'woocommerce_paypal_args', array( $this, 'modify_paypal_args' ), 10, 2 );

		add_filter( 'woocommerce_paypal_express_checkout_payment_button_data', array( $this, 'change_return_cancel_url' ), 10, 2 );

		add_filter( 'woocommerce_cart_item_name', array( $this, 'wcf_add_remove_label_and_product_image' ), 10, 3 );

		add_action( 'woocommerce_before_calculate_totals', array( $this, 'custom_price_to_cart_item' ), 9999 );

		add_action( 'init', array( $this, 'update_woo_actions_ajax' ), 10 );

		// In case of multiple checkout open at same time we are restoring the cart of specific checkout.
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'restore_cart_data' ) );

		// Load Modern Layout Checkout Customization Actions.
		add_action( 'cartflows_checkout_form_before', array( $this, 'modern_checkout_layout_actions' ), 10, 1 );

		// Change the shipping error messages text and UI.
		add_filter( 'woocommerce_shipping_may_be_available_html', array( $this, 'change_shipping_message_html' ) );
		add_filter( 'woocommerce_no_shipping_available_html', array( $this, 'change_shipping_message_html' ) );

		// Update the cart total price to display on button and on the mobile order view section.
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_updated_cart_price' ), 11, 1 );

		$this->gutenberg_editor_compatibility();

		if ( class_exists( '\Elementor\Plugin' ) ) {
			// Load the widgets.
			$this->elementor_editor_compatibility();
		}

		if ( class_exists( 'FLBuilder' ) ) {
			$this->bb_editor_compatibility();
		}

		// Load Google Auto fill address fields actions.
		add_action( 'cartflows_checkout_scripts', array( $this, 'load_google_places_library' ) );
	}

	/**
	 * Enqueue Google Maps API js.
	 */
	public function load_google_places_library() {

		$auto_fields_settings = Cartflows_Helper::get_admin_settings_option( '_cartflows_google_auto_address', false, true );

		if ( empty( $auto_fields_settings['google_map_api_key'] ) ) {
			return;
		}

		global $post;

		$checkout_id = $post->ID;

		$is_autoaddress_enable = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-google-autoaddress' );

		if ( 'yes' === $is_autoaddress_enable ) {
			wp_enqueue_script(
				'wcf-google-places-api',
				'https://maps.googleapis.com/maps/api/js?key=' . $auto_fields_settings['google_map_api_key'] . '&libraries=places',
				array( 'wcf-checkout-template' ),
				CARTFLOWS_VER,
				true
			);

			wp_enqueue_script(
				'wcf-google-places',
				wcf()->utils->get_js_url( 'google-auto-fields' ),
				array( 'wcf-google-places-api' ),
				CARTFLOWS_VER,
				true
			);
		}
	}

	/**
	 * Restore the cart data on the checkout page.
	 */
	public function restore_cart_data() {

		global $post;

		$active_checkout = isset( $_COOKIE[ CARTFLOWS_ACTIVE_CHECKOUT ] ) ? intval( $_COOKIE[ CARTFLOWS_ACTIVE_CHECKOUT ] ) : false;

		if ( $post && $post->ID && $active_checkout ) {

			$checkout_id = $post->ID;

			if ( $checkout_id !== $active_checkout ) {

				$user_key = WC()->session->get_customer_id();

				$cart_data = get_transient( 'wcf_user_' . $user_key . '_checkout_' . $checkout_id );

				if ( $cart_data ) {
					WC()->cart->empty_cart();

					foreach ( $cart_data as $key => $item ) {
						WC()->cart->add_to_cart( $item['product_id'], $item['quantity'], $item['variation_id'], $item['variation'], $item );
					}

					$expiration_time = 30;
					// Need to update the active checkout id.
					setcookie( CARTFLOWS_ACTIVE_CHECKOUT, $checkout_id, time() + $expiration_time * MINUTE_IN_SECONDS, '/', COOKIE_DOMAIN, CARTFLOWS_HTTPS );

					// Prepare the cart data with cart item key. Need to update in product options.
					add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'prepare_required_cart_data' ), 10, 1 );

				}
			}
		}
	}

	/**
	 * Prepare the cart data on the checkout page.
	 *
	 * @param array $fragments woo ajax fragments.
	 */
	public function prepare_required_cart_data( $fragments ) {

		$cart = WC()->cart->get_cart();

		$cart_data = array();

		foreach ( $cart as $key => $data ) {
			$unique_id               = isset( $data['wcf_product_data'] ) && isset( $data['wcf_product_data']['unique_id'] ) ? $data['wcf_product_data']['unique_id'] : '';
			$cart_data[ $unique_id ] = $key;
		}

		$fragments['wcf_cart_data'] = $cart_data;

		return $fragments;
	}

	/**
	 * Remove login and registration actions.
	 */
	public function update_woo_actions_ajax() {
		add_action( 'cartflows_woo_checkout_update_order_review', array( $this, 'after_the_order_review_ajax_call' ) );

		if ( _is_wcf_doing_checkout_ajax() ) {
			add_filter( 'woocommerce_order_button_text', array( $this, 'place_order_button_text' ), 99, 1 );
		}
	}

	/**
	 * Call the actions after order review ajax call.
	 *
	 * @param string $post_data post data woo.
	 */
	public function after_the_order_review_ajax_call( $post_data ) {
		if ( isset( $post_data['_wcf_checkout_id'] ) ) {
			add_filter( 'woocommerce_order_button_text', array( $this, 'place_order_button_text' ), 99, 1 );
		}
	}

	/**
	 * Modify WooCommerce paypal arguments.
	 *
	 * @param array    $args argumenets for payment.
	 * @param WC_Order $order order data.
	 * @return array
	 */
	public function modify_paypal_args( $args, $order ) {
		$checkout_id = wcf()->utils->get_checkout_id_from_post_data();

		if ( ! $checkout_id ) {
			return $args;
		}

		// Set cancel return URL.
		$args['cancel_return'] = esc_url_raw( $order->get_cancel_order_url_raw( get_permalink( $checkout_id ) ) );

		return $args;
	}

	/**
	 * Elementor editor compatibility.
	 */
	public function elementor_editor_compatibility() {
		/* Add data */

		add_action(
			'cartflows_elementor_editor_compatibility',
			function ( $post_id, $elementor_ajax ) {

				add_action( 'cartflows_elementor_before_checkout_shortcode', array( $this, 'before_checkout_shortcode_actions' ) );
			},
			10,
			2
		);
	}

	/**
	 * Gutenburg editor compatibility.
	 */
	public function gutenberg_editor_compatibility() {
		/* Add data */

		add_action(
			'cartflows_gutenberg_editor_compatibility',
			function ( $post_id ) {

				add_action( 'cartflows_gutenberg_before_checkout_shortcode', array( $this, 'before_checkout_shortcode_actions' ) );
			},
			10,
			2
		);
	}

	/**
	 * Function for bb editor compatibility.
	 */
	public function bb_editor_compatibility() {
		/* Add data. */
		add_action(
			'cartflows_bb_editor_compatibility',
			function ( $post_id ) {
				add_action( 'cartflows_bb_before_checkout_shortcode', array( $this, 'before_checkout_shortcode_actions' ) );
			},
			10,
			1
		);
	}

	/**
	 * Change PayPal Express cancel URL.
	 *
	 * @param array  $data button data.
	 * @param string $page current page.
	 * @return array $data modified button data with new cancel url.
	 */
	public function change_return_cancel_url( $data, $page ) {
		global $post;

		if ( _is_wcf_checkout_type() ) {

			$checkout_id = $post->ID;

			if ( $checkout_id ) {

				// Change the default Cart URL with the CartFlows Checkout page.
				$data['cancel_url'] = esc_url_raw( get_permalink( $checkout_id ) );
			}
		}

		// Returing the modified data.
		return $data;
	}

	/**
	 * Modify WooCommerce paypal arguments.
	 *
	 * @param string $product_name product name.
	 * @param object $cart_item cart item.
	 * @param string $cart_item_key cart item key.
	 * @return string
	 */
	public function wcf_add_remove_label_and_product_image( $product_name, $cart_item, $cart_item_key ) {

		$checkout_id = _get_wcf_checkout_id();

		if ( ! $checkout_id ) {
			$checkout_id = isset( $_GET['wcf_checkout_id'] ) && ! empty( $_GET['wcf_checkout_id'] ) ? intval( wp_unslash( $_GET['wcf_checkout_id'] ) ) : 0; //phpcs:ignore
		}

		if ( ! empty( $checkout_id ) ) {

			$is_remove_product_option = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-remove-product-field' );
			$show_product_image       = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-order-review-show-product-images' );

			$remove_label = '';
			$image        = '';

			if ( 'yes' === $is_remove_product_option ) {
				$remove_label = apply_filters(
					'woocommerce_cart_item_remove_link',
					sprintf(
						'<a href="#" rel="nofollow" class="wcf-remove-product cartflows-icon cartflows-circle-cross" data-id="%s" data-item-key="%s"></a>',
						esc_attr( $cart_item['product_id'] ),
						$cart_item_key
					),
					$cart_item_key
				);
			}

			if ( 'yes' === $show_product_image ) {

				// Get product object.
				$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

				// Get product thumbnail.
				$thumbnail = $_product->get_image();

				// Add wrapper to image and add some css.
				$image = '<div class="wcf-product-thumbnail">' . $thumbnail . $remove_label . ' </div>';
			} else {
				/**
				 * If no product image is enabled but remove_label is enabled
				 * then add the remove label outside image's div else blank will be added.
				*/
				$image = $remove_label;
			}

			$product_name = '<div class="wcf-product-image"> ' . $image . ' <div class="wcf-product-name">' . $product_name . '</div></div>';
		}

		return $product_name;
	}

	/**
	 * Change order button text .
	 *
	 * @param string $button_text place order.
	 * @return string
	 */
	public function place_order_button_text( $button_text ) {
		$checkout_id = get_the_ID();

		if ( ! $checkout_id && isset( Cartflows_Woo_Hooks::$ajax_data['_wcf_checkout_id'] ) ) {

			$checkout_id = intval( Cartflows_Woo_Hooks::$ajax_data['_wcf_checkout_id'] );
		}

		if ( ! $checkout_id && isset( $_GET['wcf_checkout_id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$checkout_id = intval( $_GET['wcf_checkout_id'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( $checkout_id ) {

			$wcf_order_button_text = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-checkout-place-order-button-text' );

			if ( ! empty( $wcf_order_button_text ) ) {
				$button_text = $wcf_order_button_text;
			}

			if ( 'yes' === wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-checkout-place-order-button-price-display' ) ) {
				$button_text .= '&nbsp;&nbsp;' . wp_strip_all_tags( WC()->cart->get_total() );
			}
		}

		return $button_text;
	}

	/**
	 * Display all WooCommerce notices.
	 *
	 * @since 1.1.5
	 */
	public function display_woo_notices() {
		if ( null != WC()->session && function_exists( 'woocommerce_output_all_notices' ) ) {
			woocommerce_output_all_notices();
		}
	}

	/**
	 * Check for checkout flag
	 *
	 * @param bool $is_checkout is checkout.
	 *
	 * @return bool
	 */
	public function woo_checkout_flag( $is_checkout ) {
		if ( ! is_admin() ) {

			if ( _is_wcf_checkout_type() ) {

				$is_checkout = true;
			}
		}

		return $is_checkout;
	}

	/**
	 * Render checkout shortcode markup.
	 *
	 * @param array $atts attributes.
	 * @return string
	 */
	public function checkout_shortcode_markup( $atts ) {
		if ( ! function_exists( 'wc_print_notices' ) ) {
			$notice_out  = '<p class="woocommerce-notice">' . __( 'WooCommerce functions do not exist. If you are in an IFrame, please reload it.', 'cartflows' ) . '</p>';
			$notice_out .= '<button onClick="location.reload()">' . __( 'Click Here to Reload', 'cartflows' ) . '</button>';

			return $notice_out;
		}

		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts
		);

		$checkout_id = intval( $atts['id'] );

		$show_checkout_demo = false;

		if ( is_admin() ) {

			$show_checkout_demo = apply_filters( 'cartflows_show_demo_checkout', false );

			if ($show_checkout_demo && 0 === $checkout_id && isset($_POST['id'])) { //phpcs:ignore
				$checkout_id = intval($_POST['id']); //phpcs:ignore
			}
		}

		if ( empty( $checkout_id ) ) {

			if ( ! _is_wcf_checkout_type() && false === $show_checkout_demo ) {

				$error_html  = '<h4>' . __( 'Checkout ID not found', 'cartflows' ) . '</h4>';
				$error_html .= '<p>' . sprintf(
					/* translators: %1$1s, %2$2s Link to article */
					__( 'It seems that this is not the CartFlows Checkout page where you have added this shortcode. Please refer to this %1$1sarticle%2$2s to know more.', 'cartflows' ),
					'<a href="https://cartflows.com/docs/resolve-checkout-id-not-found-error/" target="_blank">',
					'</a>'
				) . '</p>';

				return $error_html;
			}

			global $post;

			$checkout_id = intval( $post->ID );
		}

		$output = '';

		ob_start();

		do_action( 'cartflows_checkout_form_before', $checkout_id );

		$checkout_layout = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-checkout-layout' );

		$template_default = CARTFLOWS_CHECKOUT_DIR . 'templates/embed/checkout-template-simple.php';

		$template_layout = apply_filters( 'cartflows_checkout_layout_template', $checkout_layout );

		if ( file_exists( $template_layout ) ) {
			include $template_layout;
		} else {
			include $template_default;
		}

		$output .= ob_get_clean();

		return $output;
	}

	/**
	 * Configure Cart Data.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function preconfigured_cart_data() {
		if ( is_admin() ) {
			return;
		}

		global $post, $wcf_step;

		if ( _is_wcf_checkout_type() ) {

			if ( wp_doing_ajax() ) {
				return;
			} else {

				$checkout_id = 0;

				if ( _is_wcf_checkout_type() ) {
					$checkout_id = $post->ID;
				}

				$global_checkout = intval( Cartflows_Helper::get_common_setting( 'global_checkout' ) );

				if ( ! empty( $global_checkout ) && ( $global_checkout === $wcf_step->get_control_step() ) ) {

					if ( WC()->cart->is_empty() ) {
						wc_add_notice( __( 'Your cart is currently empty.', 'cartflows' ), 'error' );
					}

					return;
				}

				if ( apply_filters( 'cartflows_skip_configure_cart', false, $checkout_id ) ) {
					return;
				}

				do_action( 'cartflows_checkout_before_configure_cart', $checkout_id );

				$flow_id = wcf()->utils->get_flow_id_from_step_id( $checkout_id );

				$products = wcf()->utils->get_selected_checkout_products( $checkout_id );

				if ( wcf()->flow->is_flow_testmode( $flow_id ) && ( ! is_array( $products ) || empty( $products[0]['product'] ) ) ) {
					$products = $this->get_random_products();
				}

				if ( ! is_array( $products ) ) {
					return;
				}

				/* Empty the current cart */
				WC()->cart->empty_cart();

				if ( is_array( $products ) && empty( $products[0]['product'] ) ) {

					$a_start = '';
					$a_close = '';

					wc_add_notice(
						sprintf(
							/* translators: %1$1s, %2$2s Link to meta */
							__( 'No product is selected. Please select products from the %1$1scheckout meta settings%2$2s to continue.', 'cartflows' ),
							$a_start,
							$a_close
						),
						'error'
					);
					return;
				}

				/* Set customer session if not set */
				if ( ! is_user_logged_in() && WC()->cart->is_empty() ) {
					WC()->session->set_customer_session_cookie( true );
				}

				$cart_product_count = 0;
				$cart_key           = '';
				$products_new       = array();

				foreach ( $products as $index => $data ) {

					if ( ! isset( $data['product'] ) ) {
						continue;
					}

					/* Since 1.6.5 */
					if ( empty( $data['add_to_cart'] ) || 'no' === $data['add_to_cart'] ) {
						continue;
					}

					if ( apply_filters( 'cartflows_skip_other_products', false, $cart_product_count ) ) {
						break;
					}

					$product_id = $data['product'];
					$_product   = wc_get_product( $product_id );

					if ( ! empty( $_product ) ) {

						$quantity = 1;

						if ( isset( $data['quantity'] ) && ! empty( $data['quantity'] ) ) {
							$quantity = $data['quantity'];
						}

						$discount_type  = isset( $data['discount_type'] ) ? $data['discount_type'] : '';
						$discount_value = ! empty( $data['discount_value'] ) ? $data['discount_value'] : '';
						$_product_price = $_product->get_price( $data['product'] );

						$custom_price = $this->calculate_discount( '', $discount_type, $discount_value, $_product_price );

						$cart_item_data = array(
							'wcf_product_data' => array(
								'unique_id' => $data['unique_id'],
							),
						);

						// Set the Product's custom price even if it is zero. Discount may have applied.
						if ( $custom_price >= 0 && '' !== $custom_price ) {

							$cart_item_data['custom_price'] = $custom_price;
						}

						if ( ! $_product->is_type( 'grouped' ) && ! $_product->is_type( 'external' ) ) {

							if ( $_product->is_type( 'variable' ) ) {

								$default_attributes = $_product->get_default_attributes();

								if ( ! empty( $default_attributes ) ) {

									foreach ( $_product->get_children() as $variation_id ) {

										$single_variation = new WC_Product_Variation( $variation_id );

										if ( $default_attributes == $single_variation->get_attributes() ) {
											$cart_key = WC()->cart->add_to_cart( $variation_id, $quantity, 0, array(), $cart_item_data );
											$cart_product_count++;
										}
									}
								} else {

									$product_childrens = $_product->get_children();

									$variation_product    = '';
									$variation_product_id = 0;

									foreach ( $product_childrens as $key => $v_id ) {

										$_var_product = wc_get_product( $v_id );

										if ( $_var_product->is_in_stock() ) {
											$variation_product_id = $v_id;
											$variation_product    = $_var_product;
											break;
										}
									}

									if ( $variation_product ) {
										$_product_price = $variation_product->get_price();

										$custom_price = $this->calculate_discount( '', $discount_type, $discount_value, $_product_price );
										if ( ! empty( $custom_price ) ) {
											$cart_item_data['custom_price'] = $custom_price;
										}

										$cart_key = WC()->cart->add_to_cart( $variation_product_id, $quantity, 0, array(), $cart_item_data );
										$cart_product_count++;
									} else {
										echo '<p>' . esc_html__( 'Variations Not set', 'cartflows' ) . '</p>';
									}
								}
							} else {
								$cart_key = WC()->cart->add_to_cart( $product_id, $quantity, 0, array(), $cart_item_data );

								if ( false !== $cart_key ) {
									$cart_product_count++;
								}
							}
						} else {
							$wrong_product_notice = __( 'This product can\'t be purchased', 'cartflows' );
							wc_add_notice( $wrong_product_notice );
						}
					}

					$products_new[ $index ] = array(
						'cart_item_key' => $cart_key,
					);
				}

				/* Set checkout products data */
				wcf()->utils->set_selcted_checkout_products( $checkout_id, $products_new );

				/* Since 1.2.2 */
				do_action( 'cartflows_checkout_after_configure_cart', $checkout_id );

				$cart_data       = WC()->cart->get_cart();
				$expiration_time = 30;
				setcookie( CARTFLOWS_ACTIVE_CHECKOUT, $checkout_id, time() + $expiration_time * MINUTE_IN_SECONDS, '/', COOKIE_DOMAIN, CARTFLOWS_HTTPS );

				$user_key = WC()->session->get_customer_id();

				set_transient( 'wcf_user_' . $user_key . '_checkout_' . $checkout_id, $cart_data, $expiration_time * MINUTE_IN_SECONDS );
			}
		}
	}

	/**
	 * Load shortcode data.
	 *
	 * @return void
	 */
	public function shortcode_load_data() {
		if ( _is_wcf_checkout_type() ) {

			add_action( 'wp_enqueue_scripts', array( $this, 'shortcode_scripts' ), 21 );

			add_action( 'wp_enqueue_scripts', array( $this, 'compatibility_scripts' ), 101 );

			$this->before_checkout_shortcode_actions();

			global $post;

			$checkout_id = $post->ID;

			do_action( 'cartflows_checkout_before_shortcode', $checkout_id );
		}
	}

	/**
	 * Render checkout ID hidden field.
	 *
	 * @return void
	 */
	public function before_checkout_shortcode_actions() {
		/* Show notices if cart has errors */
		add_action( 'woocommerce_cart_has_errors', 'woocommerce_output_all_notices' );

		// Outputting the hidden field in checkout page.
		add_action( 'woocommerce_after_order_notes', array( $this, 'checkout_shortcode_post_id' ), 99 );
		add_action( 'woocommerce_login_form_end', array( $this, 'checkout_shortcode_post_id' ), 99 );

		// Astra removes this actions so need to add it again.
		add_action( 'woocommerce_checkout_billing', array( WC()->checkout, 'checkout_form_billing' ) );
		add_action( 'woocommerce_checkout_shipping', array( WC()->checkout, 'checkout_form_shipping' ) );

		remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form' );

		add_action( 'woocommerce_checkout_order_review', array( $this, 'display_custom_coupon_field' ) );

		add_filter( 'woocommerce_cart_totals_coupon_html', array( $this, 'remove_coupon_text' ) );

		add_filter( 'woocommerce_order_button_text', array( $this, 'place_order_button_text' ), 99, 1 );

		add_filter( 'woocommerce_checkout_fields', array( $this, 'checkout_fields_actions' ), 10, 1 );

	}

	/**
	 * Checkout fields actions.
	 *
	 * @param array $checkout_fields checkout fields.
	 * @since x.x.x
	 */
	public function checkout_fields_actions( $checkout_fields ) {

		$checkout_fields = Cartflows_Checkout_Fields::get_instance()->add_three_column_layout_fields( $checkout_fields );

		$checkout_fields = $this->prefill_checkout_fields( $checkout_fields );
		$checkout_fields = $this->unset_fields_for_modern_checkout( $checkout_fields );

		return $checkout_fields;
	}

	/**
	 * Modern Checkout Layout Actions.
	 *
	 * @param int $checkout_id checkout ID.
	 * @since x.x.x
	 */
	public function modern_checkout_layout_actions( $checkout_id ) {

		if ( $this->is_modern_checkout_layout( $checkout_id ) ) {

			$checkout_layout = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-checkout-layout' );

			add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'customer_info_parent_wrapper_start' ), 20, 1 );

			add_action( 'woocommerce_checkout_billing', array( $this, 'add_custom_billing_email_field' ), 9, 1 );

			add_action( 'woocommerce_review_order_before_payment', array( $this, 'display_custom_payment_heading' ), 12 );

			remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );

			add_action( 'woocommerce_checkout_after_customer_details', array( $this, 'customer_info_parent_wrapper_close' ), 99, 1 );

			/* Add the collapsable order review section at the top of Checkout form */
			add_action( 'woocommerce_before_checkout_form', array( $this, 'add_custom_collapsed_order_review_table' ), 8 );

			// Re-arrange the position of payment section only for two column layout of modern checkout & not for one column.
			if ( 'modern-checkout' === $checkout_layout ) {
				remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
				add_action( 'cartflows_checkout_after_modern_checkout_layout', 'woocommerce_checkout_payment', 21 );
			}
		}
	}

	/**
	 * Customized order review section used to display in modern checkout responsive devices.
	 *
	 * @return void
	 */
	public function add_custom_collapsed_order_review_table() {

		include CARTFLOWS_CHECKOUT_DIR . 'templates/checkout/collapsed-order-summary.php';
	}

	/**
	 * Add Customer Information Section.
	 *
	 * @param int $checkout_id checkout ID.
	 *
	 * @return void
	 */
	public function customer_info_parent_wrapper_start( $checkout_id ) {

		do_action( 'cartflows_checkout_before_modern_checkout_layout', $checkout_id );
		echo '<div class="wcf-customer-info-main-wrapper">';
	}

	/**
	 * Add Custom Email Field.
	 *
	 * @return void
	 */
	public function add_custom_billing_email_field() {

		$lost_password_url  = get_site_url() . '/my-account/lost-password/';
		$current_user_name  = wp_get_current_user()->display_name;
		$current_user_email = wp_get_current_user()->user_email;
		$is_allow_login     = 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' );

		?>
			<div class="wcf-customer-info" id="customer_info">
				<div class="wcf-customer-info__notice"></div>
				<div class="woocommerce-billing-fields-custom">
					<h3><?php esc_html_e( 'Customer information', 'cartflows' ); ?>
						<?php if ( ! is_user_logged_in() && $is_allow_login ) { ?>
							<div class="woocommerce-billing-fields__customer-login-label"><?php /* translators: %1$s: Link HTML start, %2$s Link HTML End */ echo sprintf( __( 'Already have an account? %1$1s Log in%2$2s', 'cartflows' ), '<a href="javascript:" class="wcf-customer-login-url">', '</a>' ); ?></div>
						<?php } ?>
					</h3>
					<div class="woocommerce-billing-fields__customer-info-wrapper">
					<?php
					if ( ! is_user_logged_in() ) {

							woocommerce_form_field(
								'billing_email',
								array(
									'type'         => 'email',
									'class'        => array( 'form-row-fill' ),
									'required'     => true,
									'label'        => __( 'Email Address', 'cartflows' ),
									'placeholder'  => __( 'Email Address', 'cartflows' ),
									'autocomplete' => 'email username',
								)
							);

						if ( 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ) {
							?>
								<div class="wcf-customer-login-section">
								<?php
									woocommerce_form_field(
										'billing_password',
										array(
											'type'        => 'password',
											'class'       => array( 'form-row-fill', 'wcf-password-field' ),
											'required'    => true,
											'label'       => __( 'Password', 'cartflows' ),
											'placeholder' => __( 'Password', 'cartflows' ),
										)
									);
								?>
								<div class="wcf-customer-login-actions">
							<?php
									echo "<input type='button' name='wcf-customer-login-btn' class='button wcf-customer-login-section__login-button' id='wcf-customer-login-section__login-button' value='" . esc_html( __( 'Login', 'cartflows' ) ) . "'>";
									echo "<a href='$lost_password_url' class='wcf-customer-login-lost-password-url'>" . esc_html( __( 'Lost your password?', 'cartflows' ) ) . '</a>';
							?>
								</div>
							<?php
							if ( 'yes' === get_option( 'woocommerce_enable_guest_checkout', false ) ) {
								echo "<p class='wcf-login-section-message'>" . esc_html( __( 'Login is optional, you can continue with your order below.', 'cartflows' ) ) . '</p>';
							}
							?>
								</div>
						<?php } ?>
						<?php
						if ( 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout' ) ) {
							?>
								<div class="wcf-create-account-section" hidden>
								<?php if ( 'yes' === get_option( 'woocommerce_enable_guest_checkout' ) ) { ?>
										<p class="form-row form-row-wide create-account">
											<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
												<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" type="checkbox" name="createaccount" value="1" /> <span><?php esc_html_e( 'Create an account?', 'cartflows' ); ?></span>
											</label>
										</p>
									<?php } ?>
									<div class="create-account">
									<?php

									if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) {
										woocommerce_form_field(
											'account_username',
											array(
												'type'     => 'text',
												'class'    => array( 'form-row-fill' ),
												'id'       => 'account_username',
												'required' => true,
												'label'    => __( 'Account username', 'cartflows' ),
												'placeholder' => __( 'Account username', 'cartflows' ),
											)
										);
									}
									if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) {
										woocommerce_form_field(
											'account_password',
											array(
												'type'     => 'password',
												'id'       => 'account_password',
												'class'    => array( 'form-row-fill' ),
												'required' => true,
												'label'    => __( 'Create account password', 'cartflows' ),
												'placeholder' => __( 'Create account password', 'cartflows' ),
											)
										);
									}
									?>
									</div>
								</div>
						<?php } ?>
					<?php } else { ?>
								<div class="wcf-logged-in-customer-info"> <?php /* translators: %1$s: username, %2$s emailid */ echo apply_filters( 'cartflows_logged_in_customer_info_text', sprintf( __( ' Welcome Back %1$s ( %2$s )', 'cartflows' ), esc_attr( $current_user_name ), esc_attr( $current_user_email ) ) ); ?>
									<div><input type="hidden" class="wcf-email-address" id="billing_email" name="billing_email" value="<?php echo esc_attr( $current_user_email ); ?>"/></div>
								</div>
					<?php } ?>
					</div>
				</div>
			</div>
		<?php
	}

	/**
	 * Add closing wrapper to customer info and shipping section.
	 *
	 * @param int $checkout_id Checkout ID.
	 *
	 * @return void
	 */
	public function customer_info_parent_wrapper_close( $checkout_id ) {

		do_action( 'cartflows_checkout_after_modern_checkout_layout', $checkout_id );

		echo '</div>';

	}

	/**
	 * Prefill the checkout fields if available in url.
	 *
	 * @param array $checkout_fields checkout fields array.
	 */
	public function prefill_checkout_fields( $checkout_fields ) {

		$autofill = apply_filters( 'cartflows_auto_prefill_checkout_fields', true );

		if ( $autofill && ! empty( $_GET ) ) { // phpcs:ignore

			$billing_fields  = isset( $checkout_fields['billing'] ) ? $checkout_fields['billing'] : array();
			$shipping_fields = isset( $checkout_fields['shipping'] ) ? $checkout_fields['shipping'] : array();

			foreach ( $billing_fields as $key => $field ) {
				$field_value = isset( $_GET[ $key ] ) && ! empty( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : ''; //phpcs:ignore

				if ( ! empty( $field_value ) ) {
					$checkout_fields['billing'][ $key ]['default'] = $field_value;
				}
			}

			foreach ( $shipping_fields as $key => $field ) {
				$field_value = isset( $_GET[ $key ] ) && ! empty( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : ''; //phpcs:ignore

				if ( ! empty( $field_value ) ) {
					$checkout_fields['shipping'][ $key ]['default'] = $field_value;
				}
			}
		}

		return $checkout_fields;
	}

	/**
	 * Prefill the checkout fields if available in url.
	 *
	 * @param array $checkout_fields checkout fields array.
	 */
	public function unset_fields_for_modern_checkout( $checkout_fields ) {

		// Check if checkout layout skin is customer info.
		if ( $this->is_modern_checkout_layout() ) {
			// Unset defalut billing email from Billing Details.
			unset( $checkout_fields['billing']['billing_email'] );
			unset( $checkout_fields['account']['account_username'] );
			unset( $checkout_fields['account']['account_password'] );
		}

		return $checkout_fields;
	}

	/**
	 * Render checkout ID hidden field.
	 *
	 * @param array $checkout checkout session data.
	 * @return void
	 */
	public function checkout_shortcode_post_id( $checkout ) {
		global $post;

		$checkout_id = 0;

		if ( _is_wcf_checkout_type() ) {
			$checkout_id = $post->ID;
		}

		$flow_id = get_post_meta( $checkout_id, 'wcf-flow-id', true );

		echo '<input type="hidden" class="input-hidden _wcf_flow_id" name="_wcf_flow_id" value="' . intval( $flow_id ) . '">';
		echo '<input type="hidden" class="input-hidden _wcf_checkout_id" name="_wcf_checkout_id" value="' . intval( $checkout_id ) . '">';
	}

	/**
	 * Load shortcode scripts.
	 *
	 * @return void
	 */
	public function shortcode_scripts() {
		wp_enqueue_style( 'wcf-checkout-template', wcf()->utils->get_css_url( 'checkout-template' ), '', CARTFLOWS_VER );

		wp_enqueue_script(
			'wcf-checkout-template',
			wcf()->utils->get_js_url( 'checkout-template' ),
			array( 'jquery' ),
			CARTFLOWS_VER,
			true
		);

		do_action( 'cartflows_checkout_scripts' );

		$checkout_dynamic_css = apply_filters( 'cartflows_checkout_enable_dynamic_css', true );

		if ( $checkout_dynamic_css ) {

			global $post;

			$checkout_id = $post->ID;

			$style = get_post_meta( $checkout_id, 'wcf-dynamic-css', true );

			$css_version = get_post_meta( $checkout_id, 'wcf-dynamic-css-version', true );

			// Regenerate the dynamic css only when key is not exist or version does not match.
			if ( empty( $style ) || CARTFLOWS_ASSETS_VERSION !== $css_version ) {
				$style = $this->generate_style();
				update_post_meta( $checkout_id, 'wcf-dynamic-css', wp_slash( $style ) );
				update_post_meta( $checkout_id, 'wcf-dynamic-css-version', CARTFLOWS_ASSETS_VERSION );
			}

			CartFlows_Font_Families::render_fonts( $checkout_id );

			wp_add_inline_style( 'wcf-checkout-template', $style );
		}
	}

	/**
	 * Load compatibility scripts.
	 *
	 * @return void
	 */
	public function compatibility_scripts() {
		global $post;

		$checkout_id = 0;

		if ( _is_wcf_checkout_type() ) {
			$checkout_id = $post->ID;
		}

		// Add DIVI Compatibility css if DIVI theme is enabled.
		if (
			Cartflows_Compatibility::get_instance()->is_divi_enabled() ||
			Cartflows_Compatibility::get_instance()->is_divi_builder_enabled( $checkout_id )
		) {
			wp_enqueue_style( 'wcf-checkout-template-divi', wcf()->utils->get_css_url( 'checkout-template-divi' ), '', CARTFLOWS_VER );
		}

		// Add Flatsome Compatibility css if Flatsome theme is enabled.
		if ( Cartflows_Compatibility::get_instance()->is_flatsome_enabled() ) {
			wp_enqueue_style( 'wcf-checkout-template-flatsome', wcf()->utils->get_css_url( 'checkout-template-flatsome' ), '', CARTFLOWS_VER );
		}

		// Add The7 Compatibility css if The7 theme is enabled.
		if ( Cartflows_Compatibility::get_instance()->is_the_seven_enabled() ) {
			wp_enqueue_style( 'wcf-checkout-template-the-seven', wcf()->utils->get_css_url( 'checkout-template-the-seven' ), '', CARTFLOWS_VER );
		}
	}

	/**
	 * Generate styles.
	 *
	 * @return string
	 */
	public function generate_style() {
		global $post;

		$checkout_id = 0;

		if ( _is_wcf_checkout_type() ) {
			$checkout_id = $post->ID;
		}

		/*Output css variable */
		$output = '';

		$enable_design_setting          = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-enable-design-settings' );
		$enable_place_order_button_lock = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-checkout-place-order-button-lock' );

		if ( 'yes' === $enable_design_setting ) {

			$checkout_layout = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-checkout-layout' );

			$primary_color = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-primary-color' );

			$base_font_family = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-base-font-family' );

			$header_logo_width = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-header-logo-width' );

			$r = '';
			$g = '';
			$b = '';

			$field_tb_padding = '';
			$field_lr_padding = '';

			$field_heading_color  = '';
			$field_color          = '';
			$field_bg_color       = '';
			$field_border_color   = '';
			$field_label_color    = '';
			$submit_tb_padding    = '';
			$submit_lr_padding    = '';
			$hl_bg_color          = '';
			$field_input_size     = '';
			$box_border_color     = '';
			$section_bg_color     = '';
			$submit_button_height = '';
			$submit_color         = '';
			$submit_bg_color      = $primary_color;
			$submit_border_color  = $primary_color;

			$submit_hover_color        = '';
			$submit_bg_hover_color     = $primary_color;
			$submit_border_hover_color = $primary_color;

			$section_heading_color = '';

			$is_advance_option = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-advance-options-fields' );

			$button_font_family  = '';
			$button_font_weight  = '';
			$input_font_family   = '';
			$input_font_weight   = '';
			$heading_font_family = '';
			$heading_font_weight = '';
			$base_font_family    = $base_font_family;

			if ( 'yes' == $is_advance_option ) {

				/**
				 * Get Font Family and Font Weight weight values
				 */
				$section_bg_color = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-section-bg-color' );

				$heading_font_family   = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-heading-font-family' );
				$heading_font_weight   = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-heading-font-weight' );
				$section_heading_color = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-heading-color' );
				$button_font_family    = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-button-font-family' );
				$button_font_weight    = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-button-font-weight' );
				$input_font_family     = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-input-font-family' );
				$input_font_weight     = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-input-font-weight' );
				$field_tb_padding      = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-field-tb-padding' );
				$field_lr_padding      = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-field-lr-padding' );
				$field_input_size      = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-input-field-size' );

				$field_heading_color = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-field-heading-color' );

				$field_color = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-field-color' );

				$field_bg_color = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-field-bg-color' );

				$field_border_color = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-field-border-color' );

				$field_label_color = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-field-label-color' );

				$submit_tb_padding = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-submit-tb-padding' );

				$submit_lr_padding = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-submit-lr-padding' );

				$submit_color = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-submit-color' );

				$submit_bg_color = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-submit-bg-color', $primary_color );

				$submit_border_color = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-submit-border-color', $primary_color );

				$submit_border_hover_color = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-submit-border-hover-color', $primary_color );

				$submit_hover_color = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-submit-hover-color' );

				$submit_bg_hover_color = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-submit-bg-hover-color', $primary_color );

				$hl_bg_color = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-hl-bg-color' );

				$box_border_color = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-box-border-color' );

				$submit_button_height = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-input-button-size' );

				/**
				 * Get font values
				 */

				if ( 'custom' == $submit_button_height ) {
					$submit_button_height = '38px';
				}

				if ( 'custom' == $field_input_size ) {
					$field_input_size = '38px';
				}
			}
			if ( isset( $primary_color ) ) {

				list($r, $g, $b) = sscanf( $primary_color, '#%02x%02x%02x' );
			}

			$submit_btn_bg_color       = ( $submit_bg_color ) ? $submit_bg_color : $primary_color;
			$submit_btn_bg_hover_color = ( $submit_bg_hover_color ) ? $submit_bg_hover_color : $primary_color;

			$output     .= '.wcf-embed-checkout-form { ';
				$output .= ! empty( $primary_color ) ? '--wcf-primary-color: ' . $primary_color . ';' : '';
				$output .= ! empty( $section_heading_color ) ? '--wcf-heading-color: ' . $section_heading_color . ';' : '';
				$output .= ! empty( $submit_btn_bg_color ) ? '--wcf-btn-bg-color: ' . $submit_btn_bg_color . ';' : '';
				$output .= ! empty( $submit_btn_bg_hover_color ) ? '--wcf-btn-bg-hover-color: ' . $submit_btn_bg_hover_color . ';' : '';
				$output .= ! empty( $submit_color ) ? '--wcf-btn-text-color: ' . $submit_color . ';' : '';
				$output .= ! empty( $submit_hover_color ) ? '--wcf-btn-hover-text-color: ' . $submit_hover_color . ';' : '';
				$output .= ! empty( $field_label_color ) ? '--wcf-field-label-color: ' . $field_label_color . ';' : '';
				$output .= ! empty( $field_bg_color ) ? '--wcf-field-bg-color: ' . $field_bg_color . ';' : '';
				$output .= ! empty( $field_border_color ) ? '--wcf-field-border-color:' . $field_border_color . ';' : '';
				$output .= ! empty( $field_color ) ? '--wcf-field-text-color: ' . $field_color . ';' : '';
			$output     .= '}';

			if (
				Cartflows_Compatibility::get_instance()->is_divi_enabled() ||
				Cartflows_Compatibility::get_instance()->is_divi_builder_enabled( $checkout_id )
			) {

				include CARTFLOWS_CHECKOUT_DIR . 'includes/checkout-dynamic-divi-css.php';
			} else {
				include CARTFLOWS_CHECKOUT_DIR . 'includes/checkout-dynamic-css.php';
			}
		}

		if ( 'yes' === $enable_place_order_button_lock ) {
			// If enabled then add the below css to show the lock icon on place order button.
			$output .= '
			.wcf-embed-checkout-form .woocommerce #payment #place_order:before{
				content: "\e902";
				font-family: "cartflows-icon" !important;
				margin-right: 10px;
				font-size: 16px;
				font-weight: 500;
				top: 0px;
    			position: relative;
			}';
		}

		return $output;
	}

	/**
	 * Get ajax end points.
	 *
	 * @param string $endpoint_url end point URL.
	 * @param string $request end point request.
	 * @return string
	 */
	public function get_ajax_endpoint( $endpoint_url, $request ) {
		global $post;

		if ( ! empty( $post ) && ! empty( $_SERVER['REQUEST_URI'] ) ) {

			if ( _is_wcf_checkout_type() ) {

				$query_args = array();
				$url        = $endpoint_url;

				if ( mb_strpos( $endpoint_url, 'checkout', 0, 'utf-8' ) === false ) {

					if ( '' === $request ) {
						$query_args = array(
							'wc-ajax' => '%%endpoint%%',
						);
					} else {
						$query_args = array(
							'wc-ajax' => $request,
						);
					}

					$uri = explode('?', $_SERVER['REQUEST_URI'], 2); //phpcs:ignore
					$url = esc_url( $uri[0] );
				}

				$query_args['wcf_checkout_id'] = $post->ID;

				$endpoint_url = add_query_arg( $query_args, $url );
			}
		}

		return $endpoint_url;
	}


	/**
	 * Save checkout fields.
	 *
	 * @param int   $order_id order id.
	 * @param array $posted posted data.
	 * @return void
	 */
	public function save_checkout_fields( $order_id, $posted ) {

		if ( isset( $_POST['_wcf_checkout_id'] ) ) { //phpcs:ignore
			$checkout_id = wc_clean( intval( $_POST['_wcf_checkout_id'] ) ); //phpcs:ignore
			$flow_id = isset( $_POST['_wcf_flow_id'] ) ? wc_clean( intval( $_POST['_wcf_flow_id'] ) ) : 0; //phpcs:ignore

		} elseif ( isset( $_GET['wcf_checkout_id'] ) ) { //phpcs:ignore
			$checkout_id = wc_clean( intval( $_GET['wcf_checkout_id'] ) ); //phpcs:ignore
			$flow_id     = wcf()->utils->get_flow_id_from_step_id( $checkout_id );
		}

		if ( ! empty( $flow_id ) && ! empty( $checkout_id ) ) {
			update_post_meta( $order_id, '_wcf_flow_id', $flow_id );
			update_post_meta( $order_id, '_wcf_checkout_id', $checkout_id );
		}
	}

	/**
	 * Enable Logo In Header Of Checkout Page
	 *
	 * @return void
	 */
	public function enable_logo_in_header() {
		global $post;

		$checkout_id = 0;

		if ( _is_wcf_checkout_type() ) {
			$checkout_id = $post->ID;
		}

		$header_logo_image = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-header-logo-image' );
		$add_image_markup  = '';

		if ( isset( $header_logo_image ) && ! empty( $header_logo_image ) ) {
			$add_image_markup  = '<div class="wcf-checkout-header-image">';
			$add_image_markup .= '<img src="' . $header_logo_image . '" />';
			$add_image_markup .= '</div>';
		}

		echo $add_image_markup;
	}

	/**
	 * Add text to the bootom of the checkout page.
	 *
	 * @return void
	 */
	public function show_cartflows_copyright_message() {
		$output_string = '';

		$output_string .= '<div class="wcf-footer-primary">';
		$output_string .= '<div class="wcf-footer-content">';
		$output_string .= '<p class="wcf-footer-message">';
		$output_string .= 'Checkout powered by CartFlows';
		$output_string .= '</p>';
		$output_string .= '</div>';
		$output_string .= '</div>';

		echo $output_string;
	}

	/**
	 * Redirect users to our checkout if hidden param
	 *
	 * @param string $redirect redirect url.
	 * @param object $user user.
	 * @return string
	 */
	public function after_login_redirect( $redirect, $user ) {
		if (isset($_POST['_wcf_checkout_id'])) { //phpcs:ignore

			$checkout_id = intval($_POST['_wcf_checkout_id']); //phpcs:ignore

			$redirect = get_permalink( $checkout_id );
		}

		return $redirect;
	}

	/**
	 * Display coupon code field after review order fields.
	 */
	public function display_custom_coupon_field() {
		$coupon_enabled = apply_filters( 'woocommerce_coupons_enabled', true );
		$show_coupon    = apply_filters( 'cartflows_show_coupon_field', true );

		if ( ! ( $coupon_enabled && $show_coupon ) ) {
			return;
		}

		$coupon_field = array(
			'field_text'  => __( 'Coupon Code', 'cartflows' ),
			'button_text' => __( 'Apply', 'cartflows' ),
			'class'       => '',
		);

		$coupon_field = apply_filters( 'cartflows_coupon_field_options', $coupon_field );

		ob_start();
		?>
		<div class="wcf-custom-coupon-field <?php echo $coupon_field['class']; ?>" id="wcf_custom_coupon_field">
			<div class="wcf-coupon-col-1">
				<span>
					<input type="text" name="coupon_code" class="input-text wcf-coupon-code-input" placeholder="<?php echo $coupon_field['field_text']; ?>" id="coupon_code" value="">
				</span>
			</div>
			<div class="wcf-coupon-col-2">
				<span>
					<button type="button" class="button wcf-submit-coupon wcf-btn-small" name="apply_coupon" value="Apply"><?php echo $coupon_field['button_text']; ?></button>
				</span>
			</div>
		</div>
		<?php
		echo ob_get_clean();
	}

	/**
	 * Display Payment heading field after coupon code fields.
	 */
	public function display_custom_payment_heading() {

		ob_start();
		?>
		<div class="wcf-payment-option-heading">
			<h3 id="payment_options_heading"><?php esc_html_e( 'Payment', 'cartflows' ); ?></h3>
		</div>
		<?php
		echo ob_get_clean();
	}

	/**
	 * Apply filter to change class of remove coupon field.
	 *
	 * @param string $coupon coupon.
	 * @return string
	 */
	public function remove_coupon_text( $coupon ) {
		$coupon = str_replace( 'woocommerce-remove-coupon', 'wcf-remove-coupon', $coupon );
		return $coupon;
	}
	/**
	 * Apply filter to change the placeholder text of coupon field.
	 *
	 * @return string
	 */
	public function coupon_field_placeholder() {
		return apply_filters( 'cartflows_coupon_field_placeholder', __( 'Coupon Code', 'cartflows' ) );
	}

	/**
	 * Apply filter to change the button text of coupon field.
	 *
	 * @return string
	 */
	public function coupon_button_text() {
		return apply_filters( 'cartflows_coupon_button_text', __( 'Apply', 'cartflows' ) );
	}

	/**
	 * Apply coupon on submit of custom coupon form.
	 */
	public function apply_coupon() {
		$response = '';

		check_ajax_referer( 'wcf-apply-coupon', 'security' );
		if ( ! empty( $_POST['coupon_code'] ) ) {
			$result = WC()->cart->add_discount( sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) );
		} else {
			wc_add_notice( WC_Coupon::get_generic_coupon_error( WC_Coupon::E_WC_COUPON_PLEASE_ENTER ), 'error' );
		}

		$response = array(
			'status' => $result,
			'msg'    => wc_print_notices( true ),
		);

		echo wp_json_encode( $response );

		die();
	}


	/**
	 * Added ajax nonce to localize variable.
	 *
	 * @param array $vars localize variables.
	 */
	public function add_localize_vars( $vars ) {
		$vars['wcf_validate_coupon_nonce'] = wp_create_nonce( 'wcf-apply-coupon' );

		$vars['wcf_validate_remove_coupon_nonce'] = wp_create_nonce( 'wcf-remove-coupon' );

		$vars['wcf_validate_remove_cart_product_nonce'] = wp_create_nonce( 'wcf-remove-cart-product' );

		$vars['check_email_exist_nonce'] = wp_create_nonce( 'check-email-exist' );

		$vars['woocommerce_login_nonce'] = wp_create_nonce( 'woocommerce-login' );

		$vars['allow_persistence'] = apply_filters( 'cartflows_allow_persistence', 'yes' );

		$vars['is_logged_in'] = is_user_logged_in();

		$vars['email_validation_msgs'] = array(
			'error_msg'   => __( 'Entered email address is not a valid email.', 'cartflows' ),
			'success_msg' => __( 'This email is already registered. Please enter the password to continue.', 'cartflows' ),
		);

		$vars['order_review_toggle_texts'] = array(
			'toggle_show_text' => $this->get_order_review_toggle_texts(),
			'toggle_hide_text' => $this->get_order_review_toggle_texts( 'hide_text' ),
		);

		return $vars;
	}

	/**
	 * Remove coupon.
	 */
	public function remove_coupon() {
		check_ajax_referer( 'wcf-remove-coupon', 'security' );
		$coupon = isset($_POST['coupon_code']) ? wc_clean(wp_unslash($_POST['coupon_code'])) : false; //phpcs:ignore

		if ( empty( $coupon ) ) {
			echo "<div class='woocommerce-error'>" . esc_html__( 'Sorry there was a problem removing this coupon.', 'cartflows' );
		} else {
			WC()->cart->remove_coupon( $coupon );
			echo "<div class='woocommerce-error'>" . esc_html__( 'Coupon has been removed.', 'cartflows' ) . '</div>';
		}
		wc_print_notices();
		wp_die();
	}

	/**
	 * Remove cart item.
	 */
	public function wcf_woo_remove_cart_product() {
		check_ajax_referer( 'wcf-remove-cart-product', 'security' );
		$product_key   = isset($_POST['p_key']) ? wc_clean(wp_unslash($_POST['p_key'])) : false; //phpcs:ignore
		$product_id    = isset($_POST['p_id']) ? wc_clean(wp_unslash($_POST['p_id'])) : ''; //phpcs:ignore
		$product_title = get_the_title( $product_id );

		$needs_shipping = false;

		if ( empty( $product_key ) ) {
			$msg = "<div class='woocommerce-message'>" . __( 'Sorry there was a problem removing ', 'cartflows' ) . $product_title;
		} else {
			WC()->cart->remove_cart_item( $product_key );
			$msg = "<div class='woocommerce-message'>" . $product_title . __( ' has been removed.', 'cartflows' ) . '</div>';
		}

		foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
			if ( $values['data']->needs_shipping() ) {
				$needs_shipping = true;
				break;
			}
		}

		$response = array(
			'need_shipping' => $needs_shipping,
			'msg'           => $msg,
		);

		echo wp_json_encode( $response );
		wp_die();
	}

	/**
	 * Check email exist.
	 */
	public function check_email_exists() {

		check_ajax_referer( 'check-email-exist', 'security' );

		$email_address = isset( $_POST['email_address'] ) ? sanitize_email( wp_unslash( $_POST['email_address'] ) ) : false;

		$is_exist = email_exists( $email_address );

		$response = array(
			'success'          => boolval( $is_exist ),
			'is_login_allowed' => 'yes' === get_option( 'woocommerce_enable_checkout_login_reminder' ),
			'msg'              => $is_exist ? __( 'Email Exist.', 'cartflows' ) : __( 'Email not exist', 'cartflows' ),
		);

		wp_send_json_success( $response );
	}

	/**
	 * Check email exist.
	 */
	public function woocommerce_user_login() {

		check_ajax_referer( 'woocommerce-login', 'security' );

		$response = array(
			'success' => false,
		);

		$email_address = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : false;
		$password      = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : false; // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$creds = array(
			'user_login'    => $email_address,
			'user_password' => $password,
			'remember'      => false,
		);

		$user = wp_signon( $creds, false );

		if ( ! is_wp_error( $user ) ) {

			$response = array(
				'success' => true,
			);
		} else {
			$response['error'] = wp_kses_post( $user->get_error_message() );
		}

		wp_send_json_success( $response );
	}

	/**
	 * Calculate discount for product.
	 *
	 * @param string $discount_coupon discount coupon.
	 * @param string $discount_type discount type.
	 * @param int    $discount_value discount value.
	 * @param int    $_product_price product price.
	 * @return int
	 * @since 1.1.5
	 */
	public function calculate_discount( $discount_coupon, $discount_type, $discount_value, $_product_price ) {
		$custom_price = '';

		if ( ! empty( $discount_type ) ) {
			if ( 'discount_percent' === $discount_type ) {

				if ( $discount_value > 0 ) {
					$custom_price = $_product_price - ( ( $_product_price * $discount_value ) / 100 );
				}
			} elseif ( 'discount_price' === $discount_type ) {

				if ( $discount_value > 0 ) {
					$custom_price = $_product_price - $discount_value;
				}
			} elseif ( 'coupon' === $discount_type ) {

				if ( ! empty( $discount_coupon ) ) {
					WC()->cart->add_discount( $discount_coupon );
				}
			}
		}

		return $custom_price;
	}

	/**
	 * Preserve the custom item price added by Variations & Quantity feature
	 *
	 * @param array $cart_object cart object.
	 * @since 1.0.0
	 */
	public function custom_price_to_cart_item( $cart_object ) {
		if ( wp_doing_ajax() && ! WC()->session->__isset( 'reload_checkout' ) ) {

			foreach ( $cart_object->cart_contents as $key => $value ) {

				if ( isset( $value['custom_price'] ) ) {

					$custom_price = floatval( $value['custom_price'] );
					$value['data']->set_price( $custom_price );
				}
			}
		}
	}

	/**
	 * Get random product for test mode.
	 */
	public function get_random_products() {

		$products = array();

		$args = array(
			'posts_per_page' => 1,
			'orderby'        => 'rand',
			'post_type'      => 'product',
			'meta_query'     => array( //phpcs:ignore
				// Exclude out of stock products.
				array(
					'key'     => '_stock_status',
					'value'   => 'outofstock',
					'compare' => 'NOT IN',
				),
			),
			'tax_query'      => array( //phpcs:ignore
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => 'simple',
				),
			),
		);

		$random_product = get_posts( $args );

		if ( isset( $random_product[0]->ID ) ) {
			$products = array(
				array(
					'product'     => $random_product[0]->ID,
					'unique_id'   => wcf()->utils->get_unique_id(),
					'add_to_cart' => true,
				),
			);
		}

		return $products;
	}

	/**
	 * Return true if checkout layout skin is conditional checkout.
	 *
	 * @param int $checkout_id Checkout ID.
	 *
	 * @return bool
	 */
	public function is_modern_checkout_layout( $checkout_id = '' ) {
		global $post;

		$is_modern_checkout = false;

		if ( ! empty( $post ) && empty( $checkout_id ) ) {
			$checkout_id = $post->ID;
		}

		if ( ! empty( $checkout_id ) ) {
			// Get checkout layout skin.
			$checkout_layout = wcf()->options->get_checkout_meta_value( $checkout_id, 'wcf-checkout-layout' );

			if ( 'modern-checkout' === $checkout_layout || 'modern-one-column' === $checkout_layout ) {
				$is_modern_checkout = true;
			}
		}

		return $is_modern_checkout;
	}

	/**
	 * Change the Shipping error messages HTML
	 *
	 * @param string $message shipping message.
	 *
	 * @return string
	 */
	public function change_shipping_message_html( $message ) {

		$checkout_id = _get_wcf_checkout_id();

		if ( ! $checkout_id ) {

			$checkout_id = isset( $_GET['wcf_checkout_id'] ) && ! empty( $_GET['wcf_checkout_id'] ) ? intval( wp_unslash( $_GET['wcf_checkout_id'] ) ) : 0; //phpcs:ignore
		}

		if ( empty( $checkout_id ) ) {
			return $message;
		}

		$message = "<span class='wcf-shipping-tooltip'><span class='dashicons dashicons-editor-help'></span><span class='wcf-tooltip-msg'>" . $message . '</span></span>';

		return $message;
	}

	/**
	 * Update cart total on button and order review mobile sction.
	 *
	 * @param string $fragments shipping message.
	 *
	 * @return array $fragments updated Woo fragments.
	 */
	public function add_updated_cart_price( $fragments ) {

		$checkout_id = _get_wcf_checkout_id();

		if ( ! $checkout_id ) {

			$checkout_id = isset( $_GET['wcf_checkout_id'] ) && ! empty( $_GET['wcf_checkout_id'] ) ? intval( wp_unslash( $_GET['wcf_checkout_id'] ) ) : 0; //phpcs:ignore
		}

		if ( empty( $checkout_id ) ) {
			return $fragments;
		}

		$fragments['.wcf-order-review-total'] = "<div class='wcf-order-review-total'>" . WC()->cart->get_total() . '</div>';

		ob_start();

		$this->wcf_order_review();
		$wcf_order_review = ob_get_clean();

		$fragments['.wcf_cartflows_review_order_wrapper .woocommerce-checkout-review-order-table'] = $wcf_order_review;

		return $fragments;
	}

	/**
	 * Array of order review toggler text.
	 *
	 * @param string $text array key to get specific value.
	 *
	 * @return string
	 */
	public function get_order_review_toggle_texts( $text = 'show_text' ) {

		$toggle_texts = apply_filters(
			'cartflows_order_review_toggle_texts',
			array(
				'show_text' => esc_html__( 'Show Order Summary', 'cartflows' ),
				'hide_text' => esc_html__( 'Hide Order Summary', 'cartflows' ),
			)
		);

		return $toggle_texts[ $text ];

	}

	/**
	 * Get WC shipping methods HTML for modern Checkout.
	 */
	public function wcf_cart_totals_shipping_html() {

		// Return if WooCommerce is not active. Also check for wc is exists or not.
		if ( ! wcf()->is_woo_active || ! function_exists( 'WC' ) ) {
			return;
		}

		$packages = WC()->shipping()->get_packages();
		$first    = true;

		foreach ( $packages as $i => $package ) {
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			$product_names = array();

			if ( count( $packages ) > 1 ) {
				foreach ( $package['contents'] as $item_id => $values ) {
					$product_names[ $item_id ] = $values['data']->get_name() . ' &times;' . $values['quantity'];
				}
				$product_names = apply_filters( 'woocommerce_shipping_package_details_array', $product_names, $package );
			}

			include CARTFLOWS_CHECKOUT_DIR . 'templates/checkout/shipping-methods.php';

			$first = false;
		}
	}

	/**
	 * Get WC order review table HTML for modern Checkout.
	 */
	public function wcf_order_review() {

		// Return if Woo is not installed.
		if ( ! wcf()->is_woo_active ) {
			return;
		}

		include CARTFLOWS_CHECKOUT_DIR . 'templates/checkout/order-review-table.php';
	}
}

/**
 *  Kicking this off by calling 'get_instance()' method
 */
Cartflows_Checkout_Markup::get_instance();
