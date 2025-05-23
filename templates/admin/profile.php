<?php
/**
 * Template für die Athena AI Profile-Seite
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap athena-ai-admin">
    <!-- Header -->
    <div class="flex justify-between items-center bg-white shadow-sm px-6 py-5 mb-6 rounded-lg border border-gray-100">
        <h1 class="text-2xl font-bold text-gray-800 m-0 flex items-center">
            <span class="bg-purple-100 text-purple-600 p-2 rounded-lg mr-3">
                <i class="fa-solid fa-user-circle"></i>
            </span>
            <?php esc_html_e('Profile', 'athena-ai'); ?>
        </h1>
    </div>

    <!-- Content -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-100 p-6">
        <form method="post" action="options.php" class="space-y-6">
            <?php
            settings_fields('athena_ai_profile_settings');
            do_settings_sections('athena_ai_profile_settings');
            
            // Holen der gespeicherten Profildaten
            $profile_data = get_option('athena_ai_profiles', []);
            ?>

            <!-- Unternehmens-Stammdaten Section -->
            <div class="mb-8 border-t pt-6">
                <h3 class="text-lg font-medium text-gray-700 mb-4">
                    <?php esc_html_e('Unternehmens-Stammdaten', 'athena-ai'); ?>
                </h3>
                <p class="text-gray-600 mb-6">
                    <?php esc_html_e('Diese Informationen werden für die KI-basierte Erstellung von Blogbeiträgen verwendet.', 'athena-ai'); ?>
                </p>

                <!-- Unternehmensprofil -->
                <fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
                    <legend class="text-md font-medium text-gray-700 px-2">
                        <?php esc_html_e('Unternehmensprofil', 'athena-ai'); ?>
                    </legend>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <input type="text" name="athena_ai_profiles[company_name]" id="company_name" placeholder="<?php esc_attr_e('Firmenname', 'athena-ai'); ?>" value="<?php echo esc_attr($profile_data['company_name'] ?? ''); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4">
                        </div>
                        <div>
                            <select name="athena_ai_profiles[company_industry]" id="company_industry" class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-3 pr-10 py-2.5 px-4 border-gray-300 rounded-md">
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

                        <div class="md:col-span-2">
                            <div class="flex justify-end mb-2">
                                <button type="button" id="athena-ai-assistant-btn" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded shadow-sm transition-colors">
                                    Athena AI Assistent
                                </button>
                            </div>
                            <textarea name="athena_ai_profiles[company_description]" id="company_description" rows="3" maxlength="500" placeholder="<?php esc_attr_e('Kurzbeschreibung des Unternehmens', 'athena-ai'); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4"><?php echo esc_textarea($profile_data['company_description'] ?? ''); ?></textarea>
                            <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Maximal 500 Zeichen', 'athena-ai'); ?></p>
                        </div>
                    </div>
                </fieldset>

                <!-- Produkte und Dienstleistungen -->
                <fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
                    <legend class="text-md font-medium text-gray-700 px-2">
                        <?php esc_html_e('Produkte und Dienstleistungen', 'athena-ai'); ?>
                    </legend>
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <textarea name="athena_ai_profiles[company_products]" id="company_products" rows="3" placeholder="<?php esc_attr_e('Hauptprodukte/Dienstleistungen', 'athena-ai'); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4"><?php echo esc_textarea($profile_data['company_products'] ?? ''); ?></textarea>
                            <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Maximal 3 Einträge, je ein Eintrag pro Zeile', 'athena-ai'); ?></p>
                        </div>
                        <div>
                            <textarea name="athena_ai_profiles[company_usps]" id="company_usps" rows="3" placeholder="<?php esc_attr_e('Alleinstellungsmerkmale', 'athena-ai'); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4"><?php echo esc_textarea($profile_data['company_usps'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </fieldset>

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
                        <div>
                            <textarea name="athena_ai_profiles[target_audience]" id="target_audience" rows="3" placeholder="<?php esc_attr_e('Beschreibung der Zielgruppe', 'athena-ai'); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4"><?php echo esc_textarea($profile_data['target_audience'] ?? ''); ?></textarea>
                        </div>
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
                                            <input type="checkbox" name="age_group[]" id="age_group_60_plus" value="60+" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded">
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

                <!-- Unternehmenswerte und Kommunikation -->
                <fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
                    <legend class="text-md font-medium text-gray-700 px-2">
                        <?php esc_html_e('Unternehmenswerte und Kommunikation', 'athena-ai'); ?>
                    </legend>
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <textarea name="athena_ai_profiles[company_values]" id="company_values" rows="3" placeholder="<?php esc_attr_e('Unternehmenswerte', 'athena-ai'); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4"><?php echo esc_textarea($profile_data['company_values'] ?? ''); ?></textarea>
                            <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Maximal 3 Werte, je ein Wert pro Zeile', 'athena-ai'); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <?php esc_html_e('Bevorzugte Ansprache', 'athena-ai'); ?>
                            </label>
                            <div class="flex space-x-4">
                                <div class="flex items-center">
                                    <input type="radio" name="communication_style" id="communication_style_formal" value="formal" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                    <label for="communication_style_formal" class="ml-2 block text-sm text-gray-700">Formell "Sie"</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="radio" name="communication_style" id="communication_style_informal" value="informal" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                    <label for="communication_style_informal" class="ml-2 block text-sm text-gray-700">Informell "Du"</label>
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
                                            <input type="checkbox" name="tonality[]" id="tonality_professional" value="professional" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded">
                                        </div>
                                        <div class="ml-2.5 text-sm">
                                            <label for="tonality_professional" class="font-medium text-gray-700">Professionell</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-2 py-1.5 flex items-center">
                                    <div class="relative flex items-start">
                                        <div class="flex items-center h-5">
                                            <input type="checkbox" name="tonality[]" id="tonality_friendly" value="friendly" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded">
                                        </div>
                                        <div class="ml-2.5 text-sm">
                                            <label for="tonality_friendly" class="font-medium text-gray-700">Freundlich</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-2 py-1.5 flex items-center">
                                    <div class="relative flex items-start">
                                        <div class="flex items-center h-5">
                                            <input type="checkbox" name="tonality[]" id="tonality_humorous" value="humorous" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded">
                                        </div>
                                        <div class="ml-2.5 text-sm">
                                            <label for="tonality_humorous" class="font-medium text-gray-700">Humorvoll</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-2 py-1.5 flex items-center">
                                    <div class="relative flex items-start">
                                        <div class="flex items-center h-5">
                                            <input type="checkbox" name="tonality[]" id="tonality_informative" value="informative" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded">
                                        </div>
                                        <div class="ml-2.5 text-sm">
                                            <label for="tonality_informative" class="font-medium text-gray-700">Informativ</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-2 py-1.5 flex items-center">
                                    <div class="relative flex items-start">
                                        <div class="flex items-center h-5">
                                            <input type="checkbox" name="tonality[]" id="tonality_authoritative" value="authoritative" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded">
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

                <!-- Fachwissen und Expertise -->
                <fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
                    <legend class="text-md font-medium text-gray-700 px-2">
                        <?php esc_html_e('Fachwissen und Expertise', 'athena-ai'); ?>
                    </legend>
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <textarea name="athena_ai_profiles[expertise_areas]" id="expertise_areas" rows="3" placeholder="<?php esc_attr_e('Fachgebiete', 'athena-ai'); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4"><?php echo esc_textarea($profile_data['expertise_areas'] ?? ''); ?></textarea>
                            <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Stichpunkte, ein Eintrag pro Zeile', 'athena-ai'); ?></p>
                        </div>
                        <div>
                            <textarea name="athena_ai_profiles[certifications]" id="certifications" rows="3" placeholder="<?php esc_attr_e('Besondere Qualifikationen oder Zertifizierungen', 'athena-ai'); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4"><?php echo esc_textarea($profile_data['certifications'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </fieldset>

                <!-- Wichtige Keywords -->
                <fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
                    <legend class="text-md font-medium text-gray-700 px-2">
                        <?php esc_html_e('Wichtige Keywords', 'athena-ai'); ?>
                    </legend>
                    <div>
                        <textarea name="athena_ai_profiles[seo_keywords]" id="seo_keywords" rows="3" placeholder="<?php esc_attr_e('SEO-Keywords', 'athena-ai'); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4"><?php echo esc_textarea($profile_data['seo_keywords'] ?? ''); ?></textarea>
                        <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Maximal 5 Begriffe, je ein Begriff pro Zeile', 'athena-ai'); ?></p>
                    </div>
                </fieldset>

                <!-- Bevorzugte Ansprache -->
                <fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
                    <legend class="text-md font-medium text-gray-700 px-2">
                        <?php esc_html_e('Bevorzugte Ansprache', 'athena-ai'); ?>
                    </legend>
                    <div class="flex space-x-6 mt-2">
                        <div class="flex items-center">
                            <input type="radio" name="athena_ai_profiles[preferred_tone]" id="formal_tone" value="formal" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300" <?php checked(($profile_data['preferred_tone'] ?? ''), 'formal'); ?>>
                            <label for="formal_tone" class="ml-2 block text-sm text-gray-700">Formell "Sie"</label>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" name="athena_ai_profiles[preferred_tone]" id="informal_tone" value="informal" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300" <?php checked(($profile_data['preferred_tone'] ?? ''), 'informal'); ?>>
                            <label for="informal_tone" class="ml-2 block text-sm text-gray-700">Informell "Du"</label>
                        </div>
                    </div>
                </fieldset>

                <!-- Tonalität -->
                <fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
                    <legend class="text-md font-medium text-gray-700 px-2">
                        <?php esc_html_e('Tonalität', 'athena-ai'); ?>
                    </legend>
                    <div class="flex flex-wrap -mx-2 mt-2">
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
                </fieldset>

                <!-- Zusätzliche Informationen -->
                <fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
                    <legend class="text-md font-medium text-gray-700 px-2">
                        <?php esc_html_e('Zusätzliche Informationen', 'athena-ai'); ?>
                    </legend>
                    <div>
                        <textarea name="athena_ai_profiles[avoided_topics]" id="avoided_topics" rows="3" placeholder="<?php esc_attr_e('Themen, die vermieden werden sollen', 'athena-ai'); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4"><?php echo esc_textarea($profile_data['avoided_topics'] ?? ''); ?></textarea>
                        <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Optional', 'athena-ai'); ?></p>
                    </div>
                </fieldset>
            </div>


            
            <?php submit_button(__('Einstellungen speichern', 'athena-ai'), 'primary', 'submit', false, ['class' => 'bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 text-white rounded-lg px-4 py-2']); ?>
        </form>
    </div>
</div>

<?php
// Modal HTML am Ende der Seite einfügen
$pages = get_pages(['sort_column' => 'post_title', 'sort_order' => 'asc']);
?>
<div id="athena-ai-modal" class="fixed z-50 inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden overflow-y-auto">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6 relative max-h-[80vh] overflow-y-auto">
        <button type="button" id="athena-ai-modal-close" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl font-bold">&times;</button>
        <h2 class="text-lg font-semibold mb-4">Athena AI Assistent</h2>
        
        <!-- KI-Anbieter auswählen -->
        <div class="mb-4">
            <label for="athena-ai-model-provider" class="block mb-2 font-medium">KI-Anbieter auswählen</label>
            <div class="flex space-x-4">
                <label class="inline-flex items-center">
                    <input type="radio" name="athena-ai-model-provider" value="openai" class="form-radio h-4 w-4 text-blue-600" checked>
                    <span class="ml-2">OpenAI</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="athena-ai-model-provider" value="gemini" class="form-radio h-4 w-4 text-blue-600">
                    <span class="ml-2">Google Gemini</span>
                </label>
            </div>
            <div class="mt-3">
                <label class="inline-flex items-center">
                    <input type="checkbox" id="athena-ai-test-only" class="form-checkbox h-4 w-4 text-blue-600">
                    <span class="ml-2">Nur Output testen</span>
                </label>
            </div>
        </div>
        
        <!-- Hidden Input Fields für Prompt-Teile -->
        <input type="hidden" id="athena-ai-prompt-intro" value="Erstelle einen professionellen SEO-Text. Du agierst als WordPress-SEO-Experte. Beschreibe das Unternehmen anhand folgender Informationen so überzeugend wie möglich.">
        <input type="hidden" id="athena-ai-prompt-limit" value="Maximal 100 Wörter. Reiner Absatztext ohne Kommentare.">
        
        <select id="athena-ai-page-select" class="block w-full border border-gray-300 rounded px-3 py-2 mb-4 flex-grow max-w-full box-border">
            <option value="">-- Seite wählen (optional) --</option>
            <?php foreach ($pages as $page): ?>
                <option value="<?php echo esc_attr($page->ID); ?>"><?php echo esc_html($page->post_title); ?></option>
            <?php endforeach; ?>
        </select>
        <textarea id="athena-ai-modal-extra-info" class="block w-full border border-gray-300 rounded px-3 py-2 mb-4" rows="4" placeholder="Zusätzliche Informationen hinterlegen"></textarea>
        <button type="button" id="athena-ai-create-content" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow-sm w-full">Create Content</button>
        <div id="athena-ai-modal-debug" class="mt-4 p-3 bg-gray-100 border border-gray-300 rounded text-xs font-mono text-gray-700" style="display:none;"></div>
    </div>
</div>
<script type="text/javascript">
var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
</script>
<script>
jQuery(function($) {
    $('#athena-ai-assistant-btn').on('click', function() {
        $('#athena-ai-modal').removeClass('hidden').addClass('flex');
    });
    $('#athena-ai-modal-close').on('click', function() {
        $('#athena-ai-modal').addClass('hidden').removeClass('flex');
    });
    // Optional: Modal schließen bei Klick auf Hintergrund
    $('#athena-ai-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).addClass('hidden').removeClass('flex');
        }
    });

    // Create Content Button: POST Daten und Debug-Ausgabe
    $('#athena-ai-create-content').on('click', function() {
        var pageId = $('#athena-ai-page-select').val();
        var extraInfo = $('#athena-ai-modal-extra-info').val();
        var modelProvider = $('input[name="athena-ai-model-provider"]:checked').val();
        var debugField = $('#athena-ai-modal-debug');
        
        // Page selection is now optional, only check for extra info
        if (!extraInfo.trim()) {
            alert('Bitte gib zusätzliche Informationen ein');
            return;
        }
        
        debugField.html('<div class="p-3 text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><div class="mt-2">AI-Antwort wird generiert...</div></div>').show();
        
        // Prompt zusammensetzen
        var promptIntro = $('#athena-ai-prompt-intro').val();
        var promptLimit = $('#athena-ai-prompt-limit').val();
        var fullPrompt = promptIntro + '\n\n' + extraInfo + '\n\n' + promptLimit;
        
        // Prüfen, ob "Nur Output testen" aktiviert ist
        var testOnly = $('#athena-ai-test-only').is(':checked');
        
        if (testOnly) {
            // Nur Debug-Informationen anzeigen, keine API-Anfrage senden
            var debugInfo = 'Test-Modus aktiviert. Keine API-Anfrage gesendet.\n\n' +
                           'Ausgewählte Seite: ' + (pageId ? 'ID: ' + pageId : 'Keine') + '\n' +
                           'Zusätzliche Informationen: ' + extraInfo + '\n' +
                           'KI-Anbieter: ' + modelProvider + '\n\n' +
                           'Generierter Prompt:\n' + fullPrompt;
            
            var htmlOutput = '<div class="debug-info bg-gray-100 p-3 mb-4 text-xs font-mono overflow-auto" style="max-height: 200px;">' + 
                             '<strong>Debug-Informationen (Test-Modus):</strong><pre>' + debugInfo + '</pre></div>';
            
            debugField.html(htmlOutput);
            return;
        }
        
        $.post(ajaxurl, {
            action: 'athena_ai_modal_debug',
            page_id: pageId,
            extra_info: extraInfo,
            model_provider: modelProvider,
            custom_prompt: fullPrompt
        }, function(response) {
            // Teile die Antwort auf, um den Debug-Teil vom OpenAI-Teil zu trennen
            var parts = response.split('--- OPENAI ANTWORT ---');
            var debugInfo = parts[0];
            var aiResponse = parts.length > 1 ? parts[1] : '';
            
            var htmlOutput = '<div class="debug-info bg-gray-100 p-3 mb-4 text-xs font-mono overflow-auto" style="max-height: 200px;">' + 
                             '<strong>Debug-Informationen:</strong><pre>' + debugInfo + '</pre></div>';
                             
            if (aiResponse) {
                // Prüfe, ob es sich um eine spezielle Fehlermeldung handelt (beginnt mit ###)
                if (aiResponse.trim().startsWith('###')) {
                    // Entferne die ### Markierung
                    var errorText = aiResponse.trim().substring(3).trim();
                    
                    htmlOutput += '<div class="ai-response">' +
                                  '<h3 class="text-xl font-bold mb-2 text-red-600">OpenAI Fehler:</h3>' +
                                  '<div class="bg-red-50 p-4 border border-red-300 rounded shadow-sm overflow-auto text-red-700" style="max-height: 400px;">' +
                                  errorText.replace(/\n/g, '<br>') +
                                  '</div></div>';
                } else {
                    // Normale AI-Antwort
                    htmlOutput += '<div class="ai-response">' +
                                  '<h3 class="text-xl font-bold mb-2">OpenAI Antwort:</h3>' +
                                  '<div class="bg-white p-4 border border-gray-300 rounded shadow-sm overflow-auto" style="max-height: 400px;">' +
                                  aiResponse.replace(/\n/g, '<br>') +
                                  '</div></div>';
                }
            }
            
            debugField.html(htmlOutput);
        }).fail(function(xhr, textStatus, errorThrown) {
            debugField.html('<div class="p-3 bg-red-100 text-red-800 border border-red-300 rounded">' +
                           '<strong>Fehler:</strong> Die Anfrage konnte nicht verarbeitet werden. ' +
                           textStatus + ' ' + errorThrown + '</div>');
        });
    });
});
</script>
