<?php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

/**
 * Add custom field "Prix achat HT" to WooCommerce product (backend only)
 */

// Add custom field in product general tab
add_action('woocommerce_product_options_pricing', 'maquette_add_prix_achat_ht_field');
function maquette_add_prix_achat_ht_field() {
    woocommerce_wp_text_input([
        'id'          => '_prix_achat_ht',
        'label'       => __('Prix achat HT', 'maquette-char-promo'),
        'placeholder' => '0.00',
        'desc_tip'    => true,
        'description' => __('Prix d\'achat hors taxes (usage interne uniquement)', 'maquette-char-promo'),
        'type'        => 'number',
        'custom_attributes' => [
            'step' => '0.01',
            'min'  => '0'
        ]
    ]);
}

// Save custom field value
add_action('woocommerce_process_product_meta', 'maquette_save_prix_achat_ht_field');
function maquette_save_prix_achat_ht_field($post_id) {
    $prix_achat_ht = isset($_POST['_prix_achat_ht']) ? sanitize_text_field($_POST['_prix_achat_ht']) : '';

    if (!empty($prix_achat_ht) && is_numeric($prix_achat_ht)) {
        update_post_meta($post_id, '_prix_achat_ht', floatval($prix_achat_ht));
    } else {
        delete_post_meta($post_id, '_prix_achat_ht');
    }
}

// Add column in products list (optional - for admin visibility)
add_filter('manage_edit-product_columns', 'maquette_add_prix_achat_ht_column', 20);
function maquette_add_prix_achat_ht_column($columns) {
    $new_columns = [];

    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;

        // Add after price column
        if ($key === 'price') {
            $new_columns['prix_achat_ht'] = __('Prix achat HT', 'maquette-char-promo');
        }
    }

    return $new_columns;
}

// Populate column value
add_action('manage_product_posts_custom_column', 'maquette_populate_prix_achat_ht_column', 10, 2);
function maquette_populate_prix_achat_ht_column($column, $post_id) {
    if ($column === 'prix_achat_ht') {
        $prix_achat_ht = get_post_meta($post_id, '_prix_achat_ht', true);

        if ($prix_achat_ht) {
            echo wc_price($prix_achat_ht);
        } else {
            echo '—';
        }
    }
}

// Make column sortable
add_filter('manage_edit-product_sortable_columns', 'maquette_make_prix_achat_ht_sortable');
function maquette_make_prix_achat_ht_sortable($columns) {
    $columns['prix_achat_ht'] = 'prix_achat_ht';
    return $columns;
}
