<?php
// Zet automatisch de langste lengte als custom field '_length_cm' bij opslaan product
add_action('save_post_product', 'fw_auto_save_length_cm', 20, 3);

function fw_auto_save_length_cm($post_ID, $post, $update) {
    // Haal vaste en maatwerk lengtes op
    $lengths = get_post_meta($post_ID, '_fw_lengths', true);
    $max_custom = get_post_meta($post_ID, '_fw_custom_length_max', true);
    $allow_custom = get_post_meta($post_ID, '_fw_allow_custom_length', true);
    $max = 0;
    if (!empty($lengths)) {
        $arr = array_map('intval', array_filter(array_map('trim', explode(',', $lengths))));
        if ($arr) {
            $max = max($arr);
        }
    }
    if ($allow_custom === '1' && intval($max_custom) > $max) {
        $max = intval($max_custom);
    }
    if ($max > 0) {
        update_post_meta($post_ID, '_length_cm', $max);
    } else {
        delete_post_meta($post_ID, '_length_cm');
    }
}

