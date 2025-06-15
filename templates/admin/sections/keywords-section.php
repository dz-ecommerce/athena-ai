<!-- Wichtige Keywords -->
<fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
    <legend class="text-md font-medium text-gray-700 px-2">
        <?php esc_html_e('Wichtige Keywords', 'athena-ai'); ?>
    </legend>
    <?php 
    athena_ai_floating_textarea([
        'name' => 'athena_ai_profiles[seo_keywords]',
        'id' => 'seo_keywords',
        'label' => 'SEO-Keywords',
        'value' => $profile_data['seo_keywords'] ?? '',
        'rows' => 3,
        'help_text' => 'Maximal 5 Begriffe, je ein Begriff pro Zeile'
    ]); 
    ?>
</fieldset> 