document.addEventListener('DOMContentLoaded', function() {
    // Initialisation du tableau de bord
    initDashboard();
    
    // Gestion des liens de navigation
    setupNavigationLinks();
    
    // Mise à jour des statistiques de projets
    updateProjectStats();
    
    // Affichage des tâches assignées
    displayAssignedTasks();
});

/**
 * Initialise les composants du tableau de bord
 */
function initDashboard() {
    console.log('Initialisation du tableau de bord');
    
    // Ajouter des écouteurs d'événements pour les cartes cliquables
    const clickableCards = document.querySelectorAll('.clickable-card');
    clickableCards.forEach(card => {
        card.addEventListener('click', function() {
            const target = this.dataset.target;
            if (target) {
                window.location.href = target;
            }
        });
    });
}

/**
 * Configure les liens de navigation avec des transitions fluides
 */
function setupNavigationLinks() {
    const navLinks = document.querySelectorAll('.sidebar-nav a');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Si le lien n'a pas d'URL valide, empêcher la navigation
            if (this.getAttribute('href') === '#') {
                e.preventDefault();
                console.log('Navigation vers une section non implémentée');
            }
        });
    });
}

/**
 * Met à jour l'affichage des statistiques de projets
 */
function updateProjectStats() {
    // Cette fonction pourrait être utilisée pour mettre à jour dynamiquement 
    // les statistiques de projets via une requête AJAX si nécessaire
    
    const projectStatsElement = document.getElementById('project-stats');
    if (projectStatsElement) {
        // Mise à jour des barres de progression
        const progressBars = document.querySelectorAll('.progress-bar .progress');
        progressBars.forEach(bar => {
            const percentage = bar.dataset.percentage || '0';
            bar.style.width = `${percentage}%`;
        });
    }
}

/**
 * Affiche les tâches assignées à l'utilisateur
 */
function displayAssignedTasks() {
    const tasksContainer = document.getElementById('assigned-tasks');
    if (!tasksContainer) return;
    
    // Ajouter des écouteurs d'événements pour les tâches cliquables
    const taskItems = document.querySelectorAll('.task-item');
    taskItems.forEach(item => {
        item.addEventListener('click', function() {
            const taskId = this.dataset.taskId;
            if (taskId) {
                window.location.href = `/project/task/${taskId}`;
            }
        });
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
