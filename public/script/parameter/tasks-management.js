// Configuration des sélecteurs
const SELECTORS = {
    projectModal: '#addProjectModal',
    editProjectModal: '#editProjectModal',
    deleteConfirmModal: '#deleteProjectModal',
    projectForm: '#addProjectForm',
    saveProjectBtn: '#saveNewProject',
    confirmDeleteBtn: '#confirmDelete',
    btnAdd: '.btn-add',
    btnEdit: '.btn-edit',
    btnDelete: '.btn-delete',
    projectPageData: '#project-page-data'
};

// État de la page
let currentProjectId = null;
let canEdit = false;
let canDelete = false;

// Initialisation
export function initProjectManagement() {
    const projectPageData = document.querySelector(SELECTORS.projectPageData);
    
    // Récupérer les permissions depuis les data attributes
    canEdit = projectPageData.dataset.canEdit === '1';
    canDelete = projectPageData.dataset.canDelete === '1';

    // Gestion des boutons selon les permissions
    handlePermissionBasedVisibility();
    bindEventListeners();
}

// Gestion de la visibilité des boutons selon les permissions
function handlePermissionBasedVisibility() {
    const buttons = document.querySelectorAll(`${SELECTORS.btnEdit}, ${SELECTORS.btnDelete}, ${SELECTORS.btnAdd}`);
    
    buttons.forEach(btn => {
        if (btn) {
            if (btn.matches(SELECTORS.btnEdit) && !canEdit) {
                btn.style.display = 'none';
            }
            if (btn.matches(SELECTORS.btnDelete) && !canDelete) {
                btn.style.display = 'none';
            }
            if (btn.matches(SELECTORS.btnAdd) && !canEdit) {
                btn.style.display = 'none';
            }
        }
    });

    if (!canEdit && !canDelete) {
        const headerElement = document.querySelector('.projects-header');
        if (headerElement) {
            const infoMessage = document.createElement('div');
            infoMessage.classList.add('alert', 'alert-info');
            infoMessage.textContent = 'Vous n\'avez pas les permissions nécessaires pour modifier les projets.';
            headerElement.appendChild(infoMessage);
        }
    }
}

// Liaison des événements
function bindEventListeners() {
    // Gestion de l'ajout
    if (document.querySelector(SELECTORS.saveProjectBtn) && canEdit) {
        document.querySelector(SELECTORS.saveProjectBtn).addEventListener('click', handleProjectSave);
    }

    // Gestion de l'édition
    if (canEdit) {
        document.querySelectorAll(SELECTORS.btnEdit).forEach(button => {
            button.addEventListener('click', () => {
                const projectId = button.getAttribute('data-project-id');
                const name = button.closest('tr').querySelector('td:nth-child(1)').textContent;
                const description = button.closest('tr').querySelector('td:nth-child(2)').textContent;
                const complexity = button.closest('tr').querySelector('td:nth-child(3)').textContent;
                const priority = button.closest('tr').querySelector('td:nth-child(4)').textContent;
                const startDate = button.closest('tr').querySelector('td:nth-child(5)').textContent;
                const endDate = button.closest('tr').querySelector('td:nth-child(6)').textContent;

                openEditModal({ 
                    projectId, 
                    name, 
                    description, 
                    complexity, 
                    priority, 
                    startDate, 
                    endDate 
                });
            });
        });
    }

    // Gestion de la suppression
    if (canDelete) {
        document.querySelectorAll(SELECTORS.btnDelete).forEach(button => {
            button.addEventListener('click', () => {
                currentProjectId = button.getAttribute('data-project-id');
            });
        });

        if (document.querySelector(SELECTORS.confirmDeleteBtn)) {
            document.querySelector(SELECTORS.confirmDeleteBtn).addEventListener('click', deleteProject);
        }
    }
}

function handleProjectSave(e) {
    e.preventDefault();
    const projectForm = document.querySelector(SELECTORS.projectForm);
    const formData = new FormData(projectForm);

    fetchData('/parameter/projects/add', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.success) {
            displayAlert('Projet créé avec succès', 'success');
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

function openEditModal(projectData) {
    const modal = document.querySelector(SELECTORS.editProjectModal);
    if (modal) {
        document.querySelector('#edit-name').value = projectData.name;
        document.querySelector('#edit-description').value = projectData.description;
        document.querySelector('#edit-complexity').value = projectData.complexity;
        document.querySelector('#edit-priority').value = projectData.priority;
        document.querySelector('#edit-targetStartDate').value = projectData.startDate;
        document.querySelector('#edit-targetEndDate').value = projectData.endDate;
        document.querySelector('#edit-projectId').value = projectData.projectId;
    }
}

async function deleteProject() {
    try {
        const response = await fetchData(`/parameter/projects/delete/${currentProjectId}`, {
            method: 'POST'
        });

        if (response.success) {
            displayAlert('Projet supprimé avec succès', 'success');
            location.reload();
        } else {
            displayAlert(`Erreur : ${response.error}`, 'danger');
        }
    } catch (error) {
        displayAlert('Une erreur est survenue lors de la suppression du projet', 'danger');
    }
}

// Fonction pour afficher les alertes
function displayAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;

    const container = document.querySelector('.projects-page');
    container.insertBefore(alertDiv, container.firstChild);

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
document.addEventListener('DOMContentLoaded', initProjectManagement);
