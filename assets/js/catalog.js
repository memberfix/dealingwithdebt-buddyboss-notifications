/**
 * Tango Catalog JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // View details button click
        $('.tango-view-details').on('click', function(e) {
            e.preventDefault();

            let $item = $(this).closest('.tango-catalog-item');
            let $data = $item.find('.tango-item-data');

            // Get brand information
            let brandName = $item.find('.tango-catalog-title').text();
            let imageUrl = $item.find('.tango-catalog-image img').attr('src');
            let description = $data.find('.tango-description').html();
            let disclaimer = $data.find('.tango-disclaimer').html();
            let terms = $data.find('.tango-terms').html();

            // Populate modal
            $('#tango-modal-title').text(brandName);

            if (imageUrl) {
                $('#tango-modal-image').html('<img src="' + imageUrl + '" alt="' + brandName + '">');
            } else {
                $('#tango-modal-image').html('');
            }

            $('#tango-modal-description').html(description || '');

            if (disclaimer) {
                $('#tango-modal-disclaimer').html('<strong>Disclaimer:</strong><br>' + disclaimer);
            } else {
                $('#tango-modal-disclaimer').html('');
            }

            if (terms) {
                $('#tango-modal-terms').html('<strong>Terms & Conditions:</strong><br>' + terms);
            } else {
                $('#tango-modal-terms').html('');
            }

            // Show modal
            $('#tango-modal').fadeIn(300);
        });

        // Close modal on X click
        $('.tango-modal-close').on('click', function() {
            $('#tango-modal').fadeOut(300);
        });

        // Close modal on outside click
        $(window).on('click', function(e) {
            if ($(e.target).is('#tango-modal')) {
                $('#tango-modal').fadeOut(300);
            }
        });

        // Close modal on ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#tango-modal').is(':visible')) {
                $('#tango-modal').fadeOut(300);
            }
        });
    });

})(jQuery);
