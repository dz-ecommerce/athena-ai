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
        const $processingContainer = $('#feed-processing-container');
        const $progressBar = $('#feed-progress-bar');
        const $feedsProcessed = $('#feeds-processed');
        const $feedsTotal = $('#feeds-total');
        const $feedProcessingList = $('#feed-processing-list');

        let feedsData = [];
        let currentFeedIndex = 0;
        let processedCount = 0;
        let errorCount = 0;
        let isProcessing = false;

        /**
         * Handle manual feed fetch button click
         */
        $fetchButton.on('click', function() {
            if (isProcessing) return;
            
            // Reset state
            feedsData = [];
            currentFeedIndex = 0;
            processedCount = 0;
            errorCount = 0;
            isProcessing = true;
            
            // Disable button and show loading status
            $fetchButton.prop('disabled', true);
            $fetchStatus.html(athenaFeedItems.fetchingText)
                .removeClass('success error')
                .addClass('loading');

            // Clear the processing list
            $feedProcessingList.empty();
            $progressBar.css('width', '0%');
            $feedsProcessed.text('0');
            $feedsTotal.text('0');
            
            // Start the feed processing
            startFeedProcessing();
        });

        /**
         * Start the feed processing workflow
         */
        function startFeedProcessing() {
            // Make AJAX request to get all feeds
            $.ajax({
                url: athenaFeedItems.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'athena_manual_feed_fetch',
                    nonce: athenaFeedItems.nonce,
                    fetch_action: 'start'
                },
                success: function(response) {
                    if (response.success && response.data.feeds && response.data.feeds.length > 0) {
                        feedsData = response.data.feeds;
                        $feedsTotal.text(feedsData.length);
                        
                        // Show the processing container
                        $processingContainer.show();
                        
                        // Populate the table with feeds
                        populateFeedsTable();
                        
                        // Process the first feed
                        processNextFeed();
                    } else {
                        // No feeds to process
                        $fetchStatus.html(athenaFeedItems.noFeedsText || 'No feeds to process')
                            .removeClass('loading success')
                            .addClass('error');
                        $fetchButton.prop('disabled', false);
                        isProcessing = false;
                    }
                },
                error: function() {
                    // Show generic error message
                    $fetchStatus.html(athenaFeedItems.fetchErrorText)
                        .removeClass('loading success')
                        .addClass('error');
                    $fetchButton.prop('disabled', false);
                    isProcessing = false;
                }
            });
        }

        /**
         * Populate the feeds table with initial data
         */
        function populateFeedsTable() {
            feedsData.forEach(function(feed, index) {
                const rowHtml = `
                    <tr id="feed-row-${feed.id}" data-feed-id="${feed.id}">
                        <td>${escapeHtml(feed.url)}</td>
                        <td>${feed.last_checked}</td>
                        <td id="feed-items-${feed.id}">${feed.item_count}</td>
                        <td class="status-cell">
                            <span id="feed-status-${feed.id}" class="feed-status-pending">
                                ${athenaFeedItems.pendingText || 'Pending'}
                            </span>
                        </td>
                    </tr>
                `;
                $feedProcessingList.append(rowHtml);
            });
        }

        /**
         * Process the next feed in the queue
         */
        function processNextFeed() {
            if (currentFeedIndex >= feedsData.length) {
                // All feeds processed, complete the process
                completeFeedProcessing();
                return;
            }
            
            const feed = feedsData[currentFeedIndex];
            const $statusCell = $(`#feed-status-${feed.id}`);
            const $itemsCell = $(`#feed-items-${feed.id}`);
            const $feedRow = $(`#feed-row-${feed.id}`);
            
            // Update status to processing
            $statusCell.removeClass('feed-status-pending feed-status-success feed-status-error')
                .addClass('feed-status-processing')
                .text(athenaFeedItems.processingText || 'Processing...');
            
            // Highlight the current row
            $feedRow.addClass('processing-row');
            
            // Process this feed
            $.ajax({
                url: athenaFeedItems.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'athena_manual_feed_fetch',
                    nonce: athenaFeedItems.nonce,
                    fetch_action: 'process',
                    feed_id: feed.id
                },
                success: function(response) {
                    if (response.success) {
                        // Update the feed status
                        if (response.data.processed) {
                            $statusCell.removeClass('feed-status-processing')
                                .addClass('feed-status-success')
                                .text(response.data.message);
                            
                            // Update item count
                            const newItemCount = parseInt(feed.item_count) + parseInt(response.data.items);
                            $itemsCell.text(newItemCount);
                            
                            processedCount++;
                        } else {
                            $statusCell.removeClass('feed-status-processing')
                                .addClass('feed-status-error')
                                .text(response.data.error || 'Error');
                            errorCount++;
                        }
                    } else {
                        // Error processing feed
                        $statusCell.removeClass('feed-status-processing')
                            .addClass('feed-status-error')
                            .text(response.data?.message || 'Error');
                        errorCount++;
                    }
                },
                error: function() {
                    // Error processing feed
                    $statusCell.removeClass('feed-status-processing')
                        .addClass('feed-status-error')
                        .text('Connection error');
                    errorCount++;
                },
                complete: function() {
                    // Remove highlight from current row
                    $feedRow.removeClass('processing-row');
                    
                    // Update progress
                    currentFeedIndex++;
                    updateProgress();
                    
                    // Process the next feed after a short delay
                    setTimeout(processNextFeed, 300);
                }
            });
        }

        /**
         * Update the progress bar and stats
         */
        function updateProgress() {
            const progress = (currentFeedIndex / feedsData.length) * 100;
            $progressBar.css('width', progress + '%');
            $feedsProcessed.text(currentFeedIndex);
        }

        /**
         * Complete the feed processing workflow
         */
        function completeFeedProcessing() {
            // Make AJAX request to complete the process
            $.ajax({
                url: athenaFeedItems.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'athena_manual_feed_fetch',
                    nonce: athenaFeedItems.nonce,
                    fetch_action: 'complete'
                },
                success: function(response) {
                    if (response.success) {
                        // Update status with success message
                        const message = sprintf(
                            athenaFeedItems.completedText || 'Processed %d feeds with %d errors.',
                            processedCount,
                            errorCount
                        );
                        
                        $fetchStatus.html(message)
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
                    isProcessing = false;
                    
                    // Clear status message after 10 seconds
                    setTimeout(function() {
                        if ($fetchStatus.hasClass('success')) {
                            $fetchStatus.fadeOut(500, function() {
                                $(this).html('').show().css('display', '');
                            });
                        }
                    }, 10000);
                }
            });
        }
        
        /**
         * Helper function to escape HTML
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        /**
         * Helper function for string formatting
         */
        function sprintf(format) {
            const args = Array.prototype.slice.call(arguments, 1);
            return format.replace(/%d/g, function() {
                return args.shift();
            });
        }
    });
})(jQuery);
