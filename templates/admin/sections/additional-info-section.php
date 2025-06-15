<!-- Zusätzliche Informationen -->
<fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
    <legend class="text-md font-medium text-gray-700 px-2">
        <?php esc_html_e('Zusätzliche Informationen', 'athena-ai'); ?>
    </legend>
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