<?php
if (!defined('ABSPATH')) {
    exit;
}

// Taux en % (par défaut 8)
function maquette_get_premium_discount_rate()
{
    return floatval(get_option('maquette_premium_discount_rate', 8));
}

function maquette_get_excluded_product_ids() {
    return [153543]; 
}

add_filter('woocommerce_product_get_sale_price', 'maquette_calculate_dynamic_price', 9999, 2);
add_filter('woocommerce_product_get_price', 'maquette_calculate_dynamic_price', 9999, 2);

function maquette_calculate_dynamic_price($price, $product) {
    // On ne modifie rien dans le back-office pour éviter les conflits
    if (is_admin()) {
        return $price;
    }

    // On ne touche pas aux produits variables (leur prix est géré au niveau des variations)
    if ($product->is_type('variable')) {
        return $price;
    }

    // Règle n°2 : On vérifie si le produit fait partie des exceptions
    // Si oui, on retourne son prix d'origine et on arrête tout.
    $excluded_product_ids = maquette_get_excluded_product_ids();
    if (in_array($product->get_id(), $excluded_product_ids)) {
        return $price; // On ne touche pas au prix de ce produit
    }

    // --- CALCUL DU PRIX DE BASE ---
    $new_price = (float) $product->get_regular_price();
    if ($new_price <= 0) {
        return $price; // Pas de prix régulier, pas de calcul possible
    }

    // Règle n°1 : On applique la réduction de base de 20% pour tout le monde
    $new_price = $new_price * 0.80;

    // Règle n°3 : On applique le bonus de 8% si le client est Premium
    // On vérifie que la fonction existe ET qu'elle retourne vrai
    if (function_exists('maquette_is_premium_active') && maquette_is_premium_active()) {
        if (function_exists('maquette_get_premium_discount_rate')) {
            $rate = maquette_get_premium_discount_rate(); // Doit retourner 8
            $new_price = $new_price * (1 - ($rate / 100)); // Applique -8% sur le prix déjà réduit
        }
    }

    // On retourne le prix final calculé
    return $new_price;
}

// CSS
add_action('wp_enqueue_scripts', 'maquette_enqueue_premium_css');
function maquette_enqueue_premium_css()
{
    wp_enqueue_style(
        'maquette-premium',
        MAQUETTE_CHAR_PROMO_PLUGIN_URL . 'assets/css/premium.css',
        [],
        '1.0.0'
    );
}
