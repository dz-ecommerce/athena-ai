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

            <!-- Form Fields Showcase -->
            <?php
            // Füge die Form-Showcase-Dateien ein
            require_once(ATHENA_AI_PLUGIN_DIR . 'templates/admin/form-fields-showcase.php');
            require_once(ATHENA_AI_PLUGIN_DIR . 'templates/admin/form-fields-showcase-part2.php');
            require_once(ATHENA_AI_PLUGIN_DIR . 'templates/admin/form-fields-showcase-part3.php');
            require_once(ATHENA_AI_PLUGIN_DIR . 'templates/admin/form-fields-showcase-part4.php');
            ?>
            
            <?php submit_button(__('Save Profile Settings', 'athena-ai'), 'primary', 'submit', false, ['class' => 'bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 text-white rounded-lg px-4 py-2']); ?>
        </form>
    </div>
</div>
