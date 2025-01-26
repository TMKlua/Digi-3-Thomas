// Fonctions pour la gestion des paramètres
export class ParameterManager {
    constructor() {
        this.searchForm = document.getElementById("searchForm");
        this.parameterTable = document.getElementById("parameter_table");
        this.createForm = document.getElementById("createForm");
        this.deleteBtns = document.querySelectorAll("#deleteBtn");
        
        this.init();
    }

    init() {
        this.initDateCheck();
        this.initSearchForm();
        this.initDeleteButtons();
        this.initCreateForm();
    }

    initDateCheck() {
        document.querySelectorAll('.parameter-item').forEach((item) => {
            const paramDateTo = new Date(item.dataset.paramDateTo);
            const currentDate = new Date();

            if (paramDateTo < currentDate) {
                item.querySelector('#deleteBtn').disabled = true;
                item.querySelector('#editBtn').disabled = true;
            }
        });
    }

    initSearchForm() {
        this.searchForm?.addEventListener("input", (event) => {
            event.preventDefault();
            const formData = new FormData(this.searchForm);

            fetch("/parameter/search", {
                method: "POST",
                body: formData,
            })
            .then(this.handleResponse)
            .then(data => {
                if (data.parameters.length > 0) {
                    this.parameterTable.innerHTML = data.html;
                } else {
                    this.parameterTable.innerHTML = '<tr><td colspan="5">Aucun paramètre trouvé.</td></tr>';
                }
            })
            .catch(this.handleError);
        });
    }

    initDeleteButtons() {
        this.deleteBtns.forEach(button => {
            button.addEventListener("click", () => this.handleDelete(button));
        });
    }

    initCreateForm() {
        this.createForm?.addEventListener("submit", (event) => {
            event.preventDefault();
            const formData = new FormData(this.createForm);

            fetch("/parameter/create", {
                method: "POST",
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                }
            })
            .then(this.handleResponse)
            .then(data => {
                if (data.success) {
                    this.parameterTable.innerHTML = data.html;
                    this.createForm.reset();
                } else {
                    console.error("Erreur lors de la création du paramètre.");
                }
            })
            .catch(this.handleError);
        });
    }

    handleDelete(button) {
        const parameterId = button.getAttribute("data-id");
        const searchData = {
            searchTerm: document.querySelector("#search_form_searchTerm")?.value,
            showAll: document.querySelector("#search_form_showAll")?.checked,
            dateSelect: document.querySelector("#search_form_dateSelect")?.value
        };

        if (confirm("Êtes-vous sûr de vouloir supprimer ce paramètre ?")) {
            fetch(`/parameter/delete/${parameterId}`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify(searchData),
            })
            .then(this.handleResponse)
            .then(data => {
                if (data.success) {
                    document.querySelector("#parameter_table").innerHTML = data.html;
                } else {
                    alert("Erreur lors de la suppression : " + data.message);
                }
            })
            .catch(this.handleError);
        }
    }

    handleResponse(response) {
        if (!response.ok) {
            throw new Error("Erreur réseau : " + response.status);
        }
        return response.json();
    }

    handleError(error) {
        console.error("Erreur:", error);
    }
}

// Initialisation
document.addEventListener("DOMContentLoaded", () => {
    new ParameterManager();
}); 