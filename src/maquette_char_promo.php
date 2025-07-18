<?php

/**
 * Plugin Name: Maquette char promo
 * Description: Add extra feature
 * Version: 1.0.0
 * Author: AtomikAgency
 * Author URI: https://atomikagency.fr/
 */

define('MAQUETTE_CHAR_PROMO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MAQUETTE_CHAR_PROMO_PLUGIN_URL', plugin_dir_url(__FILE__));

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

require_once MAQUETTE_CHAR_PROMO_PLUGIN_DIR . 'update-checker.php';