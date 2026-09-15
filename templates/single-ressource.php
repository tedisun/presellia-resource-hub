<?php
/**
 * Template Single : Fiche Ressource (Style Alex Hormozi)
 */

get_header();

$post_id            = get_the_ID();
$subtitle           = get_post_meta( $post_id, '_prh_subtitle', true );
$format_spec        = get_post_meta( $post_id, '_prh_format_spec', true );
$compatible_os      = get_post_meta( $post_id, '_prh_compatible_os', true );
$download_url       = get_post_meta( $post_id, '_prh_download_url', true );
$media_type         = get_post_meta( $post_id, '_prh_media_type', true ) ?: 'image';
$media_url          = get_post_meta( $post_id, '_prh_media_url', true );
$access_type        = get_post_meta( $post_id, '_prh_access_type', true ) ?: 'free';
$value_stack_raw    = get_post_meta( $post_id, '_prh_value_stack', true );
$linked_product_id  = absint( get_post_meta( $post_id, '_prh_linked_product_id', true ) );
$offer_badge        = get_post_meta( $post_id, '_prh_offer_badge', true ) ?: 'OFFRE COMPLÉMENTAIRE RECOMMANDÉE';
$offer_custom_title = get_post_meta( $post_id, '_prh_offer_custom_title', true );
$offer_custom_desc  = get_post_meta( $post_id, '_prh_offer_custom_desc', true );

$terms      = get_the_terms( $post_id, PRH_Cpt::TAXONOMY );
$first_cat  = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
$first_slug = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms[0]->slug : '';

$value_items = [];
if ( ! empty( $value_stack_raw ) ) {
    $lines = preg_split( '/\r\n|\r|\n/', (string) $value_stack_raw );
    if ( is_array( $lines ) ) {
        foreach ( $lines as $line ) {
            $trimmed = trim( $line );
            if ( ! empty( $trimmed ) ) {
                $value_items[] = $trimmed;
            }
        }
    }
}

function prh_get_single_svg( string $cat_slug ): string {
    switch ( $cat_slug ) {
        case 'outils-scripts':
            return '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg, #0B0F19 0%, #1E1B4B 100%);">
                <svg width="320" height="160" viewBox="0 0 220 110" fill="none" style="filter:drop-shadow(0 15px 30px rgba(0,0,0,0.5));">
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
                <svg width="280" height="170" viewBox="0 0 180 110" fill="none" style="filter:drop-shadow(0 15px 30px rgba(0,0,0,0.4));">
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
                <svg width="280" height="160" viewBox="0 0 190 110" fill="none" style="filter:drop-shadow(0 15px 30px rgba(0,0,0,0.45));">
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
                <svg width="280" height="160" viewBox="0 0 190 110" fill="none" style="filter:drop-shadow(0 15px 30px rgba(0,0,0,0.4));">
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

<div class="prh-single-wrap">
    <!-- Breadcrumb -->
    <a href="<?php echo esc_url( get_post_type_archive_link( PRH_Cpt::POST_TYPE ) ); ?>" class="prh-breadcrumb">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        <?php esc_html_e( 'Retour à toutes les ressources', 'presellia-resource-hub' ); ?>
    </a>

    <!-- En-tête Hormozi -->
    <header class="prh-single-header">
        <?php if ( $first_cat ) : ?>
            <span class="prh-single-category"><?php echo esc_html( $first_cat ); ?></span>
        <?php endif; ?>

        <h1 class="prh-single-title"><?php the_title(); ?></h1>

        <?php if ( $subtitle ) : ?>
            <p class="prh-single-subtitle"><?php echo esc_html( $subtitle ); ?></p>
        <?php endif; ?>

        <div class="prh-single-meta-bar">
            <?php if ( $format_spec ) : ?>
                <div class="prh-single-meta-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span><?php echo esc_html( $format_spec ); ?></span>
                </div>
            <?php endif; ?>

            <?php if ( $compatible_os ) : ?>
                <div class="prh-single-meta-item">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    <span><?php echo esc_html( $compatible_os ); ?></span>
                </div>
            <?php endif; ?>

            <div class="prh-single-meta-item" style="color: #10b981;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                <span><?php echo 'gated' === $access_type ? esc_html__( 'Accès 100% Offert', 'presellia-resource-hub' ) : esc_html__( 'Téléchargement Direct', 'presellia-resource-hub' ); ?></span>
            </div>
        </div>
    </header>

    <!-- Zone Média (16:9) -->
    <div class="prh-media-container">
        <?php if ( 'youtube' === $media_type && ! empty( $media_url ) ) : 
            $yt_id = $media_url;
            if ( preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $media_url, $match ) ) {
                $yt_id = $match[1];
            }
        ?>
            <iframe src="https://www.youtube.com/embed/<?php echo esc_attr( $yt_id ); ?>?rel=0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        <?php elseif ( 'custom_video' === $media_type && ! empty( $media_url ) ) : ?>
            <video controls src="<?php echo esc_url( $media_url ); ?>"></video>
        <?php elseif ( has_post_thumbnail() ) : ?>
            <?php the_post_thumbnail( 'full' ); ?>
        <?php else : ?>
            <?php echo prh_get_single_svg( $first_slug ); ?>
        <?php endif; ?>
    </div>

    <!-- Value Stack (Ce que contient la ressource) -->
    <?php if ( ! empty( $value_items ) ) : ?>
        <section class="prh-value-stack-card">
            <h3>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                <?php esc_html_e( 'Ce que cette ressource résout pour vous :', 'presellia-resource-hub' ); ?>
            </h3>
            <ul class="prh-value-stack-list">
                <?php foreach ( $value_items as $item ) : ?>
                    <li>
                        <svg class="prh-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span><?php echo esc_html( $item ); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <!-- Contenu / Guide explicatif optionnel -->
    <?php if ( get_the_content() ) : ?>
        <section class="prh-description-content" style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 30px; margin-bottom: 40px; line-height: 1.7; font-size: 16px;">
            <h3 style="margin-top: 0; font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 16px;"><?php esc_html_e( 'Instructions d\'utilisation & Détails', 'presellia-resource-hub' ); ?></h3>
            <?php the_content(); ?>
        </section>
    <?php endif; ?>

    <!-- Action Box : Téléchargement direct ou Déblocage lead -->
    <section class="prh-action-box">
        <?php if ( 'gated' === $access_type ) : ?>
            <h2 style="font-size: 24px; font-weight: 800; margin: 0 0 10px; color: #0f172a;"><?php esc_html_e( 'Accédez gratuitement à cette ressource', 'presellia-resource-hub' ); ?></h2>
            <p style="color: #64748b; font-size: 15px; margin: 0 0 24px;"><?php esc_html_e( 'Renseignez vos coordonnées pour débloquer votre accès immédiat et recevoir les futures mises à jour.', 'presellia-resource-hub' ); ?></p>
            <button type="button" class="prh-action-btn prh-open-gated-modal" data-resource-id="<?php echo esc_attr( $post_id ); ?>" data-resource-title="<?php echo esc_attr( get_the_title() ); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <?php esc_html_e( 'Débloquer mon accès immédiat', 'presellia-resource-hub' ); ?>
            </button>
            <div class="prh-action-guarantee"><?php esc_html_e( '✓ Téléchargement instantané · Accès libre garanti', 'presellia-resource-hub' ); ?></div>
        <?php else : ?>
            <h2 style="font-size: 24px; font-weight: 800; margin: 0 0 10px; color: #0f172a;"><?php esc_html_e( 'Téléchargement Direct', 'presellia-resource-hub' ); ?></h2>
            <p style="color: #64748b; font-size: 15px; margin: 0 0 24px;"><?php esc_html_e( 'Cette ressource officielle est mise à disposition gratuitement par l\'équipe technique Presellia.', 'presellia-resource-hub' ); ?></p>
            <a href="<?php echo esc_url( $download_url ?: '#' ); ?>" class="prh-action-btn" target="_blank" rel="noopener">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <?php esc_html_e( 'Télécharger le fichier maintenant', 'presellia-resource-hub' ); ?>
            </a>
            <div class="prh-action-guarantee"><?php esc_html_e( '✓ Serveur sécurisé et certifié Presellia', 'presellia-resource-hub' ); ?></div>
        <?php endif; ?>
    </section>

    <!-- Offre Complémentaire Recommandée (WooCommerce Grand Slam Box) -->
    <?php
    if ( $linked_product_id > 0 && function_exists( 'wc_get_product' ) ) :
        $product = wc_get_product( $linked_product_id );
        if ( $product && $product->is_purchasable() ) :
            $offer_title = ! empty( $offer_custom_title ) ? $offer_custom_title : $product->get_name();
            $offer_desc  = ! empty( $offer_custom_desc ) ? $offer_custom_desc : wp_strip_all_tags( $product->get_short_description() );
            $checkout_url = add_query_arg( 'add-to-cart', $linked_product_id, function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : wc_get_cart_url() );

            // Calcul du prix secondaire (Euro / CFA)
            $current_currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'XOF';
            $product_price    = (float) $product->get_price();
            $cfa_rate_to_eur  = 0.00152449;

            global $WOOCS;
            if ( isset( $WOOCS ) && method_exists( $WOOCS, 'get_currencies' ) ) {
                $currencies = $WOOCS->get_currencies();
                if ( ! empty( $currencies['EUR']['rate'] ) ) {
                    $cfa_rate_to_eur = (float) $currencies['EUR']['rate'];
                }
            }

            $secondary_price_text = '';
            if ( 'EUR' === $current_currency ) {
                $cfa_calc = round( $product_price / $cfa_rate_to_eur );
                $secondary_price_text = sprintf( __( 'soit env. %s CFA', 'presellia-resource-hub' ), number_format( $cfa_calc, 0, ',', ' ' ) );
            } else {
                $eur_calc = round( $product_price * $cfa_rate_to_eur, 2 );
                $secondary_price_text = sprintf( __( 'soit env. %s €', 'presellia-resource-hub' ), number_format( $eur_calc, 2, ',', ' ' ) );
            }
            ?>
            <section class="prh-offer-box">
                <span class="prh-offer-tag"><?php echo esc_html( $offer_badge ); ?></span>
                <div class="prh-offer-content">
                    <div class="prh-offer-left">
                        <h2><?php echo esc_html( $offer_title ); ?></h2>
                        <?php if ( $offer_desc ) : ?>
                            <p><?php echo esc_html( $offer_desc ); ?></p>
                        <?php endif; ?>
                        <div style="display: flex; gap: 16px; align-items: center; color: #cbd5e1; font-size: 13px;">
                            <span>✓ Activation garantie</span>
                            <span>✓ Facture avec TVA</span>
                            <span>✓ Support dédié</span>
                        </div>
                    </div>

                    <div class="prh-offer-right">
                        <?php if ( $product->is_on_sale() && $product->get_regular_price() ) : ?>
                            <div class="prh-offer-price-old"><?php echo wc_price( $product->get_regular_price() ); ?></div>
                        <?php endif; ?>
                        <div class="prh-offer-price-now"><?php echo wc_price( $product->get_price() ); ?></div>
                        <?php if ( ! empty( $secondary_price_text ) ) : ?>
                            <div class="prh-offer-price-secondary"><?php echo esc_html( $secondary_price_text ); ?></div>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( $checkout_url ); ?>" class="prh-offer-btn">
                            <?php esc_html_e( 'Obtenir ma licence →', 'presellia-resource-hub' ); ?>
                        </a>
                    </div>
                </div>
            </section>
        <?php
        endif;
    endif;
    ?>
</div>

<!-- Modale de capture de leads FluentCRM -->
<div class="prh-modal-backdrop" id="prh-lead-modal">
    <div class="prh-modal">
        <button type="button" class="prh-modal-close">&times;</button>
        <h3><?php esc_html_e( 'Débloquer votre ressource', 'presellia-resource-hub' ); ?></h3>
        <p id="prh-modal-title-target"><?php esc_html_e( 'Entrez vos informations pour recevoir votre accès instantané.', 'presellia-resource-hub' ); ?></p>

        <div id="prh-modal-feedback" style="display: none;"></div>

        <form id="prh-lead-form">
            <input type="hidden" id="prh-modal-resource-id" value="<?php echo esc_attr( $post_id ); ?>">

            <div class="prh-form-group">
                <label for="prh_lead_first_name"><?php esc_html_e( 'Votre Prénom', 'presellia-resource-hub' ); ?></label>
                <input type="text" id="prh_lead_first_name" required placeholder="Ex: Rodrigue">
            </div>

            <div class="prh-form-group">
                <label for="prh_lead_email"><?php esc_html_e( 'Votre Email Professionnel', 'presellia-resource-hub' ); ?></label>
                <input type="email" id="prh_lead_email" required placeholder="Ex: contact@tedisun.com">
            </div>

            <div class="prh-form-group">
                <label for="prh_lead_phone"><?php esc_html_e( 'Numéro WhatsApp / Téléphone (Optionnel)', 'presellia-resource-hub' ); ?></label>
                <input type="tel" id="prh_lead_phone" placeholder="Ex: +225 07 00 00 00 00">
            </div>

            <button type="submit" class="prh-form-submit"><?php esc_html_e( 'Débloquer mon accès immédiat', 'presellia-resource-hub' ); ?></button>
        </form>
    </div>
</div>

<?php
get_footer();
