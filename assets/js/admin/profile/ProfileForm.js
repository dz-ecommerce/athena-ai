import CompanyDescriptionModal from './CompanyDescriptionModal';
import ProductsModal from './ProductsModal';

/**
 * Main class for the profile form
 */
class ProfileForm {
    constructor() {
        this.modals = [];
        this.initializeModals();
        this.initializeEventListeners();
    }

    /**
     * Initialize all modals
     */
    initializeModals() {
        // Company Description Modal
        const companyDescModal = document.getElementById('company-description-modal');
        if (companyDescModal) {
            this.modals.push(
                new CompanyDescriptionModal('company-description-modal', {
                    closeOnBackdropClick: true,
                    closeOnEsc: true,
                })
            );
        }

        // Products Modal
        const productsModal = document.getElementById('products-modal');
        if (productsModal) {
            this.modals.push(
                new ProductsModal('products-modal', {
                    closeOnBackdropClick: true,
                    closeOnEsc: true,
                })
            );
        }

        // Initialize modal toggles
        document.querySelectorAll('[data-modal-toggle]').forEach((toggle) => {
            const modalId = toggle.dataset.modalToggle;
            const modal = this.modals.find((m) => m.id === modalId);

            if (modal) {
                toggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    modal.toggle();
                });
            }
        });
    }

    /**
     * Initialize event listeners
     */
    initializeEventListeners() {
        // Handle content transfer from modals to form fields
        this.modals.forEach((modal) => {
            modal.modal.addEventListener('content:transfer', (e) => {
                const { content, targetField } = e.detail;

                if (targetField) {
                    const targetElement = document.getElementById(targetField);
                    if (targetElement) {
                        targetElement.value = content;

                        // Trigger change event for any listeners
                        const event = new Event('input', { bubbles: true });
                        targetElement.dispatchEvent(event);

                        // Re-initialize floating labels after content transfer
                        this.updateFloatingLabel(targetElement);

                        // Also trigger global reinit if available
                        if (window.athenaAIReinitFloatingLabels) {
                            setTimeout(() => {
                                window.athenaAIReinitFloatingLabels();
                            }, 100);
                        }
                    }
                }
            });
        });

        // Initialize floating labels
        this.initializeFloatingLabels();
    }

    /**
     * Initialize floating labels for form inputs
     */
    initializeFloatingLabels() {
        const formGroups = document.querySelectorAll('.form-group');

        formGroups.forEach((group) => {
            const input = group.querySelector('input, textarea, select');
            const label = group.querySelector('.floating-label');

            if (!input || !label) return;

            // Check if input has a value on page load
            this.updateFloatingLabel(input, label);

            // Add event listeners
            input.addEventListener('focus', () => {
                group.classList.add('focused');
                this.updateFloatingLabel(input, label);
            });

            input.addEventListener('blur', () => {
                group.classList.remove('focused');
                this.updateFloatingLabel(input, label);
            });

            input.addEventListener('input', () => {
                this.updateFloatingLabel(input, label);
            });

            input.addEventListener('change', () => {
                this.updateFloatingLabel(input, label);
            });
        });
    }

    /**
     * Update floating label state
     */
    updateFloatingLabel(input, label = null) {
        const group = input.closest('.form-group');
        if (!group) return;

        if (!label) {
            label = group.querySelector('.floating-label');
        }

        if (!label) return;

        if (input.value && input.value.trim() !== '') {
            input.setAttribute('data-filled', 'true');
            group.classList.add('has-value');
            label.classList.add('floating');
            label.style.opacity = '1';
        } else {
            input.removeAttribute('data-filled');
            group.classList.remove('has-value');
            label.classList.remove('floating');

            // Only hide label if input is not focused
            if (document.activeElement !== input) {
                // Keep label visible but in default position
                label.style.opacity = '1';
            } else {
                label.style.opacity = '1';
            }
        }
    }
}

// Initialize the form when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ProfileForm();
});
