/**
 * Base Modal Component
 */
class Modal {
    /**
     * @param {string} id Modal element ID
     * @param {Object} options Configuration options
     */
    constructor(id, options = {}) {
        this.id = id;
        this.options = {
            closeOnBackdropClick: true,
            closeOnEsc: true,
            ...options
        };
        
        this.modal = document.getElementById(this.id);
        this.isOpen = false;
        
        if (!this.modal) {
            console.error(`Modal with ID "${this.id}" not found`);
            return;
        }
        
        this.init();
    }
    
    /**
     * Initialize the modal
     */
    init() {
        // Close button
        const closeBtn = this.modal.querySelector('[data-modal-close]');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.close());
        }
        
        // Close on backdrop click
        if (this.options.closeOnBackdropClick) {
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) {
                    this.close();
                }
            });
        }
        
        // Close on ESC key
        if (this.options.closeOnEsc) {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });
        }
    }
    
    /**
     * Open the modal
     */
    open() {
        this.modal.classList.remove('hidden');
        this.modal.classList.add('flex');
        this.isOpen = true;
        document.body.style.overflow = 'hidden';
        
        // Dispatch custom event
        this.modal.dispatchEvent(new CustomEvent('modal:open', { detail: this }));
    }
    
    /**
     * Close the modal
     */
    close() {
        this.modal.classList.add('hidden');
        this.modal.classList.remove('flex');
        this.isOpen = false;
        document.body.style.overflow = '';
        
        // Dispatch custom event
        this.modal.dispatchEvent(new CustomEvent('modal:close', { detail: this }));
    }
    
    /**
     * Toggle modal visibility
     */
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
}

export default Modal;
