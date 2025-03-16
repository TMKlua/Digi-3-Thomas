
function showCreateForm() {
    document.getElementById('createProjectForm').style.display = 'block';
}

function showCreateTaskForm() {
    document.getElementById('createTaskForm').style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function () {
    let projectIdToDelete;

    window.showDeletePopup = function (projectId) {
        projectIdToDelete = projectId;
        document.getElementById('deletePopup').style.display = 'flex';
    };

    document.getElementById('confirmDelete').onclick = function () {
        if (!projectIdToDelete) {
            console.error('Aucun ID de projet à supprimer.');
            return;
        }

        const form = document.createElement('form');
        form.method = 'post';
        form.action = "{{ path('app_project_delete', { 'id': '0' }) }}".replace('0', projectIdToDelete);

        document.body.appendChild(form);
        form.submit();
    };

    document.getElementById('cancelDelete').onclick = function () {
        document.getElementById('deletePopup').style.display = 'none';
    };
});

function showTaskForm() {
    document.getElementById('createTaskForm').style.display = 'block';
}

function showCreateForm() {
    document.getElementById('createProjectForm').style.display = 'block';
}

function showDeletePopup(projectId) {
    const deletePopup = document.getElementById('deletePopup');
    deletePopup.style.display = 'block';

    document.getElementById('confirmDelete').onclick = function () {
        window.location.href = `/delete-project/${projectId}`;
    };

    document.getElementById('cancelDelete').onclick = function () {
        deletePopup.style.display = 'none';
    };
}

document.addEventListener('DOMContentLoaded', function () {
const columns = document.querySelectorAll('.kanban-column');
const tasksContainers = document.querySelectorAll('.kanban-tasks');
let draggedItem = null;

document.querySelectorAll('.task-card').forEach(task => {
    task.addEventListener('dragstart', handleDragStart);
    task.addEventListener('dragend', handleDragEnd);
});

columns.forEach(column => {
    column.addEventListener('dragover', handleDragOver);
    column.addEventListener('drop', handleDrop);
});

tasksContainers.forEach(container => {
    container.addEventListener('dragover', handleDragOver);
    container.addEventListener('drop', handleDrop);
    container.addEventListener('dragenter', function (e) {
        e.preventDefault();
        this.classList.add('drag-over');
    });
    container.addEventListener('dragleave', function (e) {
        e.preventDefault();
        this.classList.remove('drag-over');
    });
});

function handleDragStart(e) {
    draggedItem = this;
    e.dataTransfer.setData('text/plain', this.getAttribute('data-task-id'));
    setTimeout(() => {
        this.style.opacity = '0.5';
    }, 0);
}

function handleDragEnd() {
    this.style.opacity = '1';
    document.querySelectorAll('.drag-over').forEach(element => {
        element.classList.remove('drag-over');
    });
}

function handleDragOver(e) {
    e.preventDefault();
}

function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();

    const taskContainer = this.querySelector('.kanban-tasks') || this;
    const columnStatus = this.getAttribute('data-column');
    const taskId = e.dataTransfer.getData('text/plain');
    const task = document.querySelector(`.task-card[data-task-id="${taskId}"]`);

    if (!task) return;

    const y = e.clientY;
    const sibling = [...taskContainer.querySelectorAll('.task-card:not([data-task-id="' + taskId + '"])')].find(sibling => {
        const box = sibling.getBoundingClientRect();
        return y <= box.top + box.height / 2;
    });

    if (sibling) {
        taskContainer.insertBefore(task, sibling);
    } else {
        taskContainer.appendChild(task);
    }

    const tasksInColumn = taskContainer.querySelectorAll('.task-card');
    const taskIds = Array.from(tasksInColumn).map((taskElement, index) => ({
        id: taskElement.getAttribute('data-task-id'),
        rank: index + 1
    }));

    fetch('/management-project/update-task-position', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            taskId, 
            newColumn: columnStatus, 
            taskOrder: taskIds 
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                displayError(data.error);
            } else {
                console.log('Mise à jour réussie:', data.success);
            }
        })
        .catch(error => {
            displayError('Erreur de connexion au serveur.');
            console.error('Erreur:', error);
        });
}

function displayError(message) {
    let errorBox = document.querySelector('.error-box');
    if (!errorBox) {
        errorBox = document.createElement('div');
        errorBox.className = 'error-box';
        document.body.appendChild(errorBox);
    }
    errorBox.innerText = message;
    errorBox.style.display = 'block';
    setTimeout(() => {
        errorBox.style.display = 'none';
    }, 3000);
}
});