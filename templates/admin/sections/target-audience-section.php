<!-- Zielgruppe -->
<fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
    <legend class="text-md font-medium text-gray-700 px-2">
        <?php esc_html_e('Zielgruppe', 'athena-ai'); ?>
    </legend>
    <div class="grid grid-cols-1 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <?php esc_html_e('B2B oder B2C', 'athena-ai'); ?>
            </label>
            <div class="flex space-x-6">
                <div class="flex items-center">
                    <input type="radio" name="athena_ai_profiles[customer_type]" id="customer_type_b2b" value="b2b" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300" <?php checked(($profile_data['customer_type'] ?? ''), 'b2b'); ?>>
                    <label for="customer_type_b2b" class="ml-2 block text-sm text-gray-700">B2B</label>
                </div>
                <div class="flex items-center">
                    <input type="radio" name="athena_ai_profiles[customer_type]" id="customer_type_b2c" value="b2c" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300" <?php checked(($profile_data['customer_type'] ?? ''), 'b2c'); ?>>
                    <label for="customer_type_b2c" class="ml-2 block text-sm text-gray-700">B2C</label>
                </div>
                <div class="flex items-center">
                    <input type="radio" name="athena_ai_profiles[customer_type]" id="customer_type_both" value="both" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300" <?php checked(($profile_data['customer_type'] ?? ''), 'both'); ?>>
                    <label for="customer_type_both" class="ml-2 block text-sm text-gray-700">Beides</label>
                </div>
            </div>
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
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <?php esc_html_e('Altersgruppe der Zielkunden', 'athena-ai'); ?>
            </label>
            <div class="flex flex-wrap -mx-2">
                <div class="px-2 py-1.5 flex items-center">
                    <div class="relative flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="athena_ai_profiles[age_group][]" id="age_group_18_25" value="18-25" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded" <?php checked(in_array('18-25', (array)($profile_data['age_group'] ?? []))); ?>>
                        </div>
                        <div class="ml-2.5 text-sm">
                            <label for="age_group_18_25" class="font-medium text-gray-700">18-25</label>
                        </div>
                    </div>
                </div>
                <div class="px-2 py-1.5 flex items-center">
                    <div class="relative flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="athena_ai_profiles[age_group][]" id="age_group_26_35" value="26-35" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded" <?php checked(in_array('26-35', (array)($profile_data['age_group'] ?? []))); ?>>
                        </div>
                        <div class="ml-2.5 text-sm">
                            <label for="age_group_26_35" class="font-medium text-gray-700">26-35</label>
                        </div>
                    </div>
                </div>
                <div class="px-2 py-1.5 flex items-center">
                    <div class="relative flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="athena_ai_profiles[age_group][]" id="age_group_36_45" value="36-45" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded" <?php checked(in_array('36-45', (array)($profile_data['age_group'] ?? []))); ?>>
                        </div>
                        <div class="ml-2.5 text-sm">
                            <label for="age_group_36_45" class="font-medium text-gray-700">36-45</label>
                        </div>
                    </div>
                </div>
                <div class="px-2 py-1.5 flex items-center">
                    <div class="relative flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="athena_ai_profiles[age_group][]" id="age_group_46_60" value="46-60" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded" <?php checked(in_array('46-60', (array)($profile_data['age_group'] ?? []))); ?>>
                        </div>
                        <div class="ml-2.5 text-sm">
                            <label for="age_group_46_60" class="font-medium text-gray-700">46-60</label>
                        </div>
                    </div>
                </div>
                <div class="px-2 py-1.5 flex items-center">
                    <div class="relative flex items-start">
                        <div class="flex items-center h-5">
                            <input type="checkbox" name="athena_ai_profiles[age_group][]" id="age_group_60_plus" value="60+" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded" <?php checked(in_array('60+', (array)($profile_data['age_group'] ?? []))); ?>>
                        </div>
                        <div class="ml-2.5 text-sm">
                            <label for="age_group_60_plus" class="font-medium text-gray-700">60+</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</fieldset> 