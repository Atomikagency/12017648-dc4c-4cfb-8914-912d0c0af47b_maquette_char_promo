<?php
if (!defined('ABSPATH')) { exit; }

// Taux en % (par défaut 8)
function maquette_get_premium_discount_rate() {
    return floatval(get_option('maquette_premium_discount_rate', 8));
}

// === Appliquer la remise premium sur les prix (une seule fois) ===
add_filter('woocommerce_product_get_price', 'maquette_apply_premium_discount', 99, 2);
add_filter('woocommerce_product_get_sale_price', 'maquette_apply_premium_discount', 99, 2);
add_filter('woocommerce_product_variation_get_price', 'maquette_apply_premium_discount', 99, 2);
add_filter('woocommerce_product_variation_get_sale_price', 'maquette_apply_premium_discount', 99, 2);

function maquette_apply_premium_discount($price, $product) {
    // Uniquement pour les membres premium
    if (!function_exists('maquette_is_premium_active') || !maquette_is_premium_active()) {
        return $price;
    }

    // Ne pas appliquer sur les produits d'abonnement premium
    if (function_exists('maquette_get_premium_product_ids')) {
        $premium_product_ids = maquette_get_premium_product_ids();
        if (in_array($product->get_id(), (array) $premium_product_ids, true)) {
            return $price;
        }
    }

    if ($price === '' || !is_numeric($price)) {
        return $price;
    }

    $regular = (float) $product->get_regular_price();
    if ($regular <= 0) {
        // S’il n’y a pas de regular price, on ne peut pas calculer la part -8% d’origine
        return $price;
    }

    $rate = maquette_get_premium_discount_rate();
    // Calculer la remise premium SUR LE PRIX D’ORIGINE
    $premium_discount_from_regular = $regular * ($rate / 100);

    // Prix courant (peut déjà incorporer -20% via sale price)
    $current = (float) $price;

    // Prix membre = prix courant - (8% du regular)
    $member_price = max(0, $current - $premium_discount_from_regular);

    // Retourner un décimal WooCommerce correct
    return wc_format_decimal($member_price, wc_get_price_decimals());
}

// === Affichage du prix membre sur la fiche produit (pour les membres) ===
add_filter('woocommerce_get_price_html', 'maquette_display_premium_price_html', 99, 2);
function maquette_display_premium_price_html($price_html, $product) {

    // Ne pas casser l’éditeur et l’admin
    if (isset($_GET['et_fb']) || isset($_GET['et_tb'])) return $price_html;
    if (is_admin() && !wp_doing_ajax()) return $price_html;
    if (!$product instanceof WC_Product) return $price_html;

    // Optionnel : pour les variables, on laisse le html par défaut
    if ($product->is_type('variable')) return $price_html;

    if (!function_exists('maquette_is_premium_active') || !maquette_is_premium_active()) return $price_html;

    if (function_exists('maquette_get_premium_product_ids')) {
        $premium_product_ids = maquette_get_premium_product_ids();
        if (in_array($product->get_id(), (array) $premium_product_ids, true)) return $price_html;
    }

    // On veut afficher le prix public actuel (non-membre) + le prix membre
    // → On enlève temporairement le filtre qui applique la remise premium
    remove_filter('woocommerce_product_get_price', 'maquette_apply_premium_discount', 99);
    remove_filter('woocommerce_product_get_sale_price', 'maquette_apply_premium_discount', 99);
    remove_filter('woocommerce_product_variation_get_price', 'maquette_apply_premium_discount', 99);
    remove_filter('woocommerce_product_variation_get_sale_price', 'maquette_apply_premium_discount', 99);

    $regular    = (float) $product->get_regular_price();
    $public_now = (float) $product->get_price(); // prix que voit un non-membre (peut déjà être -20%)

    // On remet les filtres
    add_filter('woocommerce_product_get_price', 'maquette_apply_premium_discount', 99, 2);
    add_filter('woocommerce_product_get_sale_price', 'maquette_apply_premium_discount', 99, 2);
    add_filter('woocommerce_product_variation_get_price', 'maquette_apply_premium_discount', 99, 2);
    add_filter('woocommerce_product_variation_get_sale_price', 'maquette_apply_premium_discount', 99, 2);

    if ($regular <= 0 || $public_now <= 0) {
        return $price_html;
    }

    $rate = maquette_get_premium_discount_rate();
    $premium_discount_from_regular = $regular * ($rate / 100);
    $member_price = max(0, $public_now - $premium_discount_from_regular);

    // HTML : on barre le prix public actuel, puis on affiche le prix membre
    $html  = '<div class="maquette-premium-pricing">';
    $html .= '<span class="maquette-price-public"><del>' . wc_price($public_now) . '</del></span> ';
    $html .= '<span class="maquette-premium-badge">' . esc_html__('Prix membre', 'maquette-char-promo') . '</span> ';
    $html .= '<span class="maquette-premium-price">' . wc_price($member_price) . '</span>';
    $html .= '</div>';

    return $html;
}

// === IMPORTANT : ne pas redéduire dans le panier ===
// Supprime/LAISSE COMMENTÉ l’action suivante pour éviter la double remise.
// add_action('woocommerce_before_calculate_totals', 'maquette_apply_premium_discount_to_cart', 99);
// function maquette_apply_premium_discount_to_cart($cart) { /* plus nécessaire */ }

// CSS
add_action('wp_enqueue_scripts', 'maquette_enqueue_premium_css');
function maquette_enqueue_premium_css() {
    wp_enqueue_style(
        'maquette-premium',
        MAQUETTE_CHAR_PROMO_PLUGIN_URL . 'assets/css/premium.css',
        [],
        '1.0.0'
    );
}
