export class ConfigManager {
    constructor() {
        this.initTabs();
        this.initForms();
    }

    initTabs() {
        const tabs = document.querySelectorAll('.tab');
        const panels = document.querySelectorAll('.category-panel');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const category = tab.dataset.category;

                // Désactiver tous les onglets et panneaux
                tabs.forEach(t => t.classList.remove('active'));
                panels.forEach(p => p.classList.remove('active'));

                // Activer l'onglet et le panneau courants
                tab.classList.add('active');
                document.getElementById(`${category}-params`).classList.add('active');
            });
        });
    }

    initForms() {
        // Gestion du formulaire de création
        const createForm = document.getElementById('createParameterForm');
        if (createForm) {
            createForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(createForm);

                try {
                    const response = await fetch(createForm.action, {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Mettre à jour le tableau correspondant
                        const category = formData.get('paramCategory');
                        const tableBody = document.getElementById(`${category}-parameter-table`);
                        if (tableBody) {
                            tableBody.innerHTML = data.html;
                        }
                        createForm.reset();
                    } else {
                        alert(data.error || 'Une erreur est survenue');
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la création du paramètre');
                }
            });
        }

        // Gestion des actions sur les paramètres
        document.addEventListener('click', async (e) => {
            const editButton = e.target.closest('.btn-edit-param');
            const deleteButton = e.target.closest('.btn-delete-param');

            if (editButton) {
                const paramId = editButton.dataset.id;
                // Logique d'édition à implémenter
            }

            if (deleteButton) {
                const paramId = deleteButton.dataset.id;
                if (confirm('Êtes-vous sûr de vouloir supprimer ce paramètre ?')) {
                    try {
                        const response = await fetch(`/parameter/delete/${paramId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            const row = deleteButton.closest('tr');
                            if (row) {
                                row.remove();
                            }
                        } else {
                            alert(data.error || 'Une erreur est survenue');
                        }
                    } catch (error) {
                        console.error('Erreur:', error);
                        alert('Une erreur est survenue lors de la suppression');
                    }
                }
            }
        });
    }
} 