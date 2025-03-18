/**
 * assets/js/project/project.js
 * Module central pour les fonctionnalités de projet
 */

// Créer un objet global pour les fonctionnalités de projet
window.ProjectModule = {
    // Fonctions utilitaires partagées
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    },

    // Fonction pour annoncer des messages aux lecteurs d'écran
    announceForScreenReader: function(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'assertive');
        announcement.setAttribute('role', 'status');
        announcement.className = 'visually-hidden';
        announcement.textContent = message;
        document.body.appendChild(announcement);
        
        // Supprimer l'annonce après qu'elle ait été lue
        setTimeout(() => {
            document.body.removeChild(announcement);
        }, 3000);
    },

    // Initialisation de la page de projets
    initProjectPage: function() {
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
    },

    // Configuration des filtres de projets
    setupProjectFilters: function() {
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
    },

    // Met à jour le statut d'une tâche via une requête AJAX
    updateTaskStatus: function(taskId, newStatus, onSuccess) {
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
            
            if (onSuccess && typeof onSuccess === 'function') {
                onSuccess(data);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            ProjectModule.announceForScreenReader('Une erreur est survenue lors de la mise à jour du statut de la tâche.');
            alert('Une erreur est survenue lors de la mise à jour du statut de la tâche.');
        });
    },

    // Met à jour la position d'une tâche via une requête AJAX
    updateTaskPosition: function(taskId, newColumn, taskOrder, onSuccess) {
        // Créer les données à envoyer
        const data = {
            taskId: taskId,
            newColumn: newColumn,
            taskOrder: taskOrder
        };
        
        // Envoyer la requête AJAX
        fetch('/management-project/update-task-position', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors de la mise à jour de la position');
            }
            return response.json();
        })
        .then(data => {
            console.log('Position mise à jour avec succès', data);
            
            if (onSuccess && typeof onSuccess === 'function') {
                onSuccess(data);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            ProjectModule.announceForScreenReader('Une erreur est survenue lors de la mise à jour de la position de la tâche.');
            alert('Une erreur est survenue lors de la mise à jour de la position de la tâche.');
        });
    },

    // Configuration de la gestion des tâches
    setupTaskManagement: function() {
        // Gestion du changement de statut des tâches
        const statusSelects = document.querySelectorAll('.task-status-select');
        statusSelects.forEach(select => {
            select.addEventListener('change', function() {
                const taskId = this.dataset.taskId;
                const newStatus = this.value;
                
                if (taskId && newStatus) {
                    ProjectModule.updateTaskStatus(taskId, newStatus);
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
    },

    // Initialisation générale
    init: function() {
        document.addEventListener('DOMContentLoaded', function() {
            // Détection de la page actuelle
            const path = window.location.pathname;
            
            // Initialisation spécifique selon la page
            if (path.includes('/projects-list')) {
                ProjectModule.initProjectPage();
                ProjectModule.setupProjectFilters();
            }
            
            // Configuration générale des tâches (commune à plusieurs pages)
            ProjectModule.setupTaskManagement();
        });
    }
};

// Initialiser le module
ProjectModule.init(); 