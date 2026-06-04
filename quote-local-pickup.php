<?php
/**
 * Plugin Name:       Quote Local Pickup
 * Plugin URI:        https://power-plugins.com/plugin/purchase-orders-cart-to-quote/
 * Description:       Add support for a "Local pickup" option to the Cart-to-quote plugin (cart-to-order-review)
 * Version:           0.3.0
 * Author:            Power Plugins
 * Author URI:        https://power-plugins.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || die();

const PPQLP_PLUGIN_NAME    = 'quote-local-pickup';
const PPQLP_PLUGIN_VERSION = '0.3.0';

define( 'PPQLP_DIR', plugin_dir_path( __FILE__ ) );
define( 'PPQLP_URL', plugin_dir_url( __FILE__ ) );

const PPQLP_FIELD_NAME = 'quote_local_pickup';

function ppqlp_quote_button_extras() {
	$params = array(
		'labelText' => esc_html__( 'Do not quote for shipping - I will collect from the store', 'cart-to-order-review' ),
		'fieldName' => PPQLP_FIELD_NAME,
	);

	$params = (array) apply_filters( 'quote_local_pickup_params', $params );

	printf(
		'<div class="quote-local-pickup" data-quote-local-pickup="%s" value="yes"></div>', // ...
		esc_attr( wp_json_encode( $params ) )
	);
}
add_action( 'cto_before_shipping_quote_button', 'ppqlp_quote_button_extras' );

function ppqlp_enqueue_public_assets() {
	if ( function_exists( 'WC' ) && is_checkout() ) {
		wp_enqueue_script( 'ppqlp-checkout', PPQLP_URL . 'quote-local-pickup-checkout.js', null, PPQLP_PLUGIN_VERSION );
		wp_enqueue_style( 'ppqlp-checkout', PPQLP_URL . 'quote-local-pickup-checkout.css', null, PPQLP_PLUGIN_VERSION );

		do_action( 'enqueued_quote_local_pickup_assets' );
	}
}
add_action( 'wp_enqueue_scripts', 'ppqlp_enqueue_public_assets' );

function ppqlp_find_pickup_instance() {
	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		return null;
	}

	$found      = null;
	$method_ids = array( 'local_pickup', 'pickup_location' );

	$zone_ids = array( 0 ); // "Rest of the World" is not in get_zones()
	foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
		// error_log( __FUNCTION__ . ' : ' . $zone['zone_id'] . ' ' . $zone->get_zone_name() );
		$zone_ids[] = $zone['zone_id'];
	}
	$zone_ids = array_unique( $zone_ids );

	foreach ( $zone_ids as $zone_id ) {
		$zone = WC_Shipping_Zones::get_zone( (int) $zone_id );
		foreach ( $zone->get_shipping_methods( true ) as $method ) {
			if ( in_array( $method->id, $method_ids, true ) ) {
				$found = $method;
				break;
			}
		}

		if ( null !== $found ) {
			break;
		}
	}

	return $found;
}

function ppqlp_force_local_pickup( $order, $data ) {
	// Diagnostics
	// wp_mail( 'paul@headwall.co.uk', 'QUOTE', wp_json_encode( $_POST ) );
	if ( ! isset( $_POST[ PPQLP_FIELD_NAME ] ) || 'yes' !== $_POST[ PPQLP_FIELD_NAME ] ) {
		return;
	}

	if ( empty( ( $shipping_method = ppqlp_find_pickup_instance() ) ) ) {
		// error_log( 'No local pickup method found' );
		// optionally wc_get_logger()->warning(...) so a misconfig is visible
		return;
	}

	// Diagnostics
	error_log( 'Found method: ' . $shipping_method->id );

	// TODO: Check if $order is really a WC_Order object, before we access it. It could be an instance of WC_Error.
	// ...

	// Clear existing shipping. Group-level removal works pre-save; remove_item() would not.
	$order->remove_order_items( 'shipping' );

	error_log( __FUNCTION__ . ' : CCC' );
	$item = new WC_Order_Item_Shipping();
	$item->set_props(
		array(
			'method_title' => $shipping_method->get_title(),
			'method_id'    => $shipping_method->id, // 'local_pickup' | 'pickup_location'
			'instance_id'  => $shipping_method->get_instance_id(), // resolved, not hardcoded

		// Local pick-up is usually free, and we don't have the shipping rate info.
		// 'total'        => wc_format_decimal( $rate->get_cost() ),
		// 'taxes'        => array( 'total' => $rate->get_taxes() ),
		)
	);

	// If the store admin needs to charge for local pickup, they can filter it here.
	$item = apply_filters( 'ppqlp_local_pickup_shipping_item', $item );

	// foreach ( $rate->get_meta_data() as $key => $value ) {
	// error_log( __FUNCTION__ . ' : DDD' );
	// $item->add_meta_data( $key, $value, true ); // carry pickup-location meta etc.
	// }

	$order->add_item( $item );

	// Only matters if pickup is non-free/taxed; harmless when it's free.
	$order->calculate_totals();
}
add_action( 'woocommerce_checkout_create_order', 'ppqlp_force_local_pickup', 10, 2 );
