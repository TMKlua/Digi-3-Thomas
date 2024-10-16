document.addEventListener("DOMContentLoaded", () => {
  const searchForm = document.getElementById("searchForm");
  const parameterTable = document.getElementById("parameter_table");
  const deleteBtns = document.querySelectorAll('#deleteBtn');
  // const createForm = document.getElementById('createForm');

  searchForm.addEventListener("input", (event) => {
    event.preventDefault(); // Empêche le rechargement de la page lors de la soumission du formulaire

    const formData = new FormData(searchForm); // Récupère toutes les données du formulaire

    fetch("/parameter/search", {
      method: "POST",
      body: formData, // Envoie les données du formulaire
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Erreur réseau : " + response.status);
        }
        return response.json();
      })
      .then((data) => {
        // Vérifie si des paramètres ont été trouvés
        if (data.parameters.length > 0) {
          parameterTable.innerHTML = data.html; // Met à jour le tableau avec les résultats
        } else {
          // Si aucun paramètre n'est trouvé, afficher un message ou un tableau vide
          parameterTable.innerHTML =
            '<tr><td colspan="5">Aucun paramètre trouvé.</td></tr>';
        }
      })
      .catch((error) => console.error("Erreur:", error));
  });

  deleteBtns.forEach(button => {
    button.addEventListener('click', () => {
        const parameterId = button.getAttribute('data-id'); // Récupère l'ID du paramètre
        const row = button.closest('tr'); // Récupère la ligne correspondante

        if (confirm('Êtes-vous sûr de vouloir supprimer ce paramètre ?')) {
            // Envoie une requête AJAX pour supprimer le paramètre
            fetch(`/parameter/delete/${parameterId}`, {
                method: 'DELETE', // Utilise la méthode DELETE
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest' // Indique que c'est une requête AJAX
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors de la suppression : ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Supprime la ligne du tableau si la suppression est réussie
                    row.remove();
                    alert('Paramètre supprimé avec succès.');
                } else {
                    alert('Erreur lors de la suppression : ' + data.message);
                }
            })
            .catch(error => console.error('Erreur:', error));
        }
    });
});

  // Gestion de la création de nouveaux paramètres
  //  createForm.addEventListener('submit', (event) => {
  //     event.preventDefault();

  //     const formData = new FormData(createForm); // Récupère les données du formulaire

  //     fetch('/parameter/create', {
  //         method: 'POST',
  //         body: formData // Envoie les données du formulaire
  //     })
  //     .then(response => response.ok ? response.json() : Promise.reject('Erreur réseau : ' + response.status))
  //     .then(data => {
  //         if (data.success) {
  //             // Ajouter le nouveau paramètre à la liste
  //             const parameterItem = document.createElement('div');
  //             parameterItem.classList.add('parameter-item');
  //             parameterItem.innerHTML = `<h3>${data.parameter.paramKey}</h3><p>${data.parameter.paramValue}</p>`;
  //             parameterTable.appendChild(parameterItem);

  //             // Réinitialiser le formulaire
  //             createForm.reset();
  //         } else {
  //             console.error('Erreur lors de la création du paramètre.');
  //         }
  //     })
  //     .catch(error => console.error('Erreur:', error));
  // });
});
