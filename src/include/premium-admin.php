<?php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

/**
 * Premium Admin Settings
 * - Settings page in WooCommerce
 * - Configuration for product IDs, discount rate, emails
 */

/**
 * Add settings tab to WooCommerce settings
 */
add_filter('woocommerce_settings_tabs_array', 'maquette_add_premium_settings_tab', 50);
function maquette_add_premium_settings_tab($settings_tabs) {
    $settings_tabs['premium'] = __('Premium', 'maquette-char-promo');
    return $settings_tabs;
}

/**
 * Display settings fields
 */
add_action('woocommerce_settings_tabs_premium', 'maquette_premium_settings_tab_content');
function maquette_premium_settings_tab_content() {
    woocommerce_admin_fields(maquette_get_premium_settings());
}

/**
 * Save settings
 */
add_action('woocommerce_update_options_premium', 'maquette_update_premium_settings');
function maquette_update_premium_settings() {
    woocommerce_update_options(maquette_get_premium_settings());
}

/**
 * Define settings fields
 */
function maquette_get_premium_settings() {
    $settings = [
        [
            'name' => __('Configuration Abonnement Premium', 'maquette-char-promo'),
            'type' => 'title',
            'desc' => __('Configurez les produits qui déclenchent l\'abonnement premium et les paramètres de réduction.', 'maquette-char-promo'),
            'id'   => 'maquette_premium_settings'
        ],
        [
            'name'     => __('ID(s) Produit(s) Abonnement', 'maquette-char-promo'),
            'type'     => 'text',
            'desc'     => __('IDs des produits qui accordent l\'abonnement premium (séparés par des virgules). Ex: 123, 456, 789', 'maquette-char-promo'),
            'id'       => 'maquette_premium_product_ids',
            'css'      => 'min-width:300px;',
            'default'  => '',
            'desc_tip' => true,
        ],
        [
            'name'     => __('Taux de réduction (%)', 'maquette-char-promo'),
            'type'     => 'number',
            'desc'     => __('Pourcentage de réduction accordé aux membres premium (appliqué après les promotions existantes).', 'maquette-char-promo'),
            'id'       => 'maquette_premium_discount_rate',
            'css'      => 'width:80px;',
            'default'  => '8',
            'custom_attributes' => [
                'step' => '0.01',
                'min'  => '0',
                'max'  => '100'
            ],
            'desc_tip' => true,
        ],
        [
            'name'     => __('Durée abonnement (jours)', 'maquette-char-promo'),
            'type'     => 'number',
            'desc'     => __('Nombre de jours de validité de l\'abonnement après l\'achat.', 'maquette-char-promo'),
            'id'       => 'maquette_premium_duration_days',
            'css'      => 'width:80px;',
            'default'  => '365',
            'custom_attributes' => [
                'step' => '1',
                'min'  => '1'
            ],
            'desc_tip' => true,
        ],
        [
            'type' => 'sectionend',
            'id'   => 'maquette_premium_settings'
        ],
        [
            'name' => __('Configuration Email de Confirmation', 'maquette-char-promo'),
            'type' => 'title',
            'desc' => __('Personnalisez l\'email envoyé lors de l\'activation de l\'abonnement premium.', 'maquette-char-promo'),
            'id'   => 'maquette_premium_email_settings'
        ],
        [
            'name'     => __('Sujet de l\'email', 'maquette-char-promo'),
            'type'     => 'text',
            'desc'     => __('Sujet de l\'email de confirmation.', 'maquette-char-promo'),
            'id'       => 'maquette_premium_email_subject',
            'css'      => 'min-width:400px;',
            'default'  => __('Votre abonnement Premium est activé !', 'maquette-char-promo'),
            'desc_tip' => false,
        ],
        [
            'name'     => __('Contenu de l\'email', 'maquette-char-promo'),
            'type'     => 'textarea',
            'desc'     => __('Contenu principal de l\'email. Variables disponibles: {user_name}, {expiration_date}, {discount_rate}', 'maquette-char-promo'),
            'id'       => 'maquette_premium_email_body',
            'css'      => 'min-width:400px; min-height:150px;',
            'default'  => maquette_get_default_email_body(),
            'desc_tip' => false,
        ],
        [
            'name'     => __('Envoyer email d\'expiration', 'maquette-char-promo'),
            'type'     => 'checkbox',
            'desc'     => __('Envoyer un email automatique lorsque l\'abonnement expire.', 'maquette-char-promo'),
            'id'       => 'maquette_premium_send_expiration_email',
            'default'  => 'no',
        ],
        [
            'type' => 'sectionend',
            'id'   => 'maquette_premium_email_settings'
        ],
        [
            'name' => __('Statistiques Premium', 'maquette-char-promo'),
            'type' => 'title',
            'desc' => maquette_get_premium_statistics(),
            'id'   => 'maquette_premium_stats'
        ],
        [
            'type' => 'sectionend',
            'id'   => 'maquette_premium_stats'
        ],
    ];

    return apply_filters('maquette_premium_settings', $settings);
}

/**
 * Get default email body template
 */
function maquette_get_default_email_body() {
    return "Bonjour {user_name},\n\n" .
           "Félicitations ! Votre abonnement Premium est désormais actif.\n\n" .
           "Vous bénéficiez de {discount_rate}% de réduction sur l'ensemble de notre catalogue, cumulable avec nos promotions en cours.\n\n" .
           "Votre abonnement est valide jusqu'au {expiration_date}.\n\n" .
           "Profitez-en dès maintenant sur notre boutique !\n\n" .
           "Cordialement,\n" .
           "L'équipe " . get_bloginfo('name');
}

/**
 * Display premium membership statistics
 */
function maquette_get_premium_statistics() {
    $args = [
        'role'   => 'premium_member',
        'number' => -1
    ];

    $premium_users = get_users($args);
    $active_count = 0;
    $expired_count = 0;

    foreach ($premium_users as $user) {
        if (maquette_is_premium_active($user->ID)) {
            $active_count++;
        } else {
            $expired_count++;
        }
    }

    $total_revenue = maquette_calculate_premium_revenue();

    $html = '<div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-top: 10px;">';
    $html .= '<strong>' . __('Membres actifs:', 'maquette-char-promo') . '</strong> ' . $active_count . '<br>';
    $html .= '<strong>' . __('Membres expirés:', 'maquette-char-promo') . '</strong> ' . $expired_count . '<br>';
    $html .= '<strong>' . __('Total membres:', 'maquette-char-promo') . '</strong> ' . count($premium_users) . '<br>';

    if ($total_revenue) {
        $html .= '<strong>' . __('Revenus abonnements:', 'maquette-char-promo') . '</strong> ' . wc_price($total_revenue);
    }

    $html .= '</div>';

    return $html;
}

/**
 * Calculate total revenue from premium subscriptions
 */
function maquette_calculate_premium_revenue() {
    $premium_product_ids = maquette_get_premium_product_ids();

    if (empty($premium_product_ids)) {
        return 0;
    }

    global $wpdb;

    $ids_placeholder = implode(',', array_fill(0, count($premium_product_ids), '%d'));

    $query = "
        SELECT SUM(order_item_meta.meta_value) as total
        FROM {$wpdb->prefix}woocommerce_order_items as order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta
            ON order_items.order_item_id = order_item_meta.order_item_id
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as product_meta
            ON order_items.order_item_id = product_meta.order_item_id
        LEFT JOIN {$wpdb->posts} as posts
            ON order_items.order_id = posts.ID
        WHERE order_items.order_item_type = 'line_item'
        AND order_item_meta.meta_key = '_line_total'
        AND product_meta.meta_key = '_product_id'
        AND product_meta.meta_value IN ($ids_placeholder)
        AND posts.post_status = 'wc-completed'
    ";

    $result = $wpdb->get_var($wpdb->prepare($query, $premium_product_ids));

    return floatval($result);
}

/**
 * Add quick link to premium settings in plugins page
 */
add_filter('plugin_action_links_' . plugin_basename(MAQUETTE_CHAR_PROMO_PLUGIN_DIR . 'maquette_char_promo.php'), 'maquette_add_premium_settings_link');
function maquette_add_premium_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=premium') . '">' . __('Paramètres Premium', 'maquette-char-promo') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
