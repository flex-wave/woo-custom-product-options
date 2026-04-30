<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class FlexWave_Pricing {
    public static function init(): void {
        add_filter( 'woocommerce_add_to_cart_validation', [ __CLASS__, 'validate' ], 10, 3 );
        add_filter( 'woocommerce_add_cart_item_data', [ __CLASS__, 'add_cart_data' ], 10, 2 );
        add_action( 'woocommerce_before_calculate_totals', [ __CLASS__, 'adjust_cart_price' ], 20 );
        add_filter( 'woocommerce_get_item_data', [ __CLASS__, 'display_cart_data' ], 10, 2 );
        add_filter( 'woocommerce_cart_item_key', [ __CLASS__, 'unique_cart_key' ], 10, 3 );
        add_action( 'woocommerce_checkout_create_order_line_item', [ __CLASS__, 'add_order_meta' ], 10, 3 );
        add_action( 'wp_ajax_flexwave_get_price', [ __CLASS__, 'ajax_get_price' ] );
        add_action( 'wp_ajax_nopriv_flexwave_get_price', [ __CLASS__, 'ajax_get_price' ] );
    }
    public static function validate( bool $valid, int $product_id, int $qty ): bool {
        if ( ! $valid ) return false;
        $groups = FlexWave_ProductMeta::get_active_groups( $product_id );
        foreach ( $groups as $group ) {
            if ( ! $group['required'] ) continue;
            if ( $group['type'] === 'text' ) {
                $key = 'flexwave_text_' . $product_id . '_g' . $group['id'];
                if ( empty( trim( $_POST[ $key ] ?? '' ) ) ) {
                    wc_add_notice(
                        sprintf( __( '"%s" is een verplicht veld.', 'flexwave' ), $group['name'] ),
                        'error'
                    );
                    $valid = false;
                }
            } else {
                $key = 'flexwave_' . $product_id . '_g' . $group['id'];
                $selections = json_decode( sanitize_text_field( wp_unslash( $_POST['flexwave_selections'] ?? '' ) ), true );
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
        $selections_raw = sanitize_text_field( wp_unslash( $_POST['flexwave_selections'] ?? '' ) );
        $selections     = $selections_raw ? json_decode( $selections_raw, true ) : [];
        if ( ! is_array( $selections ) ) $selections = [];
        $groups = FlexWave_ProductMeta::get_active_groups( $product_id );
        foreach ( $groups as $group ) {
            if ( $group['type'] === 'text' ) {
                $key   = 'flexwave_text_' . $product_id . '_g' . $group['id'];
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
            } elseif ( $group['type'] === 'dimensions' ) {
                $key_l = 'flexwave_diml_' . $product_id . '_' . $group['id'];
                $key_b = 'flexwave_dimb_' . $product_id . '_' . $group['id'];
                // ...rest van de code...
            }
        }
        return $data;
    }
    // ...rest van de code, alle FW_ vervangen door FLEXWAVE_OPTIONS_, FW_Pricing door FlexWave_Pricing, FW_ProductMeta door FlexWave_ProductMeta...
}
