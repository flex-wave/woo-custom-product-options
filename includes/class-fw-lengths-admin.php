<?php
/**
 * FW Lengtes Admin Class
 * Beheert de productinstellingen voor lengtes.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class FW_Lengths_Admin {
    public function __construct() {
        add_filter('woocommerce_product_data_tabs', [$this, 'add_lengths_tab']);
        add_action('woocommerce_product_data_panels', [$this, 'add_lengths_panel']);
        add_action('woocommerce_process_product_meta', [$this, 'save_lengths_fields']);
    }

    public function add_lengths_tab($tabs) {
        $tabs['fw_lengths'] = [
            'label'    => __('Lengtes', 'fw'),
            'target'   => 'fw_lengths_options',
            'class'    => [],
            'priority' => 80,
        ];
        return $tabs;
    }

    public function add_lengths_panel() {
        global $post;
        $lengths = get_post_meta($post->ID, '_fw_lengths', true);
        $prices = get_post_meta($post->ID, '_fw_length_prices', true);
        $allow_custom = get_post_meta($post->ID, '_fw_allow_custom_length', true);
        $min = get_post_meta($post->ID, '_fw_custom_length_min', true);
        $max = get_post_meta($post->ID, '_fw_custom_length_max', true);
        $step = get_post_meta($post->ID, '_fw_custom_length_step', true);
        ?>
        <div id="fw_lengths_options" class="panel woocommerce_options_panel">
            <div class="options_group">
                <p class="form-field">
                    <label for="fw_lengths"><?php _e('Vaste lengtes (cm, komma-gescheiden)', 'fw'); ?></label>
                    <input type="text" id="fw_lengths" name="fw_lengths" value="<?php echo esc_attr($lengths); ?>" placeholder="100,120,140,170,200" />
                </p>
                <p class="form-field">
                    <label for="fw_length_prices"><?php _e('Prijzen per lengte (komma-gescheiden, volgorde gelijk aan lengtes)', 'fw'); ?></label>
                    <input type="text" id="fw_length_prices" name="fw_length_prices" value="<?php echo esc_attr($prices); ?>" placeholder="10.00,12.00,14.00,17.00,20.00" />
                </p>
                <p class="form-field">
                    <label for="fw_allow_custom_length">
                        <input type="checkbox" id="fw_allow_custom_length" name="fw_allow_custom_length" value="1" <?php checked($allow_custom, '1'); ?> />
                        <?php _e('Maatwerk lengte toestaan', 'fw'); ?>
                    </label>
                </p>
                <p class="form-field">
                    <label for="fw_custom_length_min"><?php _e('Min maatwerk lengte (cm)', 'fw'); ?></label>
                    <input type="number" id="fw_custom_length_min" name="fw_custom_length_min" value="<?php echo esc_attr($min); ?>" min="0" step="1" />
                </p>
                <p class="form-field">
                    <label for="fw_custom_length_max"><?php _e('Max maatwerk lengte (cm)', 'fw'); ?></label>
                    <input type="number" id="fw_custom_length_max" name="fw_custom_length_max" value="<?php echo esc_attr($max); ?>" min="0" step="1" />
                </p>
                <p class="form-field">
                    <label for="fw_custom_length_step"><?php _e('Stapgrootte maatwerk (cm)', 'fw'); ?></label>
                    <input type="number" id="fw_custom_length_step" name="fw_custom_length_step" value="<?php echo esc_attr($step); ?>" min="1" step="1" />
                </p>
            </div>
        </div>
        <?php
    }

    public function save_lengths_fields($post_id) {
        $lengths = isset($_POST['fw_lengths']) ? sanitize_text_field($_POST['fw_lengths']) : '';
        $prices = isset($_POST['fw_length_prices']) ? sanitize_text_field($_POST['fw_length_prices']) : '';
        $allow_custom = isset($_POST['fw_allow_custom_length']) ? '1' : '';
        $min = isset($_POST['fw_custom_length_min']) ? intval($_POST['fw_custom_length_min']) : '';
        $max = isset($_POST['fw_custom_length_max']) ? intval($_POST['fw_custom_length_max']) : '';
        $step = isset($_POST['fw_custom_length_step']) ? intval($_POST['fw_custom_length_step']) : '';

        update_post_meta($post_id, '_fw_lengths', $lengths);
        update_post_meta($post_id, '_fw_length_prices', $prices);
        update_post_meta($post_id, '_fw_allow_custom_length', $allow_custom);
        update_post_meta($post_id, '_fw_custom_length_min', $min);
        update_post_meta($post_id, '_fw_custom_length_max', $max);
        update_post_meta($post_id, '_fw_custom_length_step', $step);
    }
}
