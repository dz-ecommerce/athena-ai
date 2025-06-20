<?php
/**
 * Template für die Athena AI Profile-Seite
 */

// Verhindere direkten Zugriff
if (!defined('ABSPATH')) {
    exit;
}

// Lade Component Helper Funktionen
include_once ATHENA_AI_PLUGIN_DIR . 'templates/admin/components/component-helpers.php';
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
        <button type="button" id="athena-full-assistant-btn" 
                class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white px-6 py-3 rounded-lg font-medium flex items-center space-x-2 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
            <i class="fas fa-magic"></i>
            <span><?php esc_html_e('Athena AI Full Assistent', 'athena-ai'); ?></span>
        </button>
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

                <?php include ATHENA_AI_PLUGIN_DIR . 'templates/admin/sections/company-profile-section.php'; ?>

                <?php include ATHENA_AI_PLUGIN_DIR . 'templates/admin/sections/products-services-section.php'; ?>

                <?php include ATHENA_AI_PLUGIN_DIR . 'templates/admin/sections/target-audience-section.php'; ?>

                <?php include ATHENA_AI_PLUGIN_DIR . 'templates/admin/sections/company-values-section.php'; ?>

                <?php include ATHENA_AI_PLUGIN_DIR . 'templates/admin/sections/expertise-section.php'; ?>

                <?php include ATHENA_AI_PLUGIN_DIR . 'templates/admin/sections/keywords-section.php'; ?>

                <?php include ATHENA_AI_PLUGIN_DIR . 'templates/admin/sections/additional-info-section.php'; ?>
            </div>

            
            <?php submit_button(__('Einstellungen speichern', 'athena-ai'), 'primary', 'submit', false, ['class' => 'bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 text-white rounded-lg px-4 py-2']); ?>
        </form>
    </div>
</div>

<!-- AI Modals für alle Prompt-Typen -->
<?php 
// Alle verfügbaren Prompt-Typen aus der YAML-Konfiguration
$prompt_types = [
    'company_description',
    'products', 
    'company_values',
    'target_audience',
    'company_usps',
    'expertise_areas',
    'seo_keywords'
];

// Rendere nur die Modals ohne Buttons (Buttons sind bereits in den Sections)
foreach ($prompt_types as $prompt_type) {
    $args = [
        'modal_type' => $prompt_type,
        'modal_id' => 'athena-ai-' . $prompt_type . '-modal'
    ];
    athena_ai_render_modal_html($args);
}
?>

<!-- Debug-Bereich (nur für Entwicklung) -->
<?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
<div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded">
    <h3 class="text-lg font-semibold mb-2">🔧 Debug-Informationen</h3>
    <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
            <strong>Prompt-Typen:</strong>
            <ul class="list-disc list-inside">
                <?php foreach ($prompt_types as $type): ?>
                    <li><?php echo esc_html($type); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>
            <strong>JavaScript-Tests:</strong>
            <button type="button" onclick="athenaAIDebugFloatingLabels()" class="bg-blue-500 text-white px-2 py-1 rounded text-xs">Test Floating Labels</button>
            <button type="button" onclick="console.log('Prompt Manager:', window.athenaAiPromptManager)" class="bg-green-500 text-white px-2 py-1 rounded text-xs">Test Prompt Manager</button>
            <button type="button" onclick="athenaAIDebugScripts()" class="bg-red-500 text-white px-2 py-1 rounded text-xs">Check Scripts</button>
        </div>
    </div>
    
    <!-- DEBUG: Zeige ob Scripts geladen werden -->
    <div class="mt-4 p-3 bg-blue-50 border">
        <strong>Script Loading Status:</strong><br>
        Hook Suffix: <?php echo esc_html($_GET['page'] ?? 'unknown'); ?><br>
        Current Screen: <?php echo esc_html(get_current_screen()->id ?? 'unknown'); ?><br>
        Scripts should be loaded: <?php echo wp_script_is('athena-ai-profile-modals', 'enqueued') ? 'YES' : 'NO'; ?>
    </div>
</div>

<script>
function athenaAIDebugScripts() {
    console.log('=== ATHENA AI DEBUG ===');
    console.log('jQuery loaded:', typeof jQuery !== 'undefined' ? 'YES' : 'NO');
    console.log('Modals script loaded:', typeof window.athenaAiModalsLoaded !== 'undefined' ? 'YES' : 'NO');
    console.log('Prompt Config available:', typeof window.athenaAiPromptConfig !== 'undefined' ? 'YES' : 'NO');
    console.log('AI-Buttons found:', jQuery('[data-modal-target]').length);
    console.log('Modals found:', jQuery('.fixed.hidden').length);
    
    // Test AI-Button Click
    if (jQuery('[data-modal-target]').length > 0) {
        console.log('Testing first AI-Button...');
        jQuery('[data-modal-target]').first().trigger('click');
    }
}
</script>
<?php endif; ?>

<!-- Full Assistant Functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fullAssistantBtn = document.getElementById('athena-full-assistant-btn');
    if (fullAssistantBtn) {
        fullAssistantBtn.addEventListener('click', function() {
            executeFullAssistant();
        });
    }
});

function executeFullAssistant() {
    const btn = document.getElementById('athena-full-assistant-btn');
    const originalText = btn.innerHTML;
    
    // Show loading state
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Generiere alle Inhalte...</span>';
    btn.disabled = true;
    
    // Set default values for dropdowns and radio buttons first
    setDefaultFormValues();
    
    // Execute all AI prompts in sequence
    const promptSequence = [
        'company_description',
        'products',
        'company_usps', 
        'target_audience',
        'company_values',
        'expertise_areas',
        'seo_keywords'
    ];
    
    executePromptSequence(promptSequence, 0, function() {
        // Reset button state
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        // Show success message
        showSuccessMessage('Alle Profilinhalte wurden erfolgreich generiert!');
    });
}

function setDefaultFormValues() {
    // Set company name if empty
    const companyNameField = document.getElementById('company_name');
    if (companyNameField && !companyNameField.value.trim()) {
        companyNameField.value = 'Muster GmbH';
        triggerFloatingLabelUpdate(companyNameField);
    }
    
    // Set industry
    const industryField = document.getElementById('company_industry');
    if (industryField && !industryField.value) {
        industryField.value = 'it_services';
        triggerFloatingLabelUpdate(industryField);
    }
    
    // Set preferred tone radio button
    const toneInformal = document.querySelector('input[name="athena_ai_profiles[preferred_tone]"][value="informal"]');
    if (toneInformal && !isAnyRadioChecked('athena_ai_profiles[preferred_tone]')) {
        toneInformal.checked = true;
    }
    
    // Set age group checkboxes
    const ageGroups = ['26-35', '36-45'];
    ageGroups.forEach(age => {
        const checkbox = document.querySelector(`input[name="athena_ai_profiles[age_group][]"][value="${age}"]`);
        if (checkbox && !isAnyCheckboxChecked('athena_ai_profiles[age_group]')) {
            checkbox.checked = true;
        }
    });
    
    // Set tonality checkboxes
    const tonalities = ['professional', 'friendly', 'informative'];
    tonalities.forEach(tone => {
        const checkbox = document.querySelector(`input[name="athena_ai_profiles[tonality][]"][value="${tone}"]`);
        if (checkbox && !isAnyCheckboxChecked('athena_ai_profiles[tonality]')) {
            checkbox.checked = true;
        }
    });
}

function isAnyRadioChecked(name) {
    const radios = document.querySelectorAll(`input[name="${name}"]:checked`);
    return radios.length > 0;
}

function isAnyCheckboxChecked(name) {
    const checkboxes = document.querySelectorAll(`input[name="${name}[]"]:checked`);
    return checkboxes.length > 0;
}

function triggerFloatingLabelUpdate(field) {
    // Trigger events to update floating labels
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('focus', { bubbles: true }));
    field.dispatchEvent(new Event('blur', { bubbles: true }));
}

function executePromptSequence(prompts, index, callback) {
    if (index >= prompts.length) {
        callback();
        return;
    }
    
    const promptType = prompts[index];
    const targetField = getTargetFieldForPrompt(promptType);
    
    if (!targetField) {
        executePromptSequence(prompts, index + 1, callback);
        return;
    }
    
    // Skip if field already has content
    if (targetField.value.trim()) {
        executePromptSequence(prompts, index + 1, callback);
        return;
    }
    
    // Show progress
    updateProgressMessage(`Generiere ${getPromptDisplayName(promptType)}... (${index + 1}/${prompts.length})`);
    
    // Execute AI prompt
    executeAIPrompt(promptType, targetField, function(success) {
        if (success) {
            // Wait a moment before next prompt to avoid overwhelming the API
            setTimeout(() => {
                executePromptSequence(prompts, index + 1, callback);
            }, 1000);
        } else {
            executePromptSequence(prompts, index + 1, callback);
        }
    });
}

function getTargetFieldForPrompt(promptType) {
    const fieldMap = {
        'company_description': 'company_description',
        'products': 'company_products',
        'company_usps': 'company_usps',
        'target_audience': 'target_audience',
        'company_values': 'company_values',
        'expertise_areas': 'expertise_areas',
        'seo_keywords': 'seo_keywords'
    };
    
    const fieldId = fieldMap[promptType];
    return fieldId ? document.getElementById(fieldId) : null;
}

function getPromptDisplayName(promptType) {
    const displayNames = {
        'company_description': 'Unternehmensbeschreibung',
        'products': 'Produkte & Dienstleistungen',
        'company_usps': 'Alleinstellungsmerkmale',
        'target_audience': 'Zielgruppe',
        'company_values': 'Unternehmenswerte',
        'expertise_areas': 'Expertise-Bereiche',
        'seo_keywords': 'SEO-Keywords'
    };
    
    return displayNames[promptType] || promptType;
}

function updateProgressMessage(message) {
    const btn = document.getElementById('athena-full-assistant-btn');
    if (btn) {
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i><span>${message}</span>`;
    }
}

function executeAIPrompt(promptType, targetField, callback) {
    // Get AI provider
    const aiProvider = localStorage.getItem('athena_ai_provider') || 'openai';
    const testMode = localStorage.getItem('athena_ai_test_mode') === 'true';
    
    // Collect profile data for context
    const profileData = collectProfileData();
    
    if (testMode) {
        // Use demo content for testing
        const demoContent = getDemoContent(promptType);
        targetField.value = demoContent;
        triggerFloatingLabelUpdate(targetField);
        callback(true);
        return;
    }
    
    // Real AI request
    const requestData = {
        action: 'athena_ai_generate_content',
        nonce: window.athenaAiAjax?.nonce || '',
        prompt_type: promptType,
        provider: aiProvider,
        profile_data: profileData
    };
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', window.athenaAiAjax?.ajaxurl || '/wp-admin/admin-ajax.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success && response.data) {
                        targetField.value = response.data;
                        triggerFloatingLabelUpdate(targetField);
                        callback(true);
                    } else {
                        console.error('AI Error:', response.data || 'Unknown error');
                        // Use demo content as fallback
                        targetField.value = getDemoContent(promptType);
                        triggerFloatingLabelUpdate(targetField);
                        callback(true);
                    }
                } catch (e) {
                    console.error('Response parsing error:', e);
                    callback(false);
                }
            } else {
                console.error('HTTP Error:', xhr.status);
                callback(false);
            }
        }
    };
    
    // Convert object to URL-encoded string
    const params = Object.keys(requestData)
        .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(
            typeof requestData[key] === 'object' ? JSON.stringify(requestData[key]) : requestData[key]
        ))
        .join('&');
    
    xhr.send(params);
}

function collectProfileData() {
    const data = {};
    
    // Collect all form fields
    const fields = [
        'company_name', 'company_industry', 'company_description',
        'company_products', 'company_usps', 'target_audience',
        'company_values', 'expertise_areas', 'certifications',
        'seo_keywords', 'avoided_topics'
    ];
    
    fields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            data[fieldId] = field.value;
        }
    });
    
    // Collect radio button values
    const preferredTone = document.querySelector('input[name="athena_ai_profiles[preferred_tone]"]:checked');
    if (preferredTone) data.preferred_tone = preferredTone.value;
    
    // Collect checkbox values
    const ageGroups = Array.from(document.querySelectorAll('input[name="athena_ai_profiles[age_group][]"]:checked'))
        .map(cb => cb.value);
    if (ageGroups.length) data.age_group = ageGroups;
    
    const tonalities = Array.from(document.querySelectorAll('input[name="athena_ai_profiles[tonality][]"]:checked'))
        .map(cb => cb.value);
    if (tonalities.length) data.tonality = tonalities;
    
    return data;
}

function getDemoContent(promptType) {
    const demoContents = {
        'company_description': 'Wir sind ein innovatives IT-Unternehmen, das sich auf maßgeschneiderte Softwarelösungen und digitale Transformation spezialisiert hat. Mit über 10 Jahren Erfahrung unterstützen wir Unternehmen dabei, ihre Geschäftsprozesse zu optimieren und erfolgreich in der digitalen Welt zu agieren.',
        'products': 'Webentwicklung, Mobile Apps, Cloud-Lösungen, E-Commerce Plattformen, CRM-Systeme, Datenanalyse-Tools',
        'company_usps': 'Agile Entwicklungsmethoden, 24/7 Support, Kostenlose Beratung, Langjährige Erfahrung, Individuelle Lösungen',
        'target_audience': 'Mittelständische Unternehmen aus verschiedenen Branchen, die ihre digitalen Prozesse modernisieren möchten. Unsere Kunden schätzen persönliche Betreuung und nachhaltige Lösungen.',
        'company_values': 'Innovation\nKundenorientierung\nNachhaltigkeit',
        'expertise_areas': 'PHP/Laravel Development\nReact/Vue.js Frontend\nAWS Cloud Architecture\nDatabase Design\nAPI Integration\nSEO Optimierung',
        'seo_keywords': 'Webentwicklung\nSoftware Entwicklung\nDigitale Transformation\nIT Beratung\nCloud Lösungen'
    };
    
    return demoContents[promptType] || `Generierter Inhalt für ${promptType}`;
}

function showSuccessMessage(message) {
    // Create or update success notification
    let notification = document.getElementById('athena-full-assistant-success');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'athena-full-assistant-success';
        notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300';
        document.body.appendChild(notification);
    }
    
    notification.innerHTML = `
        <div class="flex items-center space-x-2">
            <i class="fas fa-check-circle"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Show notification
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Hide after 4 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 4000);
}
</script>

<!-- Beispiel für einzelne Modal-Erstellung (falls benötigt) -->
<?php 
/*
// Einzelne Modals können auch so erstellt werden:
athena_ai_render_modal('company_description');
athena_ai_render_modal('products');
athena_ai_render_modal('company_values');
// etc.
*/
?>


