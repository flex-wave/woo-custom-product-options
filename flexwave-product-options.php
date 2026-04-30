<?php
/**
 * Plugin Name: FlexWave - Productopties
 * Plugin URI:  https://flex-wave.nl
 * Description: Centrale bibliotheek voor productopties met variaties (keuze, kleur, tekst), waarbij opties per product eenvoudig kunnen worden aangevinkt. Ondersteunt dynamische prijsberekening in WooCommerce.
 * Version:     1.0.0
 * Author:      FlexWave (Jorian - jorian@flex-wave.nl)
 * Author URI:  https://flex-wave.nl
 * Text Domain: flexwave
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 7.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'FLEXWAVE_OPTIONS_VERSION',    '1.0.0' );
define( 'FLEXWAVE_OPTIONS_DIR',        plugin_dir_path( __FILE__ ) );
define( 'FLEXWAVE_OPTIONS_URL',        plugin_dir_url( __FILE__ ) );

require_once FLEXWAVE_OPTIONS_DIR . 'includes/class-flexwave-library.php';
require_once FLEXWAVE_OPTIONS_DIR . 'includes/class-flexwave-product-meta.php';
require_once FLEXWAVE_OPTIONS_DIR . 'includes/class-flexwave-frontend.php';
require_once FLEXWAVE_OPTIONS_DIR . 'includes/class-flexwave-pricing.php';
require_once __DIR__ . '/includes/class-flexwave-lengths-autosave.php';

add_action( 'plugins_loaded', function () {
    FlexWave_Library::init();
    FlexWave_ProductMeta::init();
    FlexWave_Frontend::init();
    FlexWave_Pricing::init();
} );

register_activation_hook( __FILE__, function () {
    FlexWave_Library::register_cpt();
    flush_rewrite_rules();
} );

function flexwave_render_options( int $product_id = 0 ): void {
    FlexWave_Frontend::render( $product_id ?: get_the_ID() );
}
