
document.addEventListener('DOMContentLoaded', function() {
// Références aux éléments
const addCustomerBtn = document.getElementById('addCustomerBtn');
const addCustomerModal = document.getElementById('addCustomerModal');
const closeAddModalBtn = document.getElementById('closeAddModal');
const cancelAddCustomer = document.getElementById('cancelAddCustomer');
const deleteCustomerModal = document.getElementById('deleteCustomerModal');
const closeDeleteModalBtn = document.getElementById('closeDeleteModal');
const cancelDelete = document.getElementById('cancelDelete');
const deleteButtons = document.querySelectorAll('.delete-customer');

// Variable pour vérifier l'état de la requête
let isRequestInProgress = false;

// Fonction pour fermer le modal d'ajout
function closeAddModal() {
    addCustomerModal.style.display = 'none';
    document.body.style.overflow = '';
}

// Fonction pour ouvrir le modal d'ajout
function openAddModal() {
    addCustomerModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Fonction pour fermer le modal de suppression
function closeDeleteModal() {
    deleteCustomerModal.style.display = 'none';
    document.body.style.overflow = '';
}

// Fonction pour ouvrir le modal de suppression
function openDeleteModal() {
    deleteCustomerModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Événements pour le modal d'ajout
addCustomerBtn.addEventListener('click', openAddModal);
closeAddModalBtn.addEventListener('click', closeAddModal);
cancelAddCustomer.addEventListener('click', closeAddModal);

addCustomerModal.addEventListener('click', function(e) {
    if (e.target === addCustomerModal) {
        closeAddModal();
    }
});

// Événements pour le modal de suppression
deleteButtons.forEach(button => {
    button.addEventListener('click', function() {
        const customerId = this.getAttribute('data-customer-id');
        document.getElementById('confirmDelete').setAttribute('data-customer-id', customerId);
        openDeleteModal();
    });
});

closeDeleteModalBtn.addEventListener('click', closeDeleteModal);
cancelDelete.addEventListener('click', closeDeleteModal);

deleteCustomerModal.addEventListener('click', function(e) {
    if (e.target === deleteCustomerModal) {
        closeDeleteModal();
    }
});

// Sauvegarder un nouveau client
document.getElementById('saveNewCustomer').addEventListener('click', function() {
    // Empêcher plusieurs envois de requêtes en cours
    if (isRequestInProgress) return;

    const formData = new FormData(document.getElementById('addCustomerForm'));
    isRequestInProgress = true; // Marquer la requête comme en cours
    
    fetch('/parameter/customers/add', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        isRequestInProgress = false; // Requête terminée
        if (data.success) {
            window.location.reload();
        } else {
            alert('Erreur lors de l\'ajout du client: ' + data.message);
        }
    })
    .catch(error => {
        isRequestInProgress = false; // Requête terminée
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de l\'ajout du client.');
    });
});

document.getElementById('confirmDelete').addEventListener('click', function() {
const customerId = this.getAttribute('data-customer-id');

fetch('/parameter/customers/delete/' + customerId, {
    method: 'DELETE',  // Utilisez DELETE ici
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json', // Ajoutez si nécessaire
    }
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        window.location.reload();
    } else {
        alert('Erreur lors de la suppression du client: ' + data.message);
    }
})
.catch(error => {
    console.error('Erreur:', error);
    alert('Une erreur est survenue lors de la suppression du client.');
});

closeDeleteModal();
});



// Gestion des boutons d'édition
document.querySelectorAll('.edit-customer').forEach(button => {
    button.addEventListener('click', function() {
        const customerId = this.getAttribute('data-customer-id');
        window.location.href = '/parameter/customers/edit/' + customerId;
    });
});
});
