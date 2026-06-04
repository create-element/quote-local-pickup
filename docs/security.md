# Security

This document summarises the security posture of Quote Local Pickup. The plugin is
small and deliberately narrow in scope; this is a record of how it handles untrusted
input, output, and authorisation.

## Scope & trust model

The plugin does one thing: when Cart to Quote creates a quote and the customer ticked the
local-pickup checkbox, it attaches a Local Pickup shipping line to that quote order.

- It registers **no AJAX handlers, REST routes, admin pages, or shortcodes** of its own.
- It runs entirely inside flows owned by WooCommerce and Cart to Quote.
- It performs **no database queries, no file I/O, and no outbound HTTP requests**.

Because it adds no new entry points, its attack surface is limited to the data it reads
during checkout and the markup it prints on the checkout page.

## Authorisation

`ppqlp_force_local_pickup()` is hooked to `woocommerce_checkout_create_order`, which fires
for every order. Before doing anything it confirms a quote is genuinely being created:

```php
global $pp_cto_plugin;
if ( ! is_object( $pp_cto_plugin )
    || ! method_exists( $pp_cto_plugin, 'get_is_creating_a_quote' )
    || ! $pp_cto_plugin->get_is_creating_a_quote() ) {
    return;
}
```

`get_is_creating_a_quote()` is only true inside Cart to Quote's `request_a_quote()`
handler, which itself verifies the request nonce and the user's eligibility to request a
quote. The plugin therefore inherits the parent's authorisation and capability checks
rather than re-implementing (or weakening) them, and it never modifies a normal order.

## Input handling

The only untrusted input is the pickup checkbox value:

- **Primary source.** It is read from the `$data` array passed to
  `woocommerce_checkout_create_order`. Cart to Quote builds that array from the request
  and runs every value through `sanitize_text_field()` before the hook fires, so the
  value is already sanitised when we receive it.
- **Fallback source.** If the key is absent from `$data`, the plugin reads
  `$_POST[ PPQLP_FIELD_NAME ]` directly and sanitises it inline with
  `sanitize_text_field( wp_unslash( ... ) )`.
- The value is only ever compared for strict equality with the string `'yes'`; it is
  never echoed, concatenated into a query, or used as a callback/path.

### Nonce verification

The request nonce is verified **upstream** by Cart to Quote's `request_a_quote()` before
`woocommerce_checkout_create_order` runs. The `$_POST` fallback read therefore carries a
`phpcs:ignore WordPress.Security.NonceVerification.Missing` annotation documenting that
the nonce is checked by the parent flow and that the value is sanitised locally.

### Object validation

The order argument can be a `WC_Error` on failure, so the plugin bails unless it is a
real order before mutating it:

```php
if ( ! ( $order instanceof WC_Order ) ) {
    return;
}
```

## Output handling

The plugin prints one element on the checkout, and everything in it is escaped:

```php
printf(
    '<div class="quote-local-pickup" data-quote-local-pickup="%s"></div>',
    esc_attr( wp_json_encode( $params ) )
);
```

- The JSON payload is escaped with `esc_attr()` for the attribute context.
- The customer-facing label is escaped at source with `esc_html__()`.
- The checkbox itself is built client-side from the JSON payload; values are assigned via
  jQuery `.attr()`/`.text()` (no `innerHTML`), so the label cannot inject markup.

## Logging

When pickup is requested but no Local Pickup method is configured, a warning is written
via `wc_get_logger()`. The message is a fixed string and contains **no customer data**.

## Extension points

The plugin exposes filters (see [hooks.md](hooks.md)). The
`ppqlp_local_pickup_shipping_item` filter hands the `WC_Order_Item_Shipping` object to
third-party code; any data a site adds through that filter (e.g. a pickup fee or
metadata) is the responsibility of that code. The plugin adds no untrusted data to the
item itself.

## Dependencies

Quote Local Pickup requires WooCommerce and Cart to Quote (`cart-to-order-review`),
declared via the `Requires Plugins` header so WordPress will not activate it without
them. Keeping those plugins (and WordPress core) up to date is part of the security
baseline.

## Reporting a vulnerability

Please report suspected security issues privately to Power Plugins via
<https://power-plugins.com/> rather than opening a public issue.

## See also

- [How it works](how-it-works.md)
- [Hooks reference](hooks.md)
- [Troubleshooting](troubleshooting.md)
