<?php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

/**
 * Product Export to CSV
 * Export all WooCommerce products with custom fields to CSV
 */

/**
 * Add export page under Tools menu
 */
add_action('admin_menu', 'maquette_add_product_export_page');
function maquette_add_product_export_page() {
    add_management_page(
        __('Export Produits Maquette', 'maquette-char-promo'),
        __('Export Produits', 'maquette-char-promo'),
        'manage_woocommerce',
        'maquette-product-export',
        'maquette_product_export_page_content'
    );
}

/**
 * Display export page content
 */
function maquette_product_export_page_content() {
    // Check user permissions
    if (!current_user_can('manage_woocommerce')) {
        wp_die(__('Vous n\'avez pas les permissions nécessaires pour accéder à cette page.', 'maquette-char-promo'));
    }

    // Get statistics
    $stats = maquette_get_products_export_statistics();

    ?>
    <div class="wrap">
        <h1><?php _e('Export des Produits', 'maquette-char-promo'); ?></h1>

        <div class="notice notice-info">
            <p>
                <?php _e('Exportez tous vos produits WooCommerce au format CSV avec les informations suivantes :', 'maquette-char-promo'); ?>
            </p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li><?php _e('Nom du produit', 'maquette-char-promo'); ?></li>
                <li><?php _e('Référence (SKU)', 'maquette-char-promo'); ?></li>
                <li><?php _e('Quantité en stock', 'maquette-char-promo'); ?></li>
                <li><?php _e('Prix achat HT', 'maquette-char-promo'); ?></li>
            </ul>
        </div>

        <div class="card" style="max-width: 600px;">
            <h2><?php _e('Statistiques', 'maquette-char-promo'); ?></h2>
            <table class="widefat" style="margin-bottom: 20px;">
                <tbody>
                    <tr>
                        <td><strong><?php _e('Total produits', 'maquette-char-promo'); ?></strong></td>
                        <td><?php echo esc_html($stats['total_products']); ?></td>
                    </tr>
                    <tr class="alternate">
                        <td><strong><?php _e('Produits avec prix achat HT', 'maquette-char-promo'); ?></strong></td>
                        <td><?php echo esc_html($stats['with_prix_achat']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Produits avec gestion du stock', 'maquette-char-promo'); ?></strong></td>
                        <td><?php echo esc_html($stats['with_stock_management']); ?></td>
                    </tr>
                    <tr class="alternate">
                        <td><strong><?php _e('Produits sans référence (SKU)', 'maquette-char-promo'); ?></strong></td>
                        <td><?php echo esc_html($stats['without_sku']); ?></td>
                    </tr>
                </tbody>
            </table>

            <form method="post" action="">
                <?php wp_nonce_field('maquette_export_products', 'maquette_export_nonce'); ?>
                <input type="hidden" name="maquette_action" value="export_products_csv">
                <p>
                    <button type="submit" class="button button-primary button-hero">
                        <span class="dashicons dashicons-download" style="margin-top: 4px;"></span>
                        <?php _e('Télécharger l\'export CSV', 'maquette-char-promo'); ?>
                    </button>
                </p>
            </form>

            <p class="description">
                <?php _e('Le fichier sera téléchargé au format CSV avec séparateur point-virgule (;), compatible avec Excel.', 'maquette-char-promo'); ?>
            </p>
        </div>
    </div>
    <?php
}

/**
 * Get statistics for export page
 */
function maquette_get_products_export_statistics() {
    $args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];

    $products = get_posts($args);
    $total = count($products);

    $with_prix_achat = 0;
    $with_stock_management = 0;
    $without_sku = 0;

    foreach ($products as $product_id) {
        // Check prix achat HT
        $prix_achat = get_post_meta($product_id, '_prix_achat_ht', true);
        if (!empty($prix_achat)) {
            $with_prix_achat++;
        }

        // Check stock management
        $manage_stock = get_post_meta($product_id, '_manage_stock', true);
        if ($manage_stock === 'yes') {
            $with_stock_management++;
        }

        // Check SKU
        $sku = get_post_meta($product_id, '_sku', true);
        if (empty($sku)) {
            $without_sku++;
        }
    }

    return [
        'total_products'        => $total,
        'with_prix_achat'       => $with_prix_achat,
        'with_stock_management' => $with_stock_management,
        'without_sku'           => $without_sku,
    ];
}

/**
 * Handle CSV export request
 */
add_action('admin_init', 'maquette_handle_product_export_request');
function maquette_handle_product_export_request() {
    // Check if export action is triggered
    if (!isset($_POST['maquette_action']) || $_POST['maquette_action'] !== 'export_products_csv') {
        return;
    }

    // Verify nonce
    if (!isset($_POST['maquette_export_nonce']) || !wp_verify_nonce($_POST['maquette_export_nonce'], 'maquette_export_products')) {
        wp_die(__('Erreur de sécurité. Veuillez réessayer.', 'maquette-char-promo'));
    }

    // Check user permissions
    if (!current_user_can('manage_woocommerce')) {
        wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'maquette-char-promo'));
    }

    // Generate CSV
    maquette_generate_products_csv();
}

/**
 * Generate and download CSV file
 */
function maquette_generate_products_csv() {
    // Get all products
    $args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ];

    $products = get_posts($args);

    // Set filename with current date
    $filename = 'produits-export-' . date('Y-m-d') . '.csv';

    // Set headers for file download
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // CSV Headers
    $headers = [
        'Nom',
        'Référence',
        'Quantité Stock',
        'Prix Achat HT'
    ];
    fputcsv($output, $headers, ';');

    // Add products data
    foreach ($products as $product_post) {
        $product = wc_get_product($product_post->ID);

        if (!$product) {
            continue;
        }

        // Get product data
        $name = $product->get_name();
        $sku = $product->get_sku();
        $stock_quantity = '';
        $prix_achat_ht = '';

        // Get stock quantity
        if ($product->managing_stock()) {
            $stock_quantity = $product->get_stock_quantity();
            if ($stock_quantity === null) {
                $stock_quantity = '0';
            }
        } else {
            // If stock not managed, check stock status
            $stock_status = $product->get_stock_status();
            $stock_quantity = ($stock_status === 'instock') ? 'En stock' : 'Rupture';
        }

        // Get prix achat HT
        $prix_achat_meta = get_post_meta($product_post->ID, '_prix_achat_ht', true);
        if (!empty($prix_achat_meta)) {
            $prix_achat_ht = number_format(floatval($prix_achat_meta), 2, ',', '');
        }

        // Prepare row
        $row = [
            $name,
            $sku ?: '',
            $stock_quantity,
            $prix_achat_ht
        ];

        fputcsv($output, $row, ';');
    }

    fclose($output);

    // Log export action
    error_log('Maquette Product Export: CSV generated by user ID ' . get_current_user_id() . ' - ' . count($products) . ' products exported');

    exit;
}
