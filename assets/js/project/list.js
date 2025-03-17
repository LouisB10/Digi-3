/**
 * assets/js/project/list.js
 * Script pour la page de liste des projets
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser la page de liste des projets
    if (window.ProjectModule) {
        ProjectModule.initProjectPage();
        
        // Configurer les filtres de projets
        ProjectModule.setupProjectFilters();
    } else {
        console.error('Module ProjectModule non disponible. Assurez-vous que project.js est chargé avant list.js');
    }
    
    // Initialiser les cartes de projet cliquables
    initClickableCards();
});

/**
 * Initialise les cartes de projet cliquables
 */
function initClickableCards() {
    const projectCards = document.querySelectorAll('.project-card');
    projectCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Ne pas déclencher si on a cliqué sur un bouton à l'intérieur de la carte
            if (e.target.closest('button, a')) {
                return;
            }
            
            // Rediriger vers la page de gestion du projet
            const projectId = this.dataset.projectId;
            if (projectId) {
                window.location.href = `/management-project/${projectId}`;
            }
        });
    });
}

// Soumettre le formulaire automatiquement lorsqu'un filtre change
document.querySelectorAll('#status, #customer').forEach(function(select) {
    select.addEventListener('change', function() {
        this.form.submit();
    });
}); 