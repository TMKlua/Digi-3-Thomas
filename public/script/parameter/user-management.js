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

// État de la page
let currentUserId = null;
let canEdit = false;
let canDelete = false;

// Initialisation
export function initUserManagement() {
    const userPageData = document.querySelector(SELECTORS.userPageData);
    
    // Récupérer les permissions depuis les data attributes
    canEdit = userPageData.dataset.canEdit === '1';
    canDelete = userPageData.dataset.canDelete === '1';

    // Gestion des boutons selon les permissions
    handlePermissionBasedVisibility();

    bindEventListeners();
}

// Gestion de la visibilité des boutons selon les permissions
function handlePermissionBasedVisibility() {
    const buttons = document.querySelectorAll(`${SELECTORS.btnEdit}, ${SELECTORS.btnDelete}, ${SELECTORS.btnAdd}`);
    
    buttons.forEach(btn => {
        if (btn) {
            // Masquer les boutons d'édition si pas de permission
            if (btn.matches(SELECTORS.btnEdit) && !canEdit) {
                btn.style.display = 'none';
            }
            
            // Masquer les boutons de suppression si pas de permission
            if (btn.matches(SELECTORS.btnDelete) && !canDelete) {
                btn.style.display = 'none';
            }
            
            // Masquer le bouton d'ajout si pas de permission d'édition
            if (btn.matches(SELECTORS.btnAdd) && !canEdit) {
                btn.style.display = 'none';
            }
        }
    });

    // Message informatif si aucune permission
    if (!canEdit && !canDelete) {
        const headerElement = document.querySelector('.users-header');
        if (headerElement) {
            const infoMessage = document.createElement('div');
            infoMessage.classList.add('alert', 'alert-info');
            infoMessage.textContent = 'Vous n\'avez pas les permissions nécessaires pour modifier les utilisateurs.';
            headerElement.appendChild(infoMessage);
        }
    }
}

// Liaison des événements
function bindEventListeners() {
    const btnAdd = document.querySelector(SELECTORS.btnAdd);
    const btnClose = document.querySelector(`${SELECTORS.userModal} .btn-close`);
    const btnCancel = document.querySelector(`${SELECTORS.userModal} .btn-secondary`);

    if (btnAdd && canEdit) {
        btnAdd.addEventListener('click', () => openModal('add'));
    }

    if (btnClose) {
        btnClose.addEventListener('click', closeModal);
    }

    if (btnCancel) {
        btnCancel.addEventListener('click', closeModal);
    }

    // Boutons d'édition
    if (canEdit) {
        document.querySelectorAll(SELECTORS.btnEdit).forEach(button => {
            button.addEventListener('click', () => {
                const userId = button.getAttribute('data-user-id');
                const firstName = button.getAttribute('data-user-firstname');
                const lastName = button.getAttribute('data-user-lastname');
                const email = button.getAttribute('data-user-email');
                const role = button.getAttribute('data-user-role');

                openModal('edit', { userId, firstName, lastName, email, role });
            });
        });
    }

    // Boutons de suppression
    if (canDelete) {
        document.querySelectorAll(SELECTORS.btnDelete).forEach(button => {
            button.addEventListener('click', () => openDeleteConfirmModal(button));
        });
    }

    // Confirmation de suppression
    if (document.querySelector(SELECTORS.confirmDeleteBtn) && canDelete) {
        document.querySelector(SELECTORS.confirmDeleteBtn).addEventListener('click', deleteUser);
    }

    // Bouton de sauvegarde
    if (document.querySelector(SELECTORS.saveUserBtn) && canEdit) {
        document.querySelector(SELECTORS.saveUserBtn).addEventListener('click', handleUserSave);
    }
}

function openModal(action, userData = null) {
    const modal = document.querySelector(SELECTORS.userModal);
    const modalTitle = document.querySelector('#userModalTitle');
    const modalAction = document.querySelector('#modalAction');
    const userId = document.querySelector('#userId');

    if (modal) {
        // Réinitialiser le formulaire
        document.querySelector(SELECTORS.userForm).reset();

        if (action === 'add') {
            modalTitle.textContent = 'Ajouter un utilisateur';
            modalAction.value = 'add';
            userId.value = '';
        } else if (action === 'edit' && userData) {
            modalTitle.textContent = 'Modifier un utilisateur';
            modalAction.value = 'edit';
            userId.value = userData.userId;

            // Remplir les champs du formulaire
            document.querySelector('#firstName').value = userData.firstName;
            document.querySelector('#lastName').value = userData.lastName;
            document.querySelector('#email').value = userData.email;
            document.querySelector('#role').value = userData.role;
        }

        // Afficher la modale
        modal.classList.add('show');
        modal.style.display = 'flex';
    }
}

function closeModal() {
    const modal = document.querySelector(SELECTORS.userModal);
    if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
    }
}

function handleUserSave(e) {
    e.preventDefault();
    const modalAction = document.getElementById('modalAction').value;
    const userForm = document.querySelector(SELECTORS.userForm);
    const formData = new FormData(userForm);

    const url = modalAction === 'add' 
        ? '/parameter/users/add' 
        : `/parameter/users/edit/${formData.get('userId')}`;

    fetchData(url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.success) {
            displayAlert(
                modalAction === 'add' 
                    ? 'Utilisateur créé avec succès' 
                    : 'Utilisateur modifié avec succès', 
                'success'
            );
            location.reload();
        } else {
            displayAlert(`Erreur : ${response.error}`, 'danger');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        displayAlert('Une erreur est survenue', 'danger');
    });
}

// Fonction de suppression de modale
function openDeleteConfirmModal(button) {
    currentUserId = button.getAttribute('data-user-id');
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

// Fonctions CRUD
async function deleteUser() {
    try {
        // Utilisez l'URL directe avec l'ID
        const response = await fetchData(`/parameter/users/delete/${currentUserId}`, {
            method: 'POST'
        });

        if (response.success) {
            displayAlert('Utilisateur supprimé avec succès', 'success');
            closeModals();
            location.reload();
        } else {
            displayAlert(`Erreur : ${response.error}`, 'danger');
        }
    } catch (error) {
        displayAlert('Une erreur est survenue lors de la suppression de l\'utilisateur', 'danger');
    }
}

// Fonction de fermeture des modales
function closeModals() {
    const userModal = document.querySelector(SELECTORS.userModal);
    const deleteConfirmModal = document.querySelector(SELECTORS.deleteConfirmModal);

    if (userModal) {
        userModal.classList.remove('show');
        userModal.style.display = 'none';
    }

    if (deleteConfirmModal) {
        deleteConfirmModal.classList.remove('show');
        deleteConfirmModal.style.display = 'none';
    }
}

// Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', initUserManagement);

// Modification de fetchData pour plus de logs
async function fetchData(url, options) {
    console.log('URL de la requête:', url);
    console.log('Options de la requête:', options);

    try {
        const response = await fetch(url, options);
        
        console.log('Statut de la réponse:', response.status);
        
        const data = await response.json();
        console.log('Données de la réponse:', data);
        
        return data;
    } catch (error) {
        console.error('Erreur de fetch:', error);
        throw error;
    }
}