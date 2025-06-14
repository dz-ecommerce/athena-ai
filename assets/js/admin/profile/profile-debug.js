/**
 * Profile Debug JavaScript
 * Debug script to check if elements and scripts are loading correctly
 */

jQuery(function ($) {
    console.log('=== ATHENA AI DEBUG START ===');
    console.log('Profile Debug Script loaded');
    console.log('Current page URL:', window.location.href);
    console.log('jQuery version:', $.fn.jquery);

    // Check if buttons exist
    const assistantBtn = $('#athena-ai-assistant-btn');
    const productsBtn = $('#athena-ai-products-assistant-btn');

    console.log('Assistant button found:', assistantBtn.length > 0);
    console.log('Products button found:', productsBtn.length > 0);

    if (assistantBtn.length === 0) {
        console.log('Assistant button not found. Searching for similar elements...');
        console.log('Elements with "athena-ai" in ID:', $('[id*="athena-ai"]').length);
        $('[id*="athena-ai"]').each(function () {
            console.log('Found element with ID:', this.id);
        });
    }

    if (productsBtn.length === 0) {
        console.log('Products button not found. Searching for similar elements...');
        console.log('Elements with "products" in ID:', $('[id*="products"]').length);
        $('[id*="products"]').each(function () {
            console.log('Found element with ID:', this.id);
        });
    }

    // Check if modals exist
    const modal = $('#athena-ai-modal');
    const productsModal = $('#athena-ai-products-modal');

    console.log('Main modal found:', modal.length > 0);
    console.log('Products modal found:', productsModal.length > 0);

    // Check if variables are defined
    console.log('ajaxurl defined:', typeof ajaxurl !== 'undefined');
    console.log('ajaxurl value:', typeof ajaxurl !== 'undefined' ? ajaxurl : 'undefined');
    console.log('athenaAiAdmin defined:', typeof athenaAiAdmin !== 'undefined');
    console.log('athenaAi defined:', typeof athenaAi !== 'undefined');

    // List all loaded scripts
    console.log('All loaded scripts:');
    $('script[src]').each(function () {
        const src = $(this).attr('src');
        if (src.includes('athena') || src.includes('profile')) {
            console.log('Script:', src);
        }
    });

    // Add test click handlers
    assistantBtn.on('click', function () {
        console.log('Assistant button clicked!');
    });

    productsBtn.on('click', function () {
        console.log('Products button clicked!');
    });

    console.log('=== ATHENA AI DEBUG END ===');
});
