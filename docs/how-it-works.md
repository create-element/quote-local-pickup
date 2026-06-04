# How it works

Quote Local Pickup is a small add-on for the **Cart to Quote** plugin
(`cart-to-order-review`). It lets a customer say *"don't quote me for shipping — I'll
collect from the store"* while requesting a quote, and then attaches your store's Local
Pickup shipping method to the resulting quote order automatically.

## The problem it solves

Cart to Quote shows its **"Request a Quote"** button by *squashing* the WooCommerce
shipping packages so that no shipping method matches. No matched method is the signal
that a quote is needed.

If you enabled WooCommerce's built-in **Local Pickup** in the usual way, it would create
a matching shipping method — and that would make the Request-a-Quote button **disappear**.

Quote Local Pickup sidesteps this by **not** using Woo's native method selection.
Instead it:

1. Renders a checkbox next to the quote button (this does not affect package squashing).
2. Reads that checkbox when the quote order is created.
3. Looks up a Local Pickup shipping method and attaches it to the order in code.

## The flow, step by step

1. **Checkbox container (PHP).** On the parent's `cto_before_shipping_quote_button`
   action, the plugin prints an empty container element carrying a JSON payload
   (the label text and field name).
2. **Checkbox injection (JS).** When the parent fires the `custom_quote_button_rendered`
   browser event, the plugin's script builds the `<input type="checkbox">` and `<label>`
   inside that container.
3. **Submission.** The customer ticks the box and clicks *Request a Quote*. The parent
   plugin serialises the checkout form (the checkbox included) and sends it as an AJAX
   request.
4. **Order creation (PHP).** During quote creation the parent fires
   `woocommerce_checkout_create_order`. The plugin's handler checks that a quote is
   genuinely being created, reads the checkbox value, finds a Local Pickup method, and
   adds it to the order as a shipping line item.

## Why it only affects quotes

`woocommerce_checkout_create_order` also fires for **every normal WooCommerce order**.
To avoid touching ordinary checkouts, the handler only proceeds when the parent plugin
reports that it is mid-quote, via its global `$pp_cto_plugin->get_is_creating_a_quote()`
method. On a normal order the handler returns immediately.

## Pricing

Local pickup is treated as free by default — the shipping line item is added with no
cost or tax. If your store charges for pickup, use the
[`ppqlp_local_pickup_shipping_item`](hooks.md#ppqlp_local_pickup_shipping_item) filter to
set the total/taxes on the item.

## See also

- [Installation & setup](installation.md)
- [Hooks reference](hooks.md)
- [Troubleshooting](troubleshooting.md)
- [Security](security.md)
