<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gère les métadonnées et métaboxes des ressources Presellia.
 */
class PRH_Metaboxes {

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
        add_action( 'save_post_' . PRH_Cpt::POST_TYPE, [ $this, 'save_meta_boxes' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    public function enqueue_admin_assets( string $hook ): void {
        global $post;
        if ( in_array( $hook, [ 'post.php', 'post-new.php' ], true ) && isset( $post->post_type ) && PRH_Cpt::POST_TYPE === $post->post_type ) {
            wp_enqueue_style(
                'prh-admin-css',
                PRH_URL . 'assets/css/resource-admin.css',
                [],
                PRH_VERSION
            );
            wp_enqueue_script(
                'prh-admin-js',
                PRH_URL . 'assets/js/resource-admin.js',
                [ 'jquery' ],
                PRH_VERSION,
                true
            );
        }
    }

    public function register_meta_boxes(): void {
        add_meta_box(
            'prh_resource_settings',
            __( 'Configuration de la Ressource', 'presellia-resource-hub' ),
            [ $this, 'render_main_metabox' ],
            PRH_Cpt::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'prh_resource_offer',
            __( 'Offre Recommandée (WooCommerce Grand Slam Box)', 'presellia-resource-hub' ),
            [ $this, 'render_offer_metabox' ],
            PRH_Cpt::POST_TYPE,
            'normal',
            'default'
        );
    }

    /**
     * Récupère la liste des formulaires Fluent Forms disponibles.
     *
     * @return array<int, string> [ id => title ]
     */
    public static function get_fluent_forms(): array {
        global $wpdb;
        $forms = [];

        // 1. Table officielle Fluent Forms (wp_fluentform_forms)
        $ff_table = $wpdb->prefix . 'fluentform_forms';
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $ff_table ) ) === $ff_table ) {
            $results = $wpdb->get_results( "SELECT id, title FROM {$ff_table} WHERE status != 'trashed' ORDER BY id DESC" );
            if ( ! empty( $results ) ) {
                foreach ( $results as $row ) {
                    $forms[ intval( $row->id ) ] = $row->title;
                }
            }
        }

        // 2. Table native FluentCRM Forms (wp_fc_forms) si présente
        $fc_table = $wpdb->prefix . 'fc_forms';
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $fc_table ) ) === $fc_table ) {
            $results_fc = $wpdb->get_results( "SELECT id, title FROM {$fc_table} ORDER BY id DESC" );
            if ( ! empty( $results_fc ) ) {
                foreach ( $results_fc as $row ) {
                    if ( ! isset( $forms[ intval( $row->id ) ] ) ) {
                        $forms[ intval( $row->id ) ] = $row->title;
                    }
                }
            }
        }

        return $forms;
    }

    public function render_main_metabox( WP_Post $post ): void {
        wp_nonce_field( 'prh_save_resource_meta', 'prh_resource_nonce' );

        $format_spec    = get_post_meta( $post->ID, '_prh_format_spec', true );
        $compatible_os  = get_post_meta( $post->ID, '_prh_compatible_os', true );
        $download_url   = get_post_meta( $post->ID, '_prh_download_url', true );
        $media_type     = get_post_meta( $post->ID, '_prh_media_type', true ) ?: 'image';
        $media_url      = get_post_meta( $post->ID, '_prh_media_url', true );
        $access_type    = get_post_meta( $post->ID, '_prh_access_type', true ) ?: 'free';
        $crm_tag        = get_post_meta( $post->ID, '_prh_crm_tag', true );
        $fluent_form_id = get_post_meta( $post->ID, '_prh_fluent_form_id', true );
        $available_forms = self::get_fluent_forms();
        $subtitle       = get_post_meta( $post->ID, '_prh_subtitle', true );
        $value_stack    = get_post_meta( $post->ID, '_prh_value_stack', true );
        $cta_label      = get_post_meta( $post->ID, '_prh_cta_label', true );
        $form_label     = get_post_meta( $post->ID, '_prh_form_label', true );
        $email_subject  = get_post_meta( $post->ID, '_prh_email_subject', true );
        $email_body     = get_post_meta( $post->ID, '_prh_email_body', true );
        ?>
        <div class="prh-metabox-grid">
            <div class="prh-field full-width">
                <label for="prh_subtitle"><strong><?php esc_html_e( 'Promesse / Sous-titre d\'accroche (Style Hormozi)', 'presellia-resource-hub' ); ?></strong></label>
                <input type="text" id="prh_subtitle" name="prh_subtitle" value="<?php echo esc_attr( $subtitle ); ?>" class="large-text" placeholder="Ex: Débloquez et réparez vos licences en 3 minutes sans réinstaller Windows" />
                <p class="description"><?php esc_html_e( 'S\'affiche directement sous le titre principal pour maximiser la clarté et l\'engagement.', 'presellia-resource-hub' ); ?></p>
            </div>

            <div class="prh-field">
                <label for="prh_format_spec"><strong><?php esc_html_e( 'Format & Spécification technique', 'presellia-resource-hub' ); ?></strong></label>
                <input type="text" id="prh_format_spec" name="prh_format_spec" value="<?php echo esc_attr( $format_spec ); ?>" class="regular-text" placeholder="Ex: PDF · 18 pages ou Script PowerShell ou ISO · 4.6 Go" />
                <p class="description"><?php esc_html_e( 'Un champ texte clair affiché en pill/badge sur la carte et la page.', 'presellia-resource-hub' ); ?></p>
            </div>

            <div class="prh-field">
                <label for="prh_compatible_os"><strong><?php esc_html_e( 'Systèmes / Logiciels compatibles', 'presellia-resource-hub' ); ?></strong></label>
                <input type="text" id="prh_compatible_os" name="prh_compatible_os" value="<?php echo esc_attr( $compatible_os ); ?>" class="regular-text" placeholder="Ex: Windows 11 / 10 / Server" />
            </div>

            <div class="prh-field full-width">
                <label for="prh_download_url"><strong><?php esc_html_e( 'Lien direct de téléchargement du fichier / ressource', 'presellia-resource-hub' ); ?></strong></label>
                <input type="url" id="prh_download_url" name="prh_download_url" value="<?php echo esc_url( $download_url ); ?>" class="large-text" placeholder="https://presellia.com/fichiers/outil.zip ou CDN officiel" />
            </div>

            <div class="prh-field">
                <label for="prh_media_type"><strong><?php esc_html_e( 'Format visuel de démonstration', 'presellia-resource-hub' ); ?></strong></label>
                <select id="prh_media_type" name="prh_media_type">
                    <option value="image" <?php selected( $media_type, 'image' ); ?>><?php esc_html_e( 'Image / Mockup (Image mise en avant ou URL)', 'presellia-resource-hub' ); ?></option>
                    <option value="youtube" <?php selected( $media_type, 'youtube' ); ?>><?php esc_html_e( 'Vidéo YouTube (ID ou URL)', 'presellia-resource-hub' ); ?></option>
                    <option value="custom_video" <?php selected( $media_type, 'custom_video' ); ?>><?php esc_html_e( 'Vidéo MP4 directe', 'presellia-resource-hub' ); ?></option>
                </select>
            </div>

            <div class="prh-field">
                <label for="prh_media_url"><strong><?php esc_html_e( 'URL ou ID de la vidéo / image', 'presellia-resource-hub' ); ?></strong></label>
                <input type="text" id="prh_media_url" name="prh_media_url" value="<?php echo esc_attr( $media_url ); ?>" class="regular-text" placeholder="https://youtube.com/watch?v=... ou laisser vide pour l'image mise en avant" />
            </div>

            <div class="prh-field">
                <label for="prh_access_type"><strong><?php esc_html_e( 'Condition d\'accès', 'presellia-resource-hub' ); ?></strong></label>
                <select id="prh_access_type" name="prh_access_type">
                    <option value="free" <?php selected( $access_type, 'free' ); ?>><?php esc_html_e( 'Téléchargement direct et gratuit (aucun formulaire)', 'presellia-resource-hub' ); ?></option>
                    <option value="gated" <?php selected( $access_type, 'gated' ); ?>><?php esc_html_e( 'Accès conditionné : Formulaire FluentCRM (Prénom, Email, Tél)', 'presellia-resource-hub' ); ?></option>
                </select>
            </div>

            <div class="prh-field" id="prh_crm_tag_field">
                <label for="prh_crm_tag"><strong><?php esc_html_e( 'Tag FluentCRM appliqué au prospect', 'presellia-resource-hub' ); ?></strong></label>
                <input type="text" id="prh_crm_tag" name="prh_crm_tag" value="<?php echo esc_attr( $crm_tag ); ?>" class="regular-text" placeholder="Ex: lead-hub-cleaner" />
                <p class="description"><?php esc_html_e( 'Le contact sera créé/mis à jour dans FluentCRM avec ce tag dès validation.', 'presellia-resource-hub' ); ?></p>
            </div>

            <div class="prh-field full-width" id="prh_fluent_form_picker_field">
                <label for="prh_fluent_form_id"><strong><?php esc_html_e( 'Formulaire de capture (Fluent Forms / FluentCRM)', 'presellia-resource-hub' ); ?></strong></label>
                <select id="prh_fluent_form_id" name="prh_fluent_form_id" style="max-width: 100%;">
                    <option value="0" <?php selected( empty( $fluent_form_id ) || 0 === intval( $fluent_form_id ) ); ?>>
                        <?php esc_html_e( '⚡ Modale AJAX native Presellia (Prénom, Email, Téléphone WhatsApp)', 'presellia-resource-hub' ); ?>
                    </option>
                    <?php if ( ! empty( $available_forms ) ) : ?>
                        <?php foreach ( $available_forms as $form_id => $form_title ) : ?>
                            <option value="<?php echo esc_attr( $form_id ); ?>" <?php selected( intval( $fluent_form_id ), intval( $form_id ) ); ?>>
                                <?php echo sprintf( esc_html__( 'Formulaire #%1$d : %2$s', 'presellia-resource-hub' ), esc_attr( $form_id ), esc_html( $form_title ) ); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <p class="description">
                    <?php if ( empty( $available_forms ) ) : ?>
                        <?php esc_html_e( '💡 Dès que vous créez un formulaire dans FluentCRM Pro > Formulaires, il apparaîtra automatiquement dans cette liste sans avoir besoin de copier-coller de shortcode !', 'presellia-resource-hub' ); ?>
                    <?php else : ?>
                        <?php esc_html_e( 'Sélectionnez le formulaire Fluent Forms souhaité pour cette ressource. Si aucun n\'est choisi, la modale native Presellia est utilisée.', 'presellia-resource-hub' ); ?>
                    <?php endif; ?>
                </p>
            </div>

            <div class="prh-field full-width" id="prh_delivery_fields">
                <h4 style="margin: 8px 0 4px;"><?php esc_html_e( 'Livraison après le formulaire', 'presellia-resource-hub' ); ?></h4>
                <p class="description" style="margin-top: 0;"><?php esc_html_e( 'Après le formulaire, le visiteur voit un bouton vers le lien ci-dessus et reçoit le même lien par e-mail (même s\'il refuse les e-mails marketing). Variables : {prenom}, {titre}, {lien}.', 'presellia-resource-hub' ); ?></p>

                <label for="prh_form_label"><strong><?php esc_html_e( 'Texte du bouton du formulaire', 'presellia-resource-hub' ); ?></strong></label>
                <input type="text" id="prh_form_label" name="prh_form_label" value="<?php echo esc_attr( $form_label ); ?>" class="regular-text" placeholder="<?php echo esc_attr( PRH_Delivery::DEFAULT_FORM_LABEL ); ?>" />
                <p class="description"><?php esc_html_e( 'Ex. : Recevoir le guide, Recevoir l\'outil, Recevoir la checklist.', 'presellia-resource-hub' ); ?></p>

                <label for="prh_cta_label" style="display: block; margin-top: 12px;"><strong><?php esc_html_e( 'Texte du bouton d\'accès', 'presellia-resource-hub' ); ?></strong></label>
                <input type="text" id="prh_cta_label" name="prh_cta_label" value="<?php echo esc_attr( $cta_label ); ?>" class="regular-text" placeholder="<?php echo esc_attr( PRH_Delivery::DEFAULT_CTA_LABEL ); ?>" />

                <label for="prh_email_subject" style="display: block; margin-top: 12px;"><strong><?php esc_html_e( 'Objet de l\'e-mail de livraison', 'presellia-resource-hub' ); ?></strong></label>
                <input type="text" id="prh_email_subject" name="prh_email_subject" value="<?php echo esc_attr( $email_subject ); ?>" class="large-text" placeholder="<?php echo esc_attr( PRH_Delivery::DEFAULT_SUBJECT ); ?>" />

                <label for="prh_email_body" style="display: block; margin-top: 12px;"><strong><?php esc_html_e( 'Texte de l\'e-mail de livraison', 'presellia-resource-hub' ); ?></strong></label>
                <textarea id="prh_email_body" name="prh_email_body" rows="8" class="large-text" placeholder="<?php echo esc_attr( PRH_Delivery::DEFAULT_BODY ); ?>"><?php echo esc_textarea( $email_body ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Une ligne vide sépare deux paragraphes. Le bouton vert est placé automatiquement après le deuxième paragraphe. Laissez vide pour le texte par défaut.', 'presellia-resource-hub' ); ?></p>
            </div>

            <div class="prh-field full-width">
                <label for="prh_value_stack"><strong><?php esc_html_e( 'Ce que contient la ressource (Value Stack / Points clés)', 'presellia-resource-hub' ); ?></strong></label>
                <textarea id="prh_value_stack" name="prh_value_stack" rows="4" class="large-text" placeholder="Entrez un point par ligne :&#10;Diagnostic automatisé des clés d'enregistrement&#10;Nettoyage des restes d'anciennes installations Office&#10;Rapport d'intégrité du système"><?php echo esc_textarea( $value_stack ); ?></textarea>
                <p class="description"><?php esc_html_e( 'Chaque ligne sera affichée comme un élément de la liste avec une coche verte ✓.', 'presellia-resource-hub' ); ?></p>
            </div>
        </div>
        <?php
    }

    public function render_offer_metabox( WP_Post $post ): void {
        $linked_product_id  = get_post_meta( $post->ID, '_prh_linked_product_id', true );
        $offer_badge        = get_post_meta( $post->ID, '_prh_offer_badge', true ) ?: 'OFFRE COMPLÉMENTAIRE RECOMMANDÉE';
        $offer_custom_title = get_post_meta( $post->ID, '_prh_offer_custom_title', true );
        $offer_custom_desc  = get_post_meta( $post->ID, '_prh_offer_custom_desc', true );
        ?>
        <div class="prh-metabox-grid">
            <div class="prh-field">
                <label for="prh_linked_product_id"><strong><?php esc_html_e( 'Produit WooCommerce lié (ID)', 'presellia-resource-hub' ); ?></strong></label>
                <input type="number" id="prh_linked_product_id" name="prh_linked_product_id" value="<?php echo esc_attr( $linked_product_id ); ?>" class="regular-text" placeholder="Ex: 15420" />
                <p class="description"><?php esc_html_e( 'Laissez vide si vous ne souhaitez pas afficher d\'offre commerciale sur cette ressource.', 'presellia-resource-hub' ); ?></p>
            </div>

            <div class="prh-field">
                <label for="prh_offer_badge"><strong><?php esc_html_e( 'Badge au-dessus de l\'offre', 'presellia-resource-hub' ); ?></strong></label>
                <input type="text" id="prh_offer_badge" name="prh_offer_badge" value="<?php echo esc_attr( $offer_badge ); ?>" class="regular-text" />
            </div>

            <div class="prh-field full-width">
                <label for="prh_offer_custom_title"><strong><?php esc_html_e( 'Titre personnalisé de l\'offre (optionnel)', 'presellia-resource-hub' ); ?></strong></label>
                <input type="text" id="prh_offer_custom_title" name="prh_offer_custom_title" value="<?php echo esc_attr( $offer_custom_title ); ?>" class="large-text" placeholder="Laissez vide pour utiliser le titre officiel du produit WooCommerce" />
            </div>

            <div class="prh-field full-width">
                <label for="prh_offer_custom_desc"><strong><?php esc_html_e( 'Description / Avantage de l\'offre (optionnel)', 'presellia-resource-hub' ); ?></strong></label>
                <textarea id="prh_offer_custom_desc" name="prh_offer_custom_desc" rows="2" class="large-text" placeholder="Ex: Licence officielle à vie + support technique prioritaire 7j/7. Activation garantie en 30 secondes."><?php echo esc_textarea( $offer_custom_desc ); ?></textarea>
            </div>
        </div>
        <?php
    }

    public function save_meta_boxes( int $post_id, WP_Post $post ): void {
        if ( ! isset( $_POST['prh_resource_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['prh_resource_nonce'] ) ), 'prh_save_resource_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = [
            '_prh_format_spec'         => 'sanitize_text_field',
            '_prh_compatible_os'       => 'sanitize_text_field',
            '_prh_download_url'        => 'esc_url_raw',
            '_prh_media_type'          => 'sanitize_key',
            '_prh_media_url'           => 'sanitize_text_field',
            '_prh_access_type'         => 'sanitize_key',
            '_prh_crm_tag'             => 'sanitize_text_field',
            '_prh_fluent_form_id'      => 'absint',
            '_prh_subtitle'            => 'sanitize_text_field',
            '_prh_value_stack'         => 'sanitize_textarea_field',
            '_prh_cta_label'           => 'sanitize_text_field',
            '_prh_form_label'          => 'sanitize_text_field',
            '_prh_email_subject'       => 'sanitize_text_field',
            '_prh_email_body'          => 'sanitize_textarea_field',
            '_prh_linked_product_id'   => 'absint',
            '_prh_offer_badge'         => 'sanitize_text_field',
            '_prh_offer_custom_title'  => 'sanitize_text_field',
            '_prh_offer_custom_desc'   => 'sanitize_textarea_field',
        ];

        foreach ( $fields as $meta_key => $sanitizer ) {
            $input_key = ltrim( $meta_key, '_' );
            if ( isset( $_POST[ $input_key ] ) ) {
                $raw_val = wp_unslash( $_POST[ $input_key ] );
                $clean_val = call_user_func( $sanitizer, $raw_val );
                update_post_meta( $post_id, $meta_key, $clean_val );
            } else {
                delete_post_meta( $post_id, $meta_key );
            }
        }
    }
}
