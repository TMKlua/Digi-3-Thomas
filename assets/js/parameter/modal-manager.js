export class ModalManager {
    constructor() {
        this.activeModal = null;
        this.backdrop = null;
        this.initBackdrop();
    }

    initBackdrop() {
        this.backdrop = document.createElement('div');
        this.backdrop.className = 'modal-backdrop';
        this.backdrop.addEventListener('click', () => this.closeActiveModal());
    }

    showModal(modalElement) {
        if (!modalElement) return;
        
        this.activeModal = modalElement;
        document.body.appendChild(this.backdrop);
        modalElement.style.display = 'block';
        
        setTimeout(() => {
            this.backdrop.classList.add('show');
            modalElement.classList.add('show');
        }, 10);

        // Gestion de la fermeture
        modalElement.querySelectorAll('[data-dismiss="modal"]').forEach(button => {
            button.addEventListener('click', () => this.closeActiveModal());
        });
    }

    closeActiveModal() {
        if (!this.activeModal) return;

        this.activeModal.classList.remove('show');
        this.backdrop.classList.remove('show');

        setTimeout(() => {
            this.activeModal.style.display = 'none';
            document.body.removeChild(this.backdrop);
            this.activeModal = null;
        }, 300);
    }
} 