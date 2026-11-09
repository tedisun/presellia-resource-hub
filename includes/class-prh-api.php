<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * API REST MCP — Presellia Resource Hub
 *
 * Namespace : prh/v1
 * Authentification : Header X-PRH-API-Key
 */
class PRH_Api {

    public const NAMESPACE = 'prh/v1';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        // GET /status
        register_rest_route( self::NAMESPACE, '/status', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_status' ],
            'permission_callback' => [ $this, 'check_api_key' ],
        ] );

        // GET /logs
        register_rest_route( self::NAMESPACE, '/logs', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_logs' ],
            'permission_callback' => [ $this, 'check_api_key' ],
            'args'                => [
                'limit' => [
                    'type'              => 'integer',
                    'default'           => 50,
                    'minimum'           => 1,
                    'maximum'           => 500,
                    'sanitize_callback' => 'absint',
                ],
                'level' => [
                    'type'              => 'string',
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_key',
                    'enum'              => [ '', 'info', 'warning', 'error' ],
                ],
                'event' => [
                    'type'              => 'string',
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_key',
                ],
            ],
        ] );

        // POST /logs/clear
        register_rest_route( self::NAMESPACE, '/logs/clear', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'clear_logs' ],
            'permission_callback' => [ $this, 'check_api_key' ],
        ] );

        // GET /resources
        register_rest_route( self::NAMESPACE, '/resources', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_resources' ],
            'permission_callback' => [ $this, 'check_api_key' ],
            'args'                => [
                'category' => [
                    'type'              => 'string',
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'limit'    => [
                    'type'              => 'integer',
                    'default'           => 100,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );

        // GET /resources/{id}
        register_rest_route( self::NAMESPACE, '/resources/(?P<id>\d+)', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_single_resource' ],
            'permission_callback' => [ $this, 'check_api_key' ],
            'args'                => [
                'id' => [
                    'type'              => 'integer',
                    'required'          => true,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );

        // POST /resources (Create or Update)
        register_rest_route( self::NAMESPACE, '/resources', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'save_resource' ],
            'permission_callback' => [ $this, 'check_api_key' ],
        ] );

        // DELETE /resources/{id}
        register_rest_route( self::NAMESPACE, '/resources/(?P<id>\d+)', [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => [ $this, 'delete_resource' ],
            'permission_callback' => [ $this, 'check_api_key' ],
            'args'                => [
                'id' => [
                    'type'              => 'integer',
                    'required'          => true,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );
    }

    public function check_api_key( WP_REST_Request $request ): bool|WP_Error {
        $stored_key = get_option( 'prh_api_key', '' );

        if ( empty( $stored_key ) ) {
            return new WP_Error(
                'prh_no_api_key',
                __( 'Aucune clé API configurée. Veuillez renseigner prh_api_key.', 'presellia-resource-hub' ),
                [ 'status' => 503 ]
            );
        }

        $provided_key = $request->get_header( 'X-PRH-API-Key' );

        if ( empty( $provided_key ) ) {
            return new WP_Error(
                'prh_missing_key',
                __( 'En-tête X-PRH-API-Key manquant.', 'presellia-resource-hub' ),
                [ 'status' => 401 ]
            );
        }

        if ( ! hash_equals( hash( 'sha256', $stored_key ), hash( 'sha256', $provided_key ) ) ) {
            return new WP_Error(
                'prh_invalid_key',
                __( 'Clé API invalide.', 'presellia-resource-hub' ),
                [ 'status' => 403 ]
            );
        }

        return true;
    }

    public function get_status( WP_REST_Request $request ): WP_REST_Response {
        $resources_count = wp_count_posts( PRH_Cpt::POST_TYPE );
        $categories      = get_terms( [
            'taxonomy'   => PRH_Cpt::TAXONOMY,
            'hide_empty' => false,
        ] );

        $fluentcrm_active = function_exists( 'FluentCrmApi' );
        $wc_active        = class_exists( 'WooCommerce' );

        return new WP_REST_Response( [
            'status'            => 'ok',
            'version'           => PRH_VERSION,
            'resources_count'   => [
                'publish' => (int) ( $resources_count->publish ?? 0 ),
                'draft'   => (int) ( $resources_count->draft ?? 0 ),
                'trash'   => (int) ( $resources_count->trash ?? 0 ),
            ],
            'categories_count'  => is_array( $categories ) ? count( $categories ) : 0,
            'fluentcrm_active'  => $fluentcrm_active,
            'woocommerce_active'=> $wc_active,
            'logs_summary'      => PRH_Logger::get_counts( 7 ),
        ], 200 );
    }

    public function get_logs( WP_REST_Request $request ): WP_REST_Response {
        $limit = $request->get_param( 'limit' );
        $level = $request->get_param( 'level' );
        $event = $request->get_param( 'event' );

        $logs = PRH_Logger::get_recent( $limit, $level, $event );

        return new WP_REST_Response( [
            'total' => count( $logs ),
            'logs'  => $logs,
        ], 200 );
    }

    public function clear_logs( WP_REST_Request $request ): WP_REST_Response {
        $success = PRH_Logger::clear_all();

        return new WP_REST_Response( [
            'success' => $success,
            'message' => $success ? 'Logs vidés avec succès.' : 'Erreur lors du vidage des logs.',
        ], $success ? 200 : 500 );
    }

    public function get_resources( WP_REST_Request $request ): WP_REST_Response {
        $limit    = $request->get_param( 'limit' ) ?: 100;
        $category = $request->get_param( 'category' );

        $args = [
            'post_type'      => PRH_Cpt::POST_TYPE,
            'posts_per_page' => $limit,
            'post_status'    => [ 'publish', 'draft' ],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if ( ! empty( $category ) ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => PRH_Cpt::TAXONOMY,
                    'field'    => 'slug',
                    'terms'    => $category,
                ],
            ];
        }

        $posts = get_posts( $args );
        $data  = [];

        foreach ( $posts as $post ) {
            $data[] = $this->format_resource_data( $post );
        }

        return new WP_REST_Response( [
            'total'     => count( $data ),
            'resources' => $data,
        ], 200 );
    }

    public function get_single_resource( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $id   = $request->get_param( 'id' );
        $post = get_post( $id );

        if ( ! $post || $post->post_type !== PRH_Cpt::POST_TYPE ) {
            return new WP_Error( 'not_found', 'Ressource introuvable.', [ 'status' => 404 ] );
        }

        return new WP_REST_Response( $this->format_resource_data( $post ), 200 );
    }

    public function save_resource( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $params  = $request->get_json_params() ?: $request->get_body_params();
        $post_id = ! empty( $params['id'] ) ? absint( $params['id'] ) : 0;

        $post_data = [
            'post_type'   => PRH_Cpt::POST_TYPE,
            'post_status' => in_array( $params['status'] ?? '', [ 'publish', 'draft', 'pending', 'private' ], true ) ? $params['status'] : 'publish',
        ];

        if ( ! empty( $params['title'] ) ) {
            $post_data['post_title'] = sanitize_text_field( $params['title'] );
        }
        if ( isset( $params['content'] ) ) {
            $post_data['post_content'] = wp_kses_post( $params['content'] );
        }
        if ( isset( $params['excerpt'] ) ) {
            $post_data['post_excerpt'] = sanitize_textarea_field( $params['excerpt'] );
        }
        if ( ! empty( $params['slug'] ) ) {
            $post_data['post_name'] = sanitize_title( $params['slug'] );
        }

        if ( $post_id > 0 ) {
            $post_data['ID'] = $post_id;
            $res_id = wp_update_post( $post_data, true );
        } else {
            $res_id = wp_insert_post( $post_data, true );
        }

        if ( is_wp_error( $res_id ) ) {
            return $res_id;
        }

        $meta_sanitizers = [
            'format_spec'        => 'sanitize_text_field',
            'compatible_os'      => 'sanitize_text_field',
            'download_url'       => 'esc_url_raw',
            'media_type'         => 'sanitize_key',
            'media_url'          => 'sanitize_text_field',
            'access_type'        => 'sanitize_key',
            'crm_tag'            => 'sanitize_text_field',
            'subtitle'           => 'sanitize_text_field',
            'value_stack'        => 'sanitize_textarea_field',
            'cta_label'          => 'sanitize_text_field',
            'form_label'         => 'sanitize_text_field',
            'email_subject'      => 'sanitize_text_field',
            'email_body'         => 'sanitize_textarea_field',
            'linked_product_id'  => 'absint',
            'offer_badge'        => 'sanitize_text_field',
            'offer_custom_title' => 'sanitize_text_field',
            'offer_custom_desc'  => 'sanitize_textarea_field',
        ];

        foreach ( $meta_sanitizers as $param_key => $sanitizer ) {
            $meta_key = '_prh_' . $param_key;
            if ( isset( $params[ $param_key ] ) ) {
                $raw = $params[ $param_key ];
                $clean = call_user_func( $sanitizer, $raw );
                update_post_meta( $res_id, $meta_key, $clean );
            }
        }

        // Catégories (slugs ou noms)
        if ( ! empty( $params['categories'] ) ) {
            $terms = is_array( $params['categories'] ) ? $params['categories'] : [ $params['categories'] ];
            wp_set_object_terms( $res_id, $terms, PRH_Cpt::TAXONOMY );
        }

        // Thumbnail
        if ( ! empty( $params['thumbnail_id'] ) ) {
            set_post_thumbnail( $res_id, absint( $params['thumbnail_id'] ) );
        }

        PRH_Logger::info(
            'resource_saved_via_api',
            sprintf( 'Ressource #%d enregistrée via API REST MCP', $res_id ),
            [ 'id' => $res_id, 'title' => get_the_title( $res_id ) ]
        );

        $saved_post = get_post( $res_id );
        return new WP_REST_Response( $this->format_resource_data( $saved_post ), 200 );
    }

    public function delete_resource( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $id = $request->get_param( 'id' );
        $force = ! empty( $request->get_param( 'force' ) );

        $post = get_post( $id );
        if ( ! $post || $post->post_type !== PRH_Cpt::POST_TYPE ) {
            return new WP_Error( 'not_found', 'Ressource introuvable.', [ 'status' => 404 ] );
        }

        $deleted = wp_delete_post( $id, $force );

        if ( ! $deleted ) {
            return new WP_Error( 'delete_failed', 'Échec de la suppression.', [ 'status' => 500 ] );
        }

        PRH_Logger::info(
            'resource_deleted_via_api',
            sprintf( 'Ressource #%d supprimée via API REST MCP (force=%s)', $id, $force ? 'true' : 'false' )
        );

        return new WP_REST_Response( [
            'success' => true,
            'message' => "Ressource #{$id} supprimée.",
        ], 200 );
    }

    private function format_resource_data( WP_Post $post ): array {
        $terms      = get_the_terms( $post->ID, PRH_Cpt::TAXONOMY );
        $categories = [];
        if ( is_array( $terms ) ) {
            foreach ( $terms as $term ) {
                $categories[] = [
                    'id'   => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                ];
            }
        }

        return [
            'id'                 => $post->ID,
            'title'              => $post->post_title,
            'slug'               => $post->post_name,
            'status'             => $post->post_status,
            'permalink'          => get_permalink( $post->ID ),
            'excerpt'            => $post->post_excerpt,
            'content'            => $post->post_content,
            'subtitle'           => get_post_meta( $post->ID, '_prh_subtitle', true ),
            'format_spec'        => get_post_meta( $post->ID, '_prh_format_spec', true ),
            'compatible_os'      => get_post_meta( $post->ID, '_prh_compatible_os', true ),
            'download_url'       => get_post_meta( $post->ID, '_prh_download_url', true ),
            'media_type'         => get_post_meta( $post->ID, '_prh_media_type', true ) ?: 'image',
            'media_url'          => get_post_meta( $post->ID, '_prh_media_url', true ),
            'access_type'        => get_post_meta( $post->ID, '_prh_access_type', true ) ?: 'free',
            'crm_tag'            => get_post_meta( $post->ID, '_prh_crm_tag', true ),
            'value_stack'        => get_post_meta( $post->ID, '_prh_value_stack', true ),
            'cta_label'          => get_post_meta( $post->ID, '_prh_cta_label', true ),
            'form_label'         => get_post_meta( $post->ID, '_prh_form_label', true ),
            'email_subject'      => get_post_meta( $post->ID, '_prh_email_subject', true ),
            'email_body'         => get_post_meta( $post->ID, '_prh_email_body', true ),
            'linked_product_id'  => absint( get_post_meta( $post->ID, '_prh_linked_product_id', true ) ),
            'offer_badge'        => get_post_meta( $post->ID, '_prh_offer_badge', true ),
            'offer_custom_title' => get_post_meta( $post->ID, '_prh_offer_custom_title', true ),
            'offer_custom_desc'  => get_post_meta( $post->ID, '_prh_offer_custom_desc', true ),
            'categories'         => $categories,
            'thumbnail_url'      => get_the_post_thumbnail_url( $post->ID, 'large' ) ?: '',
            'created_at'         => $post->post_date,
            'updated_at'         => $post->post_modified,
        ];
    }
}
