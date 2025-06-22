<?php
/**
 * Template für die New AI Athena Post Seite
 */

use AthenaAI\Admin\Controllers\AIPostController;

// Fehlerbehandlung für diese Seite
$previous_error_reporting = error_reporting();
error_reporting(E_ERROR);

// Initialize the controller
AIPostController::init();

// Get current step
$current_step = AIPostController::get_current_step();
$step_config = AIPostController::get_step_config();
$stored_data = AIPostController::get_stored_form_data();

// Get the current hook suffix for debugging
global $hook_suffix;
error_log("New AI Post page hook: " . $hook_suffix);

// Ensure TailwindCSS and other assets are loaded
wp_enqueue_style(
    'athena-ai-tailwind',
    ATHENA_AI_PLUGIN_URL . 'assets/css/admin.css',
    [],
    ATHENA_AI_VERSION
);

wp_enqueue_style(
    'athena-ai-google-fonts',
    'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
    [],
    ATHENA_AI_VERSION
);

wp_enqueue_style(
    'athena-ai-fontawesome',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    [],
    '6.4.0'
);
?>

<!-- Inline CSS to ensure styling works -->
<style>
/* Force Inter font family */
.wrap.athena-ai-admin {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
}

/* Ensure TailwindCSS classes work */
.athena-ai-admin .bg-white { background-color: #ffffff !important; }
.athena-ai-admin .text-2xl { font-size: 1.5rem !important; line-height: 2rem !important; }
.athena-ai-admin .font-bold { font-weight: 700 !important; }
.athena-ai-admin .text-gray-800 { color: #1f2937 !important; }
.athena-ai-admin .shadow-sm { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important; }
.athena-ai-admin .px-6 { padding-left: 1.5rem !important; padding-right: 1.5rem !important; }
.athena-ai-admin .py-5 { padding-top: 1.25rem !important; padding-bottom: 1.25rem !important; }
.athena-ai-admin .mb-6 { margin-bottom: 1.5rem !important; }
.athena-ai-admin .rounded-lg { border-radius: 0.5rem !important; }
.athena-ai-admin .border { border-width: 1px !important; }
.athena-ai-admin .border-gray-100 { border-color: #f3f4f6 !important; }
.athena-ai-admin .flex { display: flex !important; }
.athena-ai-admin .justify-between { justify-content: space-between !important; }
.athena-ai-admin .items-center { align-items: center !important; }
.athena-ai-admin .m-0 { margin: 0 !important; }
.athena-ai-admin .bg-purple-100 { background-color: #f3e8ff !important; }
.athena-ai-admin .text-purple-600 { color: #9333ea !important; }
.athena-ai-admin .p-2 { padding: 0.5rem !important; }
.athena-ai-admin .mr-3 { margin-right: 0.75rem !important; }
.athena-ai-admin .p-8 { padding: 2rem !important; }
.athena-ai-admin .max-w-4xl { max-width: 56rem !important; }
.athena-ai-admin .mx-auto { margin-left: auto !important; margin-right: auto !important; }
.athena-ai-admin .space-y-6 > :not([hidden]) ~ :not([hidden]) { margin-top: 1.5rem !important; }
.athena-ai-admin .min-h-screen { min-height: 100vh !important; }

/* Debug info styling */
.debug-info {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    border-radius: 0.375rem;
    padding: 0.75rem;
    margin-bottom: 1rem;
    font-family: monospace;
    font-size: 0.875rem;
    color: #991b1b;
}

/* Enhanced styling for the step navigation and form */
.athena-ai-admin .step-navigation {
    display: flex !important;
    justify-content: center !important;
    align-items: center !important;
    margin-bottom: 2rem !important;
    padding: 0 1rem !important;
}

.athena-ai-admin .step-navigation .step-item {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    position: relative !important;
}

.athena-ai-admin .step-navigation .step-number {
    width: 2.5rem !important;
    height: 2.5rem !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-weight: 600 !important;
    font-size: 0.875rem !important;
    margin-bottom: 0.5rem !important;
    transition: all 0.2s ease-in-out !important;
}

.athena-ai-admin .step-navigation .step-number.active {
    background-color: #9333ea !important;
    color: white !important;
}

.athena-ai-admin .step-navigation .step-number.completed {
    background-color: #22c55e !important;
    color: white !important;
}

.athena-ai-admin .step-navigation .step-number.inactive {
    background-color: #e5e7eb !important;
    color: #6b7280 !important;
}

.athena-ai-admin .step-navigation .step-text {
    font-size: 0.75rem !important;
    font-weight: 500 !important;
    text-align: center !important;
    white-space: nowrap !important;
}

.athena-ai-admin .step-navigation .step-text.active {
    color: #9333ea !important;
}

.athena-ai-admin .step-navigation .step-text.completed {
    color: #22c55e !important;
}

.athena-ai-admin .step-navigation .step-text.inactive {
    color: #6b7280 !important;
}

/* Connection lines */
.athena-ai-admin .connection-line {
    flex: 1 !important;
    height: 2px !important;
    background-color: #e5e7eb !important;
    margin: 0 0.5rem !important;
    position: relative !important;
    top: 1.25rem !important;
}

.athena-ai-admin .connection-line.completed {
    background-color: #22c55e !important;
}

/* Clickable step numbers */
.athena-ai-admin .step-navigation .step-number {
    cursor: pointer !important;
}

.athena-ai-admin .step-navigation .step-number:hover {
    transform: scale(1.1) !important;
}

.athena-ai-admin .step-navigation .step-number.clickable {
    cursor: pointer !important;
    opacity: 1 !important;
}

.athena-ai-admin .step-navigation .step-number.non-clickable {
    cursor: not-allowed !important;
    opacity: 0.5 !important;
}

/* Form actions */
.athena-ai-admin .form-actions {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding-top: 1.5rem !important;
    border-top: 1px solid #e5e7eb !important;
    margin-top: 2rem !important;
}

.athena-ai-admin .btn {
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    padding: 0.75rem 1.5rem !important;
    border-radius: 0.5rem !important;
    font-weight: 500 !important;
    text-decoration: none !important;
    transition: all 0.2s ease !important;
    border: none !important;
    cursor: pointer !important;
    font-size: 14px !important;
}

.athena-ai-admin .btn-primary {
    background: linear-gradient(to right, #9333ea, #7c3aed) !important;
    color: white !important;
}

.athena-ai-admin .btn-primary:hover {
    background: linear-gradient(to right, #7c3aed, #6d28d9) !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 12px rgba(147, 51, 234, 0.3) !important;
}

.athena-ai-admin .btn-secondary {
    background: white !important;
    color: #6b7280 !important;
    border: 1px solid #d1d5db !important;
}

.athena-ai-admin .btn-secondary:hover {
    background: #f9fafb !important;
    color: #374151 !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
}

/* Form styling */
.athena-ai-admin .content-source-options {
    max-width: 42rem !important;
    margin: 0 auto !important;
    display: flex !important;
    flex-direction: column !important;
    gap: 1rem !important;
}

.athena-ai-admin .radio-option {
    padding: 1.5rem !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 0.5rem !important;
    cursor: pointer !important;
    transition: all 0.2s ease-in-out !important;
    background-color: white !important;
}

.athena-ai-admin .radio-option:hover {
    border-color: #c084fc !important;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1) !important;
}

.athena-ai-admin .radio-option.selected {
    border-color: #9333ea !important;
    background-color: #faf5ff !important;
}

.athena-ai-admin .radio-option label {
    display: flex !important;
    align-items: flex-start !important;
    gap: 0.75rem !important;
    cursor: pointer !important;
    margin: 0 !important;
}

.athena-ai-admin .radio-option input[type="radio"] {
    margin-top: 0.125rem !important;
    width: 1.25rem !important;
    height: 1.25rem !important;
    accent-color: #9333ea !important;
}

.athena-ai-admin .radio-option .option-content h3 {
    font-size: 1.125rem !important;
    font-weight: 500 !important;
    color: #111827 !important;
    margin: 0 0 0.25rem 0 !important;
}

.athena-ai-admin .radio-option .option-content p {
    font-size: 0.875rem !important;
    color: #6b7280 !important;
    margin: 0 !important;
}

/* Center content and improve spacing */
.athena-ai-admin .step-content {
    text-align: center !important;
    margin-bottom: 2rem !important;
}

.athena-ai-admin .step-header {
    margin-bottom: 2rem !important;
}

.athena-ai-admin .step-icon {
    display: inline-flex !important;
    padding: 1rem !important;
    border-radius: 50% !important;
    margin-bottom: 1rem !important;
}

.athena-ai-admin .step-icon.blue {
    background-color: #dbeafe !important;
    color: #2563eb !important;
}

.athena-ai-admin .step-header h2 {
    font-size: 1.5rem !important;
    font-weight: 700 !important;
    color: #111827 !important;
    margin: 0 0 0.5rem 0 !important;
}

.athena-ai-admin .step-header p {
    color: #6b7280 !important;
    margin: 0 !important;
}

/* Button styling */
.athena-ai-admin .form-actions {
    display: flex !important;
    justify-content: center !important;
    margin-top: 2rem !important;
}

.athena-ai-admin .btn {
    padding: 0.75rem 1.5rem !important;
    border-radius: 0.5rem !important;
    font-weight: 500 !important;
    cursor: pointer !important;
    transition: all 0.2s ease-in-out !important;
    border: none !important;
    text-decoration: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
}

.athena-ai-admin .btn-primary {
    background-color: #9333ea !important;
    color: white !important;
}

.athena-ai-admin .btn-primary:hover {
    background-color: #7e22ce !important;
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 8px rgba(147, 51, 234, 0.3) !important;
}

/* Content type grid for step 2 */
.athena-ai-admin .content-type-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
    gap: 1rem !important;
    max-width: 42rem !important;
    margin: 0 auto !important;
}

/* Extended grid for more content types */
.athena-ai-admin .content-type-grid-extended {
    max-width: 60rem !important;
    grid-template-columns: repeat(2, 1fr) !important;
}

@media (min-width: 768px) {
    .athena-ai-admin .content-type-grid-extended {
        grid-template-columns: repeat(4, 1fr) !important;
    }
}

.athena-ai-admin .content-type-option {
    padding: 1.5rem !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 0.5rem !important;
    cursor: pointer !important;
    transition: all 0.2s ease-in-out !important;
    background-color: white !important;
    text-align: center !important;
}

.athena-ai-admin .content-type-option:hover {
    border-color: #c084fc !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
}

.athena-ai-admin .content-type-option.selected {
    border-color: #9333ea !important;
    background-color: #faf5ff !important;
}

.athena-ai-admin .content-type-option i {
    font-size: 2rem !important;
    margin-bottom: 0.75rem !important;
    color: #9333ea !important;
}

.athena-ai-admin .content-type-option h3 {
    font-size: 1.125rem !important;
    font-weight: 500 !important;
    color: #111827 !important;
    margin: 0 0 0.25rem 0 !important;
}

.athena-ai-admin .content-type-option p {
    font-size: 0.875rem !important;
    color: #6b7280 !important;
    margin: 0 !important;
}

/* Hide elements properly */
.athena-ai-admin .hidden {
    display: none !important;
}

.athena-ai-admin .block {
    display: block !important;
}
</style>

<!-- Debug Information -->
<div class="debug-info">
    <strong>Debug Info:</strong><br>
    Hook Suffix: <?php echo esc_html($hook_suffix); ?><br>
    CSS URL: <?php echo ATHENA_AI_PLUGIN_URL . 'assets/css/admin.css'; ?><br>
    Body Classes: <span id="body-classes"></span><br>
    TailwindCSS Loaded: <span id="tailwind-status">Checking...</span>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show body classes
    document.getElementById('body-classes').textContent = document.body.className;
    
    // Check if TailwindCSS is loaded
    const testElement = document.createElement('div');
    testElement.className = 'athena-ai-admin bg-red-500';
    testElement.style.display = 'none';
    document.body.appendChild(testElement);
    
    const computedStyle = window.getComputedStyle(testElement);
    const isLoaded = computedStyle.backgroundColor === 'rgb(239, 68, 68)';
    
    document.getElementById('tailwind-status').textContent = isLoaded ? 'YES' : 'NO';
    document.getElementById('tailwind-status').style.color = isLoaded ? 'green' : 'red';
    
    document.body.removeChild(testElement);
    
    // Force add athena-ai-admin class
    if (!document.body.classList.contains('athena-ai-admin')) {
        document.body.classList.add('athena-ai-admin');
        console.log('Added athena-ai-admin class to body');
    }
    
    // Add interactivity for radio options
    function setupRadioOptions() {
        // Both content source and content type now use .content-type-option class
        const allOptions = document.querySelectorAll('.content-type-option');
        allOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Get the radio button in this option
                const radio = this.querySelector('input[type="radio"]');
                if (!radio) return;
                
                // Get the radio name to handle groups separately
                const radioName = radio.name;
                
                // Remove selected class from all options in the same group
                document.querySelectorAll(`input[name="${radioName}"]`).forEach(groupRadio => {
                    const groupOption = groupRadio.closest('.content-type-option');
                    if (groupOption) {
                        groupOption.classList.remove('selected');
                    }
                });
                
                // Add selected class to clicked option
                this.classList.add('selected');
                
                // Check the radio button
                radio.checked = true;
            });
        });
        
        // Set initial selected states for all checked radios
        document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
            const option = radio.closest('.content-type-option');
            if (option) {
                option.classList.add('selected');
            }
        });
    }
    
    // Initialize interactive elements
    setupRadioOptions();
    
    // Initialize step navigation
    initializeStepNavigation();
});

// Step navigation variables
let currentStep = 1;
const maxSteps = 4;

// Initialize step navigation
function initializeStepNavigation() {
    updateStepNavigation();
    updateFormNavigation();
}

// Navigate to specific step
function navigateToStep(step) {
    if (step >= 1 && step <= maxSteps && (step <= currentStep + 1 || step <= getHighestAccessibleStep())) {
        currentStep = step;
        showStep(step);
        updateStepNavigation();
        updateFormNavigation();
        
        if (step === 4) {
            updateReviewContent();
        }
    }
}

// Go to next step
function nextStep() {
    if (currentStep < maxSteps) {
        currentStep++;
        showStep(currentStep);
        updateStepNavigation();
        updateFormNavigation();
        
        if (currentStep === 4) {
            updateReviewContent();
        }
    }
}

// Go to previous step
function previousStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
        updateStepNavigation();
        updateFormNavigation();
    }
}

// Show specific step content
function showStep(step) {
    // Hide all steps
    for (let i = 1; i <= maxSteps; i++) {
        const stepElement = document.getElementById('step-' + i);
        if (stepElement) {
            stepElement.classList.add('hidden');
            stepElement.classList.remove('block');
        }
    }
    
    // Show current step
    const currentStepElement = document.getElementById('step-' + step);
    if (currentStepElement) {
        currentStepElement.classList.remove('hidden');
        currentStepElement.classList.add('block');
    }
}

// Update step navigation visual state
function updateStepNavigation() {
    for (let i = 1; i <= maxSteps; i++) {
        const stepNav = document.getElementById('step-nav-' + i);
        const stepText = stepNav ? stepNav.nextElementSibling : null;
        const connectionLine = document.getElementById('line-' + i);
        
        if (stepNav && stepText) {
            // Remove all classes
            stepNav.className = 'step-number';
            stepText.className = 'step-text';
            
            if (i < currentStep) {
                // Completed step
                stepNav.classList.add('completed');
                stepNav.innerHTML = '<i class="fa-solid fa-check"></i>';
                stepText.classList.add('completed');
                stepNav.classList.add('clickable');
            } else if (i === currentStep) {
                // Active step
                stepNav.classList.add('active');
                stepNav.innerHTML = i.toString();
                stepText.classList.add('active');
                stepNav.classList.add('clickable');
            } else if (i === currentStep + 1) {
                // Next accessible step
                stepNav.classList.add('inactive');
                stepNav.innerHTML = i.toString();
                stepText.classList.add('inactive');
                stepNav.classList.add('clickable');
            } else {
                // Future step
                stepNav.classList.add('inactive');
                stepNav.innerHTML = i.toString();
                stepText.classList.add('inactive');
                stepNav.classList.add('non-clickable');
            }
        }
        
        // Update connection lines
        if (connectionLine) {
            connectionLine.className = 'connection-line';
            if (i < currentStep) {
                connectionLine.classList.add('completed');
            }
        }
    }
}

// Update form navigation buttons
function updateFormNavigation() {
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const generateBtn = document.getElementById('generate-btn');
    
    if (prevBtn) {
        prevBtn.style.display = currentStep > 1 ? 'inline-flex' : 'none';
    }
    
    if (nextBtn) {
        nextBtn.style.display = currentStep < maxSteps ? 'inline-flex' : 'none';
    }
    
    if (generateBtn) {
        generateBtn.style.display = currentStep === maxSteps ? 'inline-flex' : 'none';
    }
}

// Get highest accessible step (current + 1)
function getHighestAccessibleStep() {
    return Math.min(currentStep + 1, maxSteps);
}

// Update review content in step 4
function updateReviewContent() {
    const reviewContainer = document.getElementById('review-content');
    if (!reviewContainer) return;
    
    // Get form values
    const contentSource = document.querySelector('input[name="content_source"]:checked');
    const contentType = document.querySelector('input[name="content_type"]:checked');
    const tone = document.querySelector('select[name="tone"]');
    const targetAudience = document.querySelector('input[name="target_audience"]');
    const keywords = document.querySelector('input[name="keywords"]');
    const instructions = document.querySelector('textarea[name="instructions"]');
    
    // Build review content
    let reviewHTML = '';
    
    if (contentSource) {
        const sourceLabels = {
            'feed_items': 'Feed Items',
            'page_content': 'Page Content',
            'post_content': 'Post Content',
            'custom_topic': 'Custom Topic'
        };
        const sourceLabel = sourceLabels[contentSource.value] || contentSource.value;
        reviewHTML += `<div class="flex justify-between"><span class="font-medium text-gray-700">Content Source:</span><span class="text-gray-900">${sourceLabel}</span></div>`;
    }
    
    if (contentType) {
        const typeLabels = {
            'blog_post': 'Blog Post',
            'social_post': 'Social Post',
            'product_description': 'Product Description',
            'landing_page': 'Landing Page Copy',
            'tutorial': 'Tutorial/How-To',
            'seo_article': 'SEO Article',
            'podcast_notes': 'Podcast Show Notes',
            'social_captions': 'Social Media Captions'
        };
        const typeLabel = typeLabels[contentType.value] || contentType.value;
        reviewHTML += `<div class="flex justify-between"><span class="font-medium text-gray-700">Content Type:</span><span class="text-gray-900">${typeLabel}</span></div>`;
    }
    
    if (tone && tone.value) {
        reviewHTML += `<div class="flex justify-between"><span class="font-medium text-gray-700">Tone:</span><span class="text-gray-900">${tone.value}</span></div>`;
    }
    
    if (targetAudience && targetAudience.value) {
        reviewHTML += `<div class="flex justify-between"><span class="font-medium text-gray-700">Target Audience:</span><span class="text-gray-900">${targetAudience.value}</span></div>`;
    }
    
    if (keywords && keywords.value) {
        reviewHTML += `<div class="flex justify-between"><span class="font-medium text-gray-700">Keywords:</span><span class="text-gray-900">${keywords.value}</span></div>`;
    }
    
    if (instructions && instructions.value) {
        reviewHTML += `<div class="flex justify-between"><span class="font-medium text-gray-700">Instructions:</span><span class="text-gray-900">${instructions.value.substring(0, 100)}${instructions.value.length > 100 ? '...' : ''}</span></div>`;
    }
    
    reviewContainer.innerHTML = reviewHTML || '<p class="text-gray-500">No settings configured yet.</p>';
}

// Generate post function
function generatePost() {
    const generateBtn = document.getElementById('generate-btn');
    if (generateBtn) {
        generateBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';
        generateBtn.disabled = true;
    }
    
    // Simulate post generation
    setTimeout(() => {
        if (generateBtn) {
            generateBtn.innerHTML = '<i class="fa-solid fa-magic"></i> Generate Post';
            generateBtn.disabled = false;
        }
        
        alert('Post generated successfully! (This is a demo)');
    }, 3000);
}
</script>

<div class="wrap athena-ai-admin min-h-screen">
    <!-- Header -->
    <div class="flex justify-between items-center bg-white shadow-sm px-6 py-5 mb-6 rounded-lg border border-gray-100">
        <h1 class="text-2xl font-bold text-gray-800 m-0 flex items-center">
            <span class="bg-purple-100 text-purple-600 p-2 rounded-lg mr-3">
                <i class="fa-solid fa-magic"></i>
            </span>
            <?php esc_html_e('New AI Athena Post', 'athena-ai'); ?>
        </h1>
    </div>
    
    <!-- Main Content -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8">
        <div class="max-w-4xl mx-auto">
            
            <!-- Step Navigation -->
            <div class="step-navigation">
                <div class="step-item">
                    <div class="step-number active" id="step-nav-1" onclick="navigateToStep(1)">1</div>
                    <span class="step-text active">Content Source</span>
                </div>
                
                <div class="connection-line" id="line-1"></div>
                
                <div class="step-item">
                    <div class="step-number inactive" id="step-nav-2" onclick="navigateToStep(2)">2</div>
                    <span class="step-text inactive">Content Type</span>
                </div>
                
                <div class="connection-line" id="line-2"></div>
                
                <div class="step-item">
                    <div class="step-number inactive" id="step-nav-3" onclick="navigateToStep(3)">3</div>
                    <span class="step-text inactive">Customization</span>
                </div>
                
                <div class="connection-line" id="line-3"></div>
                
                <div class="step-item">
                    <div class="step-number inactive" id="step-nav-4" onclick="navigateToStep(4)">4</div>
                    <span class="step-text inactive">Review & Generate</span>
                </div>
            </div>
            
            <!-- Step Form -->
            <form id="ai-post-form" class="space-y-6">
                <?php wp_nonce_field(AIPostController::NONCE_ACTION, 'ai_post_nonce'); ?>
                <input type="hidden" id="current_step" name="current_step" value="<?php echo esc_attr($current_step); ?>">
                
                <!-- Step Content -->
                <div id="step-content">
                    
                    <!-- Step 1: Content Source -->
                    <div id="step-1" class="step-content <?php echo $current_step === 1 ? 'block' : 'hidden'; ?>">
                        <div class="text-center mb-8">
                            <div class="bg-blue-100 text-blue-600 inline-flex p-4 rounded-full mb-4 mx-auto">
                                <i class="fa-solid fa-file-text fa-2x"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                                <?php echo esc_html($step_config[1]['title']); ?>
                            </h2>
                            <p class="text-gray-600">
                                <?php echo esc_html($step_config[1]['description']); ?>
                            </p>
                        </div>
                        
                        <div class="content-type-grid">
                            <div class="content-type-option">
                                <i class="fa-solid fa-rss"></i>
                                <input type="radio" name="content_source" value="feed_items" style="display: none;" checked>
                                <h3>Feed Items</h3>
                                <p>Generate from feed sources</p>
                            </div>
                            
                            <div class="content-type-option">
                                <i class="fa-solid fa-file-alt"></i>
                                <input type="radio" name="content_source" value="page_content" style="display: none;">
                                <h3>Page Content</h3>
                                <p>Based on WordPress pages</p>
                            </div>
                            
                            <div class="content-type-option">
                                <i class="fa-solid fa-edit"></i>
                                <input type="radio" name="content_source" value="post_content" style="display: none;">
                                <h3>Post Content</h3>
                                <p>From existing blog posts</p>
                            </div>
                            
                            <div class="content-type-option">
                                <i class="fa-solid fa-lightbulb"></i>
                                <input type="radio" name="content_source" value="custom_topic" style="display: none;">
                                <h3>Custom Topic</h3>
                                <p>Create from custom ideas</p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Content Type -->
                    <div id="step-2" class="step-content <?php echo $current_step === 2 ? 'block' : 'hidden'; ?>">
                        <div class="text-center mb-8">
                            <div class="bg-green-100 text-green-600 inline-flex p-4 rounded-full mb-4 mx-auto">
                                <i class="fa-solid fa-list fa-2x"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                                <?php echo esc_html($step_config[2]['title']); ?>
                            </h2>
                            <p class="text-gray-600">
                                <?php echo esc_html($step_config[2]['description']); ?>
                            </p>
                        </div>
                        
                        <div class="content-type-grid content-type-grid-extended">
                            <div class="content-type-option">
                                <i class="fa-solid fa-newspaper"></i>
                                <input type="radio" name="content_type" value="blog_post" style="display: none;" checked>
                                <h3>Blog Post</h3>
                                <p>Long-form article content</p>
                            </div>
                            
                            <div class="content-type-option">
                                <i class="fa-brands fa-twitter"></i>
                                <input type="radio" name="content_type" value="social_post" style="display: none;">
                                <h3>Social Post</h3>
                                <p>Short social media content</p>
                            </div>
                            
                            <div class="content-type-option">
                                <i class="fa-solid fa-box"></i>
                                <input type="radio" name="content_type" value="product_description" style="display: none;">
                                <h3>Product Description</h3>
                                <p>E-commerce product copy</p>
                            </div>
                            
                            <div class="content-type-option">
                                <i class="fa-solid fa-bullseye"></i>
                                <input type="radio" name="content_type" value="landing_page" style="display: none;">
                                <h3>Landing Page Copy</h3>
                                <p>Sales and conversion text</p>
                            </div>
                            
                            <div class="content-type-option">
                                <i class="fa-solid fa-graduation-cap"></i>
                                <input type="radio" name="content_type" value="tutorial" style="display: none;">
                                <h3>Tutorial/How-To</h3>
                                <p>Step-by-step guides</p>
                            </div>
                            
                            <div class="content-type-option">
                                <i class="fa-solid fa-search"></i>
                                <input type="radio" name="content_type" value="seo_article" style="display: none;">
                                <h3>SEO Article</h3>
                                <p>Search optimized content</p>
                            </div>
                            
                            <div class="content-type-option">
                                <i class="fa-solid fa-microphone"></i>
                                <input type="radio" name="content_type" value="podcast_notes" style="display: none;">
                                <h3>Podcast Show Notes</h3>
                                <p>Episode summaries</p>
                            </div>
                            
                            <div class="content-type-option">
                                <i class="fa-solid fa-camera"></i>
                                <input type="radio" name="content_type" value="social_captions" style="display: none;">
                                <h3>Social Media Captions</h3>
                                <p>Instagram/LinkedIn copy</p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Customization -->
                    <div id="step-3" class="step-content <?php echo $current_step === 3 ? 'block' : 'hidden'; ?>">
                        <div class="text-center mb-8">
                            <div class="bg-purple-100 text-purple-600 inline-flex p-4 rounded-full mb-4 mx-auto">
                                <i class="fa-solid fa-cog fa-2x"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                                <?php echo esc_html($step_config[3]['title']); ?>
                            </h2>
                            <p class="text-gray-600">
                                <?php echo esc_html($step_config[3]['description']); ?>
                            </p>
                        </div>
                        
                        <div class="max-w-2xl mx-auto space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tone of Voice</label>
                                <select name="tone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                                    <option value="professional">Professional</option>
                                    <option value="casual">Casual</option>
                                    <option value="friendly">Friendly</option>
                                    <option value="authoritative">Authoritative</option>
                                    <option value="conversational">Conversational</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Target Audience</label>
                                <input type="text" name="target_audience" placeholder="e.g., Business professionals, Tech enthusiasts..." 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Keywords (optional)</label>
                                <input type="text" name="keywords" placeholder="Enter keywords separated by commas" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Additional Instructions</label>
                                <textarea name="instructions" rows="4" placeholder="Any specific requirements or instructions..." 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Review & Generate -->
                    <div id="step-4" class="step-content <?php echo $current_step === 4 ? 'block' : 'hidden'; ?>">
                        <div class="text-center mb-8">
                            <div class="bg-green-100 text-green-600 inline-flex p-4 rounded-full mb-4 mx-auto">
                                <i class="fa-solid fa-check fa-2x"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                                <?php echo esc_html($step_config[4]['title']); ?>
                            </h2>
                            <p class="text-gray-600">
                                <?php echo esc_html($step_config[4]['description']); ?>
                            </p>
                        </div>
                        
                        <div class="max-w-2xl mx-auto">
                            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Review Your Settings</h3>
                                <div id="review-content" class="space-y-3 text-sm">
                                    <!-- Review content will be populated by JavaScript -->
                                </div>
                            </div>
                            
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-start space-x-3">
                                    <i class="fa-solid fa-info-circle text-blue-600 mt-0.5"></i>
                                    <div class="text-sm text-blue-800">
                                        <p class="font-medium">Ready to generate your AI content?</p>
                                        <p>This process may take a few moments. Your content will be created based on the settings above.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Navigation -->
                <div class="form-actions">
                    <button type="button" id="prev-btn" onclick="previousStep()" class="btn btn-secondary" style="display: none;">
                        <i class="fa-solid fa-arrow-left"></i>
                        Back
                    </button>
                    
                    <button type="button" id="next-btn" onclick="nextStep()" class="btn btn-primary">
                        Next
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                    
                    <button type="button" id="generate-btn" onclick="generatePost()" class="btn btn-primary" style="display: none; background: linear-gradient(to right, #22c55e, #16a34a) !important;">
                        <i class="fa-solid fa-magic"></i>
                        Generate Post
                    </button>
                </div>

                <!-- Old Form Navigation (hidden) -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200" style="display: none !important;">
                    <button type="button" id="prev-btn" onclick="previousStep()" 
                            class="px-6 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200 <?php echo $current_step <= 1 ? 'hidden' : ''; ?>">
                        <i class="fa-solid fa-arrow-left mr-2"></i>
                        <?php esc_html_e('Back', 'athena-ai'); ?>
                    </button>
                    
                    <div class="flex space-x-3">
                        <button type="button" id="next-btn" onclick="nextStep()" 
                                class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors duration-200 <?php echo $current_step >= 4 ? 'hidden' : ''; ?>">
                            <?php esc_html_e('Next', 'athena-ai'); ?>
                            <i class="fa-solid fa-arrow-right ml-2"></i>
                        </button>
                        
                        <button type="button" id="generate-btn" onclick="generatePost()" 
                                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 <?php echo $current_step < 4 ? 'hidden' : ''; ?>">
                            <i class="fa-solid fa-magic mr-2"></i>
                            <?php esc_html_e('Generate Post', 'athena-ai'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentStep = <?php echo $current_step; ?>;

function navigateToStep(step) {
    if (step <= currentStep || step <= getCompletedSteps()) {
        showStep(step);
        currentStep = step;
        updateNavigation();
        updateStepNavigation();
    }
}

function nextStep() {
    if (currentStep < 4) {
        currentStep++;
        showStep(currentStep);
        updateNavigation();
        updateStepNavigation();
        
        if (currentStep === 4) {
            updateReviewContent();
        }
    }
}

function previousStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
        updateNavigation();
        updateStepNavigation();
    }
}

function showStep(step) {
    // Hide all steps
    document.querySelectorAll('.step-content').forEach(el => {
        el.classList.add('hidden');
        el.classList.remove('block');
    });
    
    // Show current step
    const stepElement = document.getElementById(`step-${step}`);
    if (stepElement) {
        stepElement.classList.remove('hidden');
        stepElement.classList.add('block');
    }
    
    // Update hidden input
    document.getElementById('current_step').value = step;
}

function updateNavigation() {
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const generateBtn = document.getElementById('generate-btn');
    
    // Previous button
    if (currentStep <= 1) {
        prevBtn.classList.add('hidden');
    } else {
        prevBtn.classList.remove('hidden');
    }
    
    // Next/Generate buttons
    if (currentStep >= 4) {
        nextBtn.classList.add('hidden');
        generateBtn.classList.remove('hidden');
    } else {
        nextBtn.classList.remove('hidden');
        generateBtn.classList.add('hidden');
    }
}

function updateStepNavigation() {
    // Die Step-Navigation wird vollständig server-seitig generiert
    // Diese Funktion wird für eventuelle zukünftige clientseitige Updates beibehalten
    // aber ist derzeit nicht erforderlich, da wir bei jedem Step-Wechsel
    // die Navigation neu rendern
}

function getCompletedSteps() {
    // Logic to determine which steps are completed based on form data
    return currentStep - 1;
}

function updateReviewContent() {
    const reviewContainer = document.getElementById('review-content');
    if (!reviewContainer) return;
    
    let reviewHTML = '';
    
    // Content Source
    const contentSource = document.querySelector('input[name="content_source"]:checked');
    if (contentSource) {
        reviewHTML += `<div class="flex justify-between"><span class="font-medium">Content Source:</span><span>${getContentSourceLabel(contentSource.value)}</span></div>`;
    }
    
    // Content Type
    const contentType = document.querySelector('input[name="content_type"]:checked');
    if (contentType) {
        reviewHTML += `<div class="flex justify-between"><span class="font-medium">Content Type:</span><span>${getContentTypeLabel(contentType.value)}</span></div>`;
    }
    
    // Tone
    const tone = document.querySelector('select[name="tone"]');
    if (tone && tone.value) {
        reviewHTML += `<div class="flex justify-between"><span class="font-medium">Tone:</span><span>${tone.options[tone.selectedIndex].text}</span></div>`;
    }
    
    // Target Audience
    const audience = document.querySelector('input[name="target_audience"]');
    if (audience && audience.value) {
        reviewHTML += `<div class="flex justify-between"><span class="font-medium">Target Audience:</span><span>${audience.value}</span></div>`;
    }
    
    // Keywords
    const keywords = document.querySelector('input[name="keywords"]');
    if (keywords && keywords.value) {
        reviewHTML += `<div class="flex justify-between"><span class="font-medium">Keywords:</span><span>${keywords.value}</span></div>`;
    }
    
    // Instructions
    const instructions = document.querySelector('textarea[name="instructions"]');
    if (instructions && instructions.value) {
        reviewHTML += `<div><span class="font-medium">Additional Instructions:</span><br><span class="text-gray-600">${instructions.value}</span></div>`;
    }
    
    reviewContainer.innerHTML = reviewHTML || '<p class="text-gray-500">No settings configured yet.</p>';
}

function getContentSourceLabel(value) {
    const labels = {
        'feed_items': 'Feed Items',
        'custom_topic': 'Custom Topic'
    };
    return labels[value] || value;
}

function getContentTypeLabel(value) {
    const labels = {
        'blog_post': 'Blog Post',
        'social_post': 'Social Post',
        'summary': 'Summary',
        'newsletter': 'Newsletter'
    };
    return labels[value] || value;
}

function generatePost() {
    // Collect form data
    const formData = new FormData(document.getElementById('ai-post-form'));
    formData.append('action', 'athena_ai_post_generate');
    formData.append('nonce', document.querySelector('input[name="ai_post_nonce"]').value);
    
    // Show loading state
    const generateBtn = document.getElementById('generate-btn');
    const originalText = generateBtn.innerHTML;
    generateBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i><?php esc_html_e("Generating...", "athena-ai"); ?>';
    generateBtn.disabled = true;
    
    // Send AJAX request
    fetch(ajaxurl, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.data.message || '<?php esc_html_e("Post generated successfully!", "athena-ai"); ?>');
            if (data.data.redirect) {
                window.location.href = data.data.redirect;
            }
        } else {
            alert(data.data.message || '<?php esc_html_e("An error occurred. Please try again.", "athena-ai"); ?>');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('<?php esc_html_e("An error occurred. Please try again.", "athena-ai"); ?>');
    })
    .finally(() => {
        // Restore button state
        generateBtn.innerHTML = originalText;
        generateBtn.disabled = false;
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateNavigation();
    updateStepNavigation();
    
    // Add form change listeners to update review content
    document.getElementById('ai-post-form').addEventListener('change', function() {
        if (currentStep === 4) {
            updateReviewContent();
        }
    });
});
</script>

<style>
/* Additional custom styles for the step form */
.athena-ai-admin .step-content {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.athena-ai-admin .step-circle {
    transition: all 0.2s ease-in-out;
}

.athena-ai-admin .step-circle:hover {
    transform: scale(1.05);
}

.athena-ai-admin input[type="radio"]:checked + * {
    border-color: #9333ea !important;
    background-color: #faf5ff !important;
}

/* Content type selection styling */
.athena-ai-admin input[name="content_type"]:checked ~ * {
    border-color: #9333ea !important;
    background-color: #faf5ff !important;
}

.athena-ai-admin input[name="content_type"] + * i {
    transition: color 0.2s ease-in-out;
}

.athena-ai-admin input[name="content_type"]:checked + * i {
    color: #9333ea !important;
}
</style>

<?php
// Restore previous error reporting
error_reporting($previous_error_reporting);
?> 