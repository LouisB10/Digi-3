document.addEventListener('DOMContentLoaded', function() {
    // Initialisation de la page de projets
    initProjectPage();
    
    // Configuration des filtres de projets
    setupProjectFilters();
    
    // Gestion des tâches
    setupTaskManagement();
});

/**
 * Initialise la page de projets
 */
function initProjectPage() {
    console.log('Initialisation de la page de projets');
    
    // Ajouter des écouteurs d'événements pour les cartes de projet cliquables
    const projectCards = document.querySelectorAll('.project-card');
    projectCards.forEach(card => {
        card.addEventListener('click', function() {
            const projectId = this.dataset.projectId;
            if (projectId) {
                window.location.href = `/management-project/${projectId}`;
            }
        });
    });
    
    // Initialiser les tooltips si présents
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(tooltip => {
            new bootstrap.Tooltip(tooltip);
        });
    }
}

/**
 * Configure les filtres de projets
 */
function setupProjectFilters() {
    // Soumission automatique du formulaire lors du changement de filtre
    const filterForm = document.getElementById('project-filter-form');
    if (!filterForm) return;
    
    const filterInputs = filterForm.querySelectorAll('select, input[type="radio"]');
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            filterForm.submit();
        });
    });
    
    // Réinitialisation des filtres
    const resetButton = document.getElementById('reset-filters');
    if (resetButton) {
        resetButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Réinitialiser tous les champs du formulaire
            filterInputs.forEach(input => {
                if (input.tagName === 'SELECT') {
                    input.selectedIndex = 0;
                } else if (input.type === 'radio') {
                    input.checked = false;
                }
            });
            
            // Soumettre le formulaire
            filterForm.submit();
        });
    }
}

/**
 * Configure la gestion des tâches
 */
function setupTaskManagement() {
    // Gestion du changement de statut des tâches
    const statusSelects = document.querySelectorAll('.task-status-select');
    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const taskId = this.dataset.taskId;
            const newStatus = this.value;
            
            if (taskId && newStatus) {
                updateTaskStatus(taskId, newStatus);
            }
        });
    });
    
    // Gestion de l'ajout de tâche
    const addTaskForm = document.getElementById('add-task-form');
    if (addTaskForm) {
        addTaskForm.addEventListener('submit', function(e) {
            // La validation est gérée par le backend
        });
    }
}

/**
 * Met à jour le statut d'une tâche via une requête AJAX
 * @param {string} taskId - L'ID de la tâche
 * @param {string} newStatus - Le nouveau statut
 */
function updateTaskStatus(taskId, newStatus) {
    // Créer les données à envoyer
    const data = {
        taskId: taskId,
        newStatus: newStatus
    };
    
    // Envoyer la requête AJAX
    fetch('/management-project/update-task-status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erreur lors de la mise à jour du statut');
        }
        return response.json();
    })
    .then(data => {
        console.log('Statut mis à jour avec succès', data);
        
        // Mettre à jour l'interface utilisateur
        const taskElement = document.querySelector(`.task-item[data-task-id="${taskId}"]`);
        if (taskElement) {
            // Mettre à jour les classes CSS en fonction du nouveau statut
            taskElement.className = taskElement.className.replace(/status-\w+/, `status-${newStatus.toLowerCase()}`);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de la mise à jour du statut de la tâche.');
    });
}

/**
 * Formate une date pour l'affichage
 * @param {string} dateString - La date au format chaîne
 * @returns {string} - La date formatée
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}
