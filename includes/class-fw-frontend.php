<?php
/* ================================================================
   FlexWave – Frontend CSS
   https://flex-wave.nl
   Copyright (c) 2026 Jorian Beukens
   E-mail: jorian@flex-wave.nl
   Alle rechten voorbehouden
   ================================================================ */

if ( ! defined( 'ABSPATH' ) ) exit;

class FW_Frontend {

    public static function init(): void {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue' ] );

        // BINNEN de WooCommerce form, vóór de "Toevoegen" knop
        add_action( 'woocommerce_before_add_to_cart_button', [ __CLASS__, 'auto_render' ], 5 );
    }

    public static function enqueue(): void {
        if ( ! is_product() ) return;
        wp_enqueue_style(
            'fw-frontend',
            FW_URL . 'assets/frontend.css',
            [],
            FW_VERSION
        );
        wp_enqueue_script(
            'fw-frontend',
            FW_URL . 'assets/frontend.js',
            [],
            FW_VERSION,
            true   // footer
        );
    }

    public static function auto_render(): void {
        self::render( get_the_ID() );
    }

    // ── Hoofdrender ───────────────────────────────────────────────────────────
    public static function render( int $product_id ): void {
        if ( ! $product_id ) return;

        $groups = FW_ProductMeta::get_active_groups( $product_id );
        if ( empty( $groups ) ) return;

        $product    = wc_get_product( $product_id );
        $base_price = $product ? (float) $product->get_price() : 0;
        $sym        = get_woocommerce_currency_symbol();

        // Compacte JS-payload (alleen wat JS nodig heeft)
        $payload = [
            'product_id' => $product_id,
            'base_price' => $base_price,
            'symbol'     => $sym,
            'groups'     => array_map( fn( $g ) => [
                'id'         => $g['id'],
                'name'       => $g['name'],
                'type'       => $g['type'],
                'required'   => (bool) $g['required'],
                'variations' => array_values( array_map( fn( $v ) => [
                    'name'  => $v['name']  ?? '',
                    'price' => (float) ( $v['price'] ?? 0 ),
                ], $g['variations'] ) ),
            ], $groups ),
        ];
        ?>

        <div class="fw-options"
             id="fw-options-<?php echo esc_attr( $product_id ); ?>"
             data-fw="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>">

            <?php foreach ( $groups as $group ) :
                if ( empty( $group['variations'] ) && $group['type'] !== 'text' ) continue;
            ?>
            <div class="fw-group fw-group--<?php echo esc_attr( $group['type'] ); ?>"
                 data-group-id="<?php echo esc_attr( $group['id'] ); ?>">

                <div class="fw-group-label">
                    <?php echo esc_html( $group['name'] ); ?>
                    <?php if ( $group['required'] ) : ?>
                        <span class="fw-required" aria-label="Verplicht">*</span>
                    <?php endif; ?>
                </div>

                <?php
                switch ( $group['type'] ) {
                    case 'color': self::render_color( $product_id, $group, $sym ); break;
                    case 'text':  self::render_text(  $product_id, $group, $sym ); break;
                    default:      self::render_radio( $product_id, $group, $sym ); break;
                }
                ?>

                <div class="fw-group-error" role="alert" style="display:none">
                    Maak een keuze voor "<?php echo esc_html( $group['name'] ); ?>".
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Live totaalprijs -->
            <div class="fw-total">
                <span class="fw-total-label">Totaal:</span>
                <span class="fw-total-price" id="fw-total-<?php echo esc_attr( $product_id ); ?>">
                    <?php echo esc_html( $sym ); ?>&nbsp;<?php echo number_format( $base_price, 2, ',', '.' ); ?>
                </span>
            </div>

            <!-- Hidden fields — zitten nu altijd binnen de WC <form> -->
            <input type="hidden" name="fw_product_id"
                   value="<?php echo esc_attr( $product_id ); ?>">
            <input type="hidden" name="fw_selections"
                   id="fw-sel-<?php echo esc_attr( $product_id ); ?>"
                   value="[]">
            <input type="hidden" name="fw_extra_price"
                   id="fw-extra-<?php echo esc_attr( $product_id ); ?>"
                   value="0">

        </div><!-- .fw-options -->
        <?php
    }

    // ── Radio (keuzeknop) ─────────────────────────────────────────────────────
    private static function render_radio( int $pid, array $group, string $sym ): void {
        $name = 'fw_g' . $pid . '_' . $group['id'];
        echo '<div class="fw-items">';
        foreach ( $group['variations'] as $vi => $v ) {
            $iid   = $name . '_' . $vi;
            $price = (float) ( $v['price'] ?? 0 );
            $badge = $price > 0
                ? '<span class="fw-opt-price">+&nbsp;' . esc_html( $sym ) . number_format( $price, 2, ',', '.' ) . '</span>'
                : '';
            ?>
            <label class="fw-option" for="<?php echo esc_attr( $iid ); ?>">
                <?php if ( ! empty( $v['image_url'] ) ) : ?>
                    <img class="fw-opt-img"
                         src="<?php echo esc_url( $v['image_url'] ); ?>"
                         alt="<?php echo esc_attr( $v['name'] ?? '' ); ?>">
                <?php endif; ?>
                <input type="radio"
                       id="<?php echo esc_attr( $iid ); ?>"
                       name="<?php echo esc_attr( $name ); ?>"
                       data-group="<?php echo esc_attr( $group['id'] ); ?>"
                       data-price="<?php echo esc_attr( $price ); ?>"
                       data-label="<?php echo esc_attr( $v['name'] ?? '' ); ?>"
                       value="<?php echo esc_attr( $vi ); ?>">
                <span class="fw-opt-name"><?php echo esc_html( $v['name'] ?? '' ); ?></span>
                <?php echo $badge; ?>
            </label>
            <?php
        }
        echo '</div>';
    }

    // ── Kleurstaal ────────────────────────────────────────────────────────────
    private static function render_color( int $pid, array $group, string $sym ): void {
        $name = 'fw_g' . $pid . '_' . $group['id'];
        echo '<div class="fw-items fw-items--color">';
        foreach ( $group['variations'] as $vi => $v ) {
            $iid   = $name . '_' . $vi;
            $price = (float) ( $v['price'] ?? 0 );
            $color = esc_attr( $v['color'] ?? '#cccccc' );
            $label = esc_attr( $v['name'] ?? '' );
            $title = $label . ( $price > 0 ? ' (+' . $sym . number_format( $price, 2, ',', '.' ) . ')' : '' );
            ?>
            <label class="fw-option fw-option--swatch"
                   for="<?php echo esc_attr( $iid ); ?>"
                   title="<?php echo esc_attr( $title ); ?>">
                <input type="radio"
                       id="<?php echo esc_attr( $iid ); ?>"
                       name="<?php echo esc_attr( $name ); ?>"
                       data-group="<?php echo esc_attr( $group['id'] ); ?>"
                       data-price="<?php echo esc_attr( $price ); ?>"
                       data-label="<?php echo esc_attr( $v['name'] ?? '' ); ?>"
                       value="<?php echo esc_attr( $vi ); ?>">
                <span class="fw-swatch" style="background:<?php echo $color; ?>"></span>
                <span class="fw-opt-name"><?php echo esc_html( $v['name'] ?? '' ); ?></span>
                <?php if ( $price > 0 ) : ?>
                    <span class="fw-opt-price">+<?php echo esc_html( $sym . number_format( $price, 2, ',', '.' ) ); ?></span>
                <?php endif; ?>
            </label>
            <?php
        }
        echo '</div>';

        // Live kleurpreview
        echo '<div class="fw-color-preview" id="fw-cp-' . esc_attr( $name ) . '" aria-live="polite" style="display:none">'
           . '<span class="fw-cp-dot"></span><span class="fw-cp-label"></span></div>';
    }

    // ── Vrije tekstinvoer ─────────────────────────────────────────────────────
    private static function render_text( int $pid, array $group, string $sym ): void {
        $v           = $group['variations'][0] ?? [];
        $price       = (float) ( $v['price']       ?? 0 );
        $placeholder = $v['placeholder'] ?? '';
        $key         = 'fw_txt_' . $pid . '_' . $group['id'];
        $note        = $price > 0
            ? '(+ ' . $sym . number_format( $price, 2, ',', '.' ) . ' bij invullen)'
            : '';
        ?>
        <div class="fw-text-wrap">
            <input type="text"
                   id="<?php echo esc_attr( $key ); ?>"
                   name="<?php echo esc_attr( $key ); ?>"
                   class="fw-text-input"
                   data-group="<?php echo esc_attr( $group['id'] ); ?>"
                   data-price="<?php echo esc_attr( $price ); ?>"
                   placeholder="<?php echo esc_attr( $placeholder ); ?>"
                   autocomplete="off">
            <?php if ( $note ) : ?>
                <span class="fw-text-price-note"><?php echo esc_html( $note ); ?></span>
            <?php endif; ?>
        </div>
        <?php
    }
}
