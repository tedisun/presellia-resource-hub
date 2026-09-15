/**
 * Presellia Resource Hub — Admin Metabox interactions
 */
jQuery(document).ready(function($) {
    function toggleCrmTagField() {
        var accessType = $('#prh_access_type').val();
        if (accessType === 'gated') {
            $('#prh_crm_tag_field').show();
        } else {
            $('#prh_crm_tag_field').hide();
        }
    }

    $('#prh_access_type').on('change', toggleCrmTagField);
    toggleCrmTagField();
});
