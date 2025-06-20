<!-- Zusätzliche Informationen -->
<fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
    <?php 
    athena_ai_floating_textarea([
        'name' => 'athena_ai_profiles[avoided_topics]',
        'id' => 'avoided_topics',
        'label' => 'Themen, die vermieden werden sollen',
        'value' => $profile_data['avoided_topics'] ?? '',
        'rows' => 3,
        'help_text' => 'Optional'
    ]); 
    ?>
</fieldset> 