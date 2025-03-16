document.addEventListener('DOMContentLoaded', function() {
    // Add Project
    document.getElementById('saveNewProject').addEventListener('click', function() {
        const formData = new FormData(document.getElementById('addProjectForm'));
        
        fetch("{{ path('app_parameter_project_add') }}", {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de l\'ajout du projet');
        });
    });

    // Delete Project
    let projectToDelete = null;
    
    document.querySelectorAll('.delete-project').forEach(button => {
        button.addEventListener('click', function() {
            projectToDelete = this.dataset.projectId;
        });
    });

    document.getElementById('confirmDelete').addEventListener('click', function() {
        if (projectToDelete) {
            fetch(`{{ path('app_parameter_project_delete', {'id': '__id__'}) }}`.replace('__id__', projectToDelete), {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Une erreur est survenue lors de la suppression');
            });
        }
    });
});
