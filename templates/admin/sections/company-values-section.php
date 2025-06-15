<!-- Unternehmenswerte und Kommunikation -->
<fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
    <legend class="text-md font-medium text-gray-700 px-2">
        <?php esc_html_e('Unternehmenswerte und Kommunikation', 'athena-ai'); ?>
    </legend>
    <div class="grid grid-cols-1 gap-6">
        <?php 
        athena_ai_floating_textarea([
            'name' => 'athena_ai_profiles[company_values]',
            'id' => 'company_values',
            'label' => 'Unternehmenswerte',
            'value' => $profile_data['company_values'] ?? '',
            'rows' => 3,
            'help_text' => 'Maximal 3 Werte, je ein Wert pro Zeile'
        ]); 
        ?>
        <?php 
        athena_ai_radio_group([
            'name' => 'athena_ai_profiles[preferred_tone]',
            'label' => 'Bevorzugte Ansprache',
            'value' => $profile_data['preferred_tone'] ?? '',
            'options' => [
                'formal' => 'Formell "Sie"',
                'informal' => 'Informell "Du"'
            ],
            'layout' => 'horizontal'
        ]); 
        ?>
        <?php 
        athena_ai_checkbox_group([
            'name' => 'athena_ai_profiles[tonality]',
            'label' => 'Tonalität',
            'values' => $profile_data['tonality'] ?? [],
            'options' => [
                'professional' => 'Professionell',
                'friendly' => 'Freundlich',
                'humorous' => 'Humorvoll',
                'informative' => 'Informativ',
                'authoritative' => 'Autoritativ'
            ],
            'layout' => 'horizontal'
        ]); 
        ?>
    </div>
</fieldset> 