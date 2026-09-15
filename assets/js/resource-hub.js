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
                    resource_id: resId
                },
                success: function(response) {
                    if (response.success) {
                        var downloadUrl = response.data.download_url;
                        $feedback.html('<div style="color: #10b981; font-weight: 700; margin-bottom: 16px;">✓ ' + response.data.message + '</div>').show();
                        $submitBtn.text('✓ Accès débloqué !');

                        if (downloadUrl) {
                            setTimeout(function() {
                                window.location.href = downloadUrl;
                            }, 800);
                        }

                        setTimeout(function() {
                            $backdrop.fadeOut(300);
                            $submitBtn.prop('disabled', false).text('Débloquer mon accès immédiat');
                        }, 3000);
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
