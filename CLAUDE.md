# CLAUDE.md — Quote Local Pickup

Guidance for working in this plugin. Read this first.

## What this plugin is

**Quote Local Pickup** (`quote-local-pickup`) is a small companion/add-on plugin for
the **Cart to Quote** plugin (`cart-to-order-review`, internal name `PP_CTO`, vendor
"Power Plugins"). It lets a customer say "don't quote me for shipping — I'll collect
from the store" while requesting a quote, by rendering a checkbox next to the
"Request a Quote" button on the WooCommerce checkout. When the quote order is created,
this plugin programmatically attaches the store's Local Pickup shipping method to the
order.

- Prefix for all symbols: `PPQLP_` (constants), `ppqlp_` (functions).
- Single-file plugin (`quote-local-pickup.php`) plus one JS and one CSS asset.
- Current state: tidied (v0.4.0), preparing for a `1.0.0` release.

## Why it exists (the core problem)

Cart to Quote "forces" the quote stage by **squashing shipping packages**
(`PP_Cart_To_Order\Plugin::woocommerce_shipping_packages()` in
`cart-to-order-review/includes/class-plugin.php`). When packages are squashed there are
**no matched shipping methods**, which is what makes the "Request a Quote" button appear.

If you enabled WooCommerce's built-in **Local Pickup** the normal way, it would create a
matched shipping method, which would make the Request-a-Quote button **disappear**. So
instead of using Woo's native selection, this plugin:

1. Adds its own checkbox next to the quote button (does not affect package squashing).
2. Reads that checkbox when the quote order is created.
3. Looks up a Local Pickup shipping method instance and attaches it to the order
   programmatically.

## Files

| File | Purpose |
|------|---------|
| `quote-local-pickup.php` | Everything: constants, checkbox renderer, asset enqueue, pickup-method lookup, order mutation. |
| `quote-local-pickup-checkout.js` | Builds the checkbox `<input>`+`<label>` into the container on the `custom_quote_button_rendered` window event. |
| `quote-local-pickup-checkout.css` | Minor spacing for the checkbox/label. |

## How it integrates with `cart-to-order-review`

The parent plugin lives at
`/var/www/devx.headwall.tech/web/wp-content/plugins/cart-to-order-review/`.

### Hooks this plugin consumes (provided by the parent)

- **`cto_before_shipping_quote_button`** (PHP action) — fired in
  `cart-to-order-review/public-templates/checkout/request-a-quote.php`. We hook
  `ppqlp_quote_button_extras()` here to print the `[data-quote-local-pickup]`
  container (carrying a JSON `data-` payload of `labelText` + `fieldName`).
- **`custom_quote_button_rendered`** (JS window event) — triggered in
  `cart-to-order-review/assets/pp-cto-checkout.js`. Our JS listens for it and injects
  the checkbox/label into the container.
- **`woocommerce_checkout_create_order`** (WC action, `$order`, `$data`) — re-fired by
  the parent's quote flow inside `Plugin::request_a_quote()`
  (`includes/class-plugin.php:1234`). We hook `ppqlp_force_local_pickup()` here to
  attach the pickup method. **Caveat: this action also fires on every normal
  WooCommerce checkout** (see "Quote detection" below).

### Hooks/filters this plugin provides (for the store/theme)

- `ppqlp_params` (filter) — adjust `labelText` / `fieldName` before render.
- `ppqlp_pickup_method_ids` (filter) — customise which shipping method ids count as local
  pickup (default `local_pickup`, `pickup_location`).
- `ppqlp_enqueued_assets` (action) — after assets are enqueued.
- `ppqlp_local_pickup_shipping_item` (filter) — mutate the `WC_Order_Item_Shipping`
  before it's added (e.g. to charge for pickup or add meta).

Full developer/admin docs live in `docs/` (`how-it-works.md`, `installation.md`,
`hooks.md`, `troubleshooting.md`, `security.md`); `README.md` is the lean entry point that
links into them.

## Critical data flow (read before changing the checkbox handling)

The checkbox is rendered inside `form[name="checkout"]`. The parent's quote button does
**not** do a normal form POST — `convertToQuote()` in `pp-cto-checkout.js` serializes
the checkout form into a **nested** AJAX payload:

```
request.fields = getFormFields()   // every [name] in form.checkout
// checkboxes become 'yes' / 'no'
```

So on the wire the value arrives as **`$_POST['fields']['quote_local_pickup']`**, not
top-level `$_POST['quote_local_pickup']`.

Server-side, `Plugin::request_a_quote()` then merges `$_POST['fields']` (and
`$_POST['additional']`) into the `$data` array (`class-plugin.php:1069-1089`,
each value run through `sanitize_text_field()`), and passes that `$data` as the 2nd
argument to `woocommerce_checkout_create_order`. **Therefore the reliable place to read
the checkbox is `$data[ PPQLP_FIELD_NAME ]`, NOT top-level `$_POST`.** The current draft
reads top-level `$_POST`, which is a known issue (see below).

## Quote detection (the parent's "am I making a quote?" signal)

`woocommerce_checkout_create_order` fires for **both** normal orders and quotes. To run
pickup logic **only for quotes**, use the parent's signal:

```php
global $pp_cto_plugin;                       // set in cart-to-order-review.php
$pp_cto_plugin->get_is_creating_a_quote();   // true only inside request_a_quote()
```

`is_creating_a_quote` is set `true` at the top of `Plugin::request_a_quote()`
(`class-plugin.php:771`). The parent also fires `cto_before_request_a_quote` at the
start of that flow. The quote meta `META_IS_A_QUOTE` (`'_is_a_quote'`) is **not yet set
on the order** when `woocommerce_checkout_create_order` runs (it's added at line 1236,
after the action), so don't rely on order meta inside this hook.

## Useful parent constants (namespace `PP_Cart_To_Order`, in `constants.php`)

- `ORDER_STATUS_REVIEW = 'wc-internal-review'` — the quote status.
- `META_IS_A_QUOTE = '_is_a_quote'`.
- `OPT_CREATED_VIA = 'wc_pp_cto_created_via'`.

## Status

Shipped in **1.0.0** (tag `v1.0.0`; the tag builds the zips via
`.github/workflows/release.yml`):

- Reads the checkbox from the sanitized `$data` argument (with `$_POST` fallback) instead
  of top-level `$_POST` — verified live with quotes #14479 / #14480.
- Quote-only guard via `$pp_cto_plugin->get_is_creating_a_quote()`.
- `$order instanceof WC_Order` guard.
- Debug `error_log`/`wp_mail` noise removed; misconfig now logged via `wc_get_logger()`.
- Own text domain `quote-local-pickup`; jQuery declared as a script dependency.
- Public hooks standardised on the `ppqlp_` prefix (`ppqlp_params`,
  `ppqlp_pickup_method_ids`, `ppqlp_local_pickup_shipping_item`, `ppqlp_enqueued_assets`).
- PHPDoc on all functions/hooks; passes `phpcs` (WordPress standard, see `phpcs.xml`).
- Dependencies enforced via the `Requires Plugins` header (WooCommerce, cart-to-order-review).
- `/languages` (POT + bundled `.po`/`.mo`) with a named textdomain loader.
- Docs: `README.md`, `docs/` (incl. `security.md`), `readme.txt`, `CHANGELOG.md`, `LICENSE`.

Watch-outs / not-yet-done:

- `LICENSE` currently holds the short-form GPLv2 (preamble + warranty notice), not the
  full terms text.
- `readme.txt` `Tested up to:` is a best-guess value to confirm before any public listing
  (`Contributors: powerplugins` is correct — it's Power Plugins' wordpress.org slug).

## Conventions

- Match the parent's WordPress/WooCommerce coding style (Yoda-ish, tabs, `esc_*` on
  output, text-domain on user strings).
- Keep symbols under the `PPQLP_` / `ppqlp_` prefix.
- Don't reintroduce native Local Pickup selection — it breaks the quote button by
  un-squashing packages. Attach the method programmatically instead.
