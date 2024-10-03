document.addEventListener('DOMContentLoaded', function () {
    const searchForm = document.getElementById('searchForm');
    const parametersContainer = document.getElementById('parametersContainer');

    searchForm.addEventListener('submit', function (event) {
        event.preventDefault();

        // Récupérer la valeur du champ de recherche
        const searchTerm = document.getElementById('searchInput').value;

        // Envoyer la requête AJAX
        fetch('/parameter/search', {
            method: 'POST',                                                                                           
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'search_term': searchTerm,
            })
        })
        .then(response => response.text())
        .then(data => {
            console.log(data);
            // Nettoyer la zone des paramètres actuels
            parametersContainer.innerHTML = '';
            console.log("toto " + data.parameters);

            try {
                const jsonData = JSON.parse(data);
                jsonData.parameters.forEach(parameter => {
                    const parameterItem = document.createElement('div');
                    parameterItem.classList.add('parameter-item');
                    parameterItem.innerHTML = `<h3>${parameter.paramKey}</h3><p>${parameter.paramValue}</p>`;
                    parametersContainer.appendChild(parameterItem);
                });
            } catch (error) {
                console.error('Error parsing JSON:', error);
            }
            // Afficher les résultats
            data.parameters.forEach(parameter => {
                const parameterItem = document.createElement('div');
                parameterItem.classList.add('parameter-item');
                parameterItem.innerHTML = `<h3>${parameter.ParamKey}</h3><p>${parameter.ParamValue}</p>`;
                parametersContainer.appendChild(parameterItem);
            });
        })
        .catch(error => console.error('Error:', error));
    });
});
