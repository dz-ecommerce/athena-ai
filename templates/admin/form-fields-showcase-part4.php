<?php
// Part 4 of the form fields showcase - Advanced Select Fields

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Advanced Select Fields -->
<div class="mb-8">
    <h4 class="text-md font-medium text-gray-700 mb-4 border-b pb-2">
        <?php esc_html_e('Advanced Select & Dropdown Fields', 'athena-ai'); ?>
    </h4>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Select with Search (Custom) -->
        <div>
            <label for="searchable_select" class="block text-sm font-medium text-gray-700 mb-1">
                <?php esc_html_e('Searchable Select', 'athena-ai'); ?>
            </label>
            <div class="mt-1 relative">
                <button type="button" 
                    class="bg-white relative w-full border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    onclick="toggleSearchableDropdown('searchable_select_dropdown')">
                    <span class="block truncate">
                        <?php esc_html_e('Select with search...', 'athena-ai'); ?>
                    </span>
                    <span class="absolute inset-y-0 right-0 flex items-center pr-2">
                        <i class="fa-solid fa-chevron-down text-gray-400"></i>
                    </span>
                </button>
                
                <div id="searchable_select_dropdown" class="hidden absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                    <div class="sticky top-0 z-10 bg-white">
                        <input type="text"
                            class="w-full border-0 border-b border-gray-300 focus:ring-0 focus:border-blue-500 rounded-t-md px-4 py-2"
                            placeholder="<?php esc_attr_e('Search options...', 'athena-ai'); ?>"
                            oninput="filterSearchableOptions(this, 'searchable_select_options')">
                    </div>
                    <ul id="searchable_select_options" class="py-2">
                        <li class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-blue-100 hover:text-blue-900">
                            <?php esc_html_e('Option 1', 'athena-ai'); ?>
                        </li>
                        <li class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-blue-100 hover:text-blue-900">
                            <?php esc_html_e('Option 2', 'athena-ai'); ?>
                        </li>
                        <li class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-blue-100 hover:text-blue-900">
                            <?php esc_html_e('Option 3', 'athena-ai'); ?>
                        </li>
                        <li class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-blue-100 hover:text-blue-900">
                            <?php esc_html_e('Another Option', 'athena-ai'); ?>
                        </li>
                        <li class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-blue-100 hover:text-blue-900">
                            <?php esc_html_e('Something Else', 'athena-ai'); ?>
                        </li>
                    </ul>
                </div>
                <input type="hidden" name="searchable_select" id="searchable_select" value="">
            </div>
        </div>
        
        <!-- Multi-Select with Tags -->
        <div>
            <label for="multi_select" class="block text-sm font-medium text-gray-700 mb-1">
                <?php esc_html_e('Multi-Select with Tags', 'athena-ai'); ?>
            </label>
            <div class="mt-1 relative">
                <div class="flex flex-wrap gap-2 bg-white border border-gray-300 rounded-md p-2 focus-within:ring-1 focus-within:ring-blue-500 focus-within:border-blue-500">
                    <!-- Selected Tags -->
                    <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        Tag One
                        <button type="button" class="ml-1 text-blue-400 hover:text-blue-600">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                    <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        Tag Two
                        <button type="button" class="ml-1 text-blue-400 hover:text-blue-600">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Input Field -->
                    <input type="text" id="multi_select_input" 
                        class="flex-1 min-w-24 border-0 focus:ring-0 p-0 text-sm"
                        placeholder="<?php esc_attr_e('Add more...', 'athena-ai'); ?>">
                </div>
                
                <!-- Dropdown Options -->
                <div id="multi_select_dropdown" class="hidden absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                    <ul>
                        <li class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-blue-100 hover:text-blue-900">
                            <?php esc_html_e('Tag Three', 'athena-ai'); ?>
                        </li>
                        <li class="cursor-pointer select-none relative py-2 pl-3 pr-9 text-gray-900 hover:bg-blue-100 hover:text-blue-900">
                            <?php esc_html_e('Tag Four', 'athena-ai'); ?>
                        </li>
                    </ul>
                </div>
                
                <!-- Hidden Input for Form Submission -->
                <input type="hidden" name="multi_select" id="multi_select" value="tag1,tag2">
            </div>
        </div>
        
        <!-- Select with Custom Styling -->
        <div>
            <label for="styled_select" class="block text-sm font-medium text-gray-700 mb-1">
                <?php esc_html_e('Custom Styled Select', 'athena-ai'); ?>
            </label>
            <div class="mt-1 relative">
                <select id="styled_select" name="styled_select" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md appearance-none bg-white">
                    <option value="option1"><?php esc_html_e('Small', 'athena-ai'); ?></option>
                    <option value="option2"><?php esc_html_e('Medium', 'athena-ai'); ?></option>
                    <option value="option3"><?php esc_html_e('Large', 'athena-ai'); ?></option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Select with Icons -->
        <div>
            <label for="icon_select" class="block text-sm font-medium text-gray-700 mb-1">
                <?php esc_html_e('Select with Icons', 'athena-ai'); ?>
            </label>
            <div class="mt-1 relative">
                <select id="icon_select" name="icon_select" class="block w-full pl-10 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                    <option value="option1"><?php esc_html_e('Dashboard', 'athena-ai'); ?></option>
                    <option value="option2"><?php esc_html_e('Settings', 'athena-ai'); ?></option>
                    <option value="option3"><?php esc_html_e('Notifications', 'athena-ai'); ?></option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <i class="fa-solid fa-home text-gray-400"></i>
                </div>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                    <i class="fa-solid fa-chevron-down text-gray-400"></i>
                </div>
            </div>
        </div>
    </div>
</div>
