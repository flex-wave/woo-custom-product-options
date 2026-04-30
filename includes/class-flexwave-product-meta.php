<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class FlexWave_ProductMeta {
    public static function init(): void {
        add_action( 'add_meta_boxes',        [ __CLASS__, 'add' ] );
        add_action( 'save_post_product',     [ __CLASS__, 'save' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
    }
    public static function add(): void {
        add_meta_box(
            'flexwave_product_options',
            'FlexWave – Actieve optiegroepen & variaties',
            [ __CLASS__, 'render' ],
            'product',
            'normal',
            'high'
        );
    }
    public static function enqueue( string $hook ): void {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;
        if ( get_post_type() !== 'product' ) return;
        wp_enqueue_style(  'flexwave-product-css', FLEXWAVE_OPTIONS_URL . 'assets/product-meta.css', [], FLEXWAVE_OPTIONS_VERSION );
        wp_enqueue_script( 'flexwave-product-js',  FLEXWAVE_OPTIONS_URL . 'assets/product-meta.js',  [], FLEXWAVE_OPTIONS_VERSION, true );
    }
    // ...rest van de code, alle FW_ vervangen door FLEXWAVE_OPTIONS_, FW_ProductMeta door FlexWave_ProductMeta, FW_Library door FlexWave_Library...
}
