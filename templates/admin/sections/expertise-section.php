<!-- Fachwissen und Expertise -->
<fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
    <legend class="text-md font-medium text-gray-700 px-2">
        <?php esc_html_e('Fachwissen und Expertise', 'athena-ai'); ?>
    </legend>
    <div class="grid grid-cols-1 gap-6">
        <div class="relative">
            <div class="form-group">
                <label class="floating-label" for="expertise_areas"><?php esc_html_e('Fachgebiete', 'athena-ai'); ?></label>
                <textarea name="athena_ai_profiles[expertise_areas]" id="expertise_areas" rows="3" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4" <?php echo !empty($profile_data['expertise_areas']) ? 'data-filled' : ''; ?>><?php echo esc_textarea($profile_data['expertise_areas'] ?? ''); ?></textarea>
                <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Stichpunkte, ein Eintrag pro Zeile', 'athena-ai'); ?></p>
            </div>
        </div>
        <div class="relative">
            <div class="form-group">
                <label class="floating-label" for="certifications"><?php esc_html_e('Besondere Qualifikationen oder Zertifizierungen', 'athena-ai'); ?></label>
                <textarea name="athena_ai_profiles[certifications]" id="certifications" rows="3" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4" <?php echo !empty($profile_data['certifications']) ? 'data-filled' : ''; ?>><?php echo esc_textarea($profile_data['certifications'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>
</fieldset> 