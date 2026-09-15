<?php
/**
 * Template Archive : Espace Ressources Presellia
 */

get_header();

$hub_title = get_option( 'prh_hub_title', __( 'Espace Ressources & Téléchargements', 'presellia-resource-hub' ) );
$hub_sub   = get_option( 'prh_hub_subtitle', __( 'Tous les outils de diagnostic, guides d’experts et installateurs officiels certifiés Presellia.', 'presellia-resource-hub' ) );

$categories = get_terms( [
    'taxonomy'   => PRH_Cpt::TAXONOMY,
    'hide_empty' => true,
] );
?>

<div class="prh-hub-container">
    <!-- Section En-tête / Hero -->
    <header class="prh-hero">
        <span class="prh-hero-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <?php esc_html_e( 'Centre de Ressources Officielles Presellia', 'presellia-resource-hub' ); ?>
        </span>
        <h1><?php echo esc_html( $hub_title ); ?></h1>
        <p class="prh-hero-sub"><?php echo esc_html( $hub_sub ); ?></p>

        <!-- Contrôles : Recherche et Filtres -->
        <div class="prh-controls">
            <div class="prh-search-box">
                <svg class="prh-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="prh-search-input" class="prh-search-input" placeholder="<?php esc_attr_e( 'Rechercher un outil, guide, ISO, script de diagnostic...', 'presellia-resource-hub' ); ?>" autocomplete="off">
            </div>

            <nav class="prh-pills" aria-label="<?php esc_attr_e( 'Filtrer par catégorie', 'presellia-resource-hub' ); ?>">
                <button type="button" class="prh-pill active" data-slug="all"><?php esc_html_e( 'Toutes les ressources', 'presellia-resource-hub' ); ?></button>
                <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                    <?php foreach ( $categories as $cat ) : ?>
                        <button type="button" class="prh-pill" data-slug="<?php echo esc_attr( $cat->slug ); ?>">
                            <?php echo esc_html( $cat->name ); ?>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Grille des ressources -->
    <main class="prh-grid" id="prh-grid">
        <?php
        if ( have_posts() ) :
            while ( have_posts() ) :
                the_post();
                $post_id       = get_the_ID();
                $format_spec   = get_post_meta( $post_id, '_prh_format_spec', true );
                $compatible_os = get_post_meta( $post_id, '_prh_compatible_os', true );
                $access_type   = get_post_meta( $post_id, '_prh_access_type', true ) ?: 'free';
                $terms         = get_the_terms( $post_id, PRH_Cpt::TAXONOMY );
                $cat_slugs     = [];
                $first_cat     = '';

                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                    foreach ( $terms as $term ) {
                        $cat_slugs[] = $term->slug;
                    }
                    $first_cat = $terms[0]->name;
                }
                ?>
                <article class="prh-card" data-categories="<?php echo esc_attr( implode( ' ', $cat_slugs ) ); ?>">
                    <a href="<?php the_permalink(); ?>" class="prh-card-cover" tabindex="-1" aria-hidden="true">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php the_post_thumbnail( 'large', [ 'loading' => 'lazy' ] ); ?>
                        <?php else : ?>
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #94a3b8;">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            </div>
                        <?php endif; ?>
                        <?php if ( $first_cat ) : ?>
                            <span class="prh-card-badge"><?php echo esc_html( $first_cat ); ?></span>
                        <?php endif; ?>
                    </a>

                    <div class="prh-card-body">
                        <div class="prh-card-meta">
                            <?php if ( $format_spec ) : ?>
                                <span class="prh-card-spec-pill"><?php echo esc_html( $format_spec ); ?></span>
                            <?php endif; ?>
                            <?php if ( 'gated' === $access_type ) : ?>
                                <span style="color: #6366f1; font-weight: 700; font-size: 11px;">● Accès Offert</span>
                            <?php else : ?>
                                <span style="color: #10b981; font-weight: 700; font-size: 11px;">● Téléchargement Direct</span>
                            <?php endif; ?>
                        </div>

                        <h2 class="prh-card-title">
                            <a href="<?php the_permalink(); ?>" style="text-decoration: none; color: inherit;"><?php the_title(); ?></a>
                        </h2>

                        <div class="prh-card-excerpt">
                            <?php
                            if ( has_excerpt() ) {
                                echo esc_html( get_the_excerpt() );
                            } else {
                                echo esc_html( wp_trim_words( get_the_content(), 18, '...' ) );
                            }
                            ?>
                        </div>

                        <footer class="prh-card-footer">
                            <span class="prh-card-os">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                <?php echo esc_html( $compatible_os ?: 'Tous systèmes' ); ?>
                            </span>
                            <a href="<?php the_permalink(); ?>" class="prh-card-cta">
                                <?php esc_html_e( 'Accéder', 'presellia-resource-hub' ); ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </a>
                        </footer>
                    </div>
                </article>
            <?php
            endwhile;
        else :
            ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0;">
                <p style="font-size: 18px; color: #64748b; margin: 0;"><?php esc_html_e( 'Aucune ressource disponible pour le moment.', 'presellia-resource-hub' ); ?></p>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php
get_footer();
