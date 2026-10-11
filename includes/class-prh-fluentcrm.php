<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gestionnaire AJAX pour la capture de leads et l'intégration native avec FluentCRM Pro.
 */
class PRH_FluentCRM {

    public function __construct() {
        add_action( 'wp_ajax_prh_capture_lead', [ $this, 'handle_lead_capture' ] );
        add_action( 'wp_ajax_nopriv_prh_capture_lead', [ $this, 'handle_lead_capture' ] );
    }

    public function handle_lead_capture(): void {
        check_ajax_referer( 'prh_lead_capture_nonce', 'nonce' );

        $first_name  = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
        $email       = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $phone       = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
        $resource_id = absint( $_POST['resource_id'] ?? 0 );
        // Champ absent = ancien JS encore en cache : on garde le comportement d'avant (inscription)
        $consent     = ! isset( $_POST['consent'] ) || '1' === $_POST['consent'];
        $honeypot    = trim( (string) wp_unslash( $_POST['prh_lead_website'] ?? '' ) );

        // Champ piège rempli = robot : on répond comme un succès, sans rien enregistrer ni livrer
        if ( '' !== $honeypot ) {
            PRH_Logger::warning( 'lead_honeypot', 'Soumission bloquée par le champ piège', [ 'resource_id' => $resource_id ] );
            wp_send_json_success( [
                'download_url' => '',
                'message'      => __( 'Merci, votre demande a bien été prise en compte.', 'presellia-resource-hub' ),
            ] );
        }

        // Limite par IP : 5 soumissions par fenêtre de 10 minutes
        $rate_key = 'prh_rl_' . md5( PRH_Logger::get_client_ip() );
        $attempts = (int) get_transient( $rate_key );
        if ( $attempts >= 5 ) {
            PRH_Logger::warning( 'lead_rate_limited', 'Soumission bloquée par la limite par IP', [ 'resource_id' => $resource_id ] );
            wp_send_json_error( [ 'message' => __( 'Trop de tentatives. Merci de réessayer dans quelques minutes.', 'presellia-resource-hub' ) ] );
        }
        set_transient( $rate_key, $attempts + 1, 10 * MINUTE_IN_SECONDS );

        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => __( 'Veuillez saisir une adresse email valide.', 'presellia-resource-hub' ) ] );
        }

        if ( ! $resource_id || get_post_type( $resource_id ) !== PRH_Cpt::POST_TYPE ) {
            wp_send_json_error( [ 'message' => __( 'Ressource introuvable ou invalide.', 'presellia-resource-hub' ) ] );
        }

        $tag_slug     = get_post_meta( $resource_id, '_prh_crm_tag', true );
        $download_url = get_post_meta( $resource_id, '_prh_download_url', true );

        // Intégration FluentCRM Pro via l'API PHP native (uniquement avec consentement)
        if ( ! $consent ) {
            PRH_Logger::info(
                'lead_no_consent',
                sprintf( 'Ressource "%s" livrée sans inscription CRM (consentement décoché)', get_the_title( $resource_id ) ),
                [ 'resource_id' => $resource_id ]
            );
        } elseif ( function_exists( 'FluentCrmApi' ) ) {
            try {
                $contact_api = FluentCrmApi( 'contacts' );
                $tag_ids     = [];

                if ( ! empty( $tag_slug ) ) {
                    if ( class_exists( '\FluentCrm\App\Models\Tag' ) ) {
                        $existing_tag = \FluentCrm\App\Models\Tag::where( 'slug', sanitize_title( $tag_slug ) )
                            ->orWhere( 'title', $tag_slug )
                            ->first();

                        if ( $existing_tag ) {
                            $tag_ids[] = $existing_tag->id;
                        } else {
                            $new_tag = \FluentCrm\App\Models\Tag::create( [
                                'title' => sanitize_text_field( $tag_slug ),
                                'slug'  => sanitize_title( $tag_slug ),
                            ] );
                            if ( $new_tag && isset( $new_tag->id ) ) {
                                $tag_ids[] = $new_tag->id;
                            }
                        }
                    }
                }

                $contact_payload = [
                    'first_name' => $first_name,
                    'email'      => $email,
                    'phone'      => $phone,
                    'status'     => 'subscribed',
                    'source'     => 'Presellia Resource Hub',
                ];

                if ( ! empty( $tag_ids ) ) {
                    $contact_payload['tags'] = $tag_ids;
                }

                $contact = $contact_api->createOrUpdate( $contact_payload );

                PRH_Logger::info(
                    'lead_captured',
                    sprintf( 'Lead enregistré avec succès pour "%s" (%s)', get_the_title( $resource_id ), $email ),
                    [
                        'resource_id' => $resource_id,
                        'email'       => $email,
                        'tag'         => $tag_slug,
                    ]
                );
            } catch ( \Throwable $e ) {
                PRH_Logger::error(
                    'crm_sync_failed',
                    'Échec de la synchronisation avec FluentCRM: ' . $e->getMessage(),
                    [ 'exception' => $e->getMessage(), 'email' => $email ]
                );
            }
        } else {
            // FluentCRM absent ou inactif : on enregistre tout de même l'événement dans le logger
            PRH_Logger::warning(
                'fluentcrm_inactive',
                sprintf( 'Capture lead effectuée mais FluentCRM n\'est pas actif (%s)', $email ),
                [ 'resource_id' => $resource_id, 'email' => $email ]
            );
        }

        wp_send_json_success( [
            'download_url' => esc_url_raw( $download_url ),
            'message'      => __( 'Accès débloqué ! Le téléchargement va démarrer automatiquement.', 'presellia-resource-hub' ),
        ] );
    }
}
