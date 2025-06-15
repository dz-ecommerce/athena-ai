<!-- Zielgruppe -->
<fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
    <legend class="text-md font-medium text-gray-700 px-2">
        <?php esc_html_e('Zielgruppe', 'athena-ai'); ?>
    </legend>
    <div class="grid grid-cols-1 gap-6">
        <?php 
        athena_ai_radio_group([
            'name' => 'athena_ai_profiles[customer_type]',
            'label' => 'B2B oder B2C',
            'value' => $profile_data['customer_type'] ?? '',
            'options' => [
                'b2b' => 'B2B',
                'b2c' => 'B2C',
                'both' => 'Beides'
            ],
            'layout' => 'horizontal'
        ]); 
        ?>
        <div class="md:col-span-2">
            <div class="flex justify-end mb-2">
                <?php athena_ai_button([
                    'prompt_type' => 'target_audience',
                    'text' => 'Athena AI Zielgruppe',
                    'icon' => 'fas fa-users'
                ]); ?>
            </div>
            <?php 
            athena_ai_floating_textarea([
                'name' => 'athena_ai_profiles[target_audience]',
                'id' => 'target_audience',
                'label' => 'Beschreibung der Zielgruppe',
                'value' => $profile_data['target_audience'] ?? '',
                'rows' => 3
            ]); 
            ?>
        </div>
        <?php 
        athena_ai_checkbox_group([
            'name' => 'athena_ai_profiles[age_group]',
            'label' => 'Altersgruppe der Zielkunden',
            'values' => $profile_data['age_group'] ?? [],
            'options' => [
                '18-25' => '18-25',
                '26-35' => '26-35',
                '36-45' => '36-45',
                '46-60' => '46-60',
                '60+' => '60+'
            ],
            'layout' => 'horizontal'
        ]); 
        ?>
    </div>
</fieldset> 