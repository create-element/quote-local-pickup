# Quote Local Pickup

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WooCommerce](https://img.shields.io/badge/WooCommerce-required-96588a.svg)
![Requires](https://img.shields.io/badge/requires-Cart%20to%20Quote-orange.svg)
![License](https://img.shields.io/badge/license-GPLv2%2B-green.svg)

An add-on for the [**Cart to Quote**](https://power-plugins.com/plugin/purchase-orders-cart-to-quote/)
plugin that lets customers choose **local pickup** when requesting a quote.

## What it does

Cart to Quote shows its *"Request a Quote"* button by removing all matched shipping
methods. Enabling WooCommerce's native Local Pickup would re-introduce a matched method
and make that button disappear.

Quote Local Pickup avoids the conflict: it adds a small checkbox next to the quote
button — *"Do not quote for shipping - I will collect from the store"* — and, when
ticked, attaches your store's Local Pickup shipping method to the quote order in code.
It only ever acts while a quote is being created; normal orders are untouched.

## Who it's for

- **Store admins** running Cart to Quote who want to offer collection-in-store on quotes.
- **Developers** extending the quote checkout — the label, the recognised pickup
  methods, and the shipping line item are all filterable.

Requires WooCommerce and the Cart to Quote plugin (`cart-to-order-review`).

## Documentation

- [How it works](docs/how-it-works.md) — the squashed-packages problem and the full flow.
- [Installation & setup](docs/installation.md) — requirements and configuring Local Pickup.
- [Hooks reference](docs/hooks.md) — filters and actions, with examples.
- [Troubleshooting](docs/troubleshooting.md) — common issues and fixes.
- [Security](docs/security.md) — input/output handling and trust model.

## License

GPLv2 or later. See [LICENSE](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html).
