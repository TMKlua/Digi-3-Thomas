// Configuration des sélecteurs
const SELECTORS = {
    customerModal: '#customerModal',
    deleteConfirmModal: '#deleteConfirmModal',
    modalTitle: '#customerModalTitle',
    customerForm: '#customerForm',
    saveCustomerBtn: '#saveCustomer',
    confirmDeleteBtn: '#confirmDelete',
    customerToDeleteName: '#customerToDeleteName',
    btnAdd: '.btn-add',
    btnEdit: '.btn-edit',
    btnDelete: '.btn-delete',
    customerPageData: '#customer-page-data'
};

// État de la page
let currentCustomerId = null;
let canEdit = false;
let canDelete = false;

// Initialisation
export function initCustomerManagement() {
    const customerPageData = document.querySelector(SELECTORS.customerPageData);
    
    // Récupérer les permissions depuis les data attributes
    canEdit = customerPageData.dataset.canEdit === '1';
    canDelete = customerPageData.dataset.canDelete === '1';

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
        const headerElement = document.querySelector('.customers-header');
        if (headerElement) {
            const infoMessage = document.createElement('div');
            infoMessage.classList.add('alert', 'alert-info');
            infoMessage.textContent = 'Vous n\'avez pas les permissions nécessaires pour modifier les clients.';
            headerElement.appendChild(infoMessage);
        }
    }
}

// Liaison des événements
function bindEventListeners() {
    const btnAdd = document.querySelector(SELECTORS.btnAdd);
    const btnClose = document.querySelector(`${SELECTORS.customerModal} .btn-close`);
    const btnCancel = document.querySelector(`${SELECTORS.customerModal} .btn-secondary`);

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
                const customerId = button.getAttribute('data-customer-id');
                const customerName = button.getAttribute('data-customer-name');
                const street = button.getAttribute('data-customer-street');
                const zipcode = button.getAttribute('data-customer-zipcode');
                const city = button.getAttribute('data-customer-city');
                const country = button.getAttribute('data-customer-country');
                const vat = button.getAttribute('data-customer-vat');

                openModal('edit', { 
                    customerId, 
                    customerName, 
                    street, 
                    zipcode, 
                    city, 
                    country, 
                    vat 
                });
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
        document.querySelector(SELECTORS.confirmDeleteBtn).addEventListener('click', deleteCustomer);
    }

    // Bouton de sauvegarde
    if (document.querySelector(SELECTORS.saveCustomerBtn) && canEdit) {
        document.querySelector(SELECTORS.saveCustomerBtn).addEventListener('click', handleCustomerSave);
    }
}

function openModal(action, customerData = null) {
    const modal = document.querySelector(SELECTORS.customerModal);
    const modalTitle = document.querySelector('#customerModalTitle');
    const modalAction = document.querySelector('#modalAction');
    const customerId = document.querySelector('#customerId');

    if (modal) {
        // Réinitialiser le formulaire
        document.querySelector(SELECTORS.customerForm).reset();

        if (action === 'add') {
            modalTitle.textContent = 'Ajouter un client';
            modalAction.value = 'add';
            customerId.value = '';
        } else if (action === 'edit' && customerData) {
            modalTitle.textContent = 'Modifier un client';
            modalAction.value = 'edit';
            customerId.value = customerData.customerId;

            // Remplir les champs du formulaire
            document.querySelector('#customerName').value = customerData.customerName;
            document.querySelector('#customerAddressStreet').value = customerData.street;
            document.querySelector('#customerAddressZipcode').value = customerData.zipcode;
            document.querySelector('#customerAddressCity').value = customerData.city;
            document.querySelector('#customerAddressCountry').value = customerData.country;
            document.querySelector('#customerVAT').value = customerData.vat;
        }

        // Afficher la modale
        modal.classList.add('show');
        modal.style.display = 'flex';
    }
}

function closeModal() {
    const modal = document.querySelector(SELECTORS.customerModal);
    if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
    }
}

function handleCustomerSave(e) {
    e.preventDefault();
    const modalAction = document.getElementById('modalAction').value;
    const customerForm = document.querySelector(SELECTORS.customerForm);
    const formData = new FormData(customerForm);

    const url = modalAction === 'add' 
        ? '/parameter/customers/add' 
        : `/parameter/customers/edit/${formData.get('customerId')}`;

    fetchData(url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.success) {
            displayAlert(
                modalAction === 'add' 
                    ? 'Client créé avec succès' 
                    : 'Client modifié avec succès', 
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

function openDeleteConfirmModal(button) {
    currentCustomerId = button.getAttribute('data-customer-id');
    const customerName = button.closest('tr').querySelector('td:first-child').textContent;
    
    const customerToDeleteName = document.querySelector(SELECTORS.customerToDeleteName);
    if (customerToDeleteName) {
        customerToDeleteName.textContent = customerName;
    }
    
    const deleteConfirmModal = document.querySelector(SELECTORS.deleteConfirmModal);
    if (deleteConfirmModal) {
        deleteConfirmModal.classList.add('show');
        deleteConfirmModal.style.display = 'block';
    }
}

async function deleteCustomer() {
    try {
        const response = await fetchData(`/parameter/customers/delete/${currentCustomerId}`, {
            method: 'POST'
        });

        if (response.success) {
            displayAlert('Client supprimé avec succès', 'success');
            closeModals();
            location.reload();
        } else {
            displayAlert(`Erreur : ${response.error}`, 'danger');
        }
    } catch (error) {
        displayAlert('Une erreur est survenue lors de la suppression du client', 'danger');
    }
}

function closeModals() {
    const customerModal = document.querySelector(SELECTORS.customerModal);
    const deleteConfirmModal = document.querySelector(SELECTORS.deleteConfirmModal);

    if (customerModal) {
        customerModal.classList.remove('show');
        customerModal.style.display = 'none';
    }

    if (deleteConfirmModal) {
        deleteConfirmModal.classList.remove('show');
        deleteConfirmModal.style.display = 'none';
    }
}

// Fonction pour afficher les alertes
function displayAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;

    const container = document.querySelector('.customers-page');
    container.insertBefore(alertDiv, container.firstChild);

    // Supprimer l'alerte après 3 secondes
    setTimeout(() => alertDiv.remove(), 3000);
}

// Fonction fetch avec gestion des erreurs
async function fetchData(url, options) {
    try {
        const response = await fetch(url, options);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Erreur de fetch:', error);
        throw error;
    }
}

// Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', initCustomerManagement);
