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

This filter intercepts the read of the `woocommerce_gateway_order` option and injects the frontend gateway IDs at the **same position** as `alma_config_gateway`, giving each one a **unique incremental order value** to avoid collisions with other gateways.

The key steps are:

1. **Remove stale Alma entries** — any leftover frontend gateway IDs are cleaned up first.
2. **Shift existing gateways** — all non-Alma gateways with an order `>= base_order` are shifted by `+N` (where N = number of Alma gateways) to make room.
3. **Insert Alma gateways** — each frontend gateway gets a unique order value: `base_order`, `base_order+1`, `base_order+2`, etc.

```php
public static function syncGatewayOrder( $ordering ): array {
    // Remove any existing Alma frontend gateway entries to avoid duplicates.
    foreach ( self::$alma_gateway_ids as $id ) {
        unset( $ordering[ $id ] );
    }

    $base_order = isset( $ordering[ self::$config_gateway_id ] )
        ? absint( $ordering[ self::$config_gateway_id ] )
        : 0;

    $alma_count = count( self::$alma_gateway_ids );

    // Shift all non-Alma gateways with order >= base_order to make room.
    foreach ( $ordering as $id => $order ) {
        if ( $id !== self::$config_gateway_id && absint( $order ) >= $base_order ) {
            $ordering[ $id ] = absint( $order ) + $alma_count;
        }
    }

    // Insert Alma frontend gateways at the base position.
    $ordering += array_combine(
        self::$alma_gateway_ids,
        range( $base_order, $base_order + $alma_count - 1 )
    );

    return $ordering;
}
```

**Example:** if the merchant placed `alma_config_gateway` at position 2 with `paypal` at 0, `stripe` at 1, and `cod` at 3:

| Gateway              | Before | After |
|----------------------|--------|-------|
| `paypal`             | 0      | 0     |
| `stripe`             | 1      | 1     |
| `alma_pay_now`       | —      | 2     |
| `alma_pnx`           | —      | 3     |
| `alma_pay_later`     | —      | 4     |
| `alma_credit`        | —      | 5     |
| `cod`                | 3      | 7     |

### 2. Fix relative order — `woocommerce_available_payment_gateways`

After `sort_gateways()`, PHP's `uasort` is **not stable before PHP 8.0**, so the relative order among Alma gateways is undefined.

This filter extracts all Alma gateways, then re-inserts them grouped together in the desired order (Pay Now → Pnx → Pay Later → Credit) at the position of the first one found.

```php
public static function sortAlmaGateways( array $gateways ): array {
    $alma_gateways = [];
    foreach ( self::$alma_gateway_ids as $id ) {
        if ( isset( $gateways[ $id ] ) ) {
            $alma_gateways[ $id ] = $gateways[ $id ];
        }
    }

    $result        = [];
    $alma_inserted = false;

    foreach ( $gateways as $id => $gateway ) {
        if ( isset( $alma_gateways[ $id ] ) ) {
            if ( ! $alma_inserted ) {
                $result       += $alma_gateways;
                $alma_inserted = true;
            }
            continue;
        }
        $result[ $id ] = $gateway;
    }

    return $result;
}
```

> **Note:** This filter can be removed once PHP 7.4 support is dropped, since `uasort` is stable from PHP 8.0 onwards.

## Why unique order values matter

The initial implementation assigned the **same** `base_order` value to all Alma gateways. This caused two issues:

1. **Collision with other gateways** — if another gateway already had order `base_order`, WooCommerce would only display one of the two, hiding the other.
2. **Collision between Alma gateways** — with identical order values, WooCommerce could drop or misorder some Alma gateways.

The fix shifts existing gateways to create a contiguous block of unique positions for Alma, ensuring no two gateways share the same order value.

## Design choices

- **Static IDs, no container calls in filters.** Gateway IDs are computed once in `initGatewayIds()` using class constants (`PayNowGateway::PAYMENT_METHOD`, etc.) and `sprintf`. The ordering filters never resolve the DI container, avoiding unnecessary gateway instantiation on every checkout page load.
- **Named static methods instead of closures.** Each filter callback (`registerGateways`, `syncGatewayOrder`, `sortAlmaGateways`) is a named public static method, making the code easier to read and test.
- **`option_*` filter over `update_option`.** We filter the *read* of the option, not the *write*. The merchant's saved ordering is never modified in the database — our changes only affect the in-memory value used by `sort_gateways()`.
- **`array_combine` + `range` for insertion.** Instead of a manual loop with an offset counter, the Alma gateways are inserted using `array_combine(ids, range(base, base+N-1))`, which is more declarative and concise.
- **`$result += $alma_gateways` for grouping.** The `+` operator on PHP arrays merges without overwriting existing keys, replacing the need for an inner `foreach` loop.
