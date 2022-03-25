<?php
/**
 * CartFlows Flows Stats ajax actions.
 *
 * @package CartFlows
 */

namespace CartflowsAdmin\AdminCore\Ajax;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use CartflowsAdmin\AdminCore\Ajax\AjaxBase;
use CartflowsAdmin\AdminCore\Inc\AdminHelper;

/**
 * Class Flows.
 */
class FlowsStats extends AjaxBase {

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
	 * Register ajax events.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_ajax_events() {

		$ajax_events = array(
			'get_all_flows_stats',
		);

		$this->init_ajax_events( $ajax_events );
	}


	/**
	 * Get all Flows Stats.
	 */
	public function get_all_flows_stats() {

		$total_flow_revenue = $this->get_earnings();

		$response = array(
			'flow_stats' => $total_flow_revenue,
		);

		wp_send_json_success( $response );

	}

	/**
	 * Calculate earning.
	 *
	 * @return array
	 */
	public function get_earnings() {

		$currency_symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '';

		if ( _is_cartflows_pro() ) {
			// Return All Stats.
			return apply_filters(
				'cartflows_home_page_analytics',
				array(
					'order_currency'       => $currency_symbol,
					'total_orders'         => '0',
					'total_revenue'        => '0',
					'total_bump_revenue'   => '0',
					'total_offers_revenue' => '0',
					'total_visits'         => '0',
				)
			);
		}

		$orders      = $this->get_orders_by_flow();
		$gross_sale  = 0;
		$order_count = 0;

		if ( ! empty( $orders ) ) {

			foreach ( $orders as $order ) {

				$order_id    = $order->ID;
				$order       = wc_get_order( $order_id );
				$order_total = $order->get_total();
				$order_count++;

				if ( ! $order->has_status( 'cancelled' ) ) {
					$gross_sale += (float) $order_total;
				}
			}
		}

		// Return All Stats.
		return array(
			'order_currency'       => $currency_symbol,
			'total_orders'         => $order_count,
			'total_revenue'        => number_format( (float) $gross_sale, 2, '.', '' ),
			'total_bump_revenue'   => '0',
			'total_offers_revenue' => '0',
			'total_visits'         => '0',
		);

	}



	/**
	 * Get orders data for flow.
	 *
	 * @since 1.6.15
	 *
	 * @return int
	 */
	public function get_orders_by_flow() {

		global $wpdb;

		$start_date = filter_input( INPUT_POST, 'date_from', FILTER_SANITIZE_STRING );
		$end_date   = filter_input( INPUT_POST, 'date_to', FILTER_SANITIZE_STRING );

		$start_date = $start_date ? $start_date : date( 'Y-m-d' ); //phpcs:ignore
		$end_date   = $end_date ? $end_date : date( 'Y-m-d' ); //phpcs:ignore

		$start_date = date( 'Y-m-d H:i:s', strtotime( $start_date . '00:00:00' ) ); //phpcs:ignore
		$end_date   = date( 'Y-m-d H:i:s', strtotime( $end_date . '23:59:59' ) ); //phpcs:ignore

		$conditions = array(
			'tb1.post_type' => 'shop_order',
		);

		$where = $this->get_items_query_where( $conditions );

		$where .= " AND ( tb1.post_date BETWEEN IF (tb2.meta_key='wcf-analytics-reset-date'>'" . $start_date . "', tb2.meta_key, '" . $start_date . "')  AND '" . $end_date . "' )";
		$where .= " AND ( ( tb2.meta_key = '_wcf_flow_id' ) OR ( tb2.meta_key = '_cartflows_parent_flow_id' ) )";
		$where .= " AND tb1.post_status IN ( 'wc-completed', 'wc-processing', 'wc-cancelled' )";

		$query = 'SELECT tb1.ID, DATE( tb1.post_date ) date, tb2.meta_value FROM ' . $wpdb->prefix . 'posts tb1 
		INNER JOIN ' . $wpdb->prefix . 'postmeta tb2
		ON tb1.ID = tb2.post_id '
		. $where;

		return $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}


	/**
	 * Prepare where items for query.
	 *
	 * @param array $conditions conditions to prepare WHERE query.
	 * @return string
	 */
	protected function get_items_query_where( $conditions ) {

		global $wpdb;

		$where_conditions = array();

		foreach ( $conditions as $key => $condition ) {

			if ( false !== stripos( $key, 'IN' ) ) {
				$where_conditions[] = $key . $wpdb->prepare( '( %s )', $condition );
			} else {
				$where_conditions[] = $key . $wpdb->prepare( '= %s', $condition );
			}
		}

		if ( ! empty( $where_conditions ) ) {
			return 'WHERE 1 = 1 AND ' . implode( ' AND ', $where_conditions );
		} else {
			return '';
		}
	}
}
