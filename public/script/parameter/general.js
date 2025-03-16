document.getElementById("file").addEventListener("change", function (event) {
    const file = event.target.files[0];
    if (file) {
    // Vérifier si le fichier est une image valide
    const validImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (validImageTypes.includes(file.type)) {
        console.log("Fichier sélectionné:", file.name);

        const formData = new FormData();
        formData.append("profile_picture", file);

        fetch("/parameter/generaux", {
        method: "POST",
        body: formData,
        })
        .then((response) => response.json())
        .then((data) => {
            console.log("Données récupérées:", data);
            if (data.success) {
            document.getElementById("output").src = data.newProfilePictureUrl;
            } else {
            console.error("Erreur:", data.error);
            }
        })
        .catch((error) => {
            console.error("Erreur lors de la requête:", error);
        });
    } else {
        alert("Veuillez sélectionner une image valide (jpg, jpeg, png, gif).");
    }
    }
});
