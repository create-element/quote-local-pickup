# Changelog

All notable changes to **Quote Local Pickup** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-06-04

Initial public release.

### Added

- Local-pickup checkbox rendered beside the Cart to Quote button on the WooCommerce
  checkout (*"Do not quote for shipping - I will collect from the store"*).
- Automatic attachment of an existing WooCommerce Local Pickup shipping method
  (`local_pickup` or `pickup_location`) to the quote order when the box is ticked.
- Logging (via `wc_get_logger()`) when pickup is requested but no Local Pickup method is
  configured.
- WordPress plugin dependencies: requires WooCommerce and Cart to Quote
  (`cart-to-order-review`) via the `Requires Plugins` header.
- Filters: `ppqlp_params`, `ppqlp_pickup_method_ids`, `ppqlp_local_pickup_shipping_item`.
- Action: `ppqlp_enqueued_assets`.
- Documentation (`README.md`, `docs/`) and a tag-driven release workflow.

### Security

- Pickup logic runs only while Cart to Quote is creating a quote
  (`get_is_creating_a_quote()`), so ordinary WooCommerce orders are never modified.
- The pickup choice is read from the parent plugin's sanitised checkout data, with a
  sanitised `$_POST` fallback; the request nonce is verified upstream by Cart to Quote.

[1.0.0]: https://power-plugins.com/plugin/purchase-orders-cart-to-quote/
