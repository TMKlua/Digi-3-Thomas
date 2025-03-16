// Fonction pour ouvrir la popup avec les détails de la tâche
function openTaskModal(id, name, description, type, status, category, dateFrom, dateTo) {
    // Remplir les éléments de la popup avec les détails de la tâche
    document.getElementById("taskTitle").innerText = name;
    document.getElementById("taskDescription").innerText = description;
    document.getElementById("taskType").innerText = type;
    document.getElementById("taskStatus").innerText = status;
    document.getElementById("taskCategory").innerText = category;
    document.getElementById("taskDateFrom").innerText = dateFrom;
    document.getElementById("taskDateTo").innerText = dateTo;

    // Met à jour le lien "Retour au projet"
    let projectLink = document.getElementById("taskProjectLink");
    projectLink.href = "/app_management_project/" + id;  // Remplace cela avec l'URL appropriée de ton projet

    // Afficher la popup
    document.getElementById("taskDetailModal").style.display = "flex"; // Utilisation de flex pour centrer
}

// Fonction pour fermer la popup
function closeTaskModal() {
    document.getElementById("taskDetailModal").style.display = "none";
}

// Ferme la popup si on clique en dehors du contenu
window.onclick = function(event) {
    let modal = document.getElementById("taskDetailModal");
    if (event.target === modal) {
        modal.style.display = "none";
    }
};
