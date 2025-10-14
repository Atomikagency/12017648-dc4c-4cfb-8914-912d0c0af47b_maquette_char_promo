<?php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

/**
 * Premium Pricing System
 * - Apply -8% discount (cumulative with existing promotions)
 * - Display member price on frontend (only for premium members)
 */

/**
 * Get premium discount rate from settings
 * @return float
 */
function maquette_get_premium_discount_rate() {
    return floatval(get_option('maquette_premium_discount_rate', 8));
}

/**
 * Apply premium discount to product price (cumulative)
 * Works for simple and variable products
 */
add_filter('woocommerce_product_get_price', 'maquette_apply_premium_discount', 99, 2);
add_filter('woocommerce_product_get_sale_price', 'maquette_apply_premium_discount', 99, 2);
add_filter('woocommerce_product_variation_get_price', 'maquette_apply_premium_discount', 99, 2);
add_filter('woocommerce_product_variation_get_sale_price', 'maquette_apply_premium_discount', 99, 2);
function maquette_apply_premium_discount($price, $product) {
    // Only apply if user is premium member
    if (!maquette_is_premium_active()) {
        return $price;
    }

    // Don't apply to premium subscription products themselves
    $premium_product_ids = maquette_get_premium_product_ids();
    if (in_array($product->get_id(), $premium_product_ids)) {
        return $price;
    }

    // Calculate discount
    if ($price && is_numeric($price)) {
        $discount_rate = maquette_get_premium_discount_rate();
        $discounted_price = $price * (1 - ($discount_rate / 100));

        return round($discounted_price, 2);
    }

    return $price;
}

/**
 * Display premium price HTML on product pages (only for premium members)
 */
add_filter( 'woocommerce_get_price_html', 'maquette_display_premium_price_html', 99, 2);
function maquette_display_premium_price_html($price_html, $product) {

        if ( isset($_GET['et_fb']) || isset($_GET['et_tb']) ) {
        return $price_html;
    }
  if ( is_admin() && ! wp_doing_ajax() ) {
        return $price_html;
    }

     if ( ! $product instanceof WC_Product ) {
        return $price_html;
    }


       if ( $product->is_type('variable') ) {
        return $price_html;
    }
    // Only show premium pricing to premium members
   if ( ! function_exists('maquette_is_premium_active') || ! maquette_is_premium_active() ) {
        return $price_html;
    }

    // Don't show on premium subscription products
    $premium_product_ids = maquette_get_premium_product_ids();
    if (in_array($product->get_id(), $premium_product_ids)) {
        return $price_html;
    }

    // Temporarily remove premium discount filters to get real prices
    remove_filter('woocommerce_product_get_price', 'maquette_apply_premium_discount', 99);
    remove_filter('woocommerce_product_get_sale_price', 'maquette_apply_premium_discount', 99);
    remove_filter('woocommerce_product_variation_get_price', 'maquette_apply_premium_discount', 99);
    remove_filter('woocommerce_product_variation_get_sale_price', 'maquette_apply_premium_discount', 99);

    // Get original price (without premium discount)
    $original_price = $product->get_regular_price();
    $sale_price = $product->get_sale_price();
    $discount_rate = maquette_get_premium_discount_rate();

    // Restore premium discount filters
    add_filter('woocommerce_product_get_price', 'maquette_apply_premium_discount', 99, 2);
    add_filter('woocommerce_product_get_sale_price', 'maquette_apply_premium_discount', 99, 2);
    add_filter('woocommerce_product_variation_get_price', 'maquette_apply_premium_discount', 99, 2);
    add_filter('woocommerce_product_variation_get_sale_price', 'maquette_apply_premium_discount', 99, 2);

    // Calculate premium price
    $base_price = $sale_price ? $sale_price : $original_price;

    if (!$base_price || $base_price <= 0 || $base_price === '' || !is_numeric($base_price) ) {
        return $price_html;
    }

        $base_price = (float) $base_price;

    $premium_price = $base_price * (1 - ($discount_rate / 100));

    // Build custom HTML
    $html = '<div class="maquette-premium-pricing">';


    // Show only: original price (struck through) + member price
    $html .= '<span class="maquette-price-original"><del>' . wc_price($original_price) . '</del></span> ';

    // Show premium badge + price
    $html .= '<span class="maquette-premium-badge">' . __('Prix membre', 'maquette-char-promo') . '</span> ';
    $html .= '<span class="maquette-premium-price">' . wc_price($premium_price) . '</span>';

    $html .= '</div>';

    return $html;
}

/**
 * Ensure cart calculations use premium pricing
 */
add_action('woocommerce_before_calculate_totals', 'maquette_apply_premium_discount_to_cart', 99);
function maquette_apply_premium_discount_to_cart($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    if (did_action('woocommerce_before_calculate_totals') >= 2) {
        return;
    }

    // Only apply if user is premium member
    if (!maquette_is_premium_active()) {
        return;
    }

    $discount_rate = maquette_get_premium_discount_rate();
    $premium_product_ids = maquette_get_premium_product_ids();

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $product_id = $product->get_id();

        // Don't apply discount to premium subscription products
        if (in_array($product_id, $premium_product_ids)) {
            continue;
        }

        // Get current price (may already include sale price)
        $price = $product->get_price();

        if ($price && is_numeric($price)) {
            $discounted_price = $price * (1 - ($discount_rate / 100));
            $product->set_price(round($discounted_price, 2));
        }
    }
}

/**
 * Show premium discount in cart/checkout summary
 */
add_filter('woocommerce_cart_item_price', 'maquette_display_premium_cart_item_price', 99, 3);
function maquette_display_premium_cart_item_price($price_html, $cart_item, $cart_item_key) {
    if (!maquette_is_premium_active()) {
        return $price_html;
    }

    $product = $cart_item['data'];
    $premium_product_ids = maquette_get_premium_product_ids();

    if (in_array($product->get_id(), $premium_product_ids)) {
        return $price_html;
    }

    // Add badge to indicate premium pricing
    $html = '<span class="maquette-cart-premium-badge" style="font-size: 0.85em; color: #2c7a3c; font-weight: bold;">';
    $html .= __('Prix membre', 'maquette-char-promo');
    $html .= '</span><br>';
    $html .= $price_html;

    return $html;
}

/**
 * Display premium member status in cart summary
 */
add_action('woocommerce_cart_totals_before_order_total', 'maquette_display_premium_info_in_cart');
add_action('woocommerce_review_order_before_order_total', 'maquette_display_premium_info_in_cart');
function maquette_display_premium_info_in_cart() {
    if (!maquette_is_premium_active()) {
        return;
    }

    $discount_rate = maquette_get_premium_discount_rate();

    echo '<tr class="maquette-premium-info">';
    echo '<th style="color: #2c7a3c;">✓ ' . __('Réduction membre premium', 'maquette-char-promo') . '</th>';
    echo '<td style="color: #2c7a3c; font-weight: bold;">-' . $discount_rate . '%</td>';
    echo '</tr>';
}

/**
 * Add premium pricing CSS to frontend
 */
add_action('wp_enqueue_scripts', 'maquette_enqueue_premium_css');
function maquette_enqueue_premium_css() {
    wp_enqueue_style(
        'maquette-premium',
        MAQUETTE_CHAR_PROMO_PLUGIN_URL . 'assets/css/premium.css',
        [],
        '1.0.0'
    );
}
