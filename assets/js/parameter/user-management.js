// Configuration des sélecteurs
const SELECTORS = {
    userModal: '#userModal',
    deleteConfirmModal: '#deleteConfirmModal',
    modalTitle: '#userModalTitle',
    userForm: '#userForm',
    saveUserBtn: '#saveUser',
    confirmDeleteBtn: '#confirmDelete',
    userToDeleteName: '#userToDeleteName',
    btnAdd: '.btn-add',
    btnEdit: '.btn-edit',
    btnDelete: '.btn-delete',
    userPageData: '#user-page-data'
};

export class UserManager {
    constructor() {
        this.currentUserId = null;
        this.canEdit = false;
        this.canDelete = false;
        
        document.addEventListener('DOMContentLoaded', () => this.init());
    }

    init() {
        const userPageData = document.querySelector(SELECTORS.userPageData);
        if (!userPageData) return;

        // Récupérer les permissions
        this.canEdit = userPageData.dataset.canEdit === '1';
        this.canDelete = userPageData.dataset.canDelete === '1';

        this.handlePermissionBasedVisibility();
        this.bindEventListeners();
    }

    handlePermissionBasedVisibility() {
        const buttons = document.querySelectorAll(`${SELECTORS.btnEdit}, ${SELECTORS.btnDelete}, ${SELECTORS.btnAdd}`);
        
        buttons.forEach(btn => {
            if (btn.matches(SELECTORS.btnEdit) && !this.canEdit) {
                btn.style.display = 'none';
            }
            if (btn.matches(SELECTORS.btnDelete) && !this.canDelete) {
                btn.style.display = 'none';
            }
            if (btn.matches(SELECTORS.btnAdd) && !this.canEdit) {
                btn.style.display = 'none';
            }
        });

        if (!this.canEdit && !this.canDelete) {
            const headerElement = document.querySelector('.users-header');
            if (headerElement) {
                const infoMessage = document.createElement('div');
                infoMessage.classList.add('alert', 'alert-info');
                infoMessage.textContent = 'Vous n\'avez pas les permissions nécessaires pour modifier les utilisateurs.';
                headerElement.appendChild(infoMessage);
            }
        }
    }

    bindEventListeners() {
        // Bouton Ajouter
        const btnAdd = document.querySelector(SELECTORS.btnAdd);
        if (btnAdd && this.canEdit) {
            btnAdd.addEventListener('click', () => this.openModal('add'));
        }

        // Boutons de fermeture modal
        const btnClose = document.querySelector(`${SELECTORS.userModal} .btn-close`);
        const btnCancel = document.querySelector(`${SELECTORS.userModal} .btn-secondary`);
        if (btnClose) btnClose.addEventListener('click', () => this.closeModal());
        if (btnCancel) btnCancel.addEventListener('click', () => this.closeModal());

        // Boutons d'édition
        if (this.canEdit) {
            document.querySelectorAll(SELECTORS.btnEdit).forEach(button => {
                button.addEventListener('click', () => {
                    const userData = {
                        userId: button.getAttribute('data-user-id'),
                        firstName: button.getAttribute('data-user-firstname'),
                        lastName: button.getAttribute('data-user-lastname'),
                        email: button.getAttribute('data-user-email'),
                        role: button.getAttribute('data-user-role')
                    };
                    this.openModal('edit', userData);
                });
            });
        }

        // Boutons de suppression
        if (this.canDelete) {
            document.querySelectorAll(SELECTORS.btnDelete).forEach(button => {
                button.addEventListener('click', () => this.openDeleteConfirmModal(button));
            });
        }

        // Confirmation de suppression
        const confirmDeleteBtn = document.querySelector(SELECTORS.confirmDeleteBtn);
        if (confirmDeleteBtn && this.canDelete) {
            confirmDeleteBtn.addEventListener('click', () => this.deleteUser());
        }

        // Sauvegarde utilisateur
        const saveUserBtn = document.querySelector(SELECTORS.saveUserBtn);
        if (saveUserBtn && this.canEdit) {
            saveUserBtn.addEventListener('click', (e) => this.handleUserSave(e));
        }
    }

    openModal(action, userData = null) {
        const modal = document.querySelector(SELECTORS.userModal);
        if (!modal) return;

        const modalTitle = document.querySelector('#userModalTitle');
        const modalAction = document.querySelector('#modalAction');
        const userId = document.querySelector('#userId');
        const form = document.querySelector(SELECTORS.userForm);

        form.reset();

        if (action === 'add') {
            modalTitle.textContent = 'Ajouter un utilisateur';
            modalAction.value = 'add';
            userId.value = '';
        } else if (action === 'edit' && userData) {
            modalTitle.textContent = 'Modifier un utilisateur';
            modalAction.value = 'edit';
            userId.value = userData.userId;

            // Remplir le formulaire
            Object.keys(userData).forEach(key => {
                const input = document.querySelector(`#${key}`);
                if (input) input.value = userData[key];
            });
        }

        modal.classList.add('show');
        modal.style.display = 'flex';
    }

    closeModal() {
        const modal = document.querySelector(SELECTORS.userModal);
        if (modal) {
            modal.classList.remove('show');
            modal.style.display = 'none';
        }
    }

    async handleUserSave(e) {
        e.preventDefault();
        const modalAction = document.getElementById('modalAction').value;
        const form = document.querySelector(SELECTORS.userForm);
        const formData = new FormData(form);

        const url = modalAction === 'add' 
            ? '/parameter/users/add' 
            : `/parameter/users/edit/${formData.get('userId')}`;

        try {
            const response = await this.fetchData(url, {
                method: 'POST',
                body: formData
            });

            if (response.success) {
                this.displayAlert(
                    modalAction === 'add' 
                        ? 'Utilisateur créé avec succès' 
                        : 'Utilisateur modifié avec succès', 
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

    openDeleteConfirmModal(button) {
        this.currentUserId = button.getAttribute('data-user-id');
        const userName = `${button.closest('tr').querySelector('td:nth-child(3)').textContent} ${button.closest('tr').querySelector('td:nth-child(2)').textContent}`;
        
        const userToDeleteName = document.querySelector(SELECTORS.userToDeleteName);
        if (userToDeleteName) {
            userToDeleteName.textContent = userName;
        }
        
        const deleteConfirmModal = document.querySelector(SELECTORS.deleteConfirmModal);
        if (deleteConfirmModal) {
            deleteConfirmModal.classList.add('show');
            deleteConfirmModal.style.display = 'block';
        }
    }

    async deleteUser() {
        try {
            const response = await this.fetchData(`/parameter/users/delete/${this.currentUserId}`, {
                method: 'POST'
            });

            if (response.success) {
                this.displayAlert('Utilisateur supprimé avec succès', 'success');
                this.closeModals();
                location.reload();
            } else {
                this.displayAlert(`Erreur : ${response.error}`, 'danger');
            }
        } catch (error) {
            this.displayAlert('Une erreur est survenue lors de la suppression de l\'utilisateur', 'danger');
        }
    }

    closeModals() {
        [SELECTORS.userModal, SELECTORS.deleteConfirmModal].forEach(selector => {
            const modal = document.querySelector(selector);
            if (modal) {
                modal.classList.remove('show');
                modal.style.display = 'none';
            }
        });
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
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        document.querySelector('.container')?.prepend(alertDiv);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
}

// Initialisation
new UserManager(); 