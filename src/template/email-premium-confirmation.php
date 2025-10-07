<?php
/**
 * Email Template: Premium Membership Confirmation
 *
 * Available variables:
 * - $user: WP_User object
 * - $expiration_date: Expiration date string (Y-m-d H:i:s)
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_name = isset($user) ? $user->display_name : '';
$discount_rate = maquette_get_premium_discount_rate();
$formatted_date = date_i18n('d/m/Y', strtotime($expiration_date));

// Get custom email body from settings
$custom_body = get_option('maquette_premium_email_body', '');

if (!empty($custom_body)) {
    // Replace variables in custom body
    $body = str_replace(
        ['{user_name}', '{expiration_date}', '{discount_rate}'],
        [$user_name, $formatted_date, $discount_rate],
        $custom_body
    );
    $body = nl2br($body);
} else {
    // Default body
    $body = sprintf(
        __('Bonjour %s,<br><br>Félicitations ! Votre abonnement Premium est désormais actif.<br><br>Vous bénéficiez de %s%% de réduction sur l\'ensemble de notre catalogue, cumulable avec nos promotions en cours.<br><br>Votre abonnement est valide jusqu\'au %s.<br><br>Profitez-en dès maintenant sur notre boutique !<br><br>Cordialement,<br>L\'équipe %s', 'maquette-char-promo'),
        $user_name,
        $discount_rate,
        $formatted_date,
        get_bloginfo('name')
    );
}

// Get site colors
$primary_color = get_option('woocommerce_email_base_color', '#96588a');
$bg_color = get_option('woocommerce_email_bg_color', '#f7f7f7');
$body_bg_color = get_option('woocommerce_email_body_bg_color', '#ffffff');
$text_color = get_option('woocommerce_email_text_color', '#3c3c3c');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo get_bloginfo('name', 'display'); ?> - <?php _e('Abonnement Premium Activé', 'maquette-char-promo'); ?></title>
    <style type="text/css">
        body {
            margin: 0;
            padding: 0;
            background-color: <?php echo esc_attr($bg_color); ?>;
            font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: <?php echo esc_attr($text_color); ?>;
        }
        .email-wrapper {
            width: 100%;
            background-color: <?php echo esc_attr($bg_color); ?>;
            padding: 20px 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: <?php echo esc_attr($body_bg_color); ?>;
            border: 1px solid #dedede;
            border-radius: 5px;
            overflow: hidden;
        }
        .email-header {
            background-color: <?php echo esc_attr($primary_color); ?>;
            padding: 30px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            color: #ffffff;
            font-size: 28px;
            font-weight: 300;
        }
        .email-body {
            padding: 40px 30px;
        }
        .premium-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .expiration-info {
            background-color: #f0f8ff;
            border-left: 4px solid <?php echo esc_attr($primary_color); ?>;
            padding: 15px 20px;
            margin: 25px 0;
            border-radius: 3px;
        }
        .expiration-info strong {
            color: <?php echo esc_attr($primary_color); ?>;
        }
        .benefits-list {
            background-color: #f9f9f9;
            border-radius: 5px;
            padding: 20px 25px;
            margin: 20px 0;
        }
        .benefits-list ul {
            margin: 0;
            padding-left: 20px;
        }
        .benefits-list li {
            margin: 10px 0;
            color: #555;
        }
        .cta-button {
            display: inline-block;
            background-color: <?php echo esc_attr($primary_color); ?>;
            color: #ffffff !important;
            text-decoration: none;
            padding: 15px 40px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .cta-button:hover {
            opacity: 0.9;
        }
        .email-footer {
            background-color: #f7f7f7;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #dedede;
        }
        .email-footer a {
            color: <?php echo esc_attr($primary_color); ?>;
            text-decoration: none;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
            }
            .email-body {
                padding: 20px !important;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <!-- Header -->
            <div class="email-header">
                <h1>🎉 <?php _e('Bienvenue chez les membres Premium !', 'maquette-char-promo'); ?></h1>
            </div>

            <!-- Body -->
            <div class="email-body">
                <?php echo wp_kses_post($body); ?>

                <center>
                    <span class="premium-badge">✨ <?php _e('Membre Premium', 'maquette-char-promo'); ?> ✨</span>
                </center>

                <div class="expiration-info">
                    <strong><?php _e('Date d\'expiration :', 'maquette-char-promo'); ?></strong> <?php echo esc_html($formatted_date); ?>
                </div>

                <div class="benefits-list">
                    <strong><?php _e('Vos avantages Premium :', 'maquette-char-promo'); ?></strong>
                    <ul>
                        <li><?php printf(__('<strong>%s%% de réduction</strong> sur tout le catalogue', 'maquette-char-promo'), $discount_rate); ?></li>
                        <li><?php _e('Réduction <strong>cumulable</strong> avec les promotions en cours', 'maquette-char-promo'); ?></li>
                        <li><?php _e('Valable pendant <strong>365 jours</strong>', 'maquette-char-promo'); ?></li>
                        <li><?php _e('Prix membre visible directement sur les fiches produits', 'maquette-char-promo'); ?></li>
                    </ul>
                </div>

                <center>
                    <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>" class="cta-button">
                        <?php _e('Découvrir la boutique', 'maquette-char-promo'); ?>
                    </a>
                </center>
            </div>

            <!-- Footer -->
            <div class="email-footer">
                <p>
                    <?php printf(__('Vous recevez cet email car vous êtes membre de %s', 'maquette-char-promo'), '<strong>' . get_bloginfo('name') . '</strong>'); ?>
                </p>
                <p>
                    <a href="<?php echo esc_url(home_url()); ?>"><?php echo esc_html(home_url()); ?></a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
