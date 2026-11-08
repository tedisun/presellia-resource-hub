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
        // Yoast remplace document_title_parts : sans ce filtre, l'onglet affiche « Ressources Archive ».
        add_filter( 'wpseo_title', [ $this, 'filter_wpseo_title' ] );
        add_filter( 'body_class', [ $this, 'filter_body_classes' ] );
        add_action( 'template_redirect', [ $this, 'remove_theme_elements' ] );
        add_shortcode( 'presellia_resource_hub', [ $this, 'render_hub_shortcode' ] );
    }

    public function filter_body_classes( array $classes ): array {
        if ( is_post_type_archive( PRH_Cpt::POST_TYPE ) || is_singular( PRH_Cpt::POST_TYPE ) || is_tax( PRH_Cpt::TAXONOMY ) ) {
            $classes[] = 'page-template-template-fullwidth-no-heading';
            $classes[] = 'shoptimizer-full-width';
        }
        return $classes;
    }

    public function remove_theme_elements(): void {
        if ( is_post_type_archive( PRH_Cpt::POST_TYPE ) || is_singular( PRH_Cpt::POST_TYPE ) || is_tax( PRH_Cpt::TAXONOMY ) ) {
            remove_action( 'shoptimizer_content_top', 'shoptimizer_breadcrumbs', 5 );
            remove_action( 'shoptimizer_before_content', 'shoptimizer_breadcrumbs', 5 );
            remove_action( 'shoptimizer_content_top', 'shoptimizer_page_header', 10 );
        }
    }

    public function render_hub_shortcode( $atts = [] ): string {
        // Enqueue front assets if called on a regular page
        wp_enqueue_style( 'prh-frontend', PRH_URL . 'assets/css/resource-hub.css', [], PRH_VERSION );
        wp_enqueue_script( 'prh-frontend', PRH_URL . 'assets/js/resource-hub.js', [ 'jquery' ], PRH_VERSION, true );
        wp_localize_script( 'prh-frontend', 'prh_ajax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'prh_lead_capture_nonce' ),
        ] );

        ob_start();
        include PRH_PATH . 'templates/archive-ressource.php';
        return ob_get_clean();
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
            $title_parts['title'] = get_option( 'prh_hub_title', PRH_Activator::DEFAULT_HUB_TITLE );
        } elseif ( is_tax( PRH_Cpt::TAXONOMY ) ) {
            $term = get_queried_object();
            if ( $term && isset( $term->name ) ) {
                $title_parts['title'] = sprintf( __( 'Ressources : %s', 'presellia-resource-hub' ), $term->name );
            }
        }
        return $title_parts;
    }

    public function filter_wpseo_title( $title ) {
        if ( is_post_type_archive( PRH_Cpt::POST_TYPE ) ) {
            return get_option( 'prh_hub_title', PRH_Activator::DEFAULT_HUB_TITLE ) . ' | ' . get_bloginfo( 'name' );
        }
        return $title;
    }
}
