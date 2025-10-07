<?php

/**
 * Plugin Name: Maquette char promo
 * Description: Add extra feature with WooCommerce customer groups and metadata
 * Version: 1.1.0
 * Author: AtomikAgency
 * Author URI: https://atomikagency.fr/
 */

define('MAQUETTE_CHAR_PROMO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MAQUETTE_CHAR_PROMO_PLUGIN_URL', plugin_dir_url(__FILE__));

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

// Core files
require_once MAQUETTE_CHAR_PROMO_PLUGIN_DIR . 'update-checker.php';

// Include files (backend functions)
require_once MAQUETTE_CHAR_PROMO_PLUGIN_DIR . 'include/product-fields.php';
require_once MAQUETTE_CHAR_PROMO_PLUGIN_DIR . 'include/api-endpoints.php';

// Premium membership system
require_once MAQUETTE_CHAR_PROMO_PLUGIN_DIR . 'include/premium-membership.php';
require_once MAQUETTE_CHAR_PROMO_PLUGIN_DIR . 'include/premium-pricing.php';
require_once MAQUETTE_CHAR_PROMO_PLUGIN_DIR . 'include/premium-admin.php';

// Shortcodes
require_once MAQUETTE_CHAR_PROMO_PLUGIN_DIR . 'include/shortcodes.php';
