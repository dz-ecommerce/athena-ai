import AIModal from './AIModal';

/**
 * Modal for extracting products and services
 */
class ProductsModal extends AIModal {
    /**
     * Get the prompt for the API call
     */
    getPrompt(pageId, extraInfo) {
        let prompt = 'Extrahiere eine kommagetrennte Liste von Produkten und Dienstleistungen aus dem folgenden Text. ';
        prompt += 'Gib nur die Liste zurück, keine zusätzlichen Erklärungen.\n\n';
        
        if (pageId) {
            prompt += `- Inhalt der ausgewählten Seite (ID: ${pageId})\n`;
        }
        
        if (extraInfo) {
            prompt += `- Zusätzliche Informationen: ${extraInfo}\n`;
        }
        
        return prompt;
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
            // Get the text to analyze
            const textToAnalyze = await this.getTextToAnalyze(pageId, extraInfo);
            
            // Call the API to extract products and services
            const response = await this.extractProducts(textToAnalyze, provider, testOnly);
            
            if (response.success) {
                this.generatedContent = response.items.join(', ');
                this.showProductsList(response.items, testOnly);
                
                // Enable transfer button
                if (this.transferBtn) {
                    this.transferBtn.disabled = false;
                    this.transferBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    this.transferBtn.classList.add('bg-green-600', 'hover:bg-green-700');
                }
            } else {
                throw new Error(response.message || 'Failed to extract products');
            }
        } catch (error) {
            console.error('Error extracting products:', error);
            this.showError(error.message || 'Ein Fehler ist aufgetreten');
        } finally {
            this.setLoading(false);
        }
    }
    
    /**
     * Get the text to analyze (either from page content or extra info)
     */
    async getTextToAnalyze(pageId, extraInfo) {
        if (pageId) {
            try {
                // Fetch page content via AJAX
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'athena_ai_get_page_content',
                        nonce: athenaAiAdmin.nonce,
                        page_id: pageId
                    })
                });
                
                if (!response.ok) {
                    throw new Error('Failed to fetch page content');
                }
                
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.data || 'Failed to fetch page content');
                }
                
                return data.data.content + (extraInfo ? '\n\n' + extraInfo : '');
            } catch (error) {
                console.error('Error fetching page content:', error);
                return extraInfo;
            }
        }
        
        return extraInfo;
    }
    
    /**
     * Extract products using the API
     */
    async extractProducts(text, provider, testOnly = false) {
        const response = await fetch(athenaAiAdmin.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'athena_ai_extract_products',
                nonce: athenaAiAdmin.nonce,
                text: text,
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
     * Show the list of extracted products
     */
    showProductsList(products, testOnly = false) {
        if (!this.debugOutput) return;
        
        if (testOnly) {
            this.debugOutput.innerHTML = `
                <div class="p-3 bg-gray-100 rounded mb-4">
                    <h4 class="font-semibold mb-2">Test-Modus aktiviert</h4>
                    <p class="mb-2">Extrahiert ${products.length} Produkte/Dienstleistungen:</p>
                    <ul class="list-disc pl-5">
                        ${products.map(item => `<li>${item}</li>`).join('')}
                    </ul>
                </div>
            `;
        } else {
            this.debugOutput.innerHTML = `
                <div class="p-3 bg-green-50 border border-green-200 rounded mb-4">
                    <h4 class="font-semibold text-green-800 mb-2">Erfolgreich extrahiert (${products.length} Einträge)</h4>
                    <div class="bg-white p-3 rounded border">
                        <p class="text-sm text-gray-600 mb-2">Kommagetrennte Liste:</p>
                        <p class="font-mono text-sm p-2 bg-gray-50 rounded">${products.join(', ')}</p>
                    </div>
                </div>
            `;
        }
        
        this.debugOutput.style.display = 'block';
    }
}

export default ProductsModal;
