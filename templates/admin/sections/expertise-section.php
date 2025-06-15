<!-- Fachwissen und Expertise -->
<fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
    <legend class="text-md font-medium text-gray-700 px-2">
        <?php esc_html_e('Fachwissen und Expertise', 'athena-ai'); ?>
    </legend>
    <div class="grid grid-cols-1 gap-6">
        <?php 
        athena_ai_floating_textarea([
            'name' => 'athena_ai_profiles[expertise_areas]',
            'id' => 'expertise_areas',
            'label' => 'Fachgebiete',
            'value' => $profile_data['expertise_areas'] ?? '',
            'rows' => 3,
            'help_text' => 'Stichpunkte, ein Eintrag pro Zeile'
        ]); 
        ?>
        <?php 
        athena_ai_floating_textarea([
            'name' => 'athena_ai_profiles[certifications]',
            'id' => 'certifications',
            'label' => 'Besondere Qualifikationen oder Zertifizierungen',
            'value' => $profile_data['certifications'] ?? '',
            'rows' => 3
        ]); 
        ?>
    </div>
</fieldset> 