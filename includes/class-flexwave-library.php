<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class FlexWave_Library {
    public static function init(): void {
        add_action( 'init',                [ __CLASS__, 'register_cpt' ] );
        add_action( 'add_meta_boxes',      [ __CLASS__, 'add_meta_box' ] );
        add_action( 'save_post_fw_option_group', [ __CLASS__, 'save_meta' ] );
        add_action( 'admin_enqueue_scripts',     [ __CLASS__, 'enqueue' ] );
        add_filter( 'manage_fw_option_group_posts_columns',       [ __CLASS__, 'columns' ] );
        add_action( 'manage_fw_option_group_posts_custom_column', [ __CLASS__, 'column_content' ], 10, 2 );
    }
    public static function register_cpt(): void {
        register_post_type( 'fw_option_group', [
            'labels' => [
                'name'          => 'FW Optiegroepen',
                'singular_name' => 'Optiegroep',
                'add_new'       => 'Groep toevoegen',
                'add_new_item'  => 'Nieuwe optiegroep',
                'edit_item'     => 'Optiegroep bewerken',
                'all_items'     => 'Alle optiegroepen',
            ],
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => true,
            'menu_label'   => 'FW Optiegroepen',
            'menu_icon'    => 'dashicons-list-view',
            'supports'     => [ 'title' ],
            'rewrite'      => false,
        ] );
    }
    public static function enqueue( string $hook ): void {
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'fw_option_group' ) return;
        wp_enqueue_media();
        wp_enqueue_style(  'flexwave-lib-css', FLEXWAVE_OPTIONS_URL . 'assets/library.css', [], FLEXWAVE_OPTIONS_VERSION );
        wp_enqueue_script( 'flexwave-lib-js',  FLEXWAVE_OPTIONS_URL . 'assets/library.js',  [], FLEXWAVE_OPTIONS_VERSION, true );
    }
    // ...rest van de code, alle FW_ vervangen door FLEXWAVE_OPTIONS_, FW_Library door FlexWave_Library...
}
