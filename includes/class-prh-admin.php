<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Interface d'administration pour les réglages et le diagnostic MCP.
 */
class PRH_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'handle_admin_actions' ] );
    }

    public function register_admin_menu(): void {
        add_submenu_page(
            'edit.php?post_type=' . PRH_Cpt::POST_TYPE,
            __( 'Réglages & Diagnostic', 'presellia-resource-hub' ),
            __( 'Réglages & API', 'presellia-resource-hub' ),
            'manage_options',
            'prh-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    public function handle_admin_actions(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Sauvegarde des réglages généraux
        if ( isset( $_POST['prh_save_settings'] ) && check_admin_referer( 'prh_settings_action', 'prh_settings_nonce' ) ) {
            $api_key = sanitize_text_field( wp_unslash( $_POST['prh_api_key'] ?? '' ) );
            $title   = sanitize_text_field( wp_unslash( $_POST['prh_hub_title'] ?? '' ) );
            $sub     = sanitize_text_field( wp_unslash( $_POST['prh_hub_subtitle'] ?? '' ) );

            if ( ! empty( $api_key ) ) {
                update_option( 'prh_api_key', $api_key );
            }
            update_option( 'prh_hub_title', $title );
            update_option( 'prh_hub_subtitle', $sub );

            add_settings_error( 'prh_notices', 'prh_saved', __( 'Réglages enregistrés avec succès.', 'presellia-resource-hub' ), 'updated' );
        }

        // Régénération de clé
        if ( isset( $_POST['prh_regenerate_key'] ) && check_admin_referer( 'prh_settings_action', 'prh_settings_nonce' ) ) {
            $new_key = 'prh_' . wp_generate_password( 28, false, false );
            update_option( 'prh_api_key', $new_key );
            add_settings_error( 'prh_notices', 'prh_key_gen', __( 'Nouvelle clé API générée avec succès.', 'presellia-resource-hub' ), 'updated' );
        }

        // Vidage des logs
        if ( isset( $_POST['prh_clear_logs'] ) && check_admin_referer( 'prh_settings_action', 'prh_settings_nonce' ) ) {
            PRH_Logger::clear_all();
            add_settings_error( 'prh_notices', 'prh_logs_cleared', __( 'Journal des logs vidé.', 'presellia-resource-hub' ), 'updated' );
        }
    }

    public function render_settings_page(): void {
        $api_key   = get_option( 'prh_api_key', '' );
        $hub_title = get_option( 'prh_hub_title', 'Espace Ressources & Téléchargements' );
        $hub_sub   = get_option( 'prh_hub_subtitle', 'Tous les outils de diagnostic, guides d’experts et installateurs officiels certifiés Presellia.' );

        $fluentcrm_active = function_exists( 'FluentCrmApi' );
        $wc_active        = class_exists( 'WooCommerce' );
        $counts           = PRH_Logger::get_counts( 30 );
        $logs             = PRH_Logger::get_recent( 30 );
        ?>
        <div class="wrap prh-admin-wrap">
            <h1><?php esc_html_e( 'Presellia Resource Hub — Réglages & API MCP', 'presellia-resource-hub' ); ?></h1>
            <?php settings_errors( 'prh_notices' ); ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-top: 20px;">
                <!-- Colonne principale -->
                <div>
                    <form method="post" action="" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 8px; margin-bottom: 24px;">
                        <?php wp_nonce_field( 'prh_settings_action', 'prh_settings_nonce' ); ?>
                        <h2 style="margin-top: 0;"><?php esc_html_e( 'Configuration Générale & Clé API', 'presellia-resource-hub' ); ?></h2>
                        
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="prh_hub_title"><?php esc_html_e( 'Titre du Hub public', 'presellia-resource-hub' ); ?></label></th>
                                <td>
                                    <input name="prh_hub_title" type="text" id="prh_hub_title" value="<?php echo esc_attr( $hub_title ); ?>" class="regular-text" style="width: 100%;">
                                    <p class="description"><?php esc_html_e( 'Titre affiché sur la page catalogue /ressources/', 'presellia-resource-hub' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="prh_hub_subtitle"><?php esc_html_e( 'Sous-titre du Hub', 'presellia-resource-hub' ); ?></label></th>
                                <td>
                                    <textarea name="prh_hub_subtitle" id="prh_hub_subtitle" rows="2" class="large-text"><?php echo esc_textarea( $hub_sub ); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="prh_api_key"><?php esc_html_e( 'Clé API MCP / Agents IA', 'presellia-resource-hub' ); ?></label></th>
                                <td>
                                    <input name="prh_api_key" type="text" id="prh_api_key" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" style="width: 100%; font-family: monospace; font-size: 14px;">
                                    <p class="description"><?php esc_html_e( 'Header requis : X-PRH-API-Key pour interagir avec /wp-json/prh/v1/', 'presellia-resource-hub' ); ?></p>
                                </td>
                            </tr>
                        </table>

                        <p class="submit" style="display: flex; gap: 10px;">
                            <button type="submit" name="prh_save_settings" class="button button-primary"><?php esc_html_e( 'Enregistrer les modifications', 'presellia-resource-hub' ); ?></button>
                            <button type="submit" name="prh_regenerate_key" class="button button-secondary" onclick="return confirm('Attention : régénérer la clé invalidera l\'accès des agents existants. Continuer ?');"><?php esc_html_e( 'Régénérer la clé API', 'presellia-resource-hub' ); ?></button>
                        </p>
                    </form>

                    <!-- Journal des Logs -->
                    <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <h2 style="margin: 0;"><?php esc_html_e( 'Derniers Événements & Logs', 'presellia-resource-hub' ); ?></h2>
                            <form method="post" action="" style="margin: 0;">
                                <?php wp_nonce_field( 'prh_settings_action', 'prh_settings_nonce' ); ?>
                                <button type="submit" name="prh_clear_logs" class="button button-secondary button-small" onclick="return confirm('Vider tous les logs ?');"><?php esc_html_e( 'Vider les logs', 'presellia-resource-hub' ); ?></button>
                            </form>
                        </div>

                        <?php if ( empty( $logs ) ) : ?>
                            <p style="color: #666; font-style: italic;"><?php esc_html_e( 'Aucun événement enregistré pour le moment.', 'presellia-resource-hub' ); ?></p>
                        <?php else : ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th style="width: 120px;"><?php esc_html_e( 'Date', 'presellia-resource-hub' ); ?></th>
                                        <th style="width: 80px;"><?php esc_html_e( 'Niveau', 'presellia-resource-hub' ); ?></th>
                                        <th style="width: 140px;"><?php esc_html_e( 'Événement', 'presellia-resource-hub' ); ?></th>
                                        <th><?php esc_html_e( 'Message', 'presellia-resource-hub' ); ?></th>
                                        <th style="width: 110px;"><?php esc_html_e( 'IP', 'presellia-resource-hub' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $logs as $log ) : 
                                        $badge_color = '#64748b';
                                        if ( 'error' === $log['level'] ) $badge_color = '#ef4444';
                                        if ( 'warning' === $log['level'] ) $badge_color = '#f59e0b';
                                        if ( 'info' === $log['level'] ) $badge_color = '#10b981';
                                    ?>
                                        <tr>
                                            <td style="font-size: 11px;"><?php echo esc_html( $log['created_at'] ); ?></td>
                                            <td><span style="background: <?php echo esc_attr( $badge_color ); ?>; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold;"><?php echo esc_html( strtoupper( $log['level'] ) ); ?></span></td>
                                            <td><code><?php echo esc_html( $log['event'] ); ?></code></td>
                                            <td><?php echo esc_html( $log['message'] ); ?></td>
                                            <td><code><?php echo esc_html( $log['ip'] ); ?></code></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Colonne latérale : Diagnostics -->
                <div>
                    <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h3 style="margin-top: 0;"><?php esc_html_e( 'État du Système', 'presellia-resource-hub' ); ?></h3>
                        <ul style="margin: 0; padding-left: 15px; line-height: 1.8;">
                            <li><strong>Version :</strong> <code><?php echo esc_html( PRH_VERSION ); ?></code></li>
                            <li>
                                <strong>FluentCRM Pro :</strong> 
                                <?php if ( $fluentcrm_active ) : ?>
                                    <span style="color: #10b981; font-weight: bold;">● Connecté</span>
                                <?php else : ?>
                                    <span style="color: #ef4444; font-weight: bold;">✕ Inactif</span>
                                <?php endif; ?>
                            </li>
                            <li>
                                <strong>WooCommerce :</strong> 
                                <?php if ( $wc_active ) : ?>
                                    <span style="color: #10b981; font-weight: bold;">● Actif</span>
                                <?php else : ?>
                                    <span style="color: #94a3b8;">Non détecté</span>
                                <?php endif; ?>
                            </li>
                            <li><strong>Total événements 30j :</strong> <?php echo esc_html( $counts['total'] ); ?></li>
                            <li><strong>Erreurs 30j :</strong> <?php echo esc_html( $counts['error'] ); ?></li>
                        </ul>
                    </div>

                    <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 8px;">
                        <h3 style="margin-top: 0;"><?php esc_html_e( 'Endpoints MCP Disponibles', 'presellia-resource-hub' ); ?></h3>
                        <p style="font-size: 13px; color: #666;"><?php esc_html_e( 'Vos agents IA peuvent interagir avec les endpoints ci-dessous :', 'presellia-resource-hub' ); ?></p>
                        <ul style="font-family: monospace; font-size: 12px; background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0; line-height: 1.8;">
                            <li>GET /wp-json/prh/v1/status</li>
                            <li>GET /wp-json/prh/v1/logs</li>
                            <li>POST /wp-json/prh/v1/logs/clear</li>
                            <li>GET /wp-json/prh/v1/resources</li>
                            <li>POST /wp-json/prh/v1/resources</li>
                            <li>DELETE /wp-json/prh/v1/resources/{id}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
