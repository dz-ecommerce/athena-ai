<!-- Fachwissen und Expertise -->
<fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
    <div class="grid grid-cols-1 gap-6">
        <div class="md:col-span-2">
            <div class="flex justify-end mb-2">
                <?php athena_ai_button([
                    'prompt_type' => 'expertise_areas',
                    'text' => 'Athena AI Expertise',
                    'icon' => 'fas fa-brain'
                ]); ?>
            </div>
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
        </div>
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