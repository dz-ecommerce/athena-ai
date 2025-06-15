<!-- Unternehmenswerte und Kommunikation -->
<fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
    <legend class="text-md font-medium text-gray-700 px-2">
        <?php esc_html_e('Unternehmenswerte und Kommunikation', 'athena-ai'); ?>
    </legend>
    <div class="grid grid-cols-1 gap-6">
        <div class="relative">
            <div class="form-group">
                <label class="floating-label" for="company_values"><?php esc_html_e('Unternehmenswerte', 'athena-ai'); ?></label>
                <textarea name="athena_ai_profiles[company_values]" id="company_values" rows="3" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4" <?php echo !empty($profile_data['company_values']) ? 'data-filled' : ''; ?>><?php echo esc_textarea($profile_data['company_values'] ?? ''); ?></textarea>
                <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Maximal 3 Werte, je ein Wert pro Zeile', 'athena-ai'); ?></p>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <?php esc_html_e('Bevorzugte Ansprache', 'athena-ai'); ?>
            </label>
            <div class="flex space-x-4">
                <div class="flex items-center">
                    <input type="radio" name="athena_ai_profiles[preferred_tone]" id="preferred_tone_formal" value="formal" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300" <?php checked(($profile_data['preferred_tone'] ?? ''), 'formal'); ?>>
                    <label for="preferred_tone_formal" class="ml-2 block text-sm text-gray-700">Formell "Sie"</label>
                </div>
                <div class="flex items-center">
                    <input type="radio" name="athena_ai_profiles[preferred_tone]" id="preferred_tone_informal" value="informal" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300" <?php checked(($profile_data['preferred_tone'] ?? ''), 'informal'); ?>>
                    <label for="preferred_tone_informal" class="ml-2 block text-sm text-gray-700">Informell "Du"</label>
                </div>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <?php esc_html_e('Tonalität', 'athena-ai'); ?>
            </label>
            <div class="flex flex-wrap -mx-2">
                <div class="px-2 py-1.5 flex items-center">
                    <div class="relative flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="athena_ai_profiles[tonality][]" id="tonality_professional" value="professional" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded" <?php checked(in_array('professional', (array)($profile_data['tonality'] ?? []))); ?>>
                        </div>
                        <div class="ml-2.5 text-sm">
                            <label for="tonality_professional" class="font-medium text-gray-700">Professionell</label>
                        </div>
                    </div>
                </div>
                <div class="px-2 py-1.5 flex items-center">
                    <div class="relative flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="athena_ai_profiles[tonality][]" id="tonality_friendly" value="friendly" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded" <?php checked(in_array('friendly', (array)($profile_data['tonality'] ?? []))); ?>>
                        </div>
                        <div class="ml-2.5 text-sm">
                            <label for="tonality_friendly" class="font-medium text-gray-700">Freundlich</label>
                        </div>
                    </div>
                </div>
                <div class="px-2 py-1.5 flex items-center">
                    <div class="relative flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="athena_ai_profiles[tonality][]" id="tonality_humorous" value="humorous" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded" <?php checked(in_array('humorous', (array)($profile_data['tonality'] ?? []))); ?>>
                        </div>
                        <div class="ml-2.5 text-sm">
                            <label for="tonality_humorous" class="font-medium text-gray-700">Humorvoll</label>
                        </div>
                    </div>
                </div>
                <div class="px-2 py-1.5 flex items-center">
                    <div class="relative flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="athena_ai_profiles[tonality][]" id="tonality_informative" value="informative" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded" <?php checked(in_array('informative', (array)($profile_data['tonality'] ?? []))); ?>>
                        </div>
                        <div class="ml-2.5 text-sm">
                            <label for="tonality_informative" class="font-medium text-gray-700">Informativ</label>
                        </div>
                    </div>
                </div>
                <div class="px-2 py-1.5 flex items-center">
                    <div class="relative flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="athena_ai_profiles[tonality][]" id="tonality_authoritative" value="authoritative" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded" <?php checked(in_array('authoritative', (array)($profile_data['tonality'] ?? []))); ?>>
                        </div>
                        <div class="ml-2.5 text-sm">
                            <label for="tonality_authoritative" class="font-medium text-gray-700">Autoritativ</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</fieldset> 