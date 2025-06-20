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
    </div>
</fieldset> 