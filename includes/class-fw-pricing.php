<?php
/* ================================================================
   FlexWave – Frontend CSS
   https://flex-wave.nl
   Copyright (c) 2026 FlexWave
   E-mail: jorian@flex-wave.nl
   Alle rechten voorbehouden
   ================================================================ */

if ( ! defined( 'ABSPATH' ) ) exit;

class FW_Pricing {

    public static function init(): void {
        // Valideer verplichte velden vóór toevoegen aan winkelwagen
        add_filter( 'woocommerce_add_to_cart_validation', [ __CLASS__, 'validate' ], 10, 3 );

        // Voeg FlexWave data toe aan cart item
        add_filter( 'woocommerce_add_cart_item_data', [ __CLASS__, 'add_cart_data' ], 10, 2 );

        // Pas de prijs aan in de cart
        add_action( 'woocommerce_before_calculate_totals', [ __CLASS__, 'adjust_cart_price' ], 20 );

        // Toon opties in cart & checkout
        add_filter( 'woocommerce_get_item_data', [ __CLASS__, 'display_cart_data' ], 10, 2 );

        // Zorg dat cart items met andere opties als aparte regels worden behandeld
        add_filter( 'woocommerce_cart_item_key', [ __CLASS__, 'unique_cart_key' ], 10, 3 );

        // Sla opties op als order meta
        add_action( 'woocommerce_checkout_create_order_line_item', [ __CLASS__, 'add_order_meta' ], 10, 3 );
    }

    public static function validate( bool $valid, int $product_id, int $qty ): bool {
        if ( ! $valid ) return false;

        $groups = FW_ProductMeta::get_active_groups( $product_id );
        foreach ( $groups as $group ) {
            if ( ! $group['required'] ) continue;

            if ( $group['type'] === 'text' ) {
                $key = 'fw_text_' . $product_id . '_g' . $group['id'];
                if ( empty( trim( $_POST[ $key ] ?? '' ) ) ) {
                    wc_add_notice(
                        sprintf( __( '"%s" is een verplicht veld.', 'flexwave' ), $group['name'] ),
                        'error'
                    );
                    $valid = false;
                }
            } else {
                $key = 'fw_' . $product_id . '_g' . $group['id'];
                $selections = json_decode( sanitize_text_field( wp_unslash( $_POST['fw_selections'] ?? '' ) ), true );
                $found      = false;
                if ( is_array( $selections ) ) {
                    foreach ( $selections as $sel ) {
                        if ( (int) ( $sel['group_id'] ?? 0 ) === $group['id'] ) {
                            $found = true;
                            break;
                        }
                    }
                }
                if ( ! $found ) {
                    wc_add_notice(
                        sprintf( __( 'Maak een keuze voor "%s".', 'flexwave' ), $group['name'] ),
                        'error'
                    );
                    $valid = false;
                }
            }
        }
        return $valid;
    }

    public static function add_cart_data( array $data, int $product_id ): array {
        $selections_raw = sanitize_text_field( wp_unslash( $_POST['fw_selections'] ?? '' ) );
        $selections     = $selections_raw ? json_decode( $selections_raw, true ) : [];
        if ( ! is_array( $selections ) ) $selections = [];

        $groups = FW_ProductMeta::get_active_groups( $product_id );
        foreach ( $groups as $group ) {
            if ( $group['type'] !== 'text' ) continue;
            $key   = 'fw_text_' . $product_id . '_g' . $group['id'];
            $value = sanitize_text_field( wp_unslash( $_POST[ $key ] ?? '' ) );
            if ( $value === '' ) continue;
            $v     = $group['variations'][0] ?? [];
            $price = (float) ( $v['price'] ?? 0 );
            $selections[] = [
                'group_id'   => $group['id'],
                'group_name' => $group['name'],
                'type'       => 'text',
                'label'      => $value,
                'price'      => $price,
            ];
        }

        if ( ! empty( $selections ) ) {
            $extra            = array_sum( array_column( $selections, 'price' ) );
            $data['fw_data']  = $selections;
            $data['fw_extra'] = $extra;
        }

        return $data;
    }

    public static function adjust_cart_price( WC_Cart $cart ): void {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

        foreach ( $cart->get_cart() as $item ) {
            if ( empty( $item['fw_extra'] ) ) continue;

            $base  = (float) $item['data']->get_price( 'edit' );
            $extra = (float) $item['fw_extra'];
            $item['data']->set_price( $base + $extra );
        }
    }

    public static function display_cart_data( array $item_data, array $cart_item ): array {
        if ( empty( $cart_item['fw_data'] ) ) return $item_data;

        foreach ( $cart_item['fw_data'] as $sel ) {
            $price_str = ( (float) $sel['price'] ) > 0
                ? ' (+ ' . get_woocommerce_currency_symbol() . number_format( (float) $sel['price'], 2, ',', '.' ) . ')'
                : '';
            $item_data[] = [
                'name'  => esc_html( $sel['group_name'] ),
                'value' => esc_html( $sel['label'] ) . $price_str,
            ];
        }
        return $item_data;
    }

    public static function unique_cart_key( string $key, array $cart_item, array $extra ): string {
        if ( ! empty( $extra['fw_data'] ) ) {
            $key .= '_fw_' . md5( wp_json_encode( $extra['fw_data'] ) );
        }
        return $key;
    }

    public static function add_order_meta(
        WC_Order_Item_Product $item,
        string $cart_item_key,
        array $values
    ): void {
        if ( empty( $values['fw_data'] ) ) return;

        foreach ( $values['fw_data'] as $sel ) {
            $price_str = ( (float) $sel['price'] ) > 0
                ? ' (+ ' . get_woocommerce_currency_symbol() . number_format( (float) $sel['price'], 2, ',', '.' ) . ')'
                : '';
            $item->add_meta_data(
                esc_html( $sel['group_name'] ),
                esc_html( $sel['label'] ) . $price_str,
                false
            );
        }

        if ( ! empty( $values['fw_extra'] ) ) {
            $item->add_meta_data(
                __( 'meerprijs', 'flexwave' ),
                get_woocommerce_currency_symbol() . number_format( (float) $values['fw_extra'], 2, ',', '.' ),
                false
            );
        }
    }
}
