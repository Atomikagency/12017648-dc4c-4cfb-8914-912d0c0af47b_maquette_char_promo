<?php

if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

/**
 * Premium Membership Management
 * - Role creation
 * - Subscription activation on product purchase
 * - Expiration checking
 * - Email confirmation
 */

/**
 * Register premium_member role on plugin initialization
 * Creates role if it doesn't exist yet
 */
add_action('init', 'maquette_create_premium_role');
function maquette_create_premium_role() {
    // Check if role already exists
    if (!get_role('premium_member')) {
        $customer_caps = get_role('customer');

        if ($customer_caps) {
            add_role(
                'premium_member',
                __('Membre Premium', 'maquette-char-promo'),
                $customer_caps->capabilities
            );

            error_log('Maquette Premium: Role premium_member created successfully');
        } else {
            error_log('Maquette Premium: Could not create role - customer role not found');
        }
    }
}

/**
 * Check if user has active premium membership
 * @param int $user_id
 * @return bool
 */
function maquette_is_premium_active($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    $user = get_userdata($user_id);

    // Check if user has premium_member role
    if (!$user || !in_array('premium_member', (array) $user->roles)) {
        return false;
    }

    // Check expiration date
    $expiration_date = get_user_meta($user_id, '_premium_expiration_date', true);

    if (!$expiration_date) {
        return false;
    }

    // Compare with current date
    return strtotime($expiration_date) > current_time('timestamp');
}

/**
 * Get premium product IDs from settings
 * @return array
 */
function maquette_get_premium_product_ids() {
    $product_ids = get_option('maquette_premium_product_ids', '');

    if (empty($product_ids)) {
        return [];
    }

    // Parse comma-separated IDs
    $ids = array_map('trim', explode(',', $product_ids));
    $ids = array_filter($ids, 'is_numeric');

    return array_map('intval', $ids);
}

/**
 * Grant premium membership when order is completed
 */
add_action('woocommerce_order_status_completed', 'maquette_grant_premium_on_purchase', 10, 1);
add_action('woocommerce_payment_complete', 'maquette_grant_premium_on_purchase', 10, 1);
add_action('woocommerce_order_status_processing', 'maquette_grant_premium_on_purchase', 10, 1);
function maquette_grant_premium_on_purchase($order_id) {
    error_log('=== Maquette Premium: Hook triggered for order #' . $order_id . ' ===');

    $order = wc_get_order($order_id);

    if (!$order) {
        error_log('Maquette Premium: Order not found for ID ' . $order_id);
        return;
    }

    // Prevent duplicate execution
    $already_granted = get_post_meta($order_id, '_maquette_premium_granted', true);
    if ($already_granted === 'yes') {
        error_log('Maquette Premium: Already granted for order #' . $order_id);
        return;
    }

    $premium_product_ids = maquette_get_premium_product_ids();
    error_log('Maquette Premium: Configured product IDs: ' . print_r($premium_product_ids, true));

    if (empty($premium_product_ids)) {
        error_log('Maquette Premium: No premium products configured in settings');
        return;
    }

    // Check if order contains premium product
    $contains_premium = false;
    $found_products = [];

    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $found_products[] = $product_id;

        if (in_array($product_id, $premium_product_ids)) {
            $contains_premium = true;
            error_log('Maquette Premium: Premium product found - ID ' . $product_id);
            break;
        }
    }

    error_log('Maquette Premium: Products in order: ' . print_r($found_products, true));

    if (!$contains_premium) {
        error_log('Maquette Premium: No premium product in this order');
        return;
    }

    // Grant premium membership
    $user_id = $order->get_user_id();

    if (!$user_id) {
        error_log('Maquette Premium: No user ID found (guest order)');
        return;
    }

    error_log('Maquette Premium: Granting premium to user ID ' . $user_id);

    $user = get_userdata($user_id);

    if (!$user) {
        error_log('Maquette Premium: User not found for ID ' . $user_id);
        return;
    }

    // Add premium_member role
    $user->add_role('premium_member');
    error_log('Maquette Premium: Role premium_member added to user ' . $user->user_login);

    // Get duration from settings
    $duration_days = intval(get_option('maquette_premium_duration_days', 365));

    // Set expiration date
    $expiration_date = date('Y-m-d H:i:s', strtotime('+' . $duration_days . ' days', current_time('timestamp')));
    update_user_meta($user_id, '_premium_expiration_date', $expiration_date);
    error_log('Maquette Premium: Expiration date set to ' . $expiration_date);

    // Log activation date
    update_user_meta($user_id, '_premium_activation_date', current_time('mysql'));

    // Mark order as processed
    update_post_meta($order_id, '_maquette_premium_granted', 'yes');

    // Send confirmation email
    maquette_send_premium_confirmation_email($user_id, $expiration_date);
    error_log('Maquette Premium: Confirmation email sent');

    // Add order note
    $order->add_order_note(__('Abonnement Premium activé jusqu\'au ' . date_i18n('d/m/Y', strtotime($expiration_date)), 'maquette-char-promo'));
    error_log('Maquette Premium: Order note added');

    error_log('=== Maquette Premium: Process completed successfully ===');
}

/**
 * Send premium confirmation email
 */
function maquette_send_premium_confirmation_email($user_id, $expiration_date) {
    $user = get_userdata($user_id);

    if (!$user) {
        return;
    }

    $to = $user->user_email;
    $subject = get_option('maquette_premium_email_subject', __('Votre abonnement Premium est activé !', 'maquette-char-promo'));

    // Load email template
    ob_start();
    include MAQUETTE_CHAR_PROMO_PLUGIN_DIR . 'template/email-premium-confirmation.php';
    $message = ob_get_clean();

    // Send HTML email
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    wp_mail($to, $subject, $message, $headers);
}

/**
 * Schedule daily cron job to check expirations
 */
add_action('wp', 'maquette_schedule_premium_expiration_check');
function maquette_schedule_premium_expiration_check() {
    if (!wp_next_scheduled('maquette_check_premium_expirations')) {
        wp_schedule_event(time(), 'daily', 'maquette_check_premium_expirations');
    }
}

/**
 * Check and remove expired premium memberships
 */
add_action('maquette_check_premium_expirations', 'maquette_remove_expired_premium_roles');
function maquette_remove_expired_premium_roles() {
    $args = [
        'role'       => 'premium_member',
        'number'     => -1,
        'meta_key'   => '_premium_expiration_date',
        'meta_value' => date('Y-m-d H:i:s'),
        'meta_compare' => '<'
    ];

    $expired_users = get_users($args);

    foreach ($expired_users as $user) {
        // Remove premium_member role
        $user->remove_role('premium_member');

        // Log expiration
        update_user_meta($user->ID, '_premium_last_expiration', current_time('mysql'));

        // Optional: Send expiration email
        maquette_send_premium_expiration_email($user->ID);
    }
}

/**
 * Send expiration notification email (optional)
 */
function maquette_send_premium_expiration_email($user_id) {
    $send_expiration_email = get_option('maquette_premium_send_expiration_email', 'no');

    if ($send_expiration_email !== 'yes') {
        return;
    }

    $user = get_userdata($user_id);

    if (!$user) {
        return;
    }

    $to = $user->user_email;
    $subject = __('Votre abonnement Premium a expiré', 'maquette-char-promo');

    $message = sprintf(
        __('Bonjour %s,<br><br>Votre abonnement Premium a expiré.<br><br>Vous pouvez le renouveler sur notre boutique.<br><br>Merci !', 'maquette-char-promo'),
        $user->display_name
    );

    $headers = ['Content-Type: text/html; charset=UTF-8'];

    wp_mail($to, $subject, $message, $headers);
}

/**
 * Display premium status in user profile (admin) with manual controls
 */
add_action('show_user_profile', 'maquette_show_premium_status_in_profile');
add_action('edit_user_profile', 'maquette_show_premium_status_in_profile');
function maquette_show_premium_status_in_profile($user) {
    // Only admins can edit premium status
    if (!current_user_can('manage_options')) {
        return;
    }

    $is_premium = maquette_is_premium_active($user->ID);
    $expiration_date = get_user_meta($user->ID, '_premium_expiration_date', true);
    $activation_date = get_user_meta($user->ID, '_premium_activation_date', true);
    $has_role = in_array('premium_member', (array) $user->roles);
    ?>
    <h3><?php _e('Statut Premium', 'maquette-char-promo'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label><?php _e('Statut actuel', 'maquette-char-promo'); ?></label></th>
            <td>
                <?php if ($is_premium): ?>
                    <span style="color: green; font-weight: bold;">✓ Membre Premium actif</span>
                <?php else: ?>
                    <span style="color: #999;">— Non-membre ou expiré</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><label for="maquette_enable_premium"><?php _e('Activer Premium', 'maquette-char-promo'); ?></label></th>
            <td>
                <label>
                    <input type="checkbox" name="maquette_enable_premium" id="maquette_enable_premium" value="1" <?php checked($has_role, true); ?>>
                    <?php _e('Accorder le rôle Premium à cet utilisateur', 'maquette-char-promo'); ?>
                </label>
                <p class="description">
                    <?php _e('Cochez cette case pour accorder manuellement l\'abonnement Premium (même sans achat).', 'maquette-char-promo'); ?>
                </p>
            </td>
        </tr>
        <tr id="maquette_expiration_row" style="<?php echo $has_role ? '' : 'display:none;'; ?>">
            <th><label for="maquette_expiration_date"><?php _e('Date d\'expiration', 'maquette-char-promo'); ?></label></th>
            <td>
                <input type="date" name="maquette_expiration_date" id="maquette_expiration_date"
                       value="<?php echo $expiration_date ? esc_attr(date('Y-m-d', strtotime($expiration_date))) : ''; ?>"
                       class="regular-text">
                <p class="description">
                    <?php _e('Date à laquelle l\'abonnement expire (format: AAAA-MM-JJ). Laissez vide pour +365 jours par défaut.', 'maquette-char-promo'); ?>
                </p>
            </td>
        </tr>
        <?php if ($activation_date): ?>
        <tr>
            <th><label><?php _e('Date d\'activation', 'maquette-char-promo'); ?></label></th>
            <td><?php echo date_i18n('d/m/Y à H:i', strtotime($activation_date)); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($expiration_date): ?>
        <tr>
            <th><label><?php _e('Expiration actuelle', 'maquette-char-promo'); ?></label></th>
            <td>
                <?php echo date_i18n('d/m/Y à H:i', strtotime($expiration_date)); ?>
                <?php if (!$is_premium): ?>
                    <span style="color: red;">(Expiré)</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endif; ?>
    </table>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#maquette_enable_premium').on('change', function() {
            if ($(this).is(':checked')) {
                $('#maquette_expiration_row').show();

                // Set default expiration date if empty
                var expirationInput = $('#maquette_expiration_date');
                if (!expirationInput.val()) {
                    var defaultDate = new Date();
                    defaultDate.setFullYear(defaultDate.getFullYear() + 1);
                    expirationInput.val(defaultDate.toISOString().split('T')[0]);
                }
            } else {
                $('#maquette_expiration_row').hide();
            }
        });
    });
    </script>
    <?php
}

/**
 * Save manual premium settings from user profile
 */
add_action('personal_options_update', 'maquette_save_manual_premium_settings');
add_action('edit_user_profile_update', 'maquette_save_manual_premium_settings');
function maquette_save_manual_premium_settings($user_id) {
    // Only admins can edit premium status
    if (!current_user_can('manage_options')) {
        return;
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return;
    }

    $enable_premium = isset($_POST['maquette_enable_premium']) && $_POST['maquette_enable_premium'] == '1';
    $expiration_date_input = isset($_POST['maquette_expiration_date']) ? sanitize_text_field($_POST['maquette_expiration_date']) : '';

    if ($enable_premium) {
        // Add premium role
        if (!in_array('premium_member', (array) $user->roles)) {
            $user->add_role('premium_member');
            error_log('Maquette Premium: Manual role added to user ID ' . $user_id);
        }

        // Set expiration date
        if (!empty($expiration_date_input)) {
            // User provided a date
            $expiration_date = date('Y-m-d H:i:s', strtotime($expiration_date_input . ' 23:59:59'));
        } else {
            // Default: +365 days
            $duration_days = intval(get_option('maquette_premium_duration_days', 365));
            $expiration_date = date('Y-m-d H:i:s', strtotime('+' . $duration_days . ' days', current_time('timestamp')));
        }

        update_user_meta($user_id, '_premium_expiration_date', $expiration_date);

        // Set activation date if not already set
        if (!get_user_meta($user_id, '_premium_activation_date', true)) {
            update_user_meta($user_id, '_premium_activation_date', current_time('mysql'));
        }

        error_log('Maquette Premium: Manual expiration date set to ' . $expiration_date . ' for user ID ' . $user_id);

    } else {
        // Remove premium role
        if (in_array('premium_member', (array) $user->roles)) {
            $user->remove_role('premium_member');
            error_log('Maquette Premium: Manual role removed from user ID ' . $user_id);
        }
    }
}

/**
 * Add manual activation button in order edit page
 */
add_action('woocommerce_admin_order_data_after_order_details', 'maquette_add_manual_premium_activation_button');
function maquette_add_manual_premium_activation_button($order) {
    $order_id = $order->get_id();
    $already_granted = get_post_meta($order_id, '_maquette_premium_granted', true);
    $user_id = $order->get_user_id();

    if (!$user_id) {
        return; // Guest order
    }

    ?>
    <div class="order_data_column" style="clear:both; padding-top: 15px; border-top: 1px solid #ddd; margin-top: 15px;">
        <h4><?php _e('Abonnement Premium', 'maquette-char-promo'); ?></h4>

        <?php if ($already_granted === 'yes'): ?>
            <p style="color: green; font-weight: bold;">
                ✓ <?php _e('Premium déjà activé pour cette commande', 'maquette-char-promo'); ?>
            </p>
        <?php else: ?>
            <p style="color: #999;">
                <?php _e('Premium non activé pour cette commande', 'maquette-char-promo'); ?>
            </p>
            <button type="button" class="button button-primary" id="maquette-manual-premium-grant" data-order-id="<?php echo esc_attr($order_id); ?>">
                <?php _e('Activer Premium manuellement', 'maquette-char-promo'); ?>
            </button>
            <span id="maquette-premium-result" style="margin-left: 10px;"></span>
        <?php endif; ?>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#maquette-manual-premium-grant').on('click', function(e) {
                e.preventDefault();

                var button = $(this);
                var orderId = button.data('order-id');
                var resultSpan = $('#maquette-premium-result');

                button.prop('disabled', true).text('<?php _e('Activation en cours...', 'maquette-char-promo'); ?>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'maquette_manual_premium_grant',
                        order_id: orderId,
                        security: '<?php echo wp_create_nonce('maquette_manual_premium_grant'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            resultSpan.html('<span style="color: green; font-weight: bold;">✓ ' + response.data.message + '</span>');
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            resultSpan.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
                            button.prop('disabled', false).text('<?php _e('Activer Premium manuellement', 'maquette-char-promo'); ?>');
                        }
                    },
                    error: function() {
                        resultSpan.html('<span style="color: red;">✗ <?php _e('Erreur lors de l\'activation', 'maquette-char-promo'); ?></span>');
                        button.prop('disabled', false).text('<?php _e('Activer Premium manuellement', 'maquette-char-promo'); ?>');
                    }
                });
            });
        });
        </script>
    </div>
    <?php
}

/**
 * AJAX handler for manual premium activation
 */
add_action('wp_ajax_maquette_manual_premium_grant', 'maquette_handle_manual_premium_grant');
function maquette_handle_manual_premium_grant() {
    check_ajax_referer('maquette_manual_premium_grant', 'security');

    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => __('Permission refusée', 'maquette-char-promo')]);
    }

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

    if (!$order_id) {
        wp_send_json_error(['message' => __('ID de commande invalide', 'maquette-char-promo')]);
    }

    // Force premium activation
    error_log('Maquette Premium: Manual activation triggered for order #' . $order_id);
    maquette_grant_premium_on_purchase($order_id);

    wp_send_json_success(['message' => __('Premium activé avec succès !', 'maquette-char-promo')]);
}
