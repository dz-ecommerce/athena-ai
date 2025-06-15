/**
 * AI Prompt Manager
 *
 * Verwaltet AI-Prompts aus der YAML-Konfiguration
 * Ersetzt die bisherigen hidden inputs in den Modals
 */
class PromptManager {
    constructor() {
        this.prompts = {};
        this.globalSettings = {};
        this.validationRules = {};
        this.loaded = false;

        // Prompts aus PHP laden
        this.loadPromptsFromPHP();
    }

    /**
     * Prompts aus PHP-Backend laden
     */
    loadPromptsFromPHP() {
        // Prüfen ob Prompts bereits im DOM verfügbar sind
        const promptData = document.getElementById('athena-ai-prompt-config');
        if (promptData) {
            try {
                const config = JSON.parse(promptData.textContent);
                this.prompts = config.prompts || {};
                this.globalSettings = config.global || {};
                this.validationRules = config.validation || {};
                this.loaded = true;

                console.log('Athena AI: Prompts aus DOM geladen', this.prompts);
            } catch (error) {
                console.error('Athena AI: Fehler beim Laden der Prompt-Konfiguration:', error);
                this.loadFallbackPrompts();
            }
        } else {
            // Fallback: AJAX-Request
            this.loadPromptsViaAjax();
        }
    }

    /**
     * Prompts via AJAX laden
     */
    async loadPromptsViaAjax() {
        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'athena_ai_get_prompt_config',
                    nonce: athenaAiAjax?.nonce || '',
                }),
            });

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.prompts = data.data.prompts || {};
                    this.globalSettings = data.data.global || {};
                    this.validationRules = data.data.validation || {};
                    this.loaded = true;

                    console.log('Athena AI: Prompts via AJAX geladen');
                } else {
                    throw new Error(data.data || 'Unbekannter Fehler');
                }
            } else {
                throw new Error('HTTP ' + response.status);
            }
        } catch (error) {
            console.error('Athena AI: AJAX-Fehler beim Laden der Prompts:', error);
            this.loadFallbackPrompts();
        }
    }

    /**
     * Fallback-Prompts laden (bisherige hidden inputs)
     */
    loadFallbackPrompts() {
        console.log('Athena AI: Lade Fallback-Prompts aus hidden inputs');

        // Company Description
        const companyIntro = document.getElementById('athena-ai-prompt-intro');
        const companyLimit = document.getElementById('athena-ai-prompt-limit');
        const companyTarget = document.getElementById('athena-ai-target-field');

        if (companyIntro && companyLimit && companyTarget) {
            this.prompts.company_description = {
                intro: companyIntro.value,
                limit: companyLimit.value,
                target_field: companyTarget.value,
            };
        }

        // Products
        const productsIntro = document.getElementById('athena-ai-prompt-intro-products');
        const productsLimit = document.getElementById('athena-ai-prompt-limit-products');
        const productsTarget = document.getElementById('athena-ai-target-field-products');

        if (productsIntro && productsLimit && productsTarget) {
            this.prompts.products = {
                intro: productsIntro.value,
                limit: productsLimit.value,
                target_field: productsTarget.value,
            };
        }

        this.loaded = true;
    }

    /**
     * Prompt für einen Modal-Typ abrufen
     */
    getPrompt(modalType, promptPart = null) {
        if (!this.loaded) {
            console.warn('Athena AI: Prompts noch nicht geladen');
            return null;
        }

        if (!this.prompts[modalType]) {
            console.warn(`Athena AI: Prompt für Modal-Typ "${modalType}" nicht gefunden`);
            return null;
        }

        if (promptPart === null) {
            return this.prompts[modalType];
        }

        return this.prompts[modalType][promptPart] || null;
    }

    /**
     * Vollständigen Prompt zusammenbauen
     */
    buildFullPrompt(modalType, extraInfo = '') {
        const config = this.getPrompt(modalType);
        if (!config) {
            return '';
        }

        let fullPrompt = config.intro || '';

        if (extraInfo.trim()) {
            fullPrompt += '\n\n' + extraInfo.trim();
        }

        if (config.limit) {
            fullPrompt += '\n\n' + config.limit;
        }

        return fullPrompt;
    }

    /**
     * Zielfeld für einen Modal-Typ abrufen
     */
    getTargetField(modalType) {
        return this.getPrompt(modalType, 'target_field');
    }

    /**
     * Validierungsregeln abrufen
     */
    getValidationRules() {
        return this.validationRules;
    }

    /**
     * Eingabe validieren
     */
    validateInput(input) {
        const rules = this.getValidationRules();
        const errors = [];

        if (rules.min_input_length && input.length < rules.min_input_length) {
            errors.push(`Eingabe muss mindestens ${rules.min_input_length} Zeichen lang sein`);
        }

        if (rules.max_input_length && input.length > rules.max_input_length) {
            errors.push(`Eingabe darf maximal ${rules.max_input_length} Zeichen lang sein`);
        }

        return {
            valid: errors.length === 0,
            errors: errors,
        };
    }

    /**
     * Globale Einstellung abrufen
     */
    getGlobalSetting(key) {
        return this.globalSettings[key] || null;
    }

    /**
     * Verfügbare Modal-Typen abrufen
     */
    getAvailableModals() {
        return Object.keys(this.prompts);
    }

    /**
     * Debug-Informationen abrufen
     */
    getDebugInfo() {
        return {
            loaded: this.loaded,
            promptCount: Object.keys(this.prompts).length,
            availableModals: this.getAvailableModals(),
            globalSettings: this.globalSettings,
            validationRules: this.validationRules,
        };
    }

    /**
     * Prompt-Konfiguration für ein Modal aktualisieren
     * (für dynamische Updates)
     */
    updatePromptConfig(modalType, config) {
        if (this.prompts[modalType]) {
            this.prompts[modalType] = { ...this.prompts[modalType], ...config };
            console.log(`Athena AI: Prompt-Konfiguration für "${modalType}" aktualisiert`);
        }
    }

    /**
     * Prüfen ob ein Modal-Typ unterstützt wird
     */
    isModalSupported(modalType) {
        return this.prompts.hasOwnProperty(modalType);
    }

    /**
     * Prompt-Statistiken abrufen
     */
    getPromptStats(modalType) {
        const config = this.getPrompt(modalType);
        if (!config) {
            return null;
        }

        return {
            hasIntro: !!config.intro,
            hasLimit: !!config.limit,
            hasTargetField: !!config.target_field,
            maxWords: config.max_words || null,
            maxItems: config.max_items || null,
            format: config.format || 'text',
            introLength: config.intro ? config.intro.length : 0,
            limitLength: config.limit ? config.limit.length : 0,
        };
    }
}

// Globale Instanz erstellen
window.athenaAiPromptManager = new PromptManager();

// Export für Module
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PromptManager;
}
