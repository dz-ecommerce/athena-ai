/**
 * Athena AI Frontend JavaScript
 */
(function($) {
    'use strict';

    // Document ready
    $(function() {
        // Load more feeds
        $('.athena-feeds-container').on('click', '.athena-load-more-button', function() {
            const $button = $(this);
            const $container = $button.closest('.athena-feeds-container');
            
            // Get data attributes
            const category = $button.data('category');
            const limit = $button.data('limit');
            const offset = $button.data('offset');
            
            // Add loading indicator
            if (!$button.find('.athena-loading').length) {
                $button.append('<span class="athena-loading"></span>');
            }
            
            // Disable button during load
            $button.prop('disabled', true);
            
            // AJAX request
            $.ajax({
                url: athenaAiFrontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'athena_load_more_feeds',
                    nonce: athenaAiFrontend.nonce,
                    category: category,
                    limit: limit,
                    offset: offset
                },
                success: function(response) {
                    if (response.success) {
                        // Replace container content with new HTML
                        $container.replaceWith(response.data.html);
                    } else {
                        console.error('Error loading feeds:', response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    
                    // Remove loading indicator and re-enable button
                    $button.find('.athena-loading').remove();
                    $button.prop('disabled', false);
                }
            });
        });
    });
})(jQuery);
