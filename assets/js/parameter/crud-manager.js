import { ModalManager } from './modal-manager';

export class CrudManager {
    constructor(config) {
        this.config = {
            entityName: '',
            baseUrl: '',
            selectors: {},
            ...config
        };
        
        this.modalManager = new ModalManager();
        this.currentId = null;
        this.canEdit = false;
        this.canDelete = false;
        
        document.addEventListener('DOMContentLoaded', () => this.init());
    }

    init() {
        const pageData = document.querySelector(this.config.selectors.pageData);
        if (!pageData) return;

        this.canEdit = pageData.dataset.canEdit === '1';
        this.canDelete = pageData.dataset.canDelete === '1';

        this.handlePermissionBasedVisibility();
        this.bindEventListeners();
    }

    handlePermissionBasedVisibility() {
        const { btnEdit, btnDelete, btnAdd } = this.config.selectors;
        const buttons = document.querySelectorAll(`${btnEdit}, ${btnDelete}, ${btnAdd}`);
        
        buttons.forEach(btn => {
            if (btn.matches(btnEdit) && !this.canEdit) btn.style.display = 'none';
            if (btn.matches(btnDelete) && !this.canDelete) btn.style.display = 'none';
            if (btn.matches(btnAdd) && !this.canEdit) btn.style.display = 'none';
        });
    }

    bindEventListeners() {
        this.bindAddButton();
        this.bindEditButtons();
        this.bindDeleteButtons();
        this.bindSaveButton();
        this.bindConfirmDeleteButton();
    }

    bindAddButton() {
        const btnAdd = document.querySelector(this.config.selectors.btnAdd);
        if (btnAdd && this.canEdit) {
            btnAdd.addEventListener('click', () => this.openModal('add'));
        }
    }

    bindEditButtons() {
        if (!this.canEdit) return;
        document.querySelectorAll(this.config.selectors.btnEdit).forEach(button => {
            button.addEventListener('click', () => {
                const data = this.extractDataFromButton(button);
                this.openModal('edit', data);
            });
        });
    }

    bindDeleteButtons() {
        if (!this.canDelete) return;
        document.querySelectorAll(this.config.selectors.btnDelete).forEach(button => {
            button.addEventListener('click', () => this.openDeleteConfirmModal(button));
        });
    }

    bindSaveButton() {
        const saveBtn = document.querySelector(this.config.selectors.saveBtn);
        if (saveBtn && this.canEdit) {
            saveBtn.addEventListener('click', (e) => this.handleSave(e));
        }
    }

    bindConfirmDeleteButton() {
        const confirmBtn = document.querySelector(this.config.selectors.confirmDeleteBtn);
        if (confirmBtn && this.canDelete) {
            confirmBtn.addEventListener('click', () => this.handleDelete());
        }
    }

    openModal(action, data = null) {
        const modal = document.querySelector(this.config.selectors.modal);
        if (!modal) return;

        const form = modal.querySelector('form');
        if (form) form.reset();

        this.setModalMode(action, data);
        this.modalManager.showModal(modal);
    }

    setModalMode(action, data = null) {
        const modal = document.querySelector(this.config.selectors.modal);
        const modalTitle = modal.querySelector('.modal-title');
        const modalAction = modal.querySelector('#modalAction');
        const idInput = modal.querySelector(`#${this.config.entityName}Id`);

        if (action === 'add') {
            modalTitle.textContent = `Ajouter un ${this.config.entityLabel}`;
            modalAction.value = 'add';
            idInput.value = '';
        } else if (action === 'edit' && data) {
            modalTitle.textContent = `Modifier un ${this.config.entityLabel}`;
            modalAction.value = 'edit';
            idInput.value = data.id;
            this.fillFormWithData(data);
        }
    }

    openDeleteConfirmModal(button) {
        const modal = document.querySelector(this.config.selectors.deleteConfirmModal);
        if (!modal) return;

        this.currentId = button.dataset.id;
        const name = this.getEntityName(button);
        const nameSpan = modal.querySelector(`#${this.config.entityName}ToDeleteName`);
        if (nameSpan) nameSpan.textContent = name;

        this.modalManager.showModal(modal);
    }

    async handleSave(e) {
        e.preventDefault();
        const form = document.querySelector(this.config.selectors.form);
        const formData = new FormData(form);
        const action = formData.get('modalAction');
        const id = formData.get(`${this.config.entityName}Id`);

        const url = action === 'add' 
            ? `${this.config.baseUrl}/add` 
            : `${this.config.baseUrl}/edit/${id}`;

        try {
            const response = await this.fetchData(url, {
                method: 'POST',
                body: formData
            });

            if (response.success) {
                this.displayAlert(
                    action === 'add' 
                        ? `${this.config.entityLabel} créé avec succès` 
                        : `${this.config.entityLabel} modifié avec succès`, 
                    'success'
                );
                location.reload();
            } else {
                this.displayAlert(`Erreur : ${response.error}`, 'danger');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.displayAlert('Une erreur est survenue', 'danger');
        }
    }

    async handleDelete() {
        if (!this.currentId) return;

        try {
            const response = await this.fetchData(`${this.config.baseUrl}/delete/${this.currentId}`, {
                method: 'POST'
            });

            if (response.success) {
                this.displayAlert(`${this.config.entityLabel} supprimé avec succès`, 'success');
                this.modalManager.closeActiveModal();
                location.reload();
            } else {
                this.displayAlert(`Erreur : ${response.error}`, 'danger');
            }
        } catch (error) {
            this.displayAlert(`Une erreur est survenue lors de la suppression`, 'danger');
        }
    }

    async fetchData(url, options = {}) {
        try {
            const response = await fetch(url, {
                ...options,
                headers: {
                    ...options.headers,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Fetch error:', error);
            throw error;
        }
    }

    displayAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;
        
        const container = document.querySelector('.parameter_content');
        if (container) {
            container.insertBefore(alertDiv, container.firstChild);
            setTimeout(() => alertDiv.remove(), 5000);
        }
    }

    // Méthodes à implémenter dans les classes enfants
    extractDataFromButton(button) {
        throw new Error('Method not implemented');
    }

    getEntityName(button) {
        throw new Error('Method not implemented');
    }

    fillFormWithData(data) {
        throw new Error('Method not implemented');
    }
} 