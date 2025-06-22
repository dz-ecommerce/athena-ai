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

// Load the specific CSS for this page directly 
$css_url = ATHENA_AI_PLUGIN_URL . 'assets/css/new-ai-post.css?v=' . time();
?>

<!-- Load specific CSS for this page -->
<link rel="stylesheet" href="<?php echo $css_url; ?>" type="text/css" media="all" />
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" type="text/css" media="all" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" type="text/css" media="all" />

<!-- Debug Information -->
<div class="debug-info">
    <strong>Debug Info:</strong><br>
    Hook Suffix: <?php echo esc_html($hook_suffix); ?><br>
    CSS URL: <?php echo ATHENA_AI_PLUGIN_URL . 'assets/css/new-ai-post.css'; ?><br>
    Body Classes: <span id="body-classes"></span><br>
    TailwindCSS Loaded: <span id="tailwind-status">Checking...</span>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show body classes
    document.getElementById('body-classes').textContent = document.body.className;
    
    // Check if TailwindCSS is loaded
    const testElement = document.createElement('div');
    testElement.className = 'new-ai-post-page bg-red-500';
    testElement.style.display = 'none';
    document.body.appendChild(testElement);
    
    const computedStyle = window.getComputedStyle(testElement);
    const isLoaded = computedStyle.backgroundColor === 'rgb(239, 68, 68)';
    
    document.getElementById('tailwind-status').textContent = isLoaded ? 'YES' : 'NO';
    document.getElementById('tailwind-status').style.color = isLoaded ? 'green' : 'red';
    
    document.body.removeChild(testElement);
    
    // Don't add any global classes that could interfere with other pages
    console.log('New AI Post page loaded successfully');
    
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
    const selectedFeedItems = document.querySelector('select[name="selected_feed_items"]');
    const selectedPages = document.querySelector('select[name="selected_pages"]');
    const selectedPosts = document.querySelector('select[name="selected_posts"]');
    const customTopic = document.querySelector('textarea[name="custom_topic"]');
    const tone = document.querySelector('select[name="tone"]');
    const targetAudience = document.querySelector('input[name="target_audience"]');
    const keywords = document.querySelector('input[name="keywords"]');
    const contentLength = document.querySelector('select[name="content_length"]');
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
    
    // Content Selection Details
    if (selectedFeedItems && selectedFeedItems.selectedOptions.length > 0) {
        const feedItemsText = Array.from(selectedFeedItems.selectedOptions).map(option => option.text).join(', ');
        reviewHTML += `<div class="flex justify-between"><span class="font-medium text-gray-700">Selected Feed Items:</span><span class="text-gray-900">${feedItemsText.substring(0, 100)}${feedItemsText.length > 100 ? '...' : ''}</span></div>`;
    }
    
    if (selectedPages && selectedPages.selectedOptions.length > 0) {
        const pagesText = Array.from(selectedPages.selectedOptions).map(option => option.text).join(', ');
        reviewHTML += `<div class="flex justify-between"><span class="font-medium text-gray-700">Selected Pages:</span><span class="text-gray-900">${pagesText}</span></div>`;
    }
    
    if (selectedPosts && selectedPosts.selectedOptions.length > 0) {
        const postsText = Array.from(selectedPosts.selectedOptions).map(option => option.text).join(', ');
        reviewHTML += `<div class="flex justify-between"><span class="font-medium text-gray-700">Selected Posts:</span><span class="text-gray-900">${postsText.substring(0, 100)}${postsText.length > 100 ? '...' : ''}</span></div>`;
    }
    
    if (customTopic && customTopic.value) {
        reviewHTML += `<div class="flex justify-between"><span class="font-medium text-gray-700">Custom Topic:</span><span class="text-gray-900">${customTopic.value.substring(0, 100)}${customTopic.value.length > 100 ? '...' : ''}</span></div>`;
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
    
    if (contentLength && contentLength.value) {
        const lengthLabels = {
            'short': 'Short (300-500 words)',
            'medium': 'Medium (500-1000 words)',
            'long': 'Long (1000+ words)'
        };
        const lengthLabel = lengthLabels[contentLength.value] || contentLength.value;
        reviewHTML += `<div class="flex justify-between"><span class="font-medium text-gray-700">Content Length:</span><span class="text-gray-900">${lengthLabel}</span></div>`;
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

<div class="wrap new-ai-post-page min-h-screen">
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
    <div class="bg-white shadow-sm rounded-lg border border-gray-100 p-8">
        <div class="max-w-4xl mx-auto space-y-6">
            
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
            <form id="ai-post-form" method="post" action="">
                <?php wp_nonce_field('athena_ai_post_nonce', 'ai_post_nonce'); ?>
                <input type="hidden" name="action" value="<?php echo esc_attr('athena_ai_post_step'); ?>">

                <div class="step-forms">
                    <!-- Step 1: Content Source -->
                    <div id="step-1" class="step-content <?php echo $current_step === 1 ? 'block' : 'hidden'; ?>">
                        <div class="text-center mb-8">
                            <div class="bg-purple-100 text-purple-600 inline-flex p-4 rounded-full mb-4 mx-auto">
                                <i class="fa-solid fa-rss fa-2x"></i>
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
                        
                        <div class="max-w-4xl mx-auto space-y-8">
                            <!-- Content Selection Section -->
                            <div class="bg-white border border-gray-200 rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Content Selection</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Feed Items Selection -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fa-solid fa-rss text-purple-600 mr-2"></i>
                                            Select Feed Items
                                        </label>
                                        <select name="selected_feed_items" multiple class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 h-32">
                                            <option value="1">Tech News - Latest AI Developments</option>
                                            <option value="2">Business Insider - Market Updates</option>
                                            <option value="3">WordPress News - Plugin Updates</option>
                                            <option value="4">Digital Marketing - SEO Tips</option>
                                            <option value="5">Web Development - Best Practices</option>
                                        </select>
                                        <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple items</p>
                                    </div>
                                    
                                    <!-- WordPress Pages Selection -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fa-solid fa-file-alt text-purple-600 mr-2"></i>
                                            Select WordPress Pages
                                        </label>
                                        <select name="selected_pages" multiple class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 h-32">
                                            <option value="1">About Us</option>
                                            <option value="2">Services</option>
                                            <option value="3">Contact</option>
                                            <option value="4">Privacy Policy</option>
                                            <option value="5">Terms of Service</option>
                                        </select>
                                        <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple pages</p>
                                    </div>
                                    
                                    <!-- WordPress Posts Selection -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fa-solid fa-edit text-purple-600 mr-2"></i>
                                            Select WordPress Posts
                                        </label>
                                        <select name="selected_posts" multiple class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500 h-32">
                                            <option value="1">How to Build a WordPress Plugin</option>
                                            <option value="2">Best SEO Practices for 2024</option>
                                            <option value="3">AI in Content Marketing</option>
                                            <option value="4">WordPress Security Guide</option>
                                            <option value="5">E-commerce Trends 2024</option>
                                        </select>
                                        <p class="text-xs text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple posts</p>
                                    </div>
                                    
                                    <!-- Custom Topic Input -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fa-solid fa-lightbulb text-purple-600 mr-2"></i>
                                            Custom Topic
                                        </label>
                                        <textarea name="custom_topic" rows="5" placeholder="Describe your custom topic or enter specific keywords and ideas..." 
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500"></textarea>
                                        <p class="text-xs text-gray-500 mt-1">Provide detailed information about your custom topic</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Customization Section -->
                            <div class="bg-white border border-gray-200 rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Content Customization</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Content Length</label>
                                        <select name="content_length" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500">
                                            <option value="short">Short (300-500 words)</option>
                                            <option value="medium" selected>Medium (500-1000 words)</option>
                                            <option value="long">Long (1000+ words)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mt-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Additional Instructions</label>
                                    <textarea name="instructions" rows="4" placeholder="Any specific requirements or instructions..." 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500"></textarea>
                                </div>
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
            </form>
        </div>
    </div>
</div>

<?php
// Restore previous error reporting
error_reporting($previous_error_reporting);
?>
</rewritten_file>