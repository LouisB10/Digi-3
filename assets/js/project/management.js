/**
 * assets/js/project/management.js
 * Script pour la gestion des projets et des tâches avec le tableau Kanban
 */

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
        form.action = `/management-project/delete/${projectIdToDelete}`;
 
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
 
// Fonction pour ouvrir la modal de détail de tâche
function openTaskModal(taskElement) {
    // Récupérer les données de la tâche depuis les attributs data-
    const id = taskElement.getAttribute('data-task-id');
    const name = taskElement.getAttribute('data-name');
    const description = taskElement.getAttribute('data-description');
    const type = taskElement.getAttribute('data-type');
    const status = taskElement.getAttribute('data-status');
    const complexity = taskElement.getAttribute('data-complexity');
    const dateFrom = taskElement.getAttribute('data-date-from');
    const dateTo = taskElement.getAttribute('data-date-to');
    
    // Remplir les détails de la tâche
    document.getElementById('taskTitle').textContent = name;
    document.getElementById('taskDescription').textContent = description;
    document.getElementById('taskType').textContent = type;
    document.getElementById('taskStatus').textContent = status;
    document.getElementById('taskCategory').textContent = complexity;
    document.getElementById('taskDateFrom').textContent = dateFrom;
    document.getElementById('taskDateTo').textContent = dateTo;
    
    // Configurer le lien de retour
    document.getElementById('taskProjectLink').href = window.location.href;
    
    // Afficher la modal avec flexbox
    const modal = document.getElementById('taskDetailModal');
    modal.style.display = 'flex';
    
    // Empêcher le défilement de la page
    document.body.style.overflow = 'hidden';
}
 
// Fonction pour fermer la modal
function closeTaskModal() {
    document.getElementById('taskDetailModal').style.display = 'none';
    // Réactiver le défilement
    document.body.style.overflow = 'auto';
}
 
// Fermer la modal si on clique en dehors du contenu
window.onclick = function(event) {
    const modal = document.getElementById('taskDetailModal');
    if (event.target === modal) {
        closeTaskModal();
    }
} 