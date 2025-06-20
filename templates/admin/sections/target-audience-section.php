<!-- Zielgruppe -->
<fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
    <div class="grid grid-cols-1 gap-6">
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
                '18-24' => '18-24',
                '25-34' => '25-34',
                '35-44' => '35-44',
                '45-54' => '45-54',
                '55-64' => '55-64',
                '65+' => '65+'
            ],
            'layout' => 'horizontal'
        ]); 
        ?>
    </div>
</fieldset> 