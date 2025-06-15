<!-- Unternehmensprofil -->
<fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
    <legend class="text-md font-medium text-gray-700 px-2">
        <?php esc_html_e('Unternehmensprofil', 'athena-ai'); ?>
    </legend>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="relative">
            <div class="form-group">
                <label class="floating-label" for="company_name"><?php esc_html_e('Firmenname', 'athena-ai'); ?></label>
                <input type="text" name="athena_ai_profiles[company_name]" id="company_name" value="<?php echo esc_attr($profile_data['company_name'] ?? ''); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4" <?php echo !empty($profile_data['company_name']) ? 'data-filled' : ''; ?>>
            </div>
        </div>
        <div class="relative">
            <div class="form-group">
                <label class="floating-label" for="company_industry"><?php esc_html_e('Branche', 'athena-ai'); ?></label>
                <select name="athena_ai_profiles[company_industry]" id="company_industry" class="focus:ring-blue-500 focus:border-blue-500 block w-full border-gray-300 rounded-md py-2.5 px-4" <?php echo !empty($profile_data['company_industry']) ? 'data-filled' : ''; ?>>
                    <option value="" disabled<?php selected(empty($profile_data['company_industry'])); ?>><?php esc_html_e('Branche auswählen', 'athena-ai'); ?></option>
                    <?php
                    // Branchendaten definieren
                    $industry_groups = [
                        'Dienstleistungen' => [
                            'accounting' => 'Buchhaltung & Steuern',
                            'advertising' => 'Werbung & Marketing',
                            'consulting' => 'Unternehmensberatung',
                            'financial' => 'Finanzdienstleistungen',
                            'insurance' => 'Versicherungen',
                            'legal' => 'Rechtsberatung',
                            'real_estate' => 'Immobilien'
                        ],
                        'IT & Technologie' => [
                            'it_services' => 'IT-Dienstleistungen',
                            'software' => 'Softwareentwicklung',
                            'web_design' => 'Webdesign & -entwicklung',
                            'ecommerce' => 'E-Commerce',
                            'telecommunications' => 'Telekommunikation',
                            'data_analytics' => 'Datenanalyse',
                            'cloud_computing' => 'Cloud Computing',
                            'cybersecurity' => 'IT-Sicherheit'
                        ],
                        'Handel & Einzelhandel' => [
                            'retail' => 'Einzelhandel',
                            'wholesale' => 'Großhandel',
                            'ecommerce_retail' => 'Online-Handel',
                            'consumer_goods' => 'Konsumgüter',
                            'food_retail' => 'Lebensmittelhandel',
                            'fashion' => 'Mode & Bekleidung',
                            'electronics_retail' => 'Elektronik',
                            'furniture' => 'Möbel & Einrichtung'
                        ],
                        'Produktion & Fertigung' => [
                            'manufacturing' => 'Fertigungsindustrie',
                            'automotive' => 'Automobilindustrie',
                            'aerospace' => 'Luft- und Raumfahrt',
                            'electronics' => 'Elektronik & Elektrotechnik',
                            'chemicals' => 'Chemische Industrie',
                            'pharma' => 'Pharmazeutische Industrie',
                            'machinery' => 'Maschinenbau',
                            'textiles' => 'Textilindustrie'
                        ],
                        'Gesundheitswesen' => [
                            'healthcare' => 'Gesundheitswesen',
                            'medical_practice' => 'Arztpraxis',
                            'hospital' => 'Krankenhaus',
                            'biotech' => 'Biotechnologie',
                            'medical_devices' => 'Medizintechnik',
                            'pharmaceutical' => 'Pharmaindustrie',
                            'healthcare_services' => 'Gesundheitsdienstleistungen',
                            'eldercare' => 'Altenpflege'
                        ],
                        'Bildung & Forschung' => [
                            'education' => 'Bildungseinrichtungen',
                            'school' => 'Schulen',
                            'university' => 'Hochschulen & Universitäten',
                            'vocational_training' => 'Berufsbildung',
                            'research' => 'Forschungseinrichtungen',
                            'e_learning' => 'E-Learning & Online-Bildung'
                        ],
                        'Weitere Branchen' => [
                            'agriculture' => 'Landwirtschaft',
                            'architecture' => 'Architektur & Ingenieurwesen',
                            'art' => 'Kunst & Design',
                            'beauty' => 'Schönheit & Kosmetik',
                            'construction' => 'Bauwesen',
                            'energy' => 'Energie & Versorgung',
                            'entertainment' => 'Unterhaltung & Freizeit',
                            'food' => 'Gastronomie & Lebensmittel',
                            'hospitality' => 'Hotellerie & Gastgewerbe',
                            'media' => 'Medien & Kommunikation',
                            'transport' => 'Transport & Logistik',
                            'travel' => 'Tourismus & Reisen',
                            'other' => 'Sonstige'
                        ]
                    ];
                    
                    // Gespeicherte Industrie
                    $selected_industry = $profile_data['company_industry'] ?? '';
                    
                    // Optionen ausgeben
                    foreach ($industry_groups as $group_name => $industries) {
                        echo '<optgroup label="' . esc_attr__($group_name, 'athena-ai') . '">'; 
                        foreach ($industries as $value => $label) {
                            $selected = $selected_industry === $value ? ' selected' : '';
                            echo '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
                        }
                        echo '</optgroup>';
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="md:col-span-2">
            <div class="flex justify-end mb-2">
                <button type="button" id="athena-ai-assistant-btn" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded shadow-sm transition-colors">
                    <i class="fas fa-magic mr-2"></i>
                    Athena AI Assistent
                </button>
            </div>
            <div class="relative">
                <div class="form-group">
                    <label class="floating-label" for="company_description"><?php esc_html_e('Kurzbeschreibung des Unternehmens', 'athena-ai'); ?></label>
                    <textarea name="athena_ai_profiles[company_description]" id="company_description" rows="5" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4" <?php echo !empty($profile_data['company_description']) ? 'data-filled' : ''; ?>><?php echo esc_textarea($profile_data['company_description'] ?? ''); ?></textarea>
                    <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Maximal 200 Wörter', 'athena-ai'); ?></p>
                </div>
            </div>
        </div>
    </div>
</fieldset> 