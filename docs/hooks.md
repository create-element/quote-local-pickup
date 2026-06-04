# Hooks reference

Quote Local Pickup exposes the following hooks for developers. Add your code to a
small custom plugin or your theme's `functions.php`.

| Hook | Type | Purpose |
|------|------|---------|
| [`ppqlp_params`](#ppqlp_params) | filter | Change the checkbox label or field name. |
| [`ppqlp_pickup_method_ids`](#ppqlp_pickup_method_ids) | filter | Change which shipping method ids count as "local pickup". |
| [`ppqlp_local_pickup_shipping_item`](#ppqlp_local_pickup_shipping_item) | filter | Adjust the shipping line item (e.g. charge for pickup, add meta). |
| [`ppqlp_enqueued_assets`](#ppqlp_enqueued_assets) | action | Fires after the checkout CSS/JS are enqueued. |

---

## Filters

### `ppqlp_params`

Filters the parameters passed to the front-end before the checkbox is rendered. These
values are JSON-encoded into the container element and read by the checkout script.

**Parameters**

- `array $params`
  - `labelText` *(string)* — the checkbox label shown to the customer.
  - `fieldName` *(string)* — the form field name (default `quote_local_pickup`).
    Changing this only changes the rendered field; the server reads the
    `quote_local_pickup` key, so leave `fieldName` alone unless you know what you're
    doing.

**Example — reword the label**

```php
add_filter(
    'ppqlp_params',
    function ( $params ) {
        $params['labelText'] = __( 'I will collect from the warehouse', 'my-text-domain' );
        return $params;
    }
);
```

---

### `ppqlp_pickup_method_ids`

Filters the list of WooCommerce shipping **method ids** that the plugin treats as local
pickup when searching your shipping zones. Defaults to `local_pickup` and
`pickup_location`. Use this to recognise a custom or third-party pickup method.

**Parameters**

- `string[] $method_ids` — the shipping method ids to match (deduplicated; empty entries
  are dropped).

**Example — add a custom pickup method id**

```php
add_filter(
    'ppqlp_pickup_method_ids',
    function ( $method_ids ) {
        $method_ids[] = 'my_custom_pickup';
        return $method_ids;
    }
);
```

---

### `ppqlp_local_pickup_shipping_item`

Filters the `WC_Order_Item_Shipping` object just before it is added to the quote order.
Use this to charge for pickup, change the title, or attach metadata (e.g. a pickup
location).

**Parameters**

- `WC_Order_Item_Shipping $item` — the shipping line item. By default it has the
  resolved method title, method id and instance id, with zero cost and tax.

**Example — charge a flat pickup fee**

```php
add_filter(
    'ppqlp_local_pickup_shipping_item',
    function ( $item ) {
        $item->set_total( '5.00' );
        return $item;
    }
);
```

**Example — add pickup metadata**

```php
add_filter(
    'ppqlp_local_pickup_shipping_item',
    function ( $item ) {
        $item->add_meta_data( 'pickup_location', 'Main store', true );
        return $item;
    }
);
```

---

## Actions

### `ppqlp_enqueued_assets`

Fires on the checkout page immediately after the plugin enqueues its CSS and JS. Use it
to enqueue your own checkout assets that depend on this plugin's.

**Example**

```php
add_action(
    'ppqlp_enqueued_assets',
    function () {
        wp_enqueue_script(
            'my-pickup-extras',
            plugins_url( 'my-pickup-extras.js', __FILE__ ),
            array( 'ppqlp-checkout' ),
            '1.0.0',
            true
        );
    }
);
```

---

## Related hooks from Cart to Quote

These belong to the parent plugin but are useful context. The checkbox is injected by
listening for the parent's browser event:

- `cto_before_shipping_quote_button` *(PHP action)* — where the checkbox container is
  printed.
- `custom_quote_button_rendered` *(JS `window` event)* — when the front-end script
  injects the checkbox.

## See also

- [How it works](how-it-works.md)
- [Troubleshooting](troubleshooting.md)
