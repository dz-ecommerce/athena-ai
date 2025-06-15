<!-- Wichtige Keywords -->
<fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
    <legend class="text-md font-medium text-gray-700 px-2">
        <?php esc_html_e('Wichtige Keywords', 'athena-ai'); ?>
    </legend>
    <div class="relative">
        <div class="form-group">
            <label class="floating-label" for="seo_keywords"><?php esc_html_e('SEO-Keywords', 'athena-ai'); ?></label>
            <textarea name="athena_ai_profiles[seo_keywords]" id="seo_keywords" rows="3" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4" <?php echo !empty($profile_data['seo_keywords']) ? 'data-filled' : ''; ?>><?php echo esc_textarea($profile_data['seo_keywords'] ?? ''); ?></textarea>
            <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Maximal 5 Begriffe, je ein Begriff pro Zeile', 'athena-ai'); ?></p>
        </div>
    </div>
</fieldset> 