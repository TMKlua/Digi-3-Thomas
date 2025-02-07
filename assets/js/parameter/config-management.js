export class ConfigManager {
    constructor() {
        this.canEdit = document.querySelector('.parameter_content')?.dataset.canEdit === '1';
        this.initSearchForm();
        this.initParameterActions();
        this.initCreateForm();
    }

    initSearchForm() {
        const searchForm = document.getElementById('searchForm');
        if (!searchForm) return;

        const filterParameters = () => {
            const category = document.getElementById('category').value.toLowerCase();
            const searchTerm = document.getElementById('searchTerm').value.toLowerCase();
            const showAll = document.getElementById('showAll').checked;
            const now = new Date();

            document.querySelectorAll('.parameter-row').forEach(row => {
                const rowCategory = row.dataset.category;
                const key = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const value = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const endDate = new Date(row.querySelector('td:nth-child(5)').dataset.date);

                const matchesCategory = !category || rowCategory === category;
                const matchesSearch = !searchTerm || 
                    key.includes(searchTerm) || 
                    value.includes(searchTerm);
                const matchesDate = showAll || endDate > now;

                row.style.display = (matchesCategory && matchesSearch && matchesDate) ? '' : 'none';
            });
        };

        searchForm.querySelectorAll('select, input').forEach(element => {
            element.addEventListener('change', filterParameters);
            element.addEventListener('keyup', filterParameters);
        });
    }

    initParameterActions() {
        if (!this.canEdit) return;

        document.addEventListener('click', e => {
            const editBtn = e.target.closest('.btn-edit-param');
            const deleteBtn = e.target.closest('.btn-delete-param');

            if (editBtn) this.handleEdit(editBtn);
            if (deleteBtn) this.handleDelete(deleteBtn);
        });
    }

    initCreateForm() {
        const form = document.getElementById('createParameterForm');
        if (!form || !this.canEdit) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleCreate(form);
        });
    }

    async handleEdit(button) {
        const paramId = button.dataset.id;
        const paramKey = button.dataset.key;
        const paramValue = button.dataset.value;
        const dateFrom = button.dataset.dateFrom;
        const dateTo = button.dataset.dateTo;

        // Implémenter la logique d'édition avec modal
        // TODO: Créer et afficher une modal d'édition
    }

    async handleDelete(button) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce paramètre ?')) return;

        const paramId = button.dataset.id;
        try {
            const response = await fetch(`/parameter/app_configuration/delete/${paramId}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            if (data.success) {
                button.closest('tr').remove();
                this.showAlert('Paramètre supprimé avec succès', 'success');
            } else {
                this.showAlert(data.message || 'Erreur lors de la suppression', 'danger');
            }
        } catch (error) {
            this.showAlert('Erreur lors de la suppression', 'danger');
            console.error('Erreur:', error);
        }
    }

    async handleCreate(form) {
        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                location.reload(); // Recharger pour afficher le nouveau paramètre
            } else {
                this.showAlert(data.message || 'Erreur lors de la création', 'danger');
            }
        } catch (error) {
            this.showAlert('Erreur lors de la création', 'danger');
            console.error('Erreur:', error);
        }
    }

    showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;
        
        const container = document.querySelector('.parameter_content');
        if (container) {
            container.insertBefore(alertDiv, container.firstChild);
            setTimeout(() => alertDiv.remove(), 5000);
        }
    }
} 