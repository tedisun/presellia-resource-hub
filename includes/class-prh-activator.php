<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gère l'activation, la désactivation et les migrations de Presellia Resource Hub.
 */
class PRH_Activator {

    /**
     * Exécuté lors de l'activation du plugin.
     */
    public static function activate(): void {
        self::create_logs_table();
        self::set_default_options();

        // Enregistrement immédiat du CPT pour que le flush fonctionne
        require_once PRH_PATH . 'includes/class-prh-cpt.php';
        PRH_Cpt::register_post_type();
        PRH_Cpt::register_taxonomy();

        flush_rewrite_rules();

        // Programmation du cron hebdomadaire de nettoyage des vieux logs
        if ( ! wp_next_scheduled( 'prh_weekly_cleanup' ) ) {
            wp_schedule_event( time(), 'weekly', 'prh_weekly_cleanup' );
        }
    }

    /**
     * Exécuté à chaque chargement pour assurer l'idempotence des migrations.
     */
    public static function maybe_upgrade(): void {
        if ( get_option( 'prh_db_version' ) === PRH_VERSION ) {
            return;
        }

        self::create_logs_table();
        self::set_default_options();

        // Une mise à jour par remplacement de fichiers ne repasse pas par activate() :
        // on s'assure ici que la purge hebdomadaire des logs reste planifiée.
        if ( ! wp_next_scheduled( 'prh_weekly_cleanup' ) ) {
            wp_schedule_event( time(), 'weekly', 'prh_weekly_cleanup' );
        }

        update_option( 'prh_db_version', PRH_VERSION );
    }

    /**
     * Exécuté lors de la désactivation du plugin.
     */
    public static function deactivate(): void {
        $timestamp = wp_next_scheduled( 'prh_weekly_cleanup' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'prh_weekly_cleanup' );
        }

        flush_rewrite_rules();
    }

    /**
     * Crée la table prh_logs via dbDelta.
     */
    private static function create_logs_table(): void {
        global $wpdb;

        $table_name      = $wpdb->prefix . 'prh_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            level varchar(10) NOT NULL DEFAULT 'info',
            event varchar(50) NOT NULL DEFAULT '',
            message text NOT NULL,
            context longtext DEFAULT NULL,
            ip varchar(45) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY level (level),
            KEY event (event),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Initialise les options par défaut si inexistantes.
     */
    private static function set_default_options(): void {
        if ( ! get_option( 'prh_api_key' ) ) {
            // Génère une clé de 32 caractères aléatoires
            $random_key = 'prh_' . wp_generate_password( 28, false, false );
            add_option( 'prh_api_key', $random_key );
        }

        if ( ! get_option( 'prh_hub_title' ) ) {
            add_option( 'prh_hub_title', 'Espace Ressources & Téléchargements' );
        }

        if ( ! get_option( 'prh_hub_subtitle' ) ) {
            add_option( 'prh_hub_subtitle', 'Tous les outils de diagnostic, guides d’experts et installateurs officiels certifiés Presellia.' );
        }
    }
}
