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
                            <input type="text" name="company_name" id="company_name" placeholder="<?php esc_attr_e('Firmenname', 'athena-ai'); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4">
                        </div>
                        <div>
                            <select name="company_industry" id="company_industry" class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-3 pr-10 py-2.5 px-4 border-gray-300 rounded-md">
                                <option value="" disabled selected><?php esc_html_e('Branche auswählen', 'athena-ai'); ?></option>
                                <optgroup label="<?php esc_attr_e('Dienstleistungen', 'athena-ai'); ?>">
                                    <option value="accounting">Buchhaltung & Steuern</option>
                                    <option value="advertising">Werbung & Marketing</option>
                                    <option value="consulting">Unternehmensberatung</option>
                                    <option value="financial">Finanzdienstleistungen</option>
                                    <option value="insurance">Versicherungen</option>
                                    <option value="legal">Rechtsberatung</option>
                                    <option value="real_estate">Immobilien</option>
                                </optgroup>
                                <optgroup label="<?php esc_attr_e('IT & Technologie', 'athena-ai'); ?>">
                                    <option value="it_services">IT-Dienstleistungen</option>
                                    <option value="software">Softwareentwicklung</option>
                                    <option value="web_design">Webdesign & -entwicklung</option>
                                    <option value="ecommerce">E-Commerce</option>
                                    <option value="telecommunications">Telekommunikation</option>
                                    <option value="data_analytics">Datenanalyse</option>
                                    <option value="cloud_computing">Cloud Computing</option>
                                    <option value="cybersecurity">IT-Sicherheit</option>
                                </optgroup>
                                <optgroup label="<?php esc_attr_e('Handel & Einzelhandel', 'athena-ai'); ?>">
                                    <option value="retail">Einzelhandel</option>
                                    <option value="wholesale">Großhandel</option>
                                    <option value="ecommerce_retail">Online-Handel</option>
                                    <option value="consumer_goods">Konsumgüter</option>
                                    <option value="food_retail">Lebensmittelhandel</option>
                                    <option value="fashion">Mode & Bekleidung</option>
                                    <option value="electronics_retail">Elektronik</option>
                                    <option value="furniture">Möbel & Einrichtung</option>
                                </optgroup>
                                <optgroup label="<?php esc_attr_e('Produktion & Fertigung', 'athena-ai'); ?>">
                                    <option value="manufacturing">Fertigungsindustrie</option>
                                    <option value="automotive">Automobilindustrie</option>
                                    <option value="aerospace">Luft- und Raumfahrt</option>
                                    <option value="electronics">Elektronik & Elektrotechnik</option>
                                    <option value="chemicals">Chemische Industrie</option>
                                    <option value="pharma">Pharmazeutische Industrie</option>
                                    <option value="machinery">Maschinenbau</option>
                                    <option value="textiles">Textilindustrie</option>
                                </optgroup>
                                <optgroup label="<?php esc_attr_e('Gesundheitswesen', 'athena-ai'); ?>">
                                    <option value="healthcare">Gesundheitswesen</option>
                                    <option value="medical_practice">Arztpraxis</option>
                                    <option value="hospital">Krankenhaus</option>
                                    <option value="biotech">Biotechnologie</option>
                                    <option value="medical_devices">Medizintechnik</option>
                                    <option value="pharmaceutical">Pharmaindustrie</option>
                                    <option value="healthcare_services">Gesundheitsdienstleistungen</option>
                                    <option value="eldercare">Altenpflege</option>
                                </optgroup>
                                <optgroup label="<?php esc_attr_e('Bildung & Forschung', 'athena-ai'); ?>">
                                    <option value="education">Bildungseinrichtungen</option>
                                    <option value="school">Schulen</option>
                                    <option value="university">Hochschulen & Universitäten</option>
                                    <option value="vocational_training">Berufsbildung</option>
                                    <option value="research">Forschungseinrichtungen</option>
                                    <option value="e_learning">E-Learning & Online-Bildung</option>
                                </optgroup>
                                <optgroup label="<?php esc_attr_e('Weitere Branchen', 'athena-ai'); ?>">
                                    <option value="agriculture">Landwirtschaft</option>
                                    <option value="architecture">Architektur & Ingenieurwesen</option>
                                    <option value="art">Kunst & Design</option>
                                    <option value="beauty">Schönheit & Kosmetik</option>
                                    <option value="construction">Bauwesen</option>
                                    <option value="energy">Energie & Versorgung</option>
                                    <option value="entertainment">Unterhaltung & Freizeit</option>
                                    <option value="food">Gastronomie & Lebensmittel</option>
                                    <option value="hospitality">Hotellerie & Gastgewerbe</option>
                                    <option value="media">Medien & Kommunikation</option>
                                    <option value="transport">Transport & Logistik</option>
                                    <option value="travel">Tourismus & Reisen</option>
                                    <option value="other">Sonstige</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <textarea name="company_description" id="company_description" rows="3" maxlength="500" placeholder="<?php esc_attr_e('Kurzbeschreibung des Unternehmens', 'athena-ai'); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4"></textarea>
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
                            <textarea name="company_products" id="company_products" rows="3" placeholder="<?php esc_attr_e('Hauptprodukte/Dienstleistungen', 'athena-ai'); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4"></textarea>
                            <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Maximal 3 Einträge, je ein Eintrag pro Zeile', 'athena-ai'); ?></p>
                        </div>
                        <div>
                            <textarea name="company_usps" id="company_usps" rows="3" placeholder="<?php esc_attr_e('Alleinstellungsmerkmale', 'athena-ai'); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4"></textarea>
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
                            <div class="flex space-x-4">
                                <div class="flex items-center">
                                    <input type="radio" name="customer_type" id="customer_type_b2b" value="b2b" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                    <label for="customer_type_b2b" class="ml-2 block text-sm text-gray-700">B2B</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="radio" name="customer_type" id="customer_type_b2c" value="b2c" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                    <label for="customer_type_b2c" class="ml-2 block text-sm text-gray-700">B2C</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="radio" name="customer_type" id="customer_type_both" value="both" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300">
                                    <label for="customer_type_both" class="ml-2 block text-sm text-gray-700">Beides</label>
                                </div>
                            </div>
                        </div>
                        <div>
                            <textarea name="target_audience" id="target_audience" rows="3" placeholder="<?php esc_attr_e('Zielgruppenbeschreibung', 'athena-ai'); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <?php esc_html_e('Altersgruppe der Zielkunden', 'athena-ai'); ?>
                            </label>
                            <div class="flex flex-wrap -mx-2">
                                <div class="px-2 py-1.5 flex items-center">
                                    <div class="relative flex items-start">
                                        <div class="flex items-center h-5">
                                            <input type="checkbox" name="age_group[]" id="age_group_18_25" value="18-25" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded">
                                        </div>
                                        <div class="ml-2.5 text-sm">
                                            <label for="age_group_18_25" class="font-medium text-gray-700">18-25</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-2 py-1.5 flex items-center">
                                    <div class="relative flex items-start">
                                        <div class="flex items-center h-5">
                                            <input type="checkbox" name="age_group[]" id="age_group_26_35" value="26-35" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded">
                                        </div>
                                        <div class="ml-2.5 text-sm">
                                            <label for="age_group_26_35" class="font-medium text-gray-700">26-35</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-2 py-1.5 flex items-center">
                                    <div class="relative flex items-start">
                                        <div class="flex items-center h-5">
                                            <input type="checkbox" name="age_group[]" id="age_group_36_45" value="36-45" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded">
                                        </div>
                                        <div class="ml-2.5 text-sm">
                                            <label for="age_group_36_45" class="font-medium text-gray-700">36-45</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="px-2 py-1.5 flex items-center">
                                    <div class="relative flex items-start">
                                        <div class="flex items-center h-5">
                                            <input type="checkbox" name="age_group[]" id="age_group_46_60" value="46-60" class="focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300 rounded">
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
                            <textarea name="company_values" id="company_values" rows="3" placeholder="<?php esc_attr_e('Wichtigste Unternehmenswerte', 'athena-ai'); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4"></textarea>
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
                            <textarea name="expertise_areas" id="expertise_areas" rows="3" placeholder="<?php esc_attr_e('Fachgebiete', 'athena-ai'); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4"></textarea>
                            <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Stichpunkte, ein Eintrag pro Zeile', 'athena-ai'); ?></p>
                        </div>
                        <div>
                            <textarea name="certifications" id="certifications" rows="3" placeholder="<?php esc_attr_e('Besondere Qualifikationen oder Zertifizierungen', 'athena-ai'); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4"></textarea>
                        </div>
                    </div>
                </fieldset>

                <!-- Wichtige Keywords -->
                <fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
                    <legend class="text-md font-medium text-gray-700 px-2">
                        <?php esc_html_e('Wichtige Keywords', 'athena-ai'); ?>
                    </legend>
                    <div>
                        <textarea name="seo_keywords" id="seo_keywords" rows="3" placeholder="<?php esc_attr_e('SEO-Keywords', 'athena-ai'); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4"></textarea>
                        <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Maximal 5 Begriffe, je ein Begriff pro Zeile', 'athena-ai'); ?></p>
                    </div>
                </fieldset>

                <!-- Zusätzliche Informationen -->
                <fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
                    <legend class="text-md font-medium text-gray-700 px-2">
                        <?php esc_html_e('Zusätzliche Informationen', 'athena-ai'); ?>
                    </legend>
                    <div>
                        <textarea name="avoided_topics" id="avoided_topics" rows="3" placeholder="<?php esc_attr_e('Themen, die vermieden werden sollen', 'athena-ai'); ?>" class="focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2.5 px-4"></textarea>
                        <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Optional', 'athena-ai'); ?></p>
                    </div>
                </fieldset>
            </div>


            
            <?php submit_button(__('Save Profile Settings', 'athena-ai'), 'primary', 'submit', false, ['class' => 'bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 text-white rounded-lg px-4 py-2']); ?>
        </form>
    </div>
</div>
