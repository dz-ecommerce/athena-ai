import Modal from './Modal';

/**
 * AI Modal Component for generating content
 */
class AIModal extends Modal {
    /**
     * @param {string} id Modal element ID
     * @param {Object} options Configuration options
     */
    constructor(id, options = {}) {
        super(id, {
            closeOnBackdropClick: true,
            closeOnEsc: true,
            ...options
        });
        
        this.form = this.modal.querySelector('form');
        this.pageSelect = this.modal.querySelector('[data-page-select]');
        this.extraInfo = this.modal.querySelector('[data-extra-info]');
        this.debugOutput = this.modal.querySelector('[data-debug-output]');
        this.createBtn = this.modal.querySelector('[data-action="create"]');
        this.transferBtn = this.modal.querySelector('[data-action="transfer"]');
        this.testOnlyCheckbox = this.modal.querySelector('[data-test-only]');
        this.providerRadios = this.modal.querySelectorAll('input[name$="-provider"]');
        
        this.isGenerating = false;
        this.generatedContent = '';
        
        this.init();
    }
    
    /**
     * Initialize the modal
     */
    init() {
        super.init();
        
        // Form submission
        if (this.form) {
            this.form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleSubmit();
            });
        }
        
        // Transfer button
        if (this.transferBtn) {
            this.transferBtn.addEventListener('click', () => this.handleTransfer());
        }
        
        // Input validation
        this.addInputValidation();
        
        // Handle modal close to reset state
        this.modal.addEventListener('modal:close', () => this.resetState());
    }
    
    /**
     * Add input validation
     */
    addInputValidation() {
        const validateInputs = () => {
            const pageSelected = this.pageSelect && this.pageSelect.value !== '';
            const hasExtraInfo = this.extraInfo && this.extraInfo.value.trim() !== '';
            
            if (this.createBtn) {
                this.createBtn.disabled = !(pageSelected || hasExtraInfo);
            }
        };
        
        if (this.pageSelect) {
            this.pageSelect.addEventListener('change', validateInputs);
        }
        
        if (this.extraInfo) {
            this.extraInfo.addEventListener('input', validateInputs);
        }
        
        // Initial validation
        validateInputs();
    }
    
    /**
     * Handle form submission
     */
    async handleSubmit() {
        if (this.isGenerating) return;
        
        const pageId = this.pageSelect ? this.pageSelect.value : '';
        const extraInfo = this.extraInfo ? this.extraInfo.value.trim() : '';
        const testOnly = this.testOnlyCheckbox ? this.testOnlyCheckbox.checked : false;
        const provider = this.getSelectedProvider();
        
        if (!pageId && !extraInfo) {
            alert('Bitte wähle eine Seite aus ODER gib zusätzliche Informationen ein');
            return;
        }
        
        this.setLoading(true);
        
        try {
            // Get the prompt based on the modal type
            const prompt = this.getPrompt(pageId, extraInfo);
            
            // Call the API
            const response = await this.generateContent(prompt, provider, testOnly);
            
            if (response.success) {
                this.generatedContent = response.content;
                this.showDebugOutput(response, testOnly);
                
                // Enable transfer button
                if (this.transferBtn) {
                    this.transferBtn.disabled = false;
                    this.transferBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    this.transferBtn.classList.add('bg-green-600', 'hover:bg-green-700');
                }
            } else {
                throw new Error(response.message || 'Failed to generate content');
            }
        } catch (error) {
            console.error('Error generating content:', error);
            this.showError(error.message || 'Ein Fehler ist aufgetreten');
        } finally {
            this.setLoading(false);
        }
    }
    
    /**
     * Get the selected provider
     */
    getSelectedProvider() {
        if (!this.providerRadios.length) return 'openai';
        
        for (const radio of this.providerRadios) {
            if (radio.checked) {
                return radio.value;
            }
        }
        
        return 'openai';
    }
    
    /**
     * Get the prompt for the API call
     */
    getPrompt(pageId, extraInfo) {
        // This should be implemented by child classes
        return '';
    }
    
    /**
     * Generate content using the API
     */
    async generateContent(prompt, provider, testOnly = false) {
        const response = await fetch(athenaAiAdmin.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'athena_ai_generate_content',
                nonce: athenaAiAdmin.nonce,
                prompt: prompt,
                provider: provider,
                test_mode: testOnly
            })
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        return response.json();
    }
    
    /**
     * Show debug output
     */
    showDebugOutput(response, testOnly = false) {
        if (!this.debugOutput) return;
        
        if (testOnly) {
            this.debugOutput.innerHTML = `
                <div class="p-3 bg-gray-100 rounded mb-4">
                    <h4 class="font-semibold mb-2">Test-Modus aktiviert</h4>
                    <pre class="text-xs whitespace-pre-wrap">${response.content}</pre>
                </div>
            `;
        } else {
            this.debugOutput.innerHTML = `
                <div class="p-3 bg-green-50 border border-green-200 rounded mb-4">
                    <h4 class="font-semibold text-green-800 mb-2">Erfolgreich generiert</h4>
                    <div class="prose max-w-none">${response.content}</div>
                </div>
            `;
        }
        
        this.debugOutput.style.display = 'block';
    }
    
    /**
     * Show error message
     */
    showError(message) {
        if (!this.debugOutput) return;
        
        this.debugOutput.innerHTML = `
            <div class="p-3 bg-red-50 border border-red-200 rounded">
                <h4 class="font-semibold text-red-800">Fehler</h4>
                <p class="text-red-600">${message}</p>
            </div>
        `;
        this.debugOutput.style.display = 'block';
    }
    
    /**
     * Handle transfer button click
     */
    handleTransfer() {
        if (!this.generatedContent) return;
        
        // Dispatch an event that the parent can listen to
        this.modal.dispatchEvent(new CustomEvent('content:transfer', {
            detail: {
                content: this.generatedContent,
                targetField: this.modal.dataset.targetField || ''
            }
        }));
        
        this.close();
    }
    
    /**
     * Set loading state
     */
    setLoading(isLoading) {
        this.isGenerating = isLoading;
        
        if (this.createBtn) {
            this.createBtn.disabled = isLoading;
            this.createBtn.innerHTML = isLoading
                ? '<i class="fas fa-spinner fa-spin mr-2"></i> Wird generiert...'
                : 'Inhalt erstellen';
        }
    }
    
    /**
     * Reset modal state
     */
    resetState() {
        this.generatedContent = '';
        this.isGenerating = false;
        
        if (this.debugOutput) {
            this.debugOutput.style.display = 'none';
            this.debugOutput.innerHTML = '';
        }
        
        if (this.transferBtn) {
            this.transferBtn.disabled = true;
            this.transferBtn.classList.add('opacity-50', 'cursor-not-allowed');
            this.transferBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
        }
        
        if (this.createBtn) {
            this.createBtn.disabled = false;
            this.createBtn.textContent = 'Inhalt erstellen';
        }
        
        if (this.form) {
            this.form.reset();
        }
    }
}

export default AIModal;
