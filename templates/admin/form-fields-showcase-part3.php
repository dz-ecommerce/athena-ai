<?php
// Part 3 of the form fields showcase

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Form Groups with Validation -->
<div class="mb-8">
    <h4 class="text-md font-medium text-gray-700 mb-4 border-b pb-2">
        <?php esc_html_e('Form Groups with Validation', 'athena-ai'); ?>
    </h4>
    
    <!-- Input Group with Validation -->
    <div class="space-y-6">
        <!-- Valid Input -->
        <div>
            <label for="valid_input" class="block text-sm font-medium text-gray-700 mb-1">
                <?php esc_html_e('Valid Input', 'athena-ai'); ?>
            </label>
            <div class="mt-1 relative rounded-md shadow-sm">
                <input type="text" id="valid_input" name="valid_input" value="Correct value" 
                    class="block w-full pr-10 border-green-300 text-green-900 focus:ring-green-500 focus:border-green-500 rounded-md sm:text-sm" 
                    aria-invalid="false" aria-describedby="valid-input-message">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-check-circle text-green-500"></i>
                </div>
            </div>
            <p class="mt-1 text-sm text-green-600" id="valid-input-message">
                <?php esc_html_e('This input is valid!', 'athena-ai'); ?>
            </p>
        </div>
        
        <!-- Invalid Input -->
        <div>
            <label for="invalid_input" class="block text-sm font-medium text-gray-700 mb-1">
                <?php esc_html_e('Invalid Input', 'athena-ai'); ?>
            </label>
            <div class="mt-1 relative rounded-md shadow-sm">
                <input type="text" id="invalid_input" name="invalid_input" value="Incorrect value" 
                    class="block w-full pr-10 border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 rounded-md sm:text-sm" 
                    aria-invalid="true" aria-describedby="invalid-input-message">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-exclamation-circle text-red-500"></i>
                </div>
            </div>
            <p class="mt-1 text-sm text-red-600" id="invalid-input-message">
                <?php esc_html_e('This value is not valid. Please check your input.', 'athena-ai'); ?>
            </p>
        </div>
        
        <!-- Input with Addons -->
        <div>
            <label for="price_input" class="block text-sm font-medium text-gray-700 mb-1">
                <?php esc_html_e('Price Input', 'athena-ai'); ?>
            </label>
            <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">
                        €
                    </span>
                </div>
                <input type="text" name="price_input" id="price_input" value="0.00" 
                    class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md" 
                    placeholder="0.00">
                <div class="absolute inset-y-0 right-0 flex items-center">
                    <label for="currency" class="sr-only"><?php esc_html_e('Currency', 'athena-ai'); ?></label>
                    <select id="currency" name="currency" 
                        class="focus:ring-blue-500 focus:border-blue-500 h-full py-0 pl-2 pr-7 border-transparent bg-transparent text-gray-500 sm:text-sm rounded-r-md">
                        <option>EUR</option>
                        <option>USD</option>
                        <option>GBP</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Form Layout Examples -->
<div class="mb-8">
    <h4 class="text-md font-medium text-gray-700 mb-4 border-b pb-2">
        <?php esc_html_e('Form Layout Examples', 'athena-ai'); ?>
    </h4>
    
    <!-- Stacked Form Group -->
    <div class="p-4 bg-gray-50 rounded-lg mb-6">
        <h5 class="font-medium text-gray-700 mb-3">
            <?php esc_html_e('Stacked Form', 'athena-ai'); ?>
        </h5>
        <div class="space-y-4">
            <div>
                <label for="stacked_name" class="block text-sm font-medium text-gray-700">
                    <?php esc_html_e('Name', 'athena-ai'); ?>
                </label>
                <input type="text" id="stacked_name" name="stacked_name" 
                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
            <div>
                <label for="stacked_email" class="block text-sm font-medium text-gray-700">
                    <?php esc_html_e('Email', 'athena-ai'); ?>
                </label>
                <input type="email" id="stacked_email" name="stacked_email" 
                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
        </div>
    </div>
    
    <!-- Inline Form Group -->
    <div class="p-4 bg-gray-50 rounded-lg mb-6">
        <h5 class="font-medium text-gray-700 mb-3">
            <?php esc_html_e('Inline Form', 'athena-ai'); ?>
        </h5>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label for="inline_first_name" class="block text-sm font-medium text-gray-700">
                    <?php esc_html_e('First Name', 'athena-ai'); ?>
                </label>
                <input type="text" id="inline_first_name" name="inline_first_name" 
                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
            <div>
                <label for="inline_last_name" class="block text-sm font-medium text-gray-700">
                    <?php esc_html_e('Last Name', 'athena-ai'); ?>
                </label>
                <input type="text" id="inline_last_name" name="inline_last_name" 
                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
            <div>
                <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <?php esc_html_e('Submit', 'athena-ai'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Two Column Form Layout -->
    <div class="p-4 bg-gray-50 rounded-lg">
        <h5 class="font-medium text-gray-700 mb-3">
            <?php esc_html_e('Two Column Form', 'athena-ai'); ?>
        </h5>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="col_first_name" class="block text-sm font-medium text-gray-700">
                    <?php esc_html_e('First Name', 'athena-ai'); ?>
                </label>
                <input type="text" id="col_first_name" name="col_first_name" 
                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
            <div>
                <label for="col_last_name" class="block text-sm font-medium text-gray-700">
                    <?php esc_html_e('Last Name', 'athena-ai'); ?>
                </label>
                <input type="text" id="col_last_name" name="col_last_name" 
                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
            <div>
                <label for="col_email" class="block text-sm font-medium text-gray-700">
                    <?php esc_html_e('Email', 'athena-ai'); ?>
                </label>
                <input type="email" id="col_email" name="col_email" 
                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
            <div>
                <label for="col_phone" class="block text-sm font-medium text-gray-700">
                    <?php esc_html_e('Phone', 'athena-ai'); ?>
                </label>
                <input type="tel" id="col_phone" name="col_phone" 
                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
            <div class="md:col-span-2">
                <label for="col_message" class="block text-sm font-medium text-gray-700">
                    <?php esc_html_e('Message', 'athena-ai'); ?>
                </label>
                <textarea id="col_message" name="col_message" rows="3" 
                    class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mr-3">
                    <?php esc_html_e('Cancel', 'athena-ai'); ?>
                </button>
                <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <?php esc_html_e('Submit', 'athena-ai'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
