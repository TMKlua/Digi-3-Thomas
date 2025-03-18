document.addEventListener('DOMContentLoaded', function() {
    // Toggle Add User Form visibility
    document.getElementById('toggleAddUserForm').addEventListener('click', function() {
        const formContainer = document.getElementById('addUserFormContainer');
        formContainer.style.display = 'block';
    });

    // Hide form when cancel button is clicked
    document.getElementById('cancelAddUser').addEventListener('click', function() {
        const formContainer = document.getElementById('addUserFormContainer');
        formContainer.style.display = 'none';
        document.getElementById('addUserForm').reset();
    });

    // Add User
    document.getElementById('saveNewUser').addEventListener('click', function() {
        const formData = new FormData(document.getElementById('addUserForm'));
        
        fetch(addUserUrl, {
            method: 'POST',
            body: formData
        })        
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Utilisateur créé avec succès !');
                location.reload();
            } else {
                alert('Erreur lors de la création de l\'utilisateur : ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de l\'ajout de l\'utilisateur');
        });
    });
});
