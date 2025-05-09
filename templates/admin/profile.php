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
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">
                <?php esc_html_e('Profile Settings', 'athena-ai'); ?>
            </h2>
            <p class="text-gray-600">
                <?php esc_html_e('Here you can customize your Athena AI profiles and manage their related settings.', 'athena-ai'); ?>
            </p>
        </div>

        <form method="post" action="options.php" class="space-y-6">
            <?php
            settings_fields('athena_ai_profile_settings');
            do_settings_sections('athena_ai_profile_settings');
            ?>

            <!-- Profile List Section -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-700 mb-4">
                    <?php esc_html_e('Available Profiles', 'athena-ai'); ?>
                </h3>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <?php esc_html_e('Profile Name', 'athena-ai'); ?>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <?php esc_html_e('Description', 'athena-ai'); ?>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <?php esc_html_e('Status', 'athena-ai'); ?>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <?php esc_html_e('Actions', 'athena-ai'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <!-- Beispiel-Profil Zeile -->
                            <tr>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">Default</div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-sm text-gray-500">Standard Profile für Feed-Verarbeitung</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        <?php esc_html_e('Active', 'athena-ai'); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="#" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <?php esc_html_e('Edit', 'athena-ai'); ?>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add New Profile Section -->
            <div class="mb-8 border-t pt-6">
                <h3 class="text-lg font-medium text-gray-700 mb-4">
                    <?php esc_html_e('Add New Profile', 'athena-ai'); ?>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="profile_name" class="block text-sm font-medium text-gray-700 mb-1">
                            <?php esc_html_e('Profile Name', 'athena-ai'); ?>
                        </label>
                        <input type="text" name="profile_name" id="profile_name" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="<?php esc_attr_e('Enter profile name', 'athena-ai'); ?>">
                    </div>
                    <div>
                        <label for="profile_description" class="block text-sm font-medium text-gray-700 mb-1">
                            <?php esc_html_e('Description', 'athena-ai'); ?>
                        </label>
                        <input type="text" name="profile_description" id="profile_description" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="<?php esc_attr_e('Enter description', 'athena-ai'); ?>">
                    </div>
                </div>
            </div>

            <!-- Profile Settings Section -->
            <div class="mb-8 border-t pt-6">
                <h3 class="text-lg font-medium text-gray-700 mb-4">
                    <?php esc_html_e('Profile Settings', 'athena-ai'); ?>
                </h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="default_profile" class="block text-sm font-medium text-gray-700 mb-1">
                            <?php esc_html_e('Default Profile', 'athena-ai'); ?>
                        </label>
                        <select name="default_profile" id="default_profile" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="default">Default</option>
                        </select>
                        <p class="mt-2 text-sm text-gray-500">
                            <?php esc_html_e('This profile will be used as the default for new feeds.', 'athena-ai'); ?>
                        </p>
                    </div>
                </div>
            </div>

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
                            <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php esc_html_e('Firmenname', 'athena-ai'); ?>
                            </label>
                            <input type="text" name="company_name" id="company_name" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label for="company_industry" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php esc_html_e('Branche', 'athena-ai'); ?>
                            </label>
                            <select name="company_industry" id="company_industry" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value=""><?php esc_html_e('Bitte auswählen', 'athena-ai'); ?></option>
                                <option value="it"><?php esc_html_e('IT & Software', 'athena-ai'); ?></option>
                                <option value="finance"><?php esc_html_e('Finanzen & Versicherung', 'athena-ai'); ?></option>
                                <option value="retail"><?php esc_html_e('Einzelhandel', 'athena-ai'); ?></option>
                                <option value="manufacturing"><?php esc_html_e('Produktion & Fertigung', 'athena-ai'); ?></option>
                                <option value="healthcare"><?php esc_html_e('Gesundheitswesen', 'athena-ai'); ?></option>
                                <option value="education"><?php esc_html_e('Bildung & Forschung', 'athena-ai'); ?></option>
                                <option value="media"><?php esc_html_e('Medien & Kommunikation', 'athena-ai'); ?></option>
                                <option value="other"><?php esc_html_e('Sonstige', 'athena-ai'); ?></option>
                            </select>
                        </div>
                        <div>
                            <label for="company_founded" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php esc_html_e('Gründungsjahr', 'athena-ai'); ?>
                            </label>
                            <input type="number" name="company_founded" id="company_founded" min="1900" max="<?php echo date('Y'); ?>" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                        <div class="md:col-span-2">
                            <label for="company_description" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php esc_html_e('Kurzbeschreibung des Unternehmens', 'athena-ai'); ?>
                            </label>
                            <textarea name="company_description" id="company_description" rows="3" maxlength="500" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
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
                            <label for="company_products" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php esc_html_e('Hauptprodukte/Dienstleistungen', 'athena-ai'); ?>
                            </label>
                            <textarea name="company_products" id="company_products" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                            <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Maximal 3 Einträge, je ein Eintrag pro Zeile', 'athena-ai'); ?></p>
                        </div>
                        <div>
                            <label for="company_usps" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php esc_html_e('Alleinstellungsmerkmale', 'athena-ai'); ?>
                            </label>
                            <textarea name="company_usps" id="company_usps" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
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
                            <label for="target_audience" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php esc_html_e('Zielgruppenbeschreibung', 'athena-ai'); ?>
                            </label>
                            <textarea name="target_audience" id="target_audience" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <?php esc_html_e('Altersgruppe der Zielkunden', 'athena-ai'); ?>
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2">
                                <div class="flex items-center">
                                    <input type="checkbox" name="age_group[]" id="age_group_18_25" value="18-25" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                    <label for="age_group_18_25" class="ml-2 block text-sm text-gray-700">18-25</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="age_group[]" id="age_group_26_35" value="26-35" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                    <label for="age_group_26_35" class="ml-2 block text-sm text-gray-700">26-35</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="age_group[]" id="age_group_36_45" value="36-45" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                    <label for="age_group_36_45" class="ml-2 block text-sm text-gray-700">36-45</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="age_group[]" id="age_group_46_60" value="46-60" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                    <label for="age_group_46_60" class="ml-2 block text-sm text-gray-700">46-60</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="age_group[]" id="age_group_60_plus" value="60+" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                    <label for="age_group_60_plus" class="ml-2 block text-sm text-gray-700">60+</label>
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
                            <label for="company_values" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php esc_html_e('Wichtigste Unternehmenswerte', 'athena-ai'); ?>
                            </label>
                            <textarea name="company_values" id="company_values" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
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
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                <div class="flex items-center">
                                    <input type="checkbox" name="tonality[]" id="tonality_professional" value="professional" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                    <label for="tonality_professional" class="ml-2 block text-sm text-gray-700">Professionell</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="tonality[]" id="tonality_friendly" value="friendly" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                    <label for="tonality_friendly" class="ml-2 block text-sm text-gray-700">Freundlich</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="tonality[]" id="tonality_humorous" value="humorous" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                    <label for="tonality_humorous" class="ml-2 block text-sm text-gray-700">Humorvoll</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="tonality[]" id="tonality_informative" value="informative" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                    <label for="tonality_informative" class="ml-2 block text-sm text-gray-700">Informativ</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="tonality[]" id="tonality_authoritative" value="authoritative" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                    <label for="tonality_authoritative" class="ml-2 block text-sm text-gray-700">Autoritativ</label>
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
                            <label for="expertise_areas" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php esc_html_e('Fachgebiete', 'athena-ai'); ?>
                            </label>
                            <textarea name="expertise_areas" id="expertise_areas" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                            <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Stichpunkte, ein Eintrag pro Zeile', 'athena-ai'); ?></p>
                        </div>
                        <div>
                            <label for="certifications" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php esc_html_e('Besondere Qualifikationen oder Zertifizierungen', 'athena-ai'); ?>
                            </label>
                            <textarea name="certifications" id="certifications" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                        </div>
                    </div>
                </fieldset>

                <!-- Wichtige Keywords -->
                <fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
                    <legend class="text-md font-medium text-gray-700 px-2">
                        <?php esc_html_e('Wichtige Keywords', 'athena-ai'); ?>
                    </legend>
                    <div>
                        <label for="seo_keywords" class="block text-sm font-medium text-gray-700 mb-1">
                            <?php esc_html_e('SEO-Keywords', 'athena-ai'); ?>
                        </label>
                        <textarea name="seo_keywords" id="seo_keywords" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                        <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Maximal 5 Begriffe, je ein Begriff pro Zeile', 'athena-ai'); ?></p>
                    </div>
                </fieldset>

                <!-- Zusätzliche Informationen -->
                <fieldset class="mb-6 border border-gray-200 rounded-lg p-5">
                    <legend class="text-md font-medium text-gray-700 px-2">
                        <?php esc_html_e('Zusätzliche Informationen', 'athena-ai'); ?>
                    </legend>
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="avoided_topics" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php esc_html_e('Themen, die vermieden werden sollen', 'athena-ai'); ?>
                            </label>
                            <textarea name="avoided_topics" id="avoided_topics" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                            <p class="mt-1 text-sm text-gray-500"><?php esc_html_e('Optional', 'athena-ai'); ?></p>
                        </div>
                        <div>
                            <label for="preferred_cta" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php esc_html_e('Gewünschte CTA', 'athena-ai'); ?>
                            </label>
                            <select name="preferred_cta" id="preferred_cta" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value=""><?php esc_html_e('Bitte auswählen', 'athena-ai'); ?></option>
                                <option value="contact"><?php esc_html_e('Kontaktaufnahme', 'athena-ai'); ?></option>
                                <option value="newsletter"><?php esc_html_e('Newsletter-Anmeldung', 'athena-ai'); ?></option>
                                <option value="demo"><?php esc_html_e('Produkt-Demo', 'athena-ai'); ?></option>
                                <option value="consultation"><?php esc_html_e('Beratungsgespräch', 'athena-ai'); ?></option>
                                <option value="other"><?php esc_html_e('Sonstiges', 'athena-ai'); ?></option>
                            </select>
                        </div>
                    </div>
                </fieldset>
            </div>

            <!-- Form Fields Showcase -->
            <?php
            // Füge die Form-Showcase-Dateien ein
            require_once(ATHENA_AI_PLUGIN_DIR . 'templates/admin/form-fields-showcase.php');
            require_once(ATHENA_AI_PLUGIN_DIR . 'templates/admin/form-fields-showcase-part2.php');
            require_once(ATHENA_AI_PLUGIN_DIR . 'templates/admin/form-fields-showcase-part3.php');
            require_once(ATHENA_AI_PLUGIN_DIR . 'templates/admin/form-fields-showcase-part4.php');
            // JavaScript für die Formulare
            require_once(ATHENA_AI_PLUGIN_DIR . 'templates/admin/form-fields-showcase-js.php');
            ?>
            
            <?php submit_button(__('Save Profile Settings', 'athena-ai'), 'primary', 'submit', false, ['class' => 'bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 text-white rounded-lg px-4 py-2']); ?>
        </form>
    </div>
</div>
