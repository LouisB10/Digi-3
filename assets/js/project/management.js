/**
 * assets/js/project/management.js
 * Script pour la gestion des projets et des tâches avec le tableau Kanban
 */

function showCreateForm() {
    document.getElementById('createProjectForm').style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function() {
    // Bouton pour afficher le formulaire de création de projet
    const createProjectBtn = document.getElementById('createProjectBtn');
    if (createProjectBtn) {
        createProjectBtn.addEventListener('click', showCreateForm);
    }

    // Bouton pour afficher le formulaire de création de tâche
    const createTaskBtn = document.getElementById('createTaskBtn');
    if (createTaskBtn) {
        createTaskBtn.addEventListener('click', function() {
            document.getElementById('createTaskForm').style.display = 'block';
        });
    }

    // Gestion des popup de confirmation pour la suppression
    const deleteButtons = document.querySelectorAll('.delete-button');
    const deletePopup = document.getElementById('deletePopup');
    const confirmDelete = document.getElementById('confirmDelete');
    const cancelDelete = document.getElementById('cancelDelete');
    let projectIdToDelete = null;

    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            projectIdToDelete = this.getAttribute('data-project-id');
            deletePopup.style.display = 'flex';
        });
    });

    if (cancelDelete) {
        cancelDelete.addEventListener('click', function() {
            deletePopup.style.display = 'none';
        });
    }

    if (confirmDelete) {
        confirmDelete.addEventListener('click', function() {
            if (projectIdToDelete) {
                const token = document.getElementById('delete-project-token').value;
                
                fetch('/parameter/project/delete/' + projectIdToDelete, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': token
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '/management/project';
                    } else {
                        alert('Erreur lors de la suppression: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la suppression du projet.');
                })
                .finally(() => {
                    deletePopup.style.display = 'none';
                });
            }
        });
    }

    // Gestion de la modal de détail de tâche
    const taskCards = document.querySelectorAll('.task-card');
    const taskModal = document.getElementById('taskDetailModal');
    const closeTaskModal = document.querySelector('.task-modal-close');

    taskCards.forEach(function(card) {
        card.addEventListener('click', function() {
            const taskId = this.getAttribute('data-task-id');
            const taskName = this.getAttribute('data-name');
            const taskDescription = this.getAttribute('data-description');
            const taskType = this.getAttribute('data-type');
            const taskStatus = this.getAttribute('data-status');
            const taskComplexity = this.getAttribute('data-complexity');
            const taskDateFrom = this.getAttribute('data-date-from');
            const taskDateTo = this.getAttribute('data-date-to');

            document.getElementById('taskTitle').textContent = taskName;
            document.getElementById('taskDescription').textContent = taskDescription;
            document.getElementById('taskType').textContent = taskType;
            document.getElementById('taskStatus').textContent = taskStatus;
            document.getElementById('taskCategory').textContent = taskComplexity;
            document.getElementById('taskDateFrom').textContent = taskDateFrom;
            document.getElementById('taskDateTo').textContent = taskDateTo;

            taskModal.style.display = 'flex';
        });
    });

    if (closeTaskModal) {
        closeTaskModal.addEventListener('click', function() {
            taskModal.style.display = 'none';
        });
    }

    // Fermeture des modales en cliquant en dehors
    window.addEventListener('click', function(event) {
        if (event.target === taskModal) {
            taskModal.style.display = 'none';
        }
        if (event.target === deletePopup) {
            deletePopup.style.display = 'none';
        }
    });
}); 