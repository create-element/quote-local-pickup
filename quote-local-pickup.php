<?php
/**
 * Plugin Name:       Quote Local Pickup
 * Plugin URI:        https://power-plugins.com/plugin/purchase-orders-cart-to-quote/
 * Description:       Add support for a "Local pickup" option to the Cart-to-quote plugin (cart-to-order-review)
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            Power Plugins
 * Author URI:        https://power-plugins.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:       quote-local-pickup
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce, cart-to-order-review
 *
 * @package Quote_Local_Pickup
 */

defined( 'ABSPATH' ) || die();

const PPQLP_PLUGIN_NAME    = 'quote-local-pickup';
const PPQLP_PLUGIN_VERSION = '1.0.0';
const PPQLP_FIELD_NAME     = 'quote_local_pickup';

define( 'PPQLP_DIR', plugin_dir_path( __FILE__ ) );
define( 'PPQLP_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load the plugin's bundled translations from /languages.
 *
 * Hooked to `init`.
 *
 * @since 1.0.0
 *
 * @return void
 */
function ppqlp_load_textdomain() {
	load_plugin_textdomain( 'quote-local-pickup', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'ppqlp_load_textdomain' );

/**
 * Print the local-pickup checkbox container next to the "Request a Quote" button.
 *
 * Outputs an empty placeholder element carrying a JSON-encoded `data-` payload (the
 * label text and field name). The actual checkbox and label are injected into this
 * element by quote-local-pickup-checkout.js once Cart to Quote fires its
 * `custom_quote_button_rendered` browser event.
 *
 * Hooked to the parent plugin's `cto_before_shipping_quote_button` action.
 *
 * @since 0.1.0
 *
 * @return void
 */
function ppqlp_quote_button_extras() {
	$params = array(
		'labelText' => esc_html__( 'Do not quote for shipping - I will collect from the store', 'quote-local-pickup' ),
		'fieldName' => PPQLP_FIELD_NAME,
	);

	/**
	 * Filters the parameters passed to the front-end before the checkbox is rendered.
	 *
	 * @since 0.1.0
	 *
	 * @param array $params {
	 *     The render parameters.
	 *
	 *     @type string $labelText The checkbox label shown to the customer.
	 *     @type string $fieldName The form field name (default PPQLP_FIELD_NAME).
	 * }
	 */
	$params = (array) apply_filters( 'ppqlp_params', $params );

	printf( '<div class="quote-local-pickup" data-quote-local-pickup="%s"></div>', esc_attr( wp_json_encode( $params ) ) );
}
add_action( 'cto_before_shipping_quote_button', 'ppqlp_quote_button_extras' );

/**
 * Enqueue the checkout CSS and JS on the WooCommerce checkout page.
 *
 * The script depends on jQuery and is loaded in the footer. Assets are only enqueued
 * when WooCommerce is active and we are on the checkout.
 *
 * Hooked to `wp_enqueue_scripts`.
 *
 * @since 0.1.0
 *
 * @return void
 */
function ppqlp_enqueue_public_assets() {
	if ( function_exists( 'WC' ) && is_checkout() ) {
		wp_enqueue_script( 'ppqlp-checkout', PPQLP_URL . 'quote-local-pickup-checkout.js', array( 'jquery' ), PPQLP_PLUGIN_VERSION, true );
		wp_enqueue_style( 'ppqlp-checkout', PPQLP_URL . 'quote-local-pickup-checkout.css', null, PPQLP_PLUGIN_VERSION );

		/**
		 * Fires after the plugin's checkout assets have been enqueued.
		 *
		 * Useful for enqueueing dependent assets (e.g. with 'ppqlp-checkout' as a dependency).
		 *
		 * @since 0.1.0
		 */
		do_action( 'ppqlp_enqueued_assets' );
	}
}
add_action( 'wp_enqueue_scripts', 'ppqlp_enqueue_public_assets' );

/**
 * Find the first configured Local Pickup shipping method across all shipping zones.
 *
 * Searches every zone (including "Rest of the World", zone 0) for a shipping method
 * whose id matches one of the recognised pickup method ids, and returns the first
 * match so its title and instance id can be copied onto the quote order.
 *
 * @since 0.1.0
 *
 * @return WC_Shipping_Method|null The matched shipping method instance, or null if
 *                                 WooCommerce is unavailable or none is configured.
 */
function ppqlp_find_pickup_instance() {
	if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		return null;
	}

	$found      = null;
	$method_ids = array( 'local_pickup', 'pickup_location' );

	/**
	 * Filters the shipping method ids treated as "local pickup".
	 *
	 * Lets stores recognise custom or third-party pickup methods. Returned ids are
	 * deduplicated and empty values are dropped.
	 *
	 * @since 0.4.0
	 *
	 * @param string[] $method_ids Shipping method ids. Default array( 'local_pickup', 'pickup_location' ).
	 */
	$method_ids = array_values( array_unique( array_filter( (array) apply_filters( 'ppqlp_pickup_method_ids', $method_ids ) ) ) );

	if ( empty( $method_ids ) ) {
		return null;
	}

	$zone_ids = array( 0 ); // "Rest of the World" is not in get_zones()
	foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
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

/**
 * Attach the Local Pickup shipping method to a quote order when requested.
 *
 * Runs on `woocommerce_checkout_create_order`, which fires for every order, so it
 * first confirms that Cart to Quote is actually creating a quote and that the customer
 * ticked the pickup checkbox. When both are true it replaces any existing shipping with
 * a single Local Pickup line item (free by default).
 *
 * Hooked to `woocommerce_checkout_create_order`.
 *
 * @since 0.1.0
 *
 * @param WC_Order|WC_Error $order The order being created. May be a WC_Error on failure.
 * @param array             $data  The checkout data assembled by Cart to Quote. The
 *                                 pickup choice arrives here under PPQLP_FIELD_NAME.
 * @return void
 */
function ppqlp_force_local_pickup( $order, $data ) {
	// Only act while Cart to Quote is creating a quote. The same
	// woocommerce_checkout_create_order action fires for every normal order, so
	// without this guard we would mutate ordinary checkouts too.
	global $pp_cto_plugin;
	if ( ! is_object( $pp_cto_plugin ) || ! method_exists( $pp_cto_plugin, 'get_is_creating_a_quote' ) || ! $pp_cto_plugin->get_is_creating_a_quote() ) {
		return;
	}

	// The checkbox is serialised into the quote AJAX payload and merged (already
	// sanitised) into $data by the parent plugin. Fall back to $_POST for any
	// non-AJAX path.
	if ( is_array( $data ) && isset( $data[ PPQLP_FIELD_NAME ] ) ) {
		$is_pickup_requested = 'yes' === $data[ PPQLP_FIELD_NAME ];
	} else {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified upstream by Cart to Quote's request_a_quote() before this hook fires; value is sanitised here.
		$is_pickup_requested = isset( $_POST[ PPQLP_FIELD_NAME ] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST[ PPQLP_FIELD_NAME ] ) );
	}

	if ( ! $is_pickup_requested ) {
		return;
	}

	// $order can be a WC_Error on failure; bail unless we have a real order.
	if ( ! ( $order instanceof WC_Order ) ) {
		return;
	}

	$shipping_method = ppqlp_find_pickup_instance();
	if ( empty( $shipping_method ) ) {
		wc_get_logger()->warning( 'Local pickup requested for a quote but no local_pickup/pickup_location method is configured.', array( 'source' => PPQLP_PLUGIN_NAME ) );
		return;
	}

	// Clear existing shipping. Group-level removal works pre-save; remove_item() would not.
	$order->remove_order_items( 'shipping' );

	$item = new WC_Order_Item_Shipping();
	$item->set_props(
		array(
			'method_title' => $shipping_method->get_title(),
			'method_id'    => $shipping_method->id, // The matched id, e.g. local_pickup or pickup_location.
			'instance_id'  => $shipping_method->get_instance_id(), // Resolved from the zone, not hardcoded.

		// Local pickup is usually free, and we don't have a WC_Shipping_Rate
		// here, so we leave the total/taxes at zero. Stores that charge for
		// pickup can set them via the ppqlp_local_pickup_shipping_item filter.
		)
	);

	/**
	 * Filters the shipping line item before it is added to the quote order.
	 *
	 * Use this to charge for pickup (set the total/taxes), change the title, or
	 * attach metadata such as a pickup location.
	 *
	 * @since 0.1.0
	 *
	 * @param WC_Order_Item_Shipping $item The shipping line item (free by default).
	 */
	$item = apply_filters( 'ppqlp_local_pickup_shipping_item', $item );

	$order->add_item( $item );

	// Only matters if pickup is non-free/taxed; harmless when it's free.
	$order->calculate_totals();
}
add_action( 'woocommerce_checkout_create_order', 'ppqlp_force_local_pickup', 10, 2 );
