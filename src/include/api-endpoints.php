<?php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

/**
 * REST API endpoint for bulk updating Prix achat HT
 */

// Register REST API route
add_action('rest_api_init', 'maquette_register_prix_achat_api');
function maquette_register_prix_achat_api() {
    register_rest_route('maquette-char-promo/v1', '/update-prix-achat', [
        'methods'             => 'POST',
        'callback'            => 'maquette_update_prix_achat_callback',
        'permission_callback' => 'maquette_api_permission_check',
        'args'                => [
            'products' => [
                'required'          => true,
                'type'              => 'array',
                'validate_callback' => 'maquette_validate_products_array',
                'sanitize_callback' => 'maquette_sanitize_products_array',
            ]
        ]
    ]);
}

/**
 * Permission check - requires manage_woocommerce capability
 */
function maquette_api_permission_check() {
    return current_user_can('manage_woocommerce');
}

/**
 * Validate products array structure
 */
function maquette_validate_products_array($products, $request, $key) {
    if (!is_array($products) || empty($products)) {
        return new WP_Error('invalid_data', 'Products array is required and must not be empty', ['status' => 400]);
    }

    foreach ($products as $product) {
        // Each product must have at least name OR reference
        if (empty($product['name']) && empty($product['reference'])) {
            return new WP_Error('invalid_data', 'Each product must have at least a name or reference', ['status' => 400]);
        }

        // prix_achat_ht is required
        if (!isset($product['prix_achat_ht'])) {
            return new WP_Error('invalid_data', 'Each product must have prix_achat_ht field', ['status' => 400]);
        }

        // prix_achat_ht must be numeric
        if (!is_numeric($product['prix_achat_ht'])) {
            return new WP_Error('invalid_data', 'prix_achat_ht must be a numeric value', ['status' => 400]);
        }
    }

    return true;
}

/**
 * Sanitize products array
 */
function maquette_sanitize_products_array($products, $request, $key) {
    $sanitized = [];

    foreach ($products as $product) {
        $sanitized[] = [
            'name'          => isset($product['name']) ? sanitize_text_field($product['name']) : '',
            'reference'     => isset($product['reference']) ? sanitize_text_field($product['reference']) : '',
            'prix_achat_ht' => floatval($product['prix_achat_ht'])
        ];
    }

    return $sanitized;
}

/**
 * API callback - update products prix achat HT
 */
function maquette_update_prix_achat_callback($request) {
    $products = $request->get_param('products');

    $updated_count = 0;
    $not_found = [];
    $updated_products = [];

    foreach ($products as $product_data) {
        $product_id = maquette_find_product_by_name_or_reference($product_data['name'], $product_data['reference']);

        if ($product_id) {
            // Update prix achat HT
            update_post_meta($product_id, '_prix_achat_ht', $product_data['prix_achat_ht']);

            $updated_count++;
            $updated_products[] = [
                'id'            => $product_id,
                'name'          => get_the_title($product_id),
                'reference'     => get_post_meta($product_id, '_sku', true),
                'prix_achat_ht' => $product_data['prix_achat_ht']
            ];
        } else {
            // Product not found
            $identifier = !empty($product_data['reference']) ? $product_data['reference'] : $product_data['name'];
            $not_found[] = [
                'identifier' => $identifier,
                'name'       => $product_data['name'],
                'reference'  => $product_data['reference']
            ];
        }
    }

    return new WP_REST_Response([
        'success'         => true,
        'updated_count'   => $updated_count,
        'updated_products' => $updated_products,
        'not_found'       => $not_found,
        'not_found_count' => count($not_found)
    ], 200);
}

/**
 * Find product by name or SKU reference
 */
function maquette_find_product_by_name_or_reference($name, $reference) {
    global $wpdb;

    // Priority 1: Search by SKU (reference)
    if (!empty($reference)) {
        $product_id = $wpdb->get_var($wpdb->prepare("
            SELECT post_id
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_sku'
            AND meta_value = %s
            LIMIT 1
        ", $reference));

        if ($product_id) {
            // Verify it's a published product
            $post_status = get_post_status($product_id);
            if (in_array($post_status, ['publish', 'draft', 'private'])) {
                return $product_id;
            }
        }
    }

    // Priority 2: Search by exact product name
    if (!empty($name)) {
        $product_id = $wpdb->get_var($wpdb->prepare("
            SELECT ID
            FROM {$wpdb->posts}
            WHERE post_title = %s
            AND post_type = 'product'
            AND post_status IN ('publish', 'draft', 'private')
            LIMIT 1
        ", $name));

        if ($product_id) {
            return $product_id;
        }
    }

    return false;
}
