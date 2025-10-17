<?php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

/**
 * Shortcodes for Maquette Char Promo plugin
 */

/**
 * Register main menu location
 */
add_action('after_setup_theme', 'maquette_register_nav_menus');
function maquette_register_nav_menus() {
    register_nav_menu('main_menu', __('Menu Principal Maquette', 'maquette-char-promo'));
}

/**
 * Shortcode: [maquette_main_menu]
 * Displays main navigation menu with search, cart, and account sections
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
add_shortcode('maquette_main_menu', 'maquette_main_menu_shortcode');
function maquette_main_menu_shortcode($atts) {
    // Parse attributes
    $atts = shortcode_atts([
        'menu_class'     => 'maquette-main-menu',
        'show_search'    => 'yes',
        'show_cart'      => 'yes',
        'show_account'   => 'yes',
        'cart_icon'      => 'dashicons-cart',
        'account_icon'   => 'dashicons-admin-users',
    ], $atts, 'maquette_main_menu');
    
if (function_exists('WC') && WC()->cart) {
    $cart_count = WC()->cart->get_cart_contents_count();
    $cart_total = WC()->cart->get_cart_total(); // déjà formaté avec HTML (symboles, <span class="amount">)
} else {
    $cart_count = 0;
    $cart_total = wc_price(0);
}
    // Prepare data for template
    $is_user_logged = is_user_logged_in();
    $current_user = wp_get_current_user();
    $is_premium = maquette_is_premium_active();
    $cart_url = wc_get_cart_url();
    $account_url = get_permalink(get_option('woocommerce_myaccount_page_id'));
    $shop_url = get_permalink(wc_get_page_id('shop'));

    // Start output buffering
    ob_start();

    // Include template (variables are accessible via current scope)
    include MAQUETTE_CHAR_PROMO_PLUGIN_DIR . 'template/main-menu.php';

    // Return buffered content
    return ob_get_clean();
}

/**
 * Enqueue main menu styles
 */
add_action('wp_enqueue_scripts', 'maquette_enqueue_main_menu_styles');
function maquette_enqueue_main_menu_styles() {
    wp_enqueue_style(
        'maquette-main-menu',
        MAQUETTE_CHAR_PROMO_PLUGIN_URL . 'assets/css/main-menu.css',
        [],
        '1.0.0'
    );

    // Enqueue Dashicons for icons
    wp_enqueue_style('dashicons');
}

/**
 * AJAX: Update cart count
 * Useful for AJAX add to cart functionality
 */
add_filter('woocommerce_add_to_cart_fragments', 'maquette_cart_count_fragment');
function maquette_cart_count_fragment($fragments) {
    $cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;

    ob_start();
    ?>
    <span class="maquette-cart-count"><?php echo esc_html($cart_count); ?></span>
    <?php
    $fragments['.maquette-cart-count'] = ob_get_clean();

    ob_start();
    ?>
    <span class="maquette-cart-total"><?php echo WC()->cart->get_cart_total(); ?></span>
    <?php
    $fragments['.maquette-cart-total'] = ob_get_clean();

    return $fragments;
}

/**
 * Shortcode: [mcp_products]
 * Displays products list loaded via AJAX
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
add_shortcode('mcp_products', 'maquette_products_list_shortcode');
function maquette_products_list_shortcode($atts) {
    // Mark that this shortcode is present on the page
    if (!isset($GLOBALS['mcp_shortcodes'])) {
        $GLOBALS['mcp_shortcodes'] = [];
    }
    $GLOBALS['mcp_shortcodes']['products_list'] = true;

    // Parse attributes
    $atts = shortcode_atts([
        'limit'              => 12,
        'interactWithSearch' => false,
        'filters'            => '{}', // JSON string
    ], $atts, 'mcp_products');

    // Sanitize
    $limit = absint($atts['limit']);
    $interact_with_search = filter_var($atts['interactWithSearch'], FILTER_VALIDATE_BOOLEAN);
    $filters = json_decode($atts['filters'], true);
    if (!is_array($filters)) {
        $filters = [];
    }

    // Prepare data for JS
    $data = [
        'limit'              => $limit,
        'interactWithSearch' => $interact_with_search,
        'filters'            => $filters,
    ];

    // Start output buffering
    ob_start();

    // Include template (variables are accessible via current scope)
    include MAQUETTE_CHAR_PROMO_PLUGIN_DIR . 'template/products-list.php';

    // Return buffered content
    return ob_get_clean();
}

/**
 * Shortcode: [mcp_search]
 * Displays faceted search interface
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
add_shortcode('mcp_search', 'maquette_search_facets_shortcode');
function maquette_search_facets_shortcode($atts) {
    // Mark that this shortcode is present on the page
    if (!isset($GLOBALS['mcp_shortcodes'])) {
        $GLOBALS['mcp_shortcodes'] = [];
    }
    $GLOBALS['mcp_shortcodes']['search_facets'] = true;

    // Parse attributes
    $atts = shortcode_atts([
        'defaults' => '{}', // JSON string
    ], $atts, 'mcp_search');

    // Sanitize
    $defaults = json_decode($atts['defaults'], true);
    if (!is_array($defaults)) {
        $defaults = [];
    }

    // Prepare data for JS
    $data = [
        'defaults' => $defaults,
    ];

    // Start output buffering
    ob_start();

    // Include template (variables are accessible via current scope)
    include MAQUETTE_CHAR_PROMO_PLUGIN_DIR . 'template/search-facets.php';

    // Return buffered content
    return ob_get_clean();
}

/**
 * Conditionally enqueue assets based on shortcode presence
 */
add_action('wp_enqueue_scripts', 'maquette_enqueue_products_assets');
function maquette_enqueue_products_assets() {
    // Check if shortcodes are present
    $has_products_list = isset($GLOBALS['mcp_shortcodes']['products_list']);
    $has_search_facets = isset($GLOBALS['mcp_shortcodes']['search_facets']);

    // Enqueue products list assets
    if ($has_products_list) {
        wp_enqueue_style(
            'mcp-products-list',
            MAQUETTE_CHAR_PROMO_PLUGIN_URL . 'assets/css/products-list.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'mcp-products-list',
            MAQUETTE_CHAR_PROMO_PLUGIN_URL . 'assets/js/products-list.js',
            [],
            '1.0.0',
            true
        );

        wp_localize_script('mcp-products-list', 'mcpProductsListConfig', [
            'restUrl' => rest_url('maquettecharpromo/v1/products'),
            'nonce'   => wp_create_nonce('wp_rest'),
        ]);
    }

    // Enqueue search facets assets
    if ($has_search_facets) {
        wp_enqueue_style(
            'mcp-search-facets',
            MAQUETTE_CHAR_PROMO_PLUGIN_URL . 'assets/css/search-facets.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'mcp-search-facets',
            MAQUETTE_CHAR_PROMO_PLUGIN_URL . 'assets/js/search-facets.js',
            [],
            '1.0.0',
            true
        );

        wp_localize_script('mcp-search-facets', 'mcpSearchFacetsConfig', [
            'restUrl' => rest_url('maquettecharpromo/v1/products'),
            'nonce'   => wp_create_nonce('wp_rest'),
        ]);
    }
}
