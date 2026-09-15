<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Chargeur de templates pour l'archive et les fiches individuelles de ressources.
 */
class PRH_Templates {

    public function __construct() {
        add_filter( 'template_include', [ $this, 'load_custom_templates' ] );
        add_filter( 'document_title_parts', [ $this, 'filter_document_title' ] );
    }

    public function load_custom_templates( string $template ): string {
        if ( is_post_type_archive( PRH_Cpt::POST_TYPE ) || is_tax( PRH_Cpt::TAXONOMY ) ) {
            $theme_template = locate_template( [ 'archive-ressource.php', 'archive-presellia_ressource.php' ] );
            if ( ! empty( $theme_template ) ) {
                return $theme_template;
            }
            return PRH_PATH . 'templates/archive-ressource.php';
        }

        if ( is_singular( PRH_Cpt::POST_TYPE ) ) {
            $theme_template = locate_template( [ 'single-ressource.php', 'single-presellia_ressource.php' ] );
            if ( ! empty( $theme_template ) ) {
                return $theme_template;
            }
            return PRH_PATH . 'templates/single-ressource.php';
        }

        return $template;
    }

    public function filter_document_title( array $title_parts ): array {
        if ( is_post_type_archive( PRH_Cpt::POST_TYPE ) ) {
            $title_parts['title'] = get_option( 'prh_hub_title', __( 'Espace Ressources & Téléchargements', 'presellia-resource-hub' ) );
        } elseif ( is_tax( PRH_Cpt::TAXONOMY ) ) {
            $term = get_queried_object();
            if ( $term && isset( $term->name ) ) {
                $title_parts['title'] = sprintf( __( 'Ressources : %s', 'presellia-resource-hub' ), $term->name );
            }
        }
        return $title_parts;
    }
}
