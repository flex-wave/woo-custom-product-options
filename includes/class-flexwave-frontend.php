<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class FlexWave_Frontend {
    public static function init(): void {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
        add_action( 'woocommerce_before_add_to_cart_button', [ __CLASS__, 'auto_render' ], 5 );
    }
    public static function enqueue(): void {
        if ( ! is_product() ) return;
        wp_enqueue_style(
            'flexwave-frontend',
            FLEXWAVE_OPTIONS_URL . 'assets/frontend.css',
            [],
            FLEXWAVE_OPTIONS_VERSION
        );
        wp_enqueue_script(
            'flexwave-frontend',
            FLEXWAVE_OPTIONS_URL . 'assets/frontend.js',
            [],
            FLEXWAVE_OPTIONS_VERSION,
            true
        );
    }
    public static function auto_render(): void {
        self::render( get_the_ID() );
    }
    public static function render( int $product_id ): void {
        if ( ! $product_id ) return;
        $groups = FlexWave_ProductMeta::get_active_groups( $product_id );
        if ( empty( $groups ) ) return;
        $product    = wc_get_product( $product_id );
        $base_price = $product ? (float) $product->get_price() : 0;
        $sym        = get_woocommerce_currency_symbol();
        $payload = [
            'product_id' => $product_id,
            'base_price' => $base_price,
            'symbol'     => $sym,
            'groups'     => array_map(
                fn( $g ) => [
                    'id'         => $g['id'],
                    'name'       => $g['name'],
                    'type'       => $g['type'],
                    'required'   => (bool) $g['required'],
                    'variations' => array_values(
                        array_map(
                            fn( $v ) => [
                                'name'  => $v['name'] ?? '',
                                'price' => (float) ( $v['price'] ?? 0 ),
                            ],
                            $g['variations']
                        )
                    ),
                ],
                $groups
            ),
        ];
        ?>
        <div class="flexwave-options"
             id="flexwave-options-<?php echo esc_attr( $product_id ); ?>"
             data-flexwave="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>">
            <?php foreach ( $groups as $group ) : ?>
                <?php if ( empty( $group['variations'] ) && $group['type'] !== 'text' ) : ?>
                    <?php continue; ?>
                <?php endif; ?>
                <div class="flexwave-group flexwave-group--<?php echo esc_attr( $group['type'] ); ?>"
                     data-group-id="<?php echo esc_attr( $group['id'] ); ?>">
                    <div class="flexwave-group-label">
                        <?php echo esc_html( $group['name'] ); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    // ...rest van de code, alle FW_ vervangen door FLEXWAVE_OPTIONS_, FW_Frontend door FlexWave_Frontend, FW_ProductMeta door FlexWave_ProductMeta...
}
