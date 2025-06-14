/**
 * Profile Debug JavaScript
 * Debug script to check if elements and scripts are loading correctly
 */

jQuery(function ($) {
    console.log('Profile Debug Script loaded');

    // Check if buttons exist
    const assistantBtn = $('#athena-ai-assistant-btn');
    const productsBtn = $('#athena-ai-products-assistant-btn');

    console.log('Assistant button found:', assistantBtn.length > 0);
    console.log('Products button found:', productsBtn.length > 0);

    // Check if modals exist
    const modal = $('#athena-ai-modal');
    const productsModal = $('#athena-ai-products-modal');

    console.log('Main modal found:', modal.length > 0);
    console.log('Products modal found:', productsModal.length > 0);

    // Check if ajaxurl is defined
    console.log('ajaxurl defined:', typeof ajaxurl !== 'undefined');
    console.log('athenaAiAdmin defined:', typeof athenaAiAdmin !== 'undefined');

    // Add test click handlers
    assistantBtn.on('click', function () {
        console.log('Assistant button clicked!');
    });

    productsBtn.on('click', function () {
        console.log('Products button clicked!');
    });
});
