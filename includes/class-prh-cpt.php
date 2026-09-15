<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enregistre le CPT presellia_ressource et la taxonomie categorie_ressource.
 */
class PRH_Cpt {

    public const POST_TYPE = 'presellia_ressource';
    public const TAXONOMY  = 'categorie_ressource';

    public function __construct() {
        add_action( 'init', [ __CLASS__, 'register_post_type' ] );
        add_action( 'init', [ __CLASS__, 'register_taxonomy' ] );
        add_action( 'init', [ __CLASS__, 'ensure_default_terms' ], 20 );
    }

    public static function register_post_type(): void {
        $labels = [
            'name'                  => _x( 'Ressources', 'Post type general name', 'presellia-resource-hub' ),
            'singular_name'         => _x( 'Ressource', 'Post type singular name', 'presellia-resource-hub' ),
            'menu_name'             => _x( 'Ressources', 'Admin Menu text', 'presellia-resource-hub' ),
            'name_admin_bar'        => _x( 'Ressource', 'Add New on Toolbar', 'presellia-resource-hub' ),
            'add_new'               => __( 'Ajouter une ressource', 'presellia-resource-hub' ),
            'add_new_item'          => __( 'Ajouter une nouvelle ressource', 'presellia-resource-hub' ),
            'new_item'              => __( 'Nouvelle ressource', 'presellia-resource-hub' ),
            'edit_item'             => __( 'Modifier la ressource', 'presellia-resource-hub' ),
            'view_item'             => __( 'Voir la ressource', 'presellia-resource-hub' ),
            'all_items'             => __( 'Toutes les ressources', 'presellia-resource-hub' ),
            'search_items'          => __( 'Rechercher des ressources', 'presellia-resource-hub' ),
            'not_found'             => __( 'Aucune ressource trouvée.', 'presellia-resource-hub' ),
            'not_found_in_trash'    => __( 'Aucune ressource trouvée dans la corbeille.', 'presellia-resource-hub' ),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => [ 'slug' => 'ressources', 'with_front' => false ],
            'capability_type'    => 'post',
            'has_archive'        => 'ressources',
            'hierarchical'       => false,
            'menu_position'      => 26,
            'menu_icon'          => 'dashicons-download',
            'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
            'show_in_rest'       => true,
        ];

        register_post_type( self::POST_TYPE, $args );
    }

    public static function register_taxonomy(): void {
        $labels = [
            'name'              => _x( 'Catégories de ressources', 'taxonomy general name', 'presellia-resource-hub' ),
            'singular_name'     => _x( 'Catégorie de ressource', 'taxonomy singular name', 'presellia-resource-hub' ),
            'search_items'      => __( 'Rechercher des catégories', 'presellia-resource-hub' ),
            'all_items'         => __( 'Toutes les catégories', 'presellia-resource-hub' ),
            'parent_item'       => __( 'Catégorie parente', 'presellia-resource-hub' ),
            'parent_item_colon' => __( 'Catégorie parente :', 'presellia-resource-hub' ),
            'edit_item'         => __( 'Modifier la catégorie', 'presellia-resource-hub' ),
            'update_item'       => __( 'Mettre à jour la catégorie', 'presellia-resource-hub' ),
            'add_new_item'      => __( 'Ajouter une catégorie', 'presellia-resource-hub' ),
            'new_item_name'     => __( 'Nom de la nouvelle catégorie', 'presellia-resource-hub' ),
            'menu_name'         => __( 'Catégories', 'presellia-resource-hub' ),
        ];

        $args = [
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'categorie-ressource', 'with_front' => false ],
            'show_in_rest'      => true,
        ];

        register_taxonomy( self::TAXONOMY, [ self::POST_TYPE ], $args );
    }

    public static function ensure_default_terms(): void {
        $defaults = [
            'Outils & Scripts'       => 'Outils de diagnostic, scripts de nettoyage et utilitaires légers.',
            'Guides & Diagnostics'   => 'Guides techniques pas-à-pas et procédures de dépannage.',
            'Fichiers Officiels'     => 'Images ISO officielles, installateurs et miroirs certifiés Microsoft.',
            'Modèles & Checklists'   => 'Checklists de conformité et fiches pratiques pour pros.',
        ];

        foreach ( $defaults as $term_name => $term_desc ) {
            if ( ! term_exists( $term_name, self::TAXONOMY ) ) {
                wp_insert_term( $term_name, self::TAXONOMY, [ 'description' => $term_desc ] );
            }
        }
    }
}
