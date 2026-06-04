# Installation & setup

This guide is for store administrators.

## Requirements

- WordPress with **WooCommerce** active.
- The **Cart to Quote** plugin (`cart-to-order-review`) active and configured.
  Quote Local Pickup does nothing on its own — it extends Cart to Quote.
- At least one WooCommerce shipping zone with a **Local Pickup** method (method id
  `local_pickup` or `pickup_location`).

## Install

1. Copy the `quote-local-pickup` folder into `wp-content/plugins/`.
2. Go to **Plugins** in wp-admin and activate **Quote Local Pickup**.

## Configure a Local Pickup method

The plugin attaches an existing Local Pickup method to the quote — it does not create
one. Make sure one exists:

1. Go to **WooCommerce → Settings → Shipping**.
2. Open a shipping zone (or the "Locations not covered by your other zones" zone).
3. Add a **Local pickup** method and save.

The first matching method found across your zones is used. (Developers can broaden which
method ids count as pickup — see the
[`ppqlp_pickup_method_ids`](hooks.md#ppqlp_pickup_method_ids) filter.)

## How it appears to customers

On the checkout, when Cart to Quote shows its **Request a Quote** button, a checkbox
appears next to it:

> ☐ *Do not quote for shipping - I will collect from the store*

If the customer ticks it and requests a quote, the resulting quote order has the Local
Pickup shipping method attached. If they leave it unticked, the quote is created with no
shipping line, exactly as before.

To change the label wording, see the
[`ppqlp_params`](hooks.md#ppqlp_params) filter.

## Charging for pickup

By default pickup is added as **free**. If you charge a pickup fee, a developer can set
the cost with the
[`ppqlp_local_pickup_shipping_item`](hooks.md#ppqlp_local_pickup_shipping_item) filter.

## See also

- [How it works](how-it-works.md)
- [Troubleshooting](troubleshooting.md)
- [Security](security.md)
