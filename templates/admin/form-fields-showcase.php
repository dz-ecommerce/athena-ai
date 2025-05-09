<?php
/**
 * Form Fields Showcase Template
 * 
 * This file contains various form field examples styled with Tailwind CSS
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Form Fields Showcase Section -->
<div class="mb-8 border-t pt-6">
    <h3 class="text-lg font-medium text-gray-700 mb-4">
        <?php esc_html_e('Form Fields Showcase', 'athena-ai'); ?>
    </h3>
    <p class="text-gray-600 mb-6">
        <?php esc_html_e('This section demonstrates various form field types styled with Tailwind CSS.', 'athena-ai'); ?>
    </p>

    <!-- Basic Input Fields -->
    <div class="mb-8">
        <h4 class="text-md font-medium text-gray-700 mb-4 border-b pb-2">
            <?php esc_html_e('Basic Input Fields', 'athena-ai'); ?>
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Text Input -->
            <div>
                <label for="text_input" class="block text-sm font-medium text-gray-700 mb-1">
                    <?php esc_html_e('Text Input', 'athena-ai'); ?>
                </label>
                <input type="text" id="text_input" name="text_input" 
                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" 
                    placeholder="<?php esc_attr_e('Enter some text', 'athena-ai'); ?>">
                <p class="mt-1 text-xs text-gray-500">
                    <?php esc_html_e('Standard text input field with placeholder', 'athena-ai'); ?>
                </p>
            </div>
            
            <!-- Email Input -->
            <div>
                <label for="email_input" class="block text-sm font-medium text-gray-700 mb-1">
                    <?php esc_html_e('Email Input', 'athena-ai'); ?>
                </label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                    </div>
                    <input type="email" id="email_input" name="email_input" 
                        class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md" 
                        placeholder="you@example.com">
                </div>
            </div>
            
            <!-- Number Input -->
            <div>
                <label for="number_input" class="block text-sm font-medium text-gray-700 mb-1">
                    <?php esc_html_e('Number Input', 'athena-ai'); ?>
                </label>
                <input type="number" id="number_input" name="number_input" min="0" max="100" step="1"
                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" 
                    placeholder="0">
            </div>
            
            <!-- Password Input -->
            <div>
                <label for="password_input" class="block text-sm font-medium text-gray-700 mb-1">
                    <?php esc_html_e('Password Input', 'athena-ai'); ?>
                </label>
                <div class="mt-1 relative rounded-md shadow-sm">
                    <input type="password" id="password_input" name="password_input" 
                        class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-10 sm:text-sm border-gray-300 rounded-md" 
                        placeholder="Enter password">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <button type="button" class="text-gray-400 hover:text-gray-500">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
