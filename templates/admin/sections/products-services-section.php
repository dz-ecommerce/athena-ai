<!-- Produkte und Dienstleistungen -->
<fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
    <legend class="text-md font-medium text-gray-700 px-2">
        <?php esc_html_e('Produkte und Dienstleistungen', 'athena-ai'); ?>
    </legend>
    <div class="grid grid-cols-1 gap-6">
        <div class="md:col-span-2">
            <div class="flex justify-end mb-2">
                <button type="button" id="athena-ai-products-assistant-btn" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded shadow-sm transition-colors">
                    <i class="fas fa-boxes mr-2"></i>
                    Athena AI Produkte
                </button>
            </div>
            <div class="relative">
                <div class="form-group">
                    <label class="floating-label" for="company_products"><?php esc_html_e('Hauptprodukte/Dienstleistungen', 'athena-ai'); ?></label>
                    <textarea name="athena_ai_profiles[company_products]" id="company_products" rows="3" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4" <?php echo !empty($profile_data['company_products']) ? 'data-filled' : ''; ?>><?php echo esc_textarea($profile_data['company_products'] ?? ''); ?></textarea>
                    <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Produkte und Dienstleistungen durch Komma und Leerzeichen getrennt', 'athena-ai'); ?></p>
                </div>
            </div>
        </div>
        <div class="md:col-span-2">
            <div class="relative">
                <div class="form-group">
                    <label class="floating-label" for="company_usps"><?php esc_html_e('Alleinstellungsmerkmale', 'athena-ai'); ?></label>
                    <textarea name="athena_ai_profiles[company_usps]" id="company_usps" rows="3" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4" <?php echo !empty($profile_data['company_usps']) ? 'data-filled' : ''; ?>><?php echo esc_textarea($profile_data['company_usps'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>
</fieldset> 