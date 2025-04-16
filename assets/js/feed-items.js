/**
 * Feed Items Page JavaScript
 * 
 * Handles the manual feed fetch functionality and updates the UI accordingly.
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        const $fetchButton = $('#manual-fetch-button');
        const $fetchStatus = $('#fetch-status');
        const $lastFetchTime = $('#last-fetch-time');
        const $nextFetchTime = $('#next-fetch-time');

        /**
         * Handle manual feed fetch button click
         */
        $fetchButton.on('click', function() {
            // Disable button and show loading status
            $fetchButton.prop('disabled', true);
            $fetchStatus.html(athenaFeedItems.fetchingText)
                .removeClass('success error')
                .addClass('loading');

            // Make AJAX request to fetch feeds
            $.ajax({
                url: athenaFeedItems.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'athena_manual_feed_fetch',
                    nonce: athenaFeedItems.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update status with success message
                        $fetchStatus.html(response.data.message)
                            .removeClass('loading error')
                            .addClass('success');
                        
                        // Update last fetch time
                        if ($lastFetchTime.length) {
                            $lastFetchTime.html(
                                response.data.lastFetchTime + 
                                '<br><small>' + response.data.lastFetchTimeFormatted + '</small>'
                            );
                        }
                        
                        // Update next fetch time
                        if ($nextFetchTime.length) {
                            $nextFetchTime.html(
                                response.data.nextFetchTime + 
                                (response.data.nextFetchTimeFormatted ? '<br><small>' + response.data.nextFetchTimeFormatted + '</small>' : '')
                            );
                        }
                    } else {
                        // Show error message
                        $fetchStatus.html(response.data.message || athenaFeedItems.fetchErrorText)
                            .removeClass('loading success')
                            .addClass('error');
                    }
                },
                error: function() {
                    // Show generic error message
                    $fetchStatus.html(athenaFeedItems.fetchErrorText)
                        .removeClass('loading success')
                        .addClass('error');
                },
                complete: function() {
                    // Re-enable button
                    $fetchButton.prop('disabled', false);
                    
                    // Clear status message after 5 seconds
                    setTimeout(function() {
                        if ($fetchStatus.hasClass('success')) {
                            $fetchStatus.fadeOut(500, function() {
                                $(this).html('').show().css('display', '');
                            });
                        }
                    }, 5000);
                }
            });
        });
    });
})(jQuery);
