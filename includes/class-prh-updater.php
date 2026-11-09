<?php
/**
 * Mise à jour automatique depuis les releases GitHub (dépôt privé).
 *
 * Fonctionnement :
 * 1. WordPress vérifie les mises à jour → on lit la dernière release de
 *    tedisun/presellia-resource-hub via l'API GitHub (avec jeton, le dépôt est privé).
 * 2. Si elle est plus récente, la mise à jour apparaît dans Extensions comme pour
 *    n'importe quel plugin (et peut s'installer seule si les mises à jour auto sont activées).
 * 3. Au téléchargement, on demande d'abord à l'API l'adresse signée du ZIP (avec jeton),
 *    puis on télécharge ce lien signé SANS jeton : le stockage de GitHub refuse
 *    une requête qui porte à la fois un jeton et une signature.
 *
 * Depuis 1.2.3 le dépôt est public : aucun jeton n'est nécessaire, WordPress télécharge le ZIP
 * public de la release. Le jeton (constante PRESELLIA_GITHUB_TOKEN ou réglage « Jeton GitHub »)
 * reste pris en charge si le dépôt redevenait privé.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PRH_Updater {

    public const GITHUB_REPO   = 'tedisun/presellia-resource-hub';
    public const PLUGIN_SLUG   = 'presellia-resource-hub';
    public const ASSET_NAME    = 'presellia-resource-hub.zip';
    public const TRANSIENT_KEY = 'prh_update_release';
    private const CACHE_TTL    = 6 * HOUR_IN_SECONDS;

    private static ?PRH_Updater $instance = null;

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );
        add_filter( 'plugins_api', [ $this, 'plugin_info' ], 20, 3 );
        add_filter( 'upgrader_pre_download', [ $this, 'download_package' ], 10, 3 );
        add_filter( 'upgrader_source_selection', [ $this, 'fix_source_dir' ], 10, 4 );
        add_action( 'upgrader_process_complete', [ $this, 'clear_cache' ], 10, 2 );
        // « Vérifier à nouveau » (Tableau de bord > Mises à jour) : on relit GitHub tout de suite.
        add_action( 'load-update-core.php', [ $this, 'clear_cache_on_force_check' ] );

        // Nouvelle version installée (y compris par téléversement manuel) : on oublie l'ancienne réponse.
        if ( get_option( 'prh_updater_seen_version' ) !== PRH_VERSION ) {
            delete_transient( self::TRANSIENT_KEY );
            update_option( 'prh_updater_seen_version', PRH_VERSION, false );
        }
    }

    public function clear_cache_on_force_check(): void {
        if ( isset( $_GET['force-check'] ) && current_user_can( 'update_plugins' ) ) {
            delete_transient( self::TRANSIENT_KEY );
        }
    }

    public static function get_token(): string {
        if ( defined( 'PRESELLIA_GITHUB_TOKEN' ) && PRESELLIA_GITHUB_TOKEN ) {
            return (string) PRESELLIA_GITHUB_TOKEN;
        }
        return (string) get_option( 'prh_github_token', '' );
    }

    // -------------------------------------------------------------------------
    // Vérification de version
    // -------------------------------------------------------------------------

    public function check_for_update( $transient ) {
        if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
            return $transient;
        }

        $release = self::get_latest_release();
        if ( ! $release ) {
            return $transient;
        }

        $version = ltrim( (string) ( $release['tag_name'] ?? '' ), 'v' );
        $package = self::get_package_url( $release );

        $item               = new stdClass();
        $item->id           = 'github.com/' . self::GITHUB_REPO;
        $item->slug         = self::PLUGIN_SLUG;
        $item->plugin       = PRH_BASENAME;
        $item->new_version  = $version;
        $item->url          = 'https://presellia.com/ressources/';
        $item->package      = $package;
        $item->requires     = '6.0';
        $item->requires_php = '8.0';
        $item->icons        = [];
        $item->banners      = [];

        if ( '' !== $version && '' !== $package && version_compare( PRH_VERSION, $version, '<' ) ) {
            $transient->response[ PRH_BASENAME ] = $item;
        } else {
            // Déclarer le plugin « à jour » rend le bouton « Activer les mises à jour auto » disponible.
            $transient->no_update[ PRH_BASENAME ] = $item;
        }

        return $transient;
    }

    public function plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || ( $args->slug ?? '' ) !== self::PLUGIN_SLUG ) {
            return $result;
        }

        $release = self::get_latest_release();
        if ( ! $release ) {
            return $result;
        }

        $info                = new stdClass();
        $info->name          = 'Presellia Resource Hub';
        $info->slug          = self::PLUGIN_SLUG;
        $info->version       = ltrim( (string) ( $release['tag_name'] ?? '' ), 'v' );
        $info->author        = '<a href="https://presellia.com">Tedisun SARL</a>';
        $info->homepage      = 'https://presellia.com/ressources/';
        $info->requires      = '6.0';
        $info->requires_php  = '8.0';
        $info->last_updated  = $release['published_at'] ?? '';
        $info->download_link = self::get_package_url( $release );
        $info->sections      = [
            'description' => 'Espace Ressources Presellia : ressources gratuites, capture de contacts FluentCRM, livraison par e-mail et offre WooCommerce associée.',
            'changelog'   => wpautop( esc_html( (string) ( $release['body'] ?? '' ) ) ),
        ];

        return $info;
    }

    // -------------------------------------------------------------------------
    // Téléchargement authentifié (dépôt privé)
    // -------------------------------------------------------------------------

    /**
     * Intercepte le téléchargement du paquet du plugin et le fait en deux temps.
     *
     * @return false|string|WP_Error false = laisser WordPress faire, sinon chemin du fichier ou erreur.
     */
    public function download_package( $reply, $package, $upgrader ) {
        $prefix = 'https://api.github.com/repos/' . self::GITHUB_REPO . '/releases/assets/';
        if ( false !== $reply || ! is_string( $package ) || 0 !== strpos( $package, $prefix ) ) {
            return $reply;
        }

        $token = self::get_token();
        if ( '' === $token ) {
            return new WP_Error( 'prh_no_token', 'Mise à jour impossible : aucun jeton GitHub configuré (PRESELLIA_GITHUB_TOKEN ou Ressources > Réglages).' );
        }

        // 1. L'API répond par une redirection vers le lien signé du fichier.
        $response = wp_remote_get( $package, [
            'timeout'     => 20,
            'redirection' => 0,
            'headers'     => [
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/octet-stream',
                'User-Agent'    => 'Presellia-Resource-Hub/' . PRH_VERSION,
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $location = wp_remote_retrieve_header( $response, 'location' );
        if ( is_array( $location ) ) {
            $location = reset( $location );
        }
        if ( empty( $location ) ) {
            return new WP_Error( 'prh_no_redirect', sprintf( 'GitHub n\'a pas fourni le lien du paquet (HTTP %d).', (int) wp_remote_retrieve_response_code( $response ) ) );
        }

        // 2. Téléchargement du lien signé, sans jeton.
        if ( ! function_exists( 'download_url' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        return download_url( (string) $location, 300 );
    }

    /**
     * Garantit que le dossier installé s'appelle presellia-resource-hub.
     */
    public function fix_source_dir( $source, $remote_source, $upgrader, $hook_extra = [] ) {
        global $wp_filesystem;

        if ( ! isset( $hook_extra['plugin'] ) || PRH_BASENAME !== $hook_extra['plugin'] || is_wp_error( $source ) ) {
            return $source;
        }

        $expected = trailingslashit( $remote_source ) . self::PLUGIN_SLUG . '/';
        if ( trailingslashit( $source ) === $expected ) {
            return $source;
        }

        // Paquet sans dossier racine : on ne peut pas déplacer un dossier dans lui-même.
        if ( untrailingslashit( $source ) === untrailingslashit( $remote_source ) ) {
            return $source;
        }

        if ( $wp_filesystem && $wp_filesystem->move( $source, $expected ) ) {
            return $expected;
        }

        return $source;
    }

    public function clear_cache( $upgrader, $hook_extra ): void {
        if (
            isset( $hook_extra['action'], $hook_extra['type'], $hook_extra['plugins'] )
            && 'update' === $hook_extra['action']
            && 'plugin' === $hook_extra['type']
            && in_array( PRH_BASENAME, (array) $hook_extra['plugins'], true )
        ) {
            delete_transient( self::TRANSIENT_KEY );
        }
    }

    // -------------------------------------------------------------------------
    // API GitHub
    // -------------------------------------------------------------------------

    /**
     * Dernière release (mise en cache 6 h ; 1 h en cas d'échec pour ne pas marteler l'API).
     */
    public static function get_latest_release( bool $force = false ): ?array {
        if ( $force ) {
            delete_transient( self::TRANSIENT_KEY );
        }

        $cached = get_transient( self::TRANSIENT_KEY );
        if ( false !== $cached ) {
            return is_array( $cached ) && ! empty( $cached['tag_name'] ) ? $cached : null;
        }

        $headers = [
            'Accept'     => 'application/vnd.github+json',
            'User-Agent' => 'Presellia-Resource-Hub/' . PRH_VERSION,
        ];
        $token = self::get_token();
        if ( '' !== $token ) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $response = wp_remote_get( 'https://api.github.com/repos/' . self::GITHUB_REPO . '/releases/latest', [
            'timeout' => 15,
            'headers' => $headers,
        ] );

        $code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            set_transient( self::TRANSIENT_KEY, [ 'error' => $code ], 15 * MINUTE_IN_SECONDS );
            if ( class_exists( 'PRH_Logger' ) ) {
                PRH_Logger::warning( 'update_check_failed', sprintf( 'Vérification de mise à jour GitHub impossible (HTTP %d)', $code ) );
            }
            return null;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
            return null;
        }

        set_transient( self::TRANSIENT_KEY, $data, self::CACHE_TTL );
        return $data;
    }

    /**
     * Adresse du ZIP attaché à la release.
     * - Sans jeton (dépôt public) : lien public browser_download_url, téléchargé normalement par WordPress.
     * - Avec jeton (dépôt privé) : adresse API, téléchargée en deux temps par download_package().
     */
    public static function get_package_url( array $release ): string {
        $use_api = '' !== self::get_token();
        foreach ( (array) ( $release['assets'] ?? [] ) as $asset ) {
            if ( ( $asset['name'] ?? '' ) !== self::ASSET_NAME ) {
                continue;
            }
            $url = $use_api ? ( $asset['url'] ?? '' ) : ( $asset['browser_download_url'] ?? '' );
            if ( ! empty( $url ) ) {
                return (string) $url;
            }
        }
        return '';
    }

    /**
     * État lisible pour la page de réglages.
     */
    public static function get_status(): array {
        $release = self::get_latest_release();
        return [
            'token'   => '' !== self::get_token(),
            'source'  => defined( 'PRESELLIA_GITHUB_TOKEN' ) && PRESELLIA_GITHUB_TOKEN ? 'wp-config.php' : 'réglage du plugin',
            'latest'  => $release ? ltrim( (string) $release['tag_name'], 'v' ) : '',
            'package' => $release ? '' !== self::get_package_url( $release ) : false,
        ];
    }
}
