/**
 * Presellia Resource Hub — Frontend Interactions
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // ---------------------------------------------------------------------
        // 1. Filtrage en direct & Recherche instantanée (Archive Hub)
        // ---------------------------------------------------------------------
        var $cards = $('.prh-card');
        var $pills = $('.prh-pill');
        var $search = $('#prh-search-input');
        var activeCategory = 'all';

        function filterCards() {
            var query = ($search.val() || '').toLowerCase().trim();

            $cards.each(function() {
                var $card = $(this);
                var cardCats = ($card.data('categories') || '').toString().toLowerCase();
                var cardText = $card.text().toLowerCase();

                var matchesCat = (activeCategory === 'all' || cardCats.indexOf(activeCategory.toLowerCase()) !== -1);
                var matchesQuery = (query === '' || cardText.indexOf(query) !== -1);

                if (matchesCat && matchesQuery) {
                    $card.fadeIn(150);
                } else {
                    $card.fadeOut(150);
                }
            });
        }

        $pills.on('click', function(e) {
            e.preventDefault();
            $pills.removeClass('active');
            $(this).addClass('active');
            activeCategory = $(this).data('slug') || 'all';
            filterCards();
        });

        $search.on('input', function() {
            filterCards();
        });

        // ---------------------------------------------------------------------
        // 2. Gestion de la modale de capture FluentCRM (Gated Content)
        // ---------------------------------------------------------------------
        var $backdrop = $('#prh-lead-modal');
        var $form = $('#prh-lead-form');
        var $submitBtn = $form.find('.prh-form-submit');
        var $feedback = $('#prh-modal-feedback');
        var $success = $('#prh-modal-success');
        var STORE_KEY = 'prh_unlocked';

        // Mémoire locale (par navigateur) des ressources déjà débloquées : le visiteur qui revient
        // n'a pas à remplir le formulaire une deuxième fois. Peut être vide ou bloquée : on ignore.
        function readUnlocked() {
            try { return JSON.parse(window.localStorage.getItem(STORE_KEY) || '{}') || {}; } catch (err) { return {}; }
        }
        function rememberUnlocked(resId, url, label) {
            try {
                var all = readUnlocked();
                all[resId] = { url: url, label: label };
                window.localStorage.setItem(STORE_KEY, JSON.stringify(all));
            } catch (err) { /* stockage indisponible */ }
        }
        function isSafeUrl(url) {
            return typeof url === 'string' && /^https?:\/\//i.test(url);
        }

        // Ressource déjà débloquée sur ce navigateur : le bouton ouvre directement l'accès.
        $('.prh-open-gated-modal').each(function() {
            var saved = readUnlocked()[$(this).data('resource-id')];
            if (saved && isSafeUrl(saved.url)) {
                var $link = $('<a class="prh-action-btn" target="_blank" rel="noopener"></a>')
                    .attr('href', saved.url)
                    .text(saved.label || 'Ouvrir la ressource');
                $(this).replaceWith($link);
            }
        });

        function showSuccess(data) {
            var url = data.download_url;
            $feedback.hide().empty();
            $form.hide();
            $('#prh-success-message').text('✓ ' + (data.message || 'C\'est débloqué.'));
            if (isSafeUrl(url)) {
                $('#prh-success-link').attr('href', url).text((data.cta_label || 'Ouvrir la ressource') + ' →').show();
            } else {
                $('#prh-success-link').hide();
            }
            $success.show();
        }

        $(document).on('click', '.prh-open-gated-modal', function(e) {
            e.preventDefault();
            var resId = $(this).data('resource-id');
            var resTitle = $(this).data('resource-title');

            $('#prh-modal-resource-id').val(resId);
            if (resTitle) {
                $('#prh-modal-title-target').text(resTitle);
            }

            $feedback.hide().empty();
            $backdrop.css('display', 'flex').hide().fadeIn(200);
        });

        $('.prh-modal-close, .prh-modal-backdrop').on('click', function(e) {
            if ($(e.target).hasClass('prh-modal-backdrop') || $(e.target).hasClass('prh-modal-close')) {
                $backdrop.fadeOut(150);
            }
        });

        $form.on('submit', function(e) {
            e.preventDefault();

            var firstName = $('#prh_lead_first_name').val().trim();
            var email     = $('#prh_lead_email').val().trim();
            var phone     = $('#prh_lead_phone').val().trim();
            var resId     = $('#prh-modal-resource-id').val();
            var consent   = $('#prh_lead_consent').is(':checked') ? '1' : '0';
            var website   = $('#prh_lead_website').val() || '';

            if (!email) {
                $feedback.html('<div style="color: #ef4444; font-weight: 600; margin-bottom: 12px;">Veuillez entrer une adresse email.</div>').show();
                return;
            }

            $submitBtn.prop('disabled', true).text('Déblocage en cours...');
            $feedback.empty().hide();

            $.ajax({
                url: prh_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'prh_capture_lead',
                    nonce: prh_ajax.nonce,
                    first_name: firstName,
                    email: email,
                    phone: phone,
                    resource_id: resId,
                    consent: consent,
                    prh_lead_website: website
                },
                success: function(response) {
                    if (response.success) {
                        // Pas de redirection automatique : le visiteur ouvre l'accès dans un nouvel onglet
                        // et garde cette page (et l'offre recommandée) sous la main.
                        showSuccess(response.data || {});
                        if (response.data && isSafeUrl(response.data.download_url)) {
                            rememberUnlocked(resId, response.data.download_url, response.data.cta_label);
                        }
                        $submitBtn.prop('disabled', false).text('Débloquer mon accès immédiat');
                    } else {
                        $feedback.html('<div style="color: #ef4444; font-weight: 600; margin-bottom: 12px;">' + (response.data.message || 'Une erreur est survenue.') + '</div>').show();
                        $submitBtn.prop('disabled', false).text('Débloquer mon accès immédiat');
                    }
                },
                error: function() {
                    $feedback.html('<div style="color: #ef4444; font-weight: 600; margin-bottom: 12px;">Erreur de connexion au serveur.</div>').show();
                    $submitBtn.prop('disabled', false).text('Débloquer mon accès immédiat');
                }
            });
        });
    });
})(jQuery);
