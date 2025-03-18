/**
 * assets/ts/project/list.ts
 * Script pour la page de liste des projets
 */

import { ProjectModuleInterface } from '../types/project';

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser la page de liste des projets
    const ProjectModule: ProjectModuleInterface | undefined = window.ProjectModule;
    
    if (ProjectModule) {
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
function initClickableCards(): void {
    const projectCards = document.querySelectorAll<HTMLElement>('.project-card');
    
    projectCards.forEach(card => {
        card.addEventListener('click', function(e: MouseEvent) {
            // Ne pas déclencher si on a cliqué sur un bouton à l'intérieur de la carte
            if ((e.target as HTMLElement).closest('button, a')) {
                return;
            }
            
            // Rediriger vers la page de gestion du projet
            const projectId = this.dataset.projectId;
            if (projectId) {
                window.location.href = `/management-project/${projectId}`;
            }
        });
        
        // Améliorer l'accessibilité
        if (!card.hasAttribute('tabindex')) {
            card.setAttribute('tabindex', '0');
        }
        
        card.setAttribute('role', 'button');
        card.setAttribute('aria-label', `Voir les détails du projet ${card.querySelector('.project-title')?.textContent || ''}`);
        
        // Gestion du clavier pour l'accessibilité
        card.addEventListener('keydown', function(e: KeyboardEvent) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                
                const projectId = this.dataset.projectId;
                if (projectId) {
                    window.location.href = `/management-project/${projectId}`;
                }
            }
        });
    });
}

// Soumettre le formulaire automatiquement lorsqu'un filtre change
document.querySelectorAll<HTMLSelectElement>('#status, #customer').forEach(function(select) {
    select.addEventListener('change', function() {
        const form = this.closest('form') as HTMLFormElement;
        if (form) {
            form.submit();
        }
    });
}); 