<?php


function maquette_char_promo_check_for_update( $transient ) {
    if ( empty( $transient->checked ) ) {
        return $transient;
    }

    // URL de votre endpoint de mise à jour
    $update_url = 'https://plugin-manager.atomikagency.fr/api/pull/12017648-dc4c-4cfb-8914-912d0c0af47b';

    // Récupère les données de mise à jour
    $response = wp_remote_get( $update_url );
    if ( is_wp_error( $response ) ) {
        return $transient;
    }

    $update_data = json_decode( wp_remote_retrieve_body( $response) );

    // Compare les versions
    $plugin_slug = plugin_basename( MAQUETTE_CHAR_PROMO_PLUGIN_DIR.'/maquette_char_promo.php' );
    $current_version = get_plugin_data( MAQUETTE_CHAR_PROMO_PLUGIN_DIR.'/maquette_char_promo.php' )['Version'];

    if ( version_compare( $current_version, $update_data->version, '<' ) ) {
        $transient->response[ $plugin_slug ] = (object) [
            'slug'        => $plugin_slug,
            'new_version' => $update_data->version,
            'package'     => $update_data->package,
            'url'         => 'https://atomikagency.fr/', // Page de détails du plugin
        ];
    }

    return $transient;
}
add_filter( 'site_transient_update_plugins', 'maquette_char_promo_check_for_update' );
