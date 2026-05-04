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

        add_action( 'wp_ajax_fw_get_price', [ __CLASS__, 'ajax_get_price' ] );
        add_action( 'wp_ajax_nopriv_fw_get_price', [ __CLASS__, 'ajax_get_price' ] );
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
            if ( $group['type'] === 'text' ) {
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
            } elseif ( $group['type'] === 'dimensions' ) {
                // Lengte
                $key_l = 'fw_diml_' . $product_id . '_' . $group['id'];
                $key_b = 'fw_dimb_' . $product_id . '_' . $group['id'];
                $value_l = isset($_POST[$key_l]) ? floatval($_POST[$key_l]) : '';
                $value_b = isset($_POST[$key_b]) ? floatval($_POST[$key_b]) : '';
                $v_l = $group['variations'][0] ?? [];
                $v_b = $group['variations'][1] ?? [];
                $price_l = (float) ( $v_l['price'] ?? 0 );
                $price_b = (float) ( $v_b['price'] ?? 0 );
                $min_l = isset($v_l['min']) ? floatval($v_l['min']) : 0;
                $min_b = isset($v_b['min']) ? floatval($v_b['min']) : 0;
                $step_l = isset($v_l['step']) && $v_l['step'] > 0 ? floatval($v_l['step']) : 1;
                $step_b = isset($v_b['step']) && $v_b['step'] > 0 ? floatval($v_b['step']) : 1;
                $steps_l = ($value_l > $min_l) ? floor(($value_l - $min_l) / $step_l) : 0;
                $steps_b = ($value_b > $min_b) ? floor(($value_b - $min_b) / $step_b) : 0;
                if ($value_l !== '') {
                    $selections[] = [
                        'group_id'   => $group['id'],
                        'group_name' => $group['name'] . ' - Lengte',
                        'type'       => 'dimension_length',
                        'label'      => $value_l . ($v_l['label'] ? ' ' . $v_l['label'] : ''),
                        'price'      => $price_l * $steps_l,
                        'raw_value'  => $value_l,
                        'unit_price' => $price_l,
                        'steps'      => $steps_l,
                        'min'        => $min_l,
                        'step_size'  => $step_l,
                    ];
                }
                if ($value_b !== '') {
                    $selections[] = [
                        'group_id'   => $group['id'],
                        'group_name' => $group['name'] . ' - Breedte',
                        'type'       => 'dimension_width',
                        'label'      => $value_b . ($v_b['label'] ? ' ' . $v_b['label'] : ''),
                        'price'      => $price_b * $steps_b,
                        'raw_value'  => $value_b,
                        'unit_price' => $price_b,
                        'steps'      => $steps_b,
                        'min'        => $min_b,
                        'step_size'  => $step_b,
                    ];
                }
                // Zet als cart meta voor WooCommerce shipping
                if ($value_l > 0) {
                    $data['length'] = $value_l;
                }
                if ($value_b > 0) {
                    $data['width'] = $value_b;
                }
            } elseif ( $group['type'] === 'length' ) {
                $name = 'fw_length_' . $product_id . '_' . $group['id'];
                $v = $group['variations'][0] ?? [];
                $lengths = $v['lengths'] ?? [];
                usort($lengths, fn($a, $b) => floatval($a['value']) <=> floatval($b['value']));
                $allow_custom = ($v['custom'] ?? '') === '1';
                $min = isset($v['min']) && $v['min'] > 0 ? floatval($v['min']) : (count($lengths) ? min(array_column($lengths, 'value')) : 0);
                $max = isset($v['max']) && $v['max'] > 0 ? floatval($v['max']) : (count($lengths) ? max(array_column($lengths, 'value')) : 0);
                $step = isset($v['step']) ? floatval($v['step']) : 1;
                $type = sanitize_text_field($_POST[$name . '_type'] ?? '');
                // Let op: gebruiker vult in cm in, wij rekenen om naar mm (float)
                $custom_raw = $_POST[$name . '_custom'] ?? null;
                $custom_length = null;
                if ($custom_raw !== null && $custom_raw !== '') {
                    $custom_length = floatval(str_replace(',', '.', $custom_raw)) * 10;
                }
                $used_length = null;
                $used_price = null;
                // Helper voor nette cm-weergave
                function fw_format_cm($mm) {
                    $cm = $mm / 10;
                    return fmod($cm, 1) == 0 ? number_format($cm, 0, ',', '') : number_format($cm, 1, ',', '');
                }
                if (strpos($type, 'vast_') === 0) {
                    $chosen = floatval(str_replace('vast_', '', $type));
                    foreach ($lengths as $row) {
                        if (floatval($row['value']) === $chosen) {
                            $used_length = $chosen;
                            $used_price = floatval($row['price']);
                            $selections[] = [
                                'group_id'   => $group['id'],
                                'group_name' => $group['name'],
                                'type'       => 'length_fixed',
                                'label'      => fw_format_cm($used_length) . ' cm',
                                'price'      => $used_price,
                            ];
                            break;
                        }
                    }
                } elseif ($type === 'maatwerk' && $allow_custom) {
                    if ($custom_length < $min || $custom_length > $max) {
                        wc_add_notice(__('Maatwerk lengte buiten bereik.', 'fw'), 'error');
                        continue;
                    }
                    foreach ($lengths as $row) {
                        if ($custom_length <= floatval($row['value'])) {
                            $used_length = floatval($row['value']);
                            $used_price = floatval($row['price']);
                            break;
                        }
                    }
                    if ($used_length === null) {
                        wc_add_notice(__('Gekozen maatwerk lengte is te groot.', 'fw'), 'error');
                        continue;
                    }
                    $selections[] = [
                        'group_id'   => $group['id'],
                        'group_name' => $group['name'],
                        'type'       => 'length_custom',
                        'label'      => fw_format_cm($custom_length) . ' cm (prijs: ' . fw_format_cm($used_length) . ' cm)',
                        'price'      => $used_price,
                        'raw_value'  => $custom_length,
                        'price_length' => $used_length,
                    ];
                }
            }
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

        foreach ( $cart->get_cart() as $cart_item_key => &$item ) {
            if ( ! empty( $item['length'] ) ) {
                $item['data']->set_length( (float) $item['length'] );
            }
            if ( ! empty( $item['width'] ) ) {
                $item['data']->set_width( (float) $item['width'] );
            }
            if ( empty( $item['fw_extra'] ) ) continue;

            // Gebruik altijd de originele productprijs als basis
            $base = method_exists($item['data'], 'get_regular_price') ? (float) $item['data']->get_regular_price() : (float) $item['data']->get_price();
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
            // Zet lengte/breedte als order item meta voor verzending
            if ($sel['type'] === 'dimension_length') {
                $item->add_meta_data('_length', $sel['raw_value'], true);
            }
            if ($sel['type'] === 'dimension_width') {
                $item->add_meta_data('_width', $sel['raw_value'], true);
            }
        }
    }

    public static function ajax_get_price() {
        // Controleer nonce en input
        if ( ! isset( $_POST['product_id'] ) ) {
            wp_send_json_error( [ 'message' => 'Geen product_id opgegeven.' ] );
        }
        $product_id = intval( $_POST['product_id'] );
        $selections_raw = sanitize_text_field( wp_unslash( $_POST['fw_selections'] ?? '' ) );
        $selections     = $selections_raw ? json_decode( $selections_raw, true ) : [];
        if ( ! is_array( $selections ) ) $selections = [];

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            wp_send_json_error( [ 'message' => 'Product niet gevonden.' ] );
        }
        $base_price = (float) $product->get_price();
        $extra = 0;
        foreach ( $selections as $sel ) {
            $extra += (float) ( $sel['price'] ?? 0 );
        }
        $total = $base_price + $extra;
        $currency = get_woocommerce_currency_symbol();
        $formatted = wc_price( $total );
        wp_send_json_success( [
            'price' => $total,
            'formatted' => $formatted,
            'currency' => $currency,
        ] );
    }
}
