<?php
// Part 2 of the form fields showcase

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Checkboxes & Radios -->
<div class="mb-8">
    <h4 class="text-md font-medium text-gray-700 mb-4 border-b pb-2">
        <?php esc_html_e('Checkboxes & Radio Buttons', 'athena-ai'); ?>
    </h4>
    
    <!-- Single Checkbox -->
    <div class="mb-4">
        <div class="flex items-start">
            <div class="flex items-center h-5">
                <input id="single_checkbox" name="single_checkbox" type="checkbox" 
                    class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
            </div>
            <div class="ml-3 text-sm">
                <label for="single_checkbox" class="font-medium text-gray-700">
                    <?php esc_html_e('Single Checkbox Option', 'athena-ai'); ?>
                </label>
                <p class="text-gray-500">
                    <?php esc_html_e('Description for this checkbox option', 'athena-ai'); ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Checkbox Group -->
    <div class="mb-4">
        <span class="block text-sm font-medium text-gray-700 mb-2">
            <?php esc_html_e('Checkbox Group', 'athena-ai'); ?>
        </span>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input id="checkbox_option1" name="checkbox_group[]" type="checkbox" value="option1" 
                        class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                </div>
                <div class="ml-3 text-sm">
                    <label for="checkbox_option1" class="font-medium text-gray-700">
                        <?php esc_html_e('First Option', 'athena-ai'); ?>
                    </label>
                </div>
            </div>
            
            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input id="checkbox_option2" name="checkbox_group[]" type="checkbox" value="option2" 
                        class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                </div>
                <div class="ml-3 text-sm">
                    <label for="checkbox_option2" class="font-medium text-gray-700">
                        <?php esc_html_e('Second Option', 'athena-ai'); ?>
                    </label>
                </div>
            </div>
            
            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input id="checkbox_option3" name="checkbox_group[]" type="checkbox" value="option3" 
                        class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                </div>
                <div class="ml-3 text-sm">
                    <label for="checkbox_option3" class="font-medium text-gray-700">
                        <?php esc_html_e('Third Option', 'athena-ai'); ?>
                    </label>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Radio Group -->
    <div class="mb-4">
        <span class="block text-sm font-medium text-gray-700 mb-2">
            <?php esc_html_e('Radio Button Group', 'athena-ai'); ?>
        </span>
        <div class="grid grid-cols-1 gap-3">
            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input id="radio_option1" name="radio_group" type="radio" value="option1" checked 
                        class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                </div>
                <div class="ml-3 text-sm">
                    <label for="radio_option1" class="font-medium text-gray-700">
                        <?php esc_html_e('Standard Option', 'athena-ai'); ?>
                    </label>
                    <p class="text-gray-500">
                        <?php esc_html_e('Default configuration with standard features', 'athena-ai'); ?>
                    </p>
                </div>
            </div>
            
            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input id="radio_option2" name="radio_group" type="radio" value="option2" 
                        class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                </div>
                <div class="ml-3 text-sm">
                    <label for="radio_option2" class="font-medium text-gray-700">
                        <?php esc_html_e('Advanced Option', 'athena-ai'); ?>
                    </label>
                    <p class="text-gray-500">
                        <?php esc_html_e('Enhanced configuration with premium features', 'athena-ai'); ?>
                    </p>
                </div>
            </div>
            
            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input id="radio_option3" name="radio_group" type="radio" value="option3" 
                        class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                </div>
                <div class="ml-3 text-sm">
                    <label for="radio_option3" class="font-medium text-gray-700">
                        <?php esc_html_e('Custom Option', 'athena-ai'); ?>
                    </label>
                    <p class="text-gray-500">
                        <?php esc_html_e('Fully customizable configuration', 'athena-ai'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toggle Switch -->
    <div class="mb-4">
        <div class="flex items-center justify-between">
            <span class="flex-grow flex flex-col">
                <span class="text-sm font-medium text-gray-700">
                    <?php esc_html_e('Toggle Switch', 'athena-ai'); ?>
                </span>
                <span class="text-sm text-gray-500">
                    <?php esc_html_e('Enable this feature', 'athena-ai'); ?>
                </span>
            </span>
            <button type="button" id="toggle_switch" class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 bg-gray-200" role="switch" aria-checked="false">
                <span class="sr-only"><?php esc_html_e('Use setting', 'athena-ai'); ?></span>
                <span aria-hidden="true" class="translate-x-0 pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"></span>
            </button>
            <input type="hidden" name="toggle_value" id="toggle_value" value="0">
        </div>
    </div>
</div>
