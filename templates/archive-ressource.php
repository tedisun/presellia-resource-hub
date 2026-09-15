<?php
/**
 * Template Archive : Espace Ressources Presellia
 */

get_header();

$hub_title = get_option( 'prh_hub_title', __( 'Espace Ressources & Téléchargements', 'presellia-resource-hub' ) );
$hub_sub   = get_option( 'prh_hub_subtitle', __( 'Tous les outils de diagnostic, guides d’experts et installateurs officiels certifiés Presellia.', 'presellia-resource-hub' ) );

$categories = get_terms( [
    'taxonomy'   => PRH_Cpt::TAXONOMY,
    'hide_empty' => false,
] );

function prh_get_card_svg( string $cat_slug ): string {
    switch ( $cat_slug ) {
        case 'outils-scripts':
            return '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg, #0B0F19 0%, #1E1B4B 100%);">
                <svg width="220" height="110" viewBox="0 0 220 110" fill="none" style="filter:drop-shadow(0 10px 20px rgba(0,0,0,0.4));">
                    <rect width="220" height="110" rx="8" fill="#151C2C" stroke="#312E81" stroke-width="1.5"/>
                    <circle cx="14" cy="14" r="4" fill="#EF4444"/>
                    <circle cx="26" cy="14" r="4" fill="#F59E0B"/>
                    <circle cx="38" cy="14" r="4" fill="#10B981"/>
                    <text x="14" y="44" fill="#38BDF8" font-family="monospace" font-size="11" font-weight="bold">PS C:\&gt; cscript ospp.vbs</text>
                    <text x="14" y="64" fill="#4ADE80" font-family="monospace" font-size="10">&lt;Product key removed&gt;</text>
                    <text x="14" y="84" fill="#A5B4FC" font-family="monospace" font-size="10">Status: Activation OK</text>
                </svg>
            </div>';
        case 'guides-diagnostics':
            return '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg, #064E3B 0%, #065F46 100%);">
                <svg width="180" height="110" viewBox="0 0 180 110" fill="none" style="filter:drop-shadow(0 12px 24px rgba(0,0,0,0.3));">
                    <rect x="35" y="8" width="110" height="94" rx="6" fill="#047857" stroke="#34D399" stroke-width="1.5"/>
                    <rect x="42" y="16" width="96" height="78" rx="4" fill="#065F46"/>
                    <text x="50" y="38" fill="#FCD34D" font-family="sans-serif" font-size="10" font-weight="900">PRESELLIA GUIDE</text>
                    <text x="50" y="52" fill="#FFFFFF" font-family="sans-serif" font-size="11" font-weight="bold">10 ERREURS</text>
                    <text x="50" y="66" fill="#FFFFFF" font-family="sans-serif" font-size="9">D’ACTIVATION</text>
                    <rect x="50" y="74" width="40" height="12" rx="3" fill="#10B981"/>
                    <text x="55" y="83" fill="#FFFFFF" font-family="sans-serif" font-size="8" font-weight="bold">RÉSOLUES</text>
                </svg>
            </div>';
        case 'fichiers-officiels':
            return '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg, #1E3A8A 0%, #1E1B4B 100%);">
                <svg width="190" height="110" viewBox="0 0 190 110" fill="none" style="filter:drop-shadow(0 12px 24px rgba(0,0,0,0.35));">
                    <rect x="45" y="10" width="100" height="90" rx="8" fill="#1D4ED8" stroke="#60A5FA" stroke-width="1.5"/>
                    <path d="M55 24L75 16V84L55 92V24Z" fill="#1E40AF" opacity="0.6"/>
                    <text x="80" y="42" fill="#FFFFFF" font-family="sans-serif" font-size="13" font-weight="900">Office</text>
                    <text x="80" y="58" fill="#FBBF24" font-family="sans-serif" font-size="13" font-weight="900">2024</text>
                    <text x="80" y="74" fill="#93C5FD" font-family="sans-serif" font-size="9" font-weight="bold">PRO PLUS</text>
                    <rect x="80" y="80" width="55" height="10" rx="3" fill="#3B82F6"/>
                    <text x="84" y="88" fill="#FFFFFF" font-family="sans-serif" font-size="7" font-weight="bold">MICROSOFT CDN</text>
                </svg>
            </div>';
        case 'modeles-checklists':
        default:
            return '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg, #78350F 0%, #451A03 100%);">
                <svg width="190" height="110" viewBox="0 0 190 110" fill="none" style="filter:drop-shadow(0 12px 24px rgba(0,0,0,0.3));">
                    <rect x="40" y="12" width="110" height="86" rx="6" fill="#166534" stroke="#4ADE80" stroke-width="1.5"/>
                    <rect x="48" y="20" width="94" height="70" rx="3" fill="#FFFFFF"/>
                    <rect x="48" y="20" width="94" height="16" fill="#15803D"/>
                    <text x="56" y="32" fill="#FFFFFF" font-family="sans-serif" font-size="9" font-weight="bold">AUDIT LICENCES PME</text>
                    <line x1="48" y1="48" x2="142" y2="48" stroke="#E2E8F0"/>
                    <line x1="48" y1="62" x2="142" y2="62" stroke="#E2E8F0"/>
                    <line x1="48" y1="76" x2="142" y2="76" stroke="#E2E8F0"/>
                    <line x1="80" y1="36" x2="80" y2="90" stroke="#E2E8F0"/>
                    <circle cx="64" cy="55" r="3" fill="#16A34A"/>
                    <circle cx="64" cy="69" r="3" fill="#16A34A"/>
                    <circle cx="64" cy="83" r="3" fill="#16A34A"/>
                </svg>
            </div>';
    }
}
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
        $query = new WP_Query( [
            'post_type'      => PRH_Cpt::POST_TYPE,
            'posts_per_page' => 50,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );

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
                ?>
                <article class="prh-card" data-categories="<?php echo esc_attr( implode( ' ', $cat_slugs ) ); ?>">
                    <a href="<?php the_permalink(); ?>" class="prh-card-cover" tabindex="-1" aria-hidden="true">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php the_post_thumbnail( 'large', [ 'loading' => 'lazy' ] ); ?>
                        <?php else : ?>
                            <?php echo prh_get_card_svg( $first_slug ); ?>
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
            wp_reset_postdata();
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
