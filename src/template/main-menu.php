<?php
/**
 * Template: Main Menu
 * Displays navigation menu with search, cart, and account sections
 *
 * Available variables:
 * @var array  $atts            Shortcode attributes
 * @var bool   $is_user_logged  Whether user is logged in
 * @var WP_User $current_user   Current user object
 * @var bool   $is_premium      Whether user has active premium membership
 * @var int    $cart_count      Number of items in cart
 * @var string $cart_total      Formatted cart total
 * @var string $cart_url        Cart page URL
 * @var string $account_url     My account page URL
 * @var string $shop_url        Shop page URL
 */

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="maquette-main-menu-wrapper <?php echo esc_attr($atts['menu_class']); ?>">
    <div class="maquette-menu-container">

        <!-- Logo / Brand (optional) -->
        <div class="maquette-menu-logo">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <?php
                $custom_logo_id = get_theme_mod('custom_logo');
                if ($custom_logo_id) {
                    echo wp_get_attachment_image($custom_logo_id, 'full', false, ['class' => 'maquette-logo-img']);
                } else {
                    echo '<span class="maquette-site-title">' . esc_html(get_bloginfo('name')) . '</span>';
                }
                ?>
            </a>
        </div>

        <!-- Main Navigation Menu -->
        <nav class="maquette-menu-nav">
            <?php
            if (has_nav_menu('main_menu')) {
                wp_nav_menu([
                    'theme_location'  => 'main_menu',
                    'container'       => false,
                    'menu_class'      => 'maquette-nav-menu',
                    'fallback_cb'     => false,
                    'depth'           => 2,
                    'walker'          => new Walker_Nav_Menu()
                ]);
            } else {
                echo '<div class="maquette-menu-notice">';
                if (current_user_can('manage_options')) {
                    echo '<p>' . __('Veuillez assigner un menu à l\'emplacement "Menu Principal Maquette" dans <a href="' . admin_url('nav-menus.php') . '">Apparence → Menus</a>.', 'maquette-char-promo') . '</p>';
                }
                echo '</div>';
            }
            ?>
        </nav>

        <!-- Right Section: Search, Cart, Account -->
        <div class="maquette-menu-actions">

            <?php if ($atts['show_search'] === 'yes'): ?>
            <!-- Search Form -->
            <div class="maquette-menu-search">
                <button class="maquette-search-toggle" aria-label="<?php esc_attr_e('Rechercher', 'maquette-char-promo'); ?>">
                    <span class="dashicons dashicons-search"></span>
                </button>
                <div class="maquette-search-form-wrapper">
                    <form role="search" method="get" class="maquette-search-form" action="<?php echo esc_url(home_url('/')); ?>">
                        <input type="search"
                               class="maquette-search-input"
                               placeholder="<?php esc_attr_e('Rechercher des produits...', 'maquette-char-promo'); ?>"
                               value="<?php echo get_search_query(); ?>"
                               name="s">
                        <input type="hidden" name="post_type" value="product">
                        <button type="submit" class="maquette-search-submit">
                            <span class="dashicons dashicons-search"></span>
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($atts['show_cart'] === 'yes' && class_exists('WooCommerce')): ?>
            <!-- Cart -->
            <div class="maquette-menu-cart">
                <a href="<?php echo esc_url($cart_url); ?>" class="maquette-cart-link">
                    <span class="dashicons <?php echo esc_attr($atts['cart_icon']); ?>"></span>
                    <?php if ($cart_count > 0): ?>
                        <span class="maquette-cart-count"><?php echo esc_html($cart_count); ?></span>
                    <?php endif; ?>
                    <span class="maquette-cart-total"><?php echo $cart_total; ?></span>
                </a>
            </div>
            <?php endif; ?>

            <?php if ($atts['show_account'] === 'yes'): ?>
            <!-- Account -->
            <div class="maquette-menu-account">
                <?php if ($is_user_logged): ?>
                    <!-- Logged In User -->
                    <div class="maquette-account-dropdown">
                        <button class="maquette-account-toggle">
                            <span class="dashicons <?php echo esc_attr($atts['account_icon']); ?>"></span>
                            <span class="maquette-account-name">
                                <?php echo esc_html($current_user->display_name); ?>
                                <?php if ($is_premium): ?>
                                    <span class="maquette-premium-badge-small">Premium</span>
                                <?php endif; ?>
                            </span>
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>

                        <div class="maquette-account-dropdown-menu">
                            <ul>
                                <li>
                                    <a href="<?php echo esc_url($account_url); ?>">
                                        <span class="dashicons dashicons-admin-home"></span>
                                        <?php _e('Mon compte', 'maquette-char-promo'); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="<?php echo esc_url(wc_get_endpoint_url('orders', '', $account_url)); ?>">
                                        <span class="dashicons dashicons-list-view"></span>
                                        <?php _e('Mes commandes', 'maquette-char-promo'); ?>
                                    </a>
                                </li>
                                <?php if ($is_premium): ?>
                                <li class="maquette-premium-item">
                                    <a href="<?php echo esc_url($account_url); ?>">
                                        <span class="dashicons dashicons-star-filled"></span>
                                        <?php _e('Mon abonnement Premium', 'maquette-char-promo'); ?>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <li>
                                    <a href="<?php echo esc_url(wc_get_endpoint_url('edit-address', '', $account_url)); ?>">
                                        <span class="dashicons dashicons-location"></span>
                                        <?php _e('Adresses', 'maquette-char-promo'); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="<?php echo esc_url(wc_get_endpoint_url('edit-account', '', $account_url)); ?>">
                                        <span class="dashicons dashicons-admin-generic"></span>
                                        <?php _e('Paramètres', 'maquette-char-promo'); ?>
                                    </a>
                                </li>
                                <li class="maquette-logout-item">
                                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>">
                                        <span class="dashicons dashicons-exit"></span>
                                        <?php _e('Déconnexion', 'maquette-char-promo'); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Not Logged In -->
                    <a href="<?php echo esc_url($account_url); ?>" class="maquette-account-login">
                        <span class="dashicons <?php echo esc_attr($atts['account_icon']); ?>"></span>
                        <span><?php _e('Connexion / Inscription', 'maquette-char-promo'); ?></span>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>

        <!-- Mobile Menu Toggle -->
        <button class="maquette-mobile-toggle" aria-label="<?php esc_attr_e('Menu', 'maquette-char-promo'); ?>">
            <span class="dashicons dashicons-menu"></span>
        </button>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search toggle
    const searchToggle = document.querySelector('.maquette-search-toggle');
    const searchForm = document.querySelector('.maquette-search-form-wrapper');

    if (searchToggle && searchForm) {
        searchToggle.addEventListener('click', function(e) {
            e.preventDefault();
            searchForm.classList.toggle('active');
            if (searchForm.classList.contains('active')) {
                searchForm.querySelector('.maquette-search-input').focus();
            }
        });

        // Close search on click outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.maquette-menu-search')) {
                searchForm.classList.remove('active');
            }
        });
    }

    // Account dropdown toggle
    const accountToggle = document.querySelector('.maquette-account-toggle');
    const accountMenu = document.querySelector('.maquette-account-dropdown-menu');

    if (accountToggle && accountMenu) {
        accountToggle.addEventListener('click', function(e) {
            e.preventDefault();
            accountMenu.classList.toggle('active');
        });

        // Close dropdown on click outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.maquette-account-dropdown')) {
                accountMenu.classList.remove('active');
            }
        });
    }

    // Mobile menu toggle
    const mobileToggle = document.querySelector('.maquette-mobile-toggle');
    const menuNav = document.querySelector('.maquette-menu-nav');

    if (mobileToggle && menuNav) {
        mobileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            menuNav.classList.toggle('active');
            this.classList.toggle('active');
        });
    }
});
</script>
