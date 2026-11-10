<?php
/**
 * Template Archive : Espace Ressources Presellia
 *
 * Même charte que les hubs /telechargements/ (plugin presellia-telechargements) :
 * en-tête centré, cartes blanches compactes, pied de carte séparé.
 */

get_header();

$hub_title = get_option( 'prh_hub_title', PRH_Activator::DEFAULT_HUB_TITLE );
$hub_sub   = get_option( 'prh_hub_subtitle', PRH_Activator::DEFAULT_HUB_SUBTITLE );

// Seuil sous lequel la recherche et les filtres n'apportent rien (un filtre = une carte).
$prh_filters_min = (int) apply_filters( 'prh_archive_filters_min', 8 );

$categories = get_terms( [
    'taxonomy'   => PRH_Cpt::TAXONOMY,
    'hide_empty' => true,
] );

if ( ! function_exists( 'prh_get_card_icon' ) ) {
    /**
     * Icône et teinte de la carte selon la catégorie de la ressource.
     *
     * @return array{0: string, 1: string} [ classe de teinte, SVG ]
     */
    function prh_get_card_icon( string $cat_slug ): array {
        $open = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';

        switch ( $cat_slug ) {
            case 'outils-scripts':
                return [ 'prh-tint-purple', $open . '<polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>' ];
            case 'guides-diagnostics':
                return [ 'prh-tint-blue', $open . '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>' ];
            case 'fichiers-officiels':
                return [ 'prh-tint-green', $open . '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>' ];
            case 'modeles-checklists':
            default:
                return [ 'prh-tint-amber', $open . '<path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="m9 14 2 2 4-4"/></svg>' ];
        }
    }
}

if ( ! function_exists( 'prh_get_card_cta' ) ) {
    /**
     * Libellé d'action précis selon la catégorie (au lieu d'un « Accéder » générique).
     */
    function prh_get_card_cta( string $cat_slug ): string {
        switch ( $cat_slug ) {
            case 'outils-scripts':
                return __( 'Télécharger l’outil', 'presellia-resource-hub' );
            case 'guides-diagnostics':
                return __( 'Lire le guide', 'presellia-resource-hub' );
            case 'fichiers-officiels':
                return __( 'Télécharger', 'presellia-resource-hub' );
            case 'modeles-checklists':
                return __( 'Obtenir le modèle', 'presellia-resource-hub' );
            default:
                return __( 'Voir la ressource', 'presellia-resource-hub' );
        }
    }
}

$query = new WP_Query( [
    'post_type'      => PRH_Cpt::POST_TYPE,
    'posts_per_page' => 50,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
] );

$show_filters = $query->post_count >= $prh_filters_min;
?>

<div class="prh-hub-container">
    <header class="prh-hero">
        <h1><?php echo esc_html( $hub_title ); ?></h1>
        <p class="prh-hero-sub"><?php echo esc_html( $hub_sub ); ?></p>
    </header>

    <?php if ( $show_filters ) : ?>
        <div class="prh-controls">
            <nav class="prh-pills" aria-label="<?php esc_attr_e( 'Filtrer par catégorie', 'presellia-resource-hub' ); ?>">
                <button type="button" class="prh-pill active" data-slug="all"><?php esc_html_e( 'Tout', 'presellia-resource-hub' ); ?></button>
                <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                    <?php foreach ( $categories as $cat ) : ?>
                        <button type="button" class="prh-pill" data-slug="<?php echo esc_attr( $cat->slug ); ?>">
                            <?php echo esc_html( $cat->name ); ?>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </nav>
            <div class="prh-search-box">
                <svg class="prh-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="prh-search-input" class="prh-search-input" placeholder="<?php esc_attr_e( 'Rechercher une ressource…', 'presellia-resource-hub' ); ?>" autocomplete="off">
            </div>
        </div>
    <?php endif; ?>

    <main class="prh-grid" id="prh-grid">
        <?php
        if ( $query->have_posts() ) :
            while ( $query->have_posts() ) :
                $query->the_post();
                $post_id       = get_the_ID();
                $format_spec   = get_post_meta( $post_id, '_prh_format_spec', true );
                $compatible_os = get_post_meta( $post_id, '_prh_compatible_os', true );
                $access_type   = get_post_meta( $post_id, '_prh_access_type', true ) ?: 'free';
                $terms         = get_the_terms( $post_id, PRH_Cpt::TAXONOMY );
                $cat_slugs     = [];
                $first_cat     = '';
                $first_slug    = '';

                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                    foreach ( $terms as $term ) {
                        $cat_slugs[] = $term->slug;
                    }
                    $first_cat  = $terms[0]->name;
                    $first_slug = $terms[0]->slug;
                }

                [ $tint_class, $icon_svg ] = prh_get_card_icon( $first_slug );
                // Même verbe que le bouton de la page (« Recevoir le guide ») pour une ressource sous formulaire.
                $card_cta = 'gated' === $access_type ? PRH_Delivery::get_form_label( $post_id ) : prh_get_card_cta( $first_slug );
                ?>
                <a href="<?php the_permalink(); ?>" class="prh-card" data-categories="<?php echo esc_attr( implode( ' ', $cat_slugs ) ); ?>">
                    <div class="prh-card-media <?php echo has_post_thumbnail() ? '' : esc_attr( $tint_class ); ?>">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php the_post_thumbnail( 'medium_large', [ 'loading' => 'lazy', 'alt' => esc_attr( get_the_title() ) ] ); ?>
                        <?php else : ?>
                            <?php echo $icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput -- SVG statique interne ?>
                        <?php endif; ?>
                    </div>

                    <div class="prh-card-body">
                        <h2 class="prh-card-title"><?php the_title(); ?></h2>
                        <p class="prh-card-excerpt">
                            <?php
                            if ( has_excerpt() ) {
                                echo esc_html( get_the_excerpt() );
                            } else {
                                echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_content() ), 30, '…' ) );
                            }
                            ?>
                        </p>
                        <span class="prh-card-cta"><?php echo esc_html( $card_cta ); ?> →</span>
                    </div>
                </a>
            <?php
            endwhile;
            wp_reset_postdata();
        else :
            ?>
            <div class="prh-empty">
                <p><?php esc_html_e( 'Aucune ressource disponible pour le moment.', 'presellia-resource-hub' ); ?></p>
            </div>
        <?php endif; ?>
    </main>

    <div class="prh-help-bar">
        <div class="prh-help-main">
            <div class="prh-help-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </div>
            <div class="prh-help-text">
                <strong><?php esc_html_e( 'Vous cherchez l’installateur d’un logiciel ?', 'presellia-resource-hub' ); ?></strong>
                <span><?php esc_html_e( 'Office, Windows, Kaspersky : les liens officiels sont regroupés dans nos pages de téléchargement.', 'presellia-resource-hub' ); ?></span>
            </div>
        </div>
        <div class="prh-help-actions">
            <a class="prh-help-btn" href="<?php echo esc_url( home_url( '/telechargements/' ) ); ?>"><?php esc_html_e( 'Téléchargements', 'presellia-resource-hub' ); ?></a>
            <a class="prh-help-btn" href="<?php echo esc_url( home_url( '/aide/' ) ); ?>"><?php esc_html_e( 'Centre d’aide', 'presellia-resource-hub' ); ?></a>
        </div>
    </div>
</div>

<?php
get_footer();
