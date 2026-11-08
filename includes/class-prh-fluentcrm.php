<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gestionnaire AJAX pour la capture de leads, la livraison de la ressource
 * et l'intégration native avec FluentCRM Pro.
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
            wp_send_json_error( [ 'message' => __( 'Veuillez saisir une adresse e-mail valide.', 'presellia-resource-hub' ) ] );
        }

        if ( ! $resource_id || get_post_type( $resource_id ) !== PRH_Cpt::POST_TYPE ) {
            wp_send_json_error( [ 'message' => __( 'Ressource introuvable ou invalide.', 'presellia-resource-hub' ) ] );
        }

        // Une ressource non publiée ne se débloque que pour un éditeur connecté (tests avant publication).
        if ( 'publish' !== get_post_status( $resource_id ) && ! current_user_can( 'edit_post', $resource_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Ressource introuvable ou invalide.', 'presellia-resource-hub' ) ] );
        }

        $tag_slug     = (string) get_post_meta( $resource_id, '_prh_crm_tag', true );
        $download_url = (string) get_post_meta( $resource_id, '_prh_download_url', true );

        if ( '' === $download_url ) {
            PRH_Logger::error( 'resource_without_url', 'Ressource débloquée sans lien d\'accès configuré', [ 'resource_id' => $resource_id ] );
        }

        if ( ! $consent ) {
            PRH_Logger::info(
                'lead_no_consent',
                sprintf( 'Ressource "%s" livrée sans inscription CRM (consentement décoché)', get_the_title( $resource_id ) ),
                [ 'resource_id' => $resource_id ]
            );
        } else {
            self::sync_contact( $resource_id, $email, $first_name, $phone, $tag_slug );
        }

        // E-mail de livraison : toujours envoyé, le visiteur a demandé la ressource.
        $email_sent = PRH_Delivery::send( $resource_id, $email, $first_name, $download_url );

        wp_send_json_success( [
            'download_url' => esc_url_raw( $download_url ),
            'cta_label'    => PRH_Delivery::get_cta_label( $resource_id ),
            'email_sent'   => $email_sent,
            'message'      => $email_sent
                ? __( 'C\'est débloqué. Nous vous avons aussi envoyé le lien par e-mail.', 'presellia-resource-hub' )
                : __( 'C\'est débloqué. Ouvrez la ressource avec le bouton ci-dessous.', 'presellia-resource-hub' ),
        ] );
    }

    /**
     * Crée ou met à jour le contact FluentCRM, applique le tag de la ressource et laisse une note.
     *
     * Règles :
     * - un contact désinscrit, en rebond ou en plainte n'est jamais réinscrit ;
     * - un champ vide du formulaire n'efface pas une valeur déjà connue (prénom, téléphone) ;
     * - le tag est attaché après l'enregistrement, ce qui déclenche les automatisations
     *   FluentCRM « tag appliqué » (une seule fois par contact et par tag).
     */
    private static function sync_contact( int $resource_id, string $email, string $first_name, string $phone, string $tag_slug ): void {
        if ( ! function_exists( 'FluentCrmApi' ) ) {
            PRH_Logger::warning(
                'fluentcrm_inactive',
                sprintf( 'Capture lead effectuée mais FluentCRM n\'est pas actif (%s)', $email ),
                [ 'resource_id' => $resource_id, 'email' => $email ]
            );
            return;
        }

        try {
            $contact_api = FluentCrmApi( 'contacts' );
            $existing    = $contact_api->getContact( $email );
            $blocked     = [ 'unsubscribed', 'bounced', 'complained', 'spammed' ];

            $payload = [
                'email'  => $email,
                'source' => 'Presellia Resource Hub',
            ];
            if ( '' !== $first_name ) {
                $payload['first_name'] = $first_name;
            }
            if ( '' !== $phone ) {
                $payload['phone'] = $phone;
            }
            if ( ! $existing || ! in_array( $existing->status, $blocked, true ) ) {
                $payload['status'] = 'subscribed';
            }

            $contact = $contact_api->createOrUpdate( $payload );
            if ( ! $contact || empty( $contact->id ) ) {
                throw new \RuntimeException( 'createOrUpdate n\'a renvoyé aucun contact' );
            }

            $tag_id = self::resolve_tag_id( $tag_slug );
            if ( $tag_id ) {
                $contact->attachTags( [ $tag_id ] );
            }

            self::add_note( (int) $contact->id, $resource_id );

            PRH_Logger::info(
                'lead_captured',
                sprintf( 'Lead enregistré pour "%s"', get_the_title( $resource_id ) ),
                [
                    'resource_id' => $resource_id,
                    'email'       => $email,
                    'tag'         => $tag_slug,
                    'contact_id'  => (int) $contact->id,
                    'new_contact' => ! $existing,
                    'status'      => $payload['status'] ?? ( $existing->status ?? '' ),
                ]
            );
        } catch ( \Throwable $e ) {
            PRH_Logger::error(
                'crm_sync_failed',
                'Échec de la synchronisation avec FluentCRM: ' . $e->getMessage(),
                [ 'resource_id' => $resource_id, 'email' => $email ]
            );
        }
    }

    /**
     * Retrouve le tag par slug ou par titre, le crée s'il n'existe pas.
     */
    private static function resolve_tag_id( string $tag_slug ): int {
        $tag_slug = trim( $tag_slug );
        if ( '' === $tag_slug || ! class_exists( '\FluentCrm\App\Models\Tag' ) ) {
            return 0;
        }

        $tag = \FluentCrm\App\Models\Tag::where( 'slug', sanitize_title( $tag_slug ) )
            ->orWhere( 'title', $tag_slug )
            ->first();

        if ( ! $tag ) {
            $tag = \FluentCrm\App\Models\Tag::create( [
                'title' => sanitize_text_field( $tag_slug ),
                'slug'  => sanitize_title( $tag_slug ),
            ] );
        }

        return ( $tag && ! empty( $tag->id ) ) ? (int) $tag->id : 0;
    }

    /**
     * Note visible dans la fiche du contact : quelle ressource, quand. Non bloquant.
     */
    private static function add_note( int $contact_id, int $resource_id ): void {
        if ( ! class_exists( '\FluentCrm\App\Models\SubscriberNote' ) ) {
            return;
        }

        try {
            \FluentCrm\App\Models\SubscriberNote::create( [
                'subscriber_id' => $contact_id,
                'type'          => 'note',
                'title'         => 'Ressource téléchargée',
                'description'   => sprintf( '%s — %s', get_the_title( $resource_id ), get_permalink( $resource_id ) ),
                'created_by'    => get_current_user_id(),
                'status'        => 'open',
            ] );
        } catch ( \Throwable $e ) {
            PRH_Logger::warning( 'crm_note_failed', 'Note FluentCRM non créée: ' . $e->getMessage(), [ 'contact_id' => $contact_id ] );
        }
    }
}
