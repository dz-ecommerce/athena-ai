<!-- Produkte und Dienstleistungen -->
<fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
    <legend class="text-md font-medium text-gray-700 px-2">
        <?php esc_html_e('Produkte und Dienstleistungen', 'athena-ai'); ?>
    </legend>
    <div class="grid grid-cols-1 gap-6">
        <div class="md:col-span-2">
            <div class="flex justify-end mb-2">
                <?php athena_ai_button([
                    'prompt_type' => 'products',
                    'text' => 'Athena AI Produkte',
                    'icon' => 'fas fa-boxes'
                ]); ?>
            </div>
            <?php 
            athena_ai_floating_textarea([
                'name' => 'athena_ai_profiles[company_products]',
                'id' => 'company_products',
                'label' => 'Hauptprodukte/Dienstleistungen',
                'value' => $profile_data['company_products'] ?? '',
                'rows' => 3,
                'help_text' => 'Produkte und Dienstleistungen durch Komma und Leerzeichen getrennt'
            ]); 
            ?>
        </div>
        <div class="md:col-span-2">
            <div class="flex justify-end mb-2">
                <?php athena_ai_button([
                    'prompt_type' => 'company_usps',
                    'text' => 'Athena AI USPs',
                    'icon' => 'fas fa-star'
                ]); ?>
            </div>
            <?php 
            athena_ai_floating_textarea([
                'name' => 'athena_ai_profiles[company_usps]',
                'id' => 'company_usps',
                'label' => 'Alleinstellungsmerkmale',
                'value' => $profile_data['company_usps'] ?? '',
                'rows' => 3
            ]); 
            ?>
        </div>
    </div>
</fieldset> 