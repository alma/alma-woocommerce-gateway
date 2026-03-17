# Checkout Gateway Ordering

## Problem

WooCommerce uses a single admin settings page (**WooCommerce > Settings > Payments**) where the merchant reorders payment gateways via drag-and-drop. The order is persisted in the `woocommerce_gateway_order` option.

However, the Alma plugin registers **different gateway IDs on admin vs frontend**:

| Context  | Gateway ID(s)                                                                  |
|----------|--------------------------------------------------------------------------------|
| Admin    | `alma_config_gateway` (single entry for plugin configuration)                  |
| Frontend | `alma_pay_now_gateway`, `alma_pnx_gateway`, `alma_pay_later_gateway`, `alma_credit_gateway` |

Because the frontend IDs are never present in the saved `woocommerce_gateway_order`, WooCommerce's `sort_gateways()` assigns them an order of `999+`, pushing Alma to the bottom of the checkout — regardless of where the merchant placed `alma_config_gateway` in the admin.

## Solution

Two filters work together in `FrontendHelper::loadFrontendGateways()`:

### 1. Sync ordering — `option_woocommerce_gateway_order`

This filter intercepts the read of the `woocommerce_gateway_order` option and injects the frontend gateway IDs with the **same order value** as `alma_config_gateway`. This way, `sort_gateways()` places them at the position the merchant chose in the admin.

```php
public static function syncGatewayOrder( $ordering ): array {
    // ...
    $base_order = isset( $ordering[ self::$config_gateway_id ] )
        ? absint( $ordering[ self::$config_gateway_id ] )
        : 0;

    foreach ( self::$alma_gateway_ids as $id ) {
        $ordering[ $id ] = $base_order;
    }

    return $ordering;
}
```

- If the merchant has never reordered gateways (option empty), `$base_order` defaults to `0` → Alma appears first.
- If the merchant placed `alma_config_gateway` at position 3, all frontend gateways get order `3` → they appear at that position among other payment methods.

### 2. Fix relative order — `woocommerce_available_payment_gateways`

After `sort_gateways()`, all Alma gateways share the same `->order` value. PHP's `uasort` is **not stable before PHP 8.0**, so their relative order is undefined.

This filter extracts all Alma gateways, then re-inserts them grouped together in the desired order (Pay Now → Pnx → Pay Later → Credit) at the position of the first one found.

```php
public static function sortAlmaGateways( array $gateways ): array {
    // Collect present Alma gateways in desired relative order.
    $alma_gateways = [];
    foreach ( self::$alma_gateway_ids as $id ) {
        if ( isset( $gateways[ $id ] ) ) {
            $alma_gateways[ $id ] = $gateways[ $id ];
        }
    }
    // Re-insert grouped at the position of the first Alma gateway found.
    // ...
}
```

> **Note:** This filter can be removed once PHP 7.4 support is dropped, since `uasort` is stable from PHP 8.0 onwards.

## Design choices

- **Static IDs, no container calls in filters.** Gateway IDs are computed once in `initGatewayIds()` using class constants (`PayNowGateway::PAYMENT_METHOD`, etc.) and `sprintf`. The ordering filters never resolve the DI container, avoiding unnecessary gateway instantiation on every checkout page load.
- **Named static methods instead of closures.** Each filter callback (`registerGateways`, `syncGatewayOrder`, `sortAlmaGateways`) is a named public static method, making the code easier to read and test.
- **`option_*` filter over `update_option`.** We filter the *read* of the option, not the *write*. The merchant's saved ordering is never modified in the database — our changes only affect the in-memory value used by `sort_gateways()`.

