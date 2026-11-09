<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Envoi de l'e-mail de livraison d'une ressource après le formulaire.
 *
 * L'e-mail est transactionnel : il part même si le visiteur a décoché le consentement
 * marketing, car il a demandé la ressource. Il passe par wp_mail (donc par le SMTP du site).
 */
class PRH_Delivery {

    public const DEFAULT_CTA_LABEL = 'Ouvrir la ressource';

    public const DEFAULT_FORM_LABEL = 'Recevoir la ressource';

    /**
     * Texte du bouton qui ouvre le formulaire et le valide (ex. « Recevoir le guide »).
     */
    public static function get_form_label( int $resource_id ): string {
        $label = trim( (string) get_post_meta( $resource_id, '_prh_form_label', true ) );
        return '' !== $label ? $label : self::DEFAULT_FORM_LABEL;
    }

    public const DEFAULT_SUBJECT = '{prenom}, voici votre accès : {titre}';

    // Court par principe : un e-mail de livraison se lit en 5 secondes et arrive mieux en boîte principale.
    public const DEFAULT_BODY = "Bonjour {prenom},\n\nVoici le lien vers « {titre} ».\n\nUne question ? Répondez simplement à cet e-mail.\n\nL'équipe Presellia";

    /**
     * Libellé du bouton d'accès pour une ressource.
     */
    public static function get_cta_label( int $resource_id ): string {
        $label = trim( (string) get_post_meta( $resource_id, '_prh_cta_label', true ) );
        return '' !== $label ? $label : self::DEFAULT_CTA_LABEL;
    }

    /**
     * Envoie l'e-mail de livraison. Retourne true si wp_mail a accepté l'envoi.
     */
    public static function send( int $resource_id, string $email, string $first_name, string $access_url ): bool {
        if ( '' === $access_url || ! is_email( $email ) ) {
            return false;
        }

        $title      = get_the_title( $resource_id );
        $first_name = trim( $first_name );

        $subject_tpl = trim( (string) get_post_meta( $resource_id, '_prh_email_subject', true ) );
        $body_tpl    = trim( (string) get_post_meta( $resource_id, '_prh_email_body', true ) );

        $vars = [
            '{prenom}' => $first_name,
            '{titre}'  => $title,
            '{lien}'   => $access_url,
        ];

        // Prénom absent : « Bonjour {prenom}, » devient « Bonjour, » et « {prenom}, voici » devient « Voici ».
        $subject = self::tidy( strtr( '' !== $subject_tpl ? $subject_tpl : self::DEFAULT_SUBJECT, $vars ) );
        $subject = wp_specialchars_decode( wp_strip_all_tags( $subject ), ENT_QUOTES );

        $body_text = self::tidy( strtr( '' !== $body_tpl ? $body_tpl : self::DEFAULT_BODY, $vars ) );
        $html      = self::render_html( $body_text, $access_url, self::get_cta_label( $resource_id ) );

        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
        $reply_to = sanitize_email( (string) get_option( 'prh_reply_to', '' ) );
        if ( $reply_to ) {
            $headers[] = 'Reply-To: Presellia <' . $reply_to . '>';
        }

        // Version texte jointe à la version HTML : un e-mail « HTML seul » est mal noté par les filtres.
        $text      = wp_strip_all_tags( $body_text ) . "\n\n" . self::get_cta_label( $resource_id ) . ' : ' . $access_url . "\n";
        $alt_body  = static function ( $phpmailer ) use ( $text ) {
            $phpmailer->AltBody = $text;
        };
        add_action( 'phpmailer_init', $alt_body );
        $sent = (bool) wp_mail( $email, $subject, $html, $headers );
        remove_action( 'phpmailer_init', $alt_body );

        if ( $sent ) {
            PRH_Logger::info(
                'delivery_email_sent',
                sprintf( 'E-mail de livraison envoyé pour "%s"', $title ),
                [ 'resource_id' => $resource_id, 'email' => $email ]
            );
        } else {
            PRH_Logger::error(
                'delivery_email_failed',
                sprintf( 'Échec de l\'envoi de l\'e-mail de livraison pour "%s"', $title ),
                [ 'resource_id' => $resource_id, 'email' => $email ]
            );
        }

        return $sent;
    }

    /**
     * Nettoie les restes d'un prénom vide : espace avant virgule, virgule en tête de texte.
     */
    private static function tidy( string $text ): string {
        $text = preg_replace( '/[ \t]+,/u', ',', $text ) ?? $text;
        $text = preg_replace( '/^\s*,\s*/u', '', $text ) ?? $text;
        return function_exists( 'mb_strtoupper' )
            ? mb_strtoupper( mb_substr( $text, 0, 1 ) ) . mb_substr( $text, 1 )
            : ucfirst( $text );
    }

    /**
     * Gabarit volontairement sobre (proche d'un e-mail personnel) : meilleure arrivée en boîte principale.
     * Le texte est découpé en paragraphes sur les lignes vides ; le bouton est inséré après le
     * premier paragraphe qui contient {lien}, ou après le deuxième paragraphe à défaut.
     */
    private static function render_html( string $body_text, string $access_url, string $cta_label ): string {
        $paragraphs = preg_split( "/\R{2,}/", trim( $body_text ) ) ?: [];
        $button     = self::render_button( $access_url, $cta_label );
        $out        = '';
        $placed     = false;

        foreach ( $paragraphs as $index => $paragraph ) {
            $has_link = false !== strpos( $paragraph, $access_url );
            $safe     = nl2br( esc_html( $paragraph ) );
            // Les URL écrites en clair dans le texte deviennent cliquables.
            $safe     = str_replace(
                esc_html( $access_url ),
                '<a href="' . esc_url( $access_url ) . '" style="color:#2563eb;">' . esc_html( $access_url ) . '</a>',
                $safe
            );
            $out .= '<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#1f2937;">' . $safe . '</p>';

            if ( ! $placed && ( $has_link || 1 === $index ) ) {
                $out   .= $button;
                $placed = true;
            }
        }

        if ( ! $placed ) {
            $out .= $button;
        }

        return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
            . '<body style="margin:0;padding:0;background:#ffffff;font-family:Arial,Helvetica,sans-serif;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="left" style="padding:24px 16px;">'
            . '<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;"><tr><td>'
            . $out
            . '<p style="margin:24px 0 0;font-size:12px;line-height:1.5;color:#9ca3af;">Le bouton ne s\'ouvre pas ? Copiez ce lien dans votre navigateur :<br>'
            . '<a href="' . esc_url( $access_url ) . '" style="color:#6b7280;">' . esc_html( $access_url ) . '</a></p>'
            . '</td></tr></table></td></tr></table></body></html>';
    }

    private static function render_button( string $url, string $label ): string {
        return '<table cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 24px;"><tr>'
            . '<td style="background:#16a34a;border-radius:6px;padding:13px 26px;">'
            . '<a href="' . esc_url( $url ) . '" style="color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none;">' . esc_html( $label ) . '</a>'
            . '</td></tr></table>';
    }
}
