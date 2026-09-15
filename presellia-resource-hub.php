<?php
/**
 * Plugin Name:       Presellia Resource Hub
 * Plugin URI:        https://presellia.com
 * Description:       Espace Ressources, Guides, Outils et Diagnostics Presellia avec capture FluentCRM, offres WooCommerce et API MCP pour Agents IA.
 * Version:           1.0.2
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Tedisun SARL
 * Author URI:        https://presellia.com
 * License:           Proprietary
 * Text Domain:       presellia-resource-hub
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// -----------------------------------------------------------------------------
// Constantes globales
// -----------------------------------------------------------------------------
define( 'PRH_VERSION', '1.0.2' );
define( 'PRH_PATH', plugin_dir_path( __FILE__ ) );
define( 'PRH_URL', plugin_dir_url( __FILE__ ) );
define( 'PRH_BASENAME', plugin_basename( __FILE__ ) );

// -----------------------------------------------------------------------------
// Compatibilité WooCommerce HPOS (High-Performance Order Storage)
// -----------------------------------------------------------------------------
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
} );

// -----------------------------------------------------------------------------
// Activation / Désactivation
// -----------------------------------------------------------------------------
require_once PRH_PATH . 'includes/class-prh-activator.php';

register_activation_hook( __FILE__, [ 'PRH_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'PRH_Activator', 'deactivate' ] );

// -----------------------------------------------------------------------------
// Chargement du plugin
// -----------------------------------------------------------------------------
add_action( 'plugins_loaded', 'prh_init' );

function prh_init(): void {
    // Migration automatique / idempotence
    PRH_Activator::maybe_upgrade();

    // Classes fondamentales
    require_once PRH_PATH . 'includes/class-prh-logger.php';
    require_once PRH_PATH . 'includes/class-prh-cpt.php';
    require_once PRH_PATH . 'includes/class-prh-metaboxes.php';
    require_once PRH_PATH . 'includes/class-prh-fluentcrm.php';
    require_once PRH_PATH . 'includes/class-prh-templates.php';
    require_once PRH_PATH . 'includes/class-prh-api.php';
    require_once PRH_PATH . 'includes/class-prh-admin.php';

    // Instanciations
    new PRH_Cpt();
    new PRH_Metaboxes();
    new PRH_FluentCRM();
    new PRH_Templates();
    new PRH_Api();
    new PRH_Admin();

    // Enqueue front-end assets
    add_action( 'wp_enqueue_scripts', 'prh_enqueue_front_assets' );
}

/**
 * Enregistrement des assets front-end
 */
function prh_enqueue_front_assets(): void {
    if ( is_post_type_archive( 'presellia_ressource' ) || is_singular( 'presellia_ressource' ) || is_tax( 'categorie_ressource' ) ) {
        wp_enqueue_style(
            'prh-frontend',
            PRH_URL . 'assets/css/resource-hub.css',
            [],
            PRH_VERSION
        );

        wp_enqueue_script(
            'prh-frontend',
            PRH_URL . 'assets/js/resource-hub.js',
            [ 'jquery' ],
            PRH_VERSION,
            true
        );

        wp_localize_script(
            'prh-frontend',
            'prh_ajax',
            [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'prh_lead_capture_nonce' ),
            ]
        );
    }
}
