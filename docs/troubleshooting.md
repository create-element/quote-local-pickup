# Troubleshooting

## The checkbox doesn't appear next to the quote button

- Confirm **Cart to Quote** (`cart-to-order-review`) is active and that the
  *Request a Quote* button itself is showing. The checkbox is only injected when the
  parent renders that button.
- The checkbox is added by JavaScript on the checkout. Check the browser console for
  errors and make sure the plugin's `quote-local-pickup-checkout.js` is loading.
- Some caching/minification plugins can interfere with the parent's
  `custom_quote_button_rendered` event. Try clearing caches.

## The customer ticked the box but the quote has no shipping line

- Make sure a **Local Pickup** method (`local_pickup` or `pickup_location`) exists in at
  least one WooCommerce shipping zone. See [Installation](installation.md#configure-a-local-pickup-method).
- If you use a custom pickup method, register its id with the
  [`ppqlp_pickup_method_ids`](hooks.md#ppqlp_pickup_method_ids) filter.
- When pickup is requested but no method is configured, the plugin writes a warning to
  the WooCommerce logs: **WooCommerce → Status → Logs**, source `quote-local-pickup`.

## Local pickup is added to normal orders too

This shouldn't happen — the plugin only acts while Cart to Quote is creating a quote
(it checks `$pp_cto_plugin->get_is_creating_a_quote()`). If you see pickup on a regular
order, confirm you're running a current version of both plugins and that no other code is
calling `ppqlp_force_local_pickup()` directly.

## I want to charge for pickup

Pickup is free by default. Use the
[`ppqlp_local_pickup_shipping_item`](hooks.md#ppqlp_local_pickup_shipping_item) filter to
set a cost.

## See also

- [How it works](how-it-works.md)
- [Installation & setup](installation.md)
- [Hooks reference](hooks.md)
- [Security](security.md)
