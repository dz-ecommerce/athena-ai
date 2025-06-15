<!-- Zusätzliche Informationen -->
<fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
    <legend class="text-md font-medium text-gray-700 px-2">
        <?php esc_html_e('Zusätzliche Informationen', 'athena-ai'); ?>
    </legend>
    <div class="relative">
        <div class="form-group">
            <label class="floating-label" for="avoided_topics"><?php esc_html_e('Themen, die vermieden werden sollen', 'athena-ai'); ?></label>
            <textarea name="athena_ai_profiles[avoided_topics]" id="avoided_topics" rows="3" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4" <?php echo !empty($profile_data['avoided_topics']) ? 'data-filled' : ''; ?>><?php echo esc_textarea($profile_data['avoided_topics'] ?? ''); ?></textarea>
            <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Optional', 'athena-ai'); ?></p>
        </div>
    </div>
</fieldset> 