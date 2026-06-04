=== Quote Local Pickup ===
Contributors: powerplugins
Tags: woocommerce, quote, local pickup, cart to quote, shipping
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.0
Requires Plugins: woocommerce, cart-to-order-review
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Let customers choose local pickup when requesting a quote with the Cart to Quote plugin.

== Description ==

Quote Local Pickup is a small add-on for the [Cart to Quote](https://power-plugins.com/plugin/purchase-orders-cart-to-quote/) plugin (`cart-to-order-review`).

Cart to Quote shows its "Request a Quote" button by removing all matched shipping methods. Enabling WooCommerce's native Local Pickup would re-introduce a matched method and make that button disappear.

Quote Local Pickup avoids the conflict: it adds a checkbox next to the quote button — *"Do not quote for shipping - I will collect from the store"* — and, when ticked, attaches your store's Local Pickup shipping method to the quote order in code. It only ever acts while a quote is being created; normal orders are untouched.

= Features =

* Adds a local-pickup checkbox beside the Cart to Quote button on the checkout.
* Attaches your existing WooCommerce Local Pickup method to the quote automatically.
* Recognises the standard `local_pickup` and `pickup_location` methods (extendable by a filter).
* Free pickup by default, with a filter to charge a fee or attach pickup metadata.
* Quote-only: ordinary WooCommerce orders are never modified.

= For developers =

The label, the recognised pickup method ids, and the shipping line item are all filterable. See the documentation for the full hooks reference.

== Installation ==

1. Make sure WooCommerce and the Cart to Quote plugin (`cart-to-order-review`) are installed and active.
2. Upload the `quote-local-pickup` folder to `/wp-content/plugins/`, or install the zip via Plugins → Add New → Upload Plugin.
3. Activate the plugin through the Plugins screen in WordPress.
4. In WooCommerce → Settings → Shipping, make sure at least one shipping zone has a Local Pickup method.

== Frequently Asked Questions ==

= The checkbox doesn't appear =

The checkbox is only shown when Cart to Quote renders its "Request a Quote" button. Confirm that plugin is active and that the button itself is visible.

= The customer ticked the box but the quote has no shipping line =

Make sure a Local Pickup method (`local_pickup` or `pickup_location`) exists in at least one shipping zone. If a pickup is requested but no method is configured, a warning is written to WooCommerce → Status → Logs (source `quote-local-pickup`).

= Can I charge for local pickup? =

Yes. Pickup is free by default, but the `ppqlp_local_pickup_shipping_item` filter lets a developer set a cost or attach metadata.

= Does this affect normal orders? =

No. The plugin only acts while Cart to Quote is creating a quote.

== Changelog ==

= 1.0.0 =
* Initial public release.
