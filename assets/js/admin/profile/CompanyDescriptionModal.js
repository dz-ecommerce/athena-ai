import AIModal from './AIModal';

/**
 * Modal for generating company descriptions
 */
class CompanyDescriptionModal extends AIModal {
    /**
     * Get the prompt for the API call
     */
    getPrompt(pageId, extraInfo) {
        let prompt = 'Erstelle eine professionelle Unternehmensbeschreibung. ';
        prompt += 'Die Beschreibung sollte maximal 100 Wörter umfassen und folgende Informationen berücksichtigen:\n\n';
        
        if (pageId) {
            prompt += `- Inhalt der ausgewählten Seite (ID: ${pageId})\n`;
        }
        
        if (extraInfo) {
            prompt += `- Zusätzliche Informationen: ${extraInfo}\n`;
        }
        
        prompt += '\nDie Beschreibung sollte professionell und ansprechend formuliert sein. ';
        prompt += 'Verwende eine klare Struktur und achte auf korrekte Rechtschreibung und Grammatik.';
        
        return prompt;
    }
}

export default CompanyDescriptionModal;
