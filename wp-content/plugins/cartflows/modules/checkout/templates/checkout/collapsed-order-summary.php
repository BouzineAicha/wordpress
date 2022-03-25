<?php
/**
 * CartFlows Mobile Order Review Table for Modern Checkout.
 *
 * @package cartflows
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<!-- Mobile responsive order review template -->
<div class='wcf-order-review-toggle'>
	<div class='wcf-order-review-toggle-button-wrap'>
		<span class='wcf-order-review-toggle-text'><?php echo $this->get_order_review_toggle_texts(); ?></span>
		<span class='wcf-order-review-toggle-button dashicons dashicons-arrow-down-alt2'></span>
		<span class='wcf-order-review-toggle-button dashicons dashicons-arrow-up-alt2'></span>
	</div>
	<div class='wcf-order-review-total'><?php echo wp_strip_all_tags( WC()->cart->get_total() ); ?></div>
</div>

<div class="wcf_cartflows_review_order_wrapper">
	<?php $this->wcf_order_review(); ?>
	<!-- Order review coupon field -->
	<div class="wcf-custom-coupon-field" id="wcf_custom_coupon_field_order_review">
		<div class="wcf-coupon-col-1">
			<span>
				<input type="text" name="coupon_code" class="input-text wcf-coupon-code-input" placeholder="<?php esc_attr_e( 'Coupon Code', 'cartflows' ); ?>" id="order_review_coupon_code" value="">
			</span>
		</div>
		<div class="wcf-coupon-col-2">
			<span>
				<button type="button" class="button wcf-submit-coupon wcf-btn-small" name="apply_coupon" value="Apply"><?php esc_html_e( 'Apply', 'cartflows' ); ?></button>
			</span>
		</div>
	</div>
</div>

<?php
