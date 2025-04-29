<?php
/**
 * Template für die Feed-Items-Seite
 */

// Fehlerbehandlung für diese Seite - nur Fehler protokollieren, nicht anzeigen
$previous_error_reporting = error_reporting();
error_reporting(E_ERROR);

// Wichtig: Stelle sicher, dass die Import-Date-Eigenschaft korrekt behandelt wird
if (!empty($items)) {
    foreach ($items as $key => $item) {
        // Stelle sicher, dass alle erforderlichen Eigenschaften existieren
        if (!isset($item->import_date)) {
            $items[$key]->import_date = '';
        }
    }
}
?>
<div class="wrap athena-ai-admin bg-gray-50 -mx-4 px-4 pb-10 min-h-screen">
    <!-- Header -->
    <div class="flex justify-between items-center bg-white shadow-sm px-6 py-5 mb-6 rounded-lg border border-gray-100">
        <h1 class="text-2xl font-bold text-gray-800 m-0 flex items-center">
            <span class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-3">
                <i class="fa-solid fa-rss"></i>
            </span>
            <?php esc_html_e('Feed Items', 'athena-ai'); ?>
        </h1>
        <div class="flex space-x-2">
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="inline-block">
                <input type="hidden" name="action" value="athena_fetch_feeds">
                <?php wp_nonce_field('athena_fetch_feeds_nonce'); ?>
                <button type="submit" class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg transition-all hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                    <i class="fa-solid fa-sync-alt mr-2"></i>
                    <?php esc_html_e('Fetch Feeds Now', 'athena-ai'); ?>
                </button>
            </form>
            
            <?php if (current_user_can('manage_options')): ?>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="inline-block">
                <input type="hidden" name="action" value="athena_debug_cron_health">
                <?php wp_nonce_field('athena_debug_cron_health_nonce'); ?>
                <button type="submit" class="flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg transition-all hover:bg-gray-300 focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50">
                    <i class="fa-solid fa-wrench mr-2"></i>
                    <?php esc_html_e('Debug Cron Health', 'athena-ai'); ?>
                </button>
            </form>
            <?php endif; ?>
            
            <a href="<?php echo admin_url('post-new.php?post_type=athena-feed'); ?>" class="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg transition-all hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
                <i class="fa-solid fa-plus mr-2"></i>
                <?php esc_html_e('Add New Feed', 'athena-ai'); ?>
            </a>
        </div>
    </div>
    
    <?php if (isset($_GET['cron_debugged'])): ?>
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-r-lg shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fa-solid fa-info-circle text-blue-500"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700 font-medium">
                    <?php esc_html_e('Cron health debug completed.', 'athena-ai'); ?>
                </p>
                <p class="text-sm text-blue-700">
                    <?php echo sprintf(esc_html__('Next scheduled: %s', 'athena-ai'), esc_html($_GET['next_scheduled'])); ?><br>
                    <?php echo sprintf(esc_html__('Current interval: %s', 'athena-ai'), esc_html($_GET['current_interval'])); ?><br>
                    <?php echo sprintf(esc_html__('Expected interval: %s', 'athena-ai'), esc_html($_GET['expected_interval'])); ?>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($show_success_message): ?>
    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-r-lg shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fa-solid fa-check-circle text-green-500"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700 font-medium">
                    <?php 
                    $success_message = $fetch_result 
                        ? sprintf(_n('Successfully fetched %d feed with %d new items.', 'Successfully fetched %d feeds with %d new items.', $fetch_result['success'], 'athena-ai'), $fetch_result['success'], $fetch_result['new_items']) 
                        : esc_html__('Feeds successfully fetched.', 'athena-ai');
                    echo $success_message;
                    ?>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($show_error_message): ?>
    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fa-solid fa-exclamation-circle text-red-500"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700 font-medium">
                    <?php printf(_n('Error fetching %d feed.', 'Error fetching %d feeds.', $fetch_result['error'], 'athena-ai'), $fetch_result['error']); ?>
                    <?php esc_html_e('Check the console for detailed error messages.', 'athena-ai'); ?>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Statistiken Karte 1 -->
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow duration-300">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-100 rounded-full p-3 text-blue-600">
                    <i class="fa-solid fa-rss fa-lg"></i>
                </div>
                <div class="ml-4">
                    <h2 class="text-lg font-semibold text-gray-900"><?php esc_html_e('Feed Sources', 'athena-ai'); ?></h2>
                    <p class="text-3xl font-bold text-gray-800"><?php echo esc_html($feed_count); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Statistiken Karte 2 -->
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow duration-300">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 rounded-full p-3 text-green-600">
                    <i class="fa-solid fa-newspaper fa-lg"></i>
                </div>
                <div class="ml-4">
                    <h2 class="text-lg font-semibold text-gray-900"><?php esc_html_e('Total Items', 'athena-ai'); ?></h2>
                    <p class="text-3xl font-bold text-gray-800"><?php echo esc_html($total_items); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Statistiken Karte 3 -->
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow duration-300">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-100 rounded-full p-3 text-yellow-600">
                    <i class="fa-solid fa-clock fa-lg"></i>
                </div>
                <div class="ml-4">
                    <h2 class="text-lg font-semibold text-gray-900"><?php esc_html_e('Last Fetch', 'athena-ai'); ?></h2>
                    <p class="text-xl font-bold text-gray-800"><?php echo esc_html($last_fetch_text); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter und Suche -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6 border border-gray-100">
        <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php esc_html_e('Filter Feeds', 'athena-ai'); ?></h2>
        <form method="get" action="<?php echo admin_url('admin.php'); ?>" class="flex flex-wrap gap-4">
            <input type="hidden" name="page" value="athena-feed-items" />
            
            <div class="w-full md:w-auto flex-grow">
                <label for="feed_id" class="block text-sm font-medium text-gray-700 mb-1"><?php esc_html_e('Filter by Feed', 'athena-ai'); ?></label>
                <select name="feed_id" id="feed_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value=""><?php esc_html_e('All Feeds', 'athena-ai'); ?></option>
                    <?php foreach ($feeds as $feed): ?>
                        <option value="<?php echo esc_attr($feed->ID); ?>" <?php selected($feed_filter, $feed->ID); ?>>
                            <?php echo esc_html($feed->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="w-full md:w-auto flex-grow">
                <label for="date_filter" class="block text-sm font-medium text-gray-700 mb-1"><?php esc_html_e('Filter by Date', 'athena-ai'); ?></label>
                <select name="date_filter" id="date_filter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value=""><?php esc_html_e('All Time', 'athena-ai'); ?></option>
                    <option value="today" <?php selected($date_filter, 'today'); ?>><?php esc_html_e('Today', 'athena-ai'); ?></option>
                    <option value="yesterday" <?php selected($date_filter, 'yesterday'); ?>><?php esc_html_e('Yesterday', 'athena-ai'); ?></option>
                    <option value="this_week" <?php selected($date_filter, 'this_week'); ?>><?php esc_html_e('This Week', 'athena-ai'); ?></option>
                    <option value="last_week" <?php selected($date_filter, 'last_week'); ?>><?php esc_html_e('Last Week', 'athena-ai'); ?></option>
                    <option value="this_month" <?php selected($date_filter, 'this_month'); ?>><?php esc_html_e('This Month', 'athena-ai'); ?></option>
                    <option value="last_month" <?php selected($date_filter, 'last_month'); ?>><?php esc_html_e('Last Month', 'athena-ai'); ?></option>
                </select>
            </div>
            
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg transition-all hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                    <i class="fa-solid fa-filter mr-2"></i>
                    <?php esc_html_e('Apply Filters', 'athena-ai'); ?>
                </button>
                
                <?php if ($feed_filter || $date_filter): ?>
                <a href="<?php echo admin_url('admin.php?page=athena-feed-items'); ?>" class="flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg transition-all hover:bg-gray-300 focus:ring-2 focus:ring-gray-400 focus:ring-opacity-50">
                    <i class="fa-solid fa-times mr-2"></i>
                    <?php esc_html_e('Clear Filters', 'athena-ai'); ?>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if (empty($items)): ?>
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 flex items-center justify-center p-12">
        <div class="text-center">
            <div class="bg-blue-100 text-blue-600 inline-flex p-3 rounded-full mb-4 mx-auto">
                <i class="fa-solid fa-info-circle fa-2x"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2"><?php esc_html_e('No Feed Items Found', 'athena-ai'); ?></h3>
            <p class="text-gray-600"><?php esc_html_e('Try clearing your filters or fetch new feeds.', 'athena-ai'); ?></p>
        </div>
    </div>
    <?php else: ?>
    
    <!-- Feed-Items-Tabelle -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-gray-50">
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php esc_html_e('Title', 'athena-ai'); ?></th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php esc_html_e('Feed Source', 'athena-ai'); ?></th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php esc_html_e('Publication Date', 'athena-ai'); ?></th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php esc_html_e('Imported Date', 'athena-ai'); ?></th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php esc_html_e('Actions', 'athena-ai'); ?></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($items as $item): 
                        // Ensure raw_content is a string before decoding
                        if (!isset($item->raw_content) || !is_string($item->raw_content)) {
                            continue; // Skip this item if raw_content is missing or not a string
                        }
                        
                        // Safely decode JSON with error handling
                        $raw_content = json_decode($item->raw_content);
                        if (json_last_error() !== JSON_ERROR_NONE || !is_object($raw_content)) {
                            // Skip this item if JSON is invalid
                            continue;
                        }
                        
                        // Safely extract and convert properties to strings
                        $title = '';
                        if (isset($raw_content->title)) {
                            $title = is_scalar($raw_content->title) ? (string)$raw_content->title : '';
                        }
                        
                        $link = '';
                        if (isset($raw_content->link)) {
                            $link = is_scalar($raw_content->link) ? (string)$raw_content->link : '';
                        }
                        
                        $description = '';
                        if (isset($raw_content->description)) {
                            $description = is_scalar($raw_content->description) ? (string)$raw_content->description : '';
                        }
                        
                        // Handle different feed formats
                        if (empty($link) && isset($raw_content->guid) && is_scalar($raw_content->guid)) {
                            $link = (string)$raw_content->guid;
                        }
                        
                        if (empty($title) && !empty($description)) {
                            $title = wp_trim_words($description, 10, '...');
                        } elseif (empty($title)) {
                            $title = __('(No Title)', 'athena-ai');
                        }
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php if ($link): ?>
                                    <a href="<?php echo esc_url($link); ?>" target="_blank" class="hover:text-blue-600 transition-colors duration-150 flex items-center">
                                        <?php echo esc_html($title); ?>
                                        <i class="fa-solid fa-external-link-alt ml-1 text-xs text-gray-400"></i>
                                    </a>
                                <?php else: ?>
                                    <?php echo esc_html($title); ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                <i class="fa-solid fa-rss mr-1"></i>
                                <?php echo esc_html($item->feed_title); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php
                            $pub_date = strtotime($item->pub_date);
                            echo $pub_date ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $pub_date) : '–';
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php
                            // Überprüfe, ob import_date existiert
                            $import_date = isset($item->import_date) && !empty($item->import_date) ? strtotime($item->import_date) : false;
                            echo $import_date ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $import_date) : '–';
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-3">
                                <button onclick="showItemContent(<?php echo esc_attr($item->id); ?>); return false;" class="text-blue-600 hover:text-blue-900 transition-colors duration-150" title="<?php esc_attr_e('View Content', 'athena-ai'); ?>">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <?php if (current_user_can('manage_options')): ?>
                                <button onclick="if(confirm('<?php esc_attr_e('Are you sure you want to delete this item?', 'athena-ai'); ?>')) deleteItem(<?php echo esc_attr($item->id); ?>); return false;" class="text-red-600 hover:text-red-900 transition-colors duration-150" title="<?php esc_attr_e('Delete Item', 'athena-ai'); ?>">
                                    <i class="fa-solid fa-trash-alt"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_items > $items_per_page): ?>
    <div class="flex justify-between items-center mt-6">
        <div class="text-sm text-gray-700">
            <?php 
            $from = min($offset + 1, $total_items);
            $to = min($offset + $items_per_page, $total_items);
            printf(esc_html__('Showing %1$d to %2$d of %3$d entries', 'athena-ai'), $from, $to, $total_items); 
            ?>
        </div>
        
        <div class="flex rounded-md shadow-sm">
            <?php
            $total_pages = ceil($total_items / $items_per_page);
            $base_url = admin_url('admin.php?page=athena-feed-items');
            if ($feed_filter) {
                $base_url .= '&feed_id=' . $feed_filter;
            }
            if ($date_filter) {
                $base_url .= '&date_filter=' . $date_filter;
            }
            
            // Previous button
            if ($current_page > 1): 
                $prev_url = add_query_arg('paged', $current_page - 1, $base_url);
            ?>
                <a href="<?php echo esc_url($prev_url); ?>" class="relative inline-flex items-center px-4 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors duration-150">
                    <i class="fa-solid fa-chevron-left mr-2"></i>
                    <?php esc_html_e('Previous', 'athena-ai'); ?>
                </a>
            <?php else: ?>
                <span class="relative inline-flex items-center px-4 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-500 cursor-not-allowed">
                    <i class="fa-solid fa-chevron-left mr-2"></i>
                    <?php esc_html_e('Previous', 'athena-ai'); ?>
                </span>
            <?php endif; ?>
            
            <!-- Page numbers -->
            <div class="hidden md:flex">
                <?php
                $start_page = max(1, min($current_page - 2, $total_pages - 4));
                $end_page = min($total_pages, max($current_page + 2, 5));
                
                for ($i = $start_page; $i <= $end_page; $i++):
                    $page_url = add_query_arg('paged', $i, $base_url);
                    if ($i == $current_page): 
                ?>
                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600 border-blue-300">
                        <?php echo $i; ?>
                    </span>
                <?php else: ?>
                    <a href="<?php echo esc_url($page_url); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors duration-150">
                        <?php echo $i; ?>
                    </a>
                <?php endif; endfor; ?>
            </div>
            
            <!-- Next button -->
            <?php if ($current_page < $total_pages): 
                $next_url = add_query_arg('paged', $current_page + 1, $base_url);
            ?>
                <a href="<?php echo esc_url($next_url); ?>" class="relative inline-flex items-center px-4 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors duration-150">
                    <?php esc_html_e('Next', 'athena-ai'); ?>
                    <i class="fa-solid fa-chevron-right ml-2"></i>
                </a>
            <?php else: ?>
                <span class="relative inline-flex items-center px-4 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-500 cursor-not-allowed">
                    <?php esc_html_e('Next', 'athena-ai'); ?>
                    <i class="fa-solid fa-chevron-right ml-2"></i>
                </span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    
    <!-- Modal für Feed-Item-Inhalt -->
    <div id="item-content-modal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" id="modal-backdrop"></div>
            
            <!-- Modal container -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <div class="flex items-start justify-between border-b border-gray-200 pb-3 mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            <?php esc_html_e('Feed Item Content', 'athena-ai'); ?>
                        </h3>
                        <button type="button" id="close-modal-x" class="bg-white rounded-md text-gray-400 hover:text-gray-500">
                            <span class="sr-only"><?php esc_html_e('Close', 'athena-ai'); ?></span>
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                    <div class="mt-4 max-h-96 overflow-y-auto px-1">
                        <div id="item-content-container" class="prose max-w-none"></div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-end">
                    <button type="button" id="close-modal-button" class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-150">
                        <?php esc_html_e('Close', 'athena-ai'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Modal handling
    $('#close-modal-button, #close-modal-x, #modal-backdrop').on('click', function() {
        $('#item-content-modal').addClass('hidden');
    });
    
    // Close modal on ESC key
    $(document).keydown(function(e) {
        if (e.keyCode == 27 && !$('#item-content-modal').hasClass('hidden')) {
            $('#item-content-modal').addClass('hidden');
        }
    });
    
    // Animate modal opening
    function openModal() {
        $('#item-content-modal').removeClass('hidden');
        setTimeout(function() {
            $('#item-content-modal .transform').addClass('scale-100').removeClass('scale-95');
        }, 10);
    }
});

// Show feed item content in modal
function showItemContent(itemId) {
    jQuery(document).ready(function($) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'athena_get_feed_item_content',
                item_id: itemId,
                nonce: '<?php echo wp_create_nonce('athena_get_feed_item_content'); ?>'
            },
            beforeSend: function() {
                $('#item-content-container').html('<div class="flex justify-center py-8"><div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div><p class="mt-4 text-gray-600"><?php esc_html_e('Loading content...', 'athena-ai'); ?></p></div>');
                $('#item-content-modal').removeClass('hidden');
            },
            success: function(response) {
                if (response.success) {
                    $('#item-content-container').html(response.data.content);
                } else {
                    $('#item-content-container').html('<div class="text-center py-8 text-red-500"><i class="fa-solid fa-exclamation-circle fa-2x"></i><p class="mt-2"><?php esc_html_e('Error loading content', 'athena-ai'); ?></p></div>');
                }
            },
            error: function() {
                $('#item-content-container').html('<div class="text-center py-8 text-red-500"><i class="fa-solid fa-exclamation-circle fa-2x"></i><p class="mt-2"><?php esc_html_e('Error loading content', 'athena-ai'); ?></p></div>');
            }
        });
    });
}

// Delete feed item
function deleteItem(itemId) {
    jQuery(document).ready(function($) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'athena_delete_feed_item',
                item_id: itemId,
                nonce: '<?php echo wp_create_nonce('athena_delete_feed_item'); ?>'
            },
            beforeSend: function() {
                // Show delete animation/indicator
            },
            success: function(response) {
                if (response.success) {
                    // Animate row removal
                    location.reload();
                } else {
                    alert('<?php esc_html_e('Error deleting item. Please try again.', 'athena-ai'); ?>');
                }
            },
            error: function() {
                alert('<?php esc_html_e('Error deleting item. Please try again.', 'athena-ai'); ?>');
            }
        });
    });
}
</script>
