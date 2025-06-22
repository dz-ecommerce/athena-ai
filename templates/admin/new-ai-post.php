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
?>
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
            <?php echo AIPostController::render_step_navigation($current_step); ?>
            
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
                        
                        <div class="max-w-2xl mx-auto">
                            <div class="space-y-4">
                                <div class="p-6 border border-gray-200 rounded-lg hover:border-purple-300 transition-colors duration-200">
                                    <label class="flex items-start space-x-3 cursor-pointer">
                                        <input type="radio" name="content_source" value="feed_items" class="mt-1" checked>
                                        <div>
                                            <h3 class="text-lg font-medium text-gray-900">Feed Items</h3>
                                            <p class="text-gray-600 text-sm">Generate content based on your existing feed items</p>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="p-6 border border-gray-200 rounded-lg hover:border-purple-300 transition-colors duration-200">
                                    <label class="flex items-start space-x-3 cursor-pointer">
                                        <input type="radio" name="content_source" value="custom_topic" class="mt-1">
                                        <div>
                                            <h3 class="text-lg font-medium text-gray-900">Custom Topic</h3>
                                            <p class="text-gray-600 text-sm">Create content from a custom topic or keyword</p>
                                        </div>
                                    </label>
                                </div>
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
                        
                        <div class="max-w-2xl mx-auto">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="p-6 border border-gray-200 rounded-lg hover:border-purple-300 transition-colors duration-200">
                                    <label class="flex flex-col items-center text-center cursor-pointer">
                                        <input type="radio" name="content_type" value="blog_post" class="mb-3" checked>
                                        <i class="fa-solid fa-newspaper text-2xl text-blue-600 mb-2"></i>
                                        <h3 class="text-lg font-medium text-gray-900">Blog Post</h3>
                                        <p class="text-gray-600 text-sm">Long-form article content</p>
                                    </label>
                                </div>
                                
                                <div class="p-6 border border-gray-200 rounded-lg hover:border-purple-300 transition-colors duration-200">
                                    <label class="flex flex-col items-center text-center cursor-pointer">
                                        <input type="radio" name="content_type" value="social_post" class="mb-3">
                                        <i class="fa-solid fa-share-alt text-2xl text-green-600 mb-2"></i>
                                        <h3 class="text-lg font-medium text-gray-900">Social Post</h3>
                                        <p class="text-gray-600 text-sm">Short social media content</p>
                                    </label>
                                </div>
                                
                                <div class="p-6 border border-gray-200 rounded-lg hover:border-purple-300 transition-colors duration-200">
                                    <label class="flex flex-col items-center text-center cursor-pointer">
                                        <input type="radio" name="content_type" value="summary" class="mb-3">
                                        <i class="fa-solid fa-compress-alt text-2xl text-purple-600 mb-2"></i>
                                        <h3 class="text-lg font-medium text-gray-900">Summary</h3>
                                        <p class="text-gray-600 text-sm">Condensed overview</p>
                                    </label>
                                </div>
                                
                                <div class="p-6 border border-gray-200 rounded-lg hover:border-purple-300 transition-colors duration-200">
                                    <label class="flex flex-col items-center text-center cursor-pointer">
                                        <input type="radio" name="content_type" value="newsletter" class="mb-3">
                                        <i class="fa-solid fa-envelope text-2xl text-orange-600 mb-2"></i>
                                        <h3 class="text-lg font-medium text-gray-900">Newsletter</h3>
                                        <p class="text-gray-600 text-sm">Email newsletter format</p>
                                    </label>
                                </div>
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
                <div class="flex justify-between items-center pt-6 border-t border-gray-200">
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
    // Update step navigation visual state
    document.querySelectorAll('[onclick^="navigateToStep"]').forEach((btn, index) => {
        const stepNum = index + 1;
        const isActive = stepNum === currentStep;
        const isCompleted = stepNum < currentStep;
        
        // Reset classes
        btn.className = 'flex items-center justify-center w-10 h-10 rounded-full border-2 text-sm font-medium transition-all duration-200';
        
        if (isActive) {
            btn.className += ' bg-purple-600 border-purple-600 text-white';
        } else if (isCompleted) {
            btn.className += ' bg-green-500 border-green-500 text-white cursor-pointer hover:bg-green-600';
            btn.innerHTML = '<i class="fa-solid fa-check"></i>';
        } else {
            btn.className += ' bg-gray-100 border-gray-300 text-gray-500';
            btn.innerHTML = stepNum;
        }
        
        // Enable/disable buttons
        if (stepNum > currentStep && !isCompleted) {
            btn.disabled = true;
        } else {
            btn.disabled = false;
        }
    });
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

<?php
// Restore previous error reporting
error_reporting($previous_error_reporting);
?> 