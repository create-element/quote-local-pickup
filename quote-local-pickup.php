<?php
/**
 * Plugin Name:       Quote Local Pickup
 * Plugin URI:        https://power-plugins.com/plugin/purchase-orders-cart-to-quote/
 * Description:       Add support for a "Local pickup" option to the Cart-to-quote plugin (cart-to-order-review)
 * Version:           0.1.0
 * Author:            Power Plugins
 * Author URI:        https://power-plugins.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || die();

const QLP_PLUGIN_NAME    = 'quote-local-pickup';
const QLP_PLUGIN_VERSION = '0.1.0';

define( 'QLP_DIR', plugin_dir_path( __FILE__ ) );
define( 'QLP_URL', plugin_dir_url( __FILE__ ) );
