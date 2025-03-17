/**
 * assets/js/header.js
 * Script pour la gestion du header et de la boîte de dialogue de déconnexion
 * Améliore l'accessibilité et la gestion des événements
 */

document.addEventListener('DOMContentLoaded', function() {
    // Sélection des éléments DOM
    const logoutDialog = document.getElementById('logout-dialog');
    const closeButton = logoutDialog?.querySelector('.close');
    const cancelButton = logoutDialog?.querySelector('.logout-dialog-buttons button:first-child');
    const logoutButton = logoutDialog?.querySelector('.logout-dialog-buttons button:last-child');
    const logoutForm = document.querySelector('.logout-form');
    
    // Variables pour la gestion du focus
    let lastFocusedElement = null;
    
    // Fonction pour afficher la boîte de dialogue de déconnexion
    window.showLogoutDialog = function(event) {
        if (event) {
            event.preventDefault();
        }
        
        if (!logoutDialog) {
            console.error("Élément de dialogue de déconnexion non trouvé");
            return false;
        }
        
        // Stocker l'élément qui avait le focus avant d'ouvrir la modale
        lastFocusedElement = document.activeElement;
        
        // Afficher la boîte de dialogue
        logoutDialog.removeAttribute('hidden');
        
        // Mettre le focus sur le premier élément interactif de la modale
        if (closeButton) {
            closeButton.focus();
        }
        
        // Ajouter un gestionnaire pour la touche Echap
        document.addEventListener('keydown', handleEscapeKey);
        
        // Piéger le focus dans la modale
        trapFocus(logoutDialog);
        
        return false;
    };

    // Fonction pour fermer la boîte de dialogue de déconnexion
    function closeLogoutDialog() {
        if (!logoutDialog) return;
        
        logoutDialog.setAttribute('hidden', 'true');
        
        // Supprimer les gestionnaires d'événements temporaires
        document.removeEventListener('keydown', handleEscapeKey);
        
        // Restaurer le focus à l'élément qui l'avait avant l'ouverture
        if (lastFocusedElement) {
            lastFocusedElement.focus();
        }
    }
    
    // Gestionnaire pour la touche Echap
    function handleEscapeKey(event) {
        if (event.key === 'Escape') {
            closeLogoutDialog();
        }
    }
    
    // Fonction pour piéger le focus dans la modale
    function trapFocus(dialogElement) {
        if (!dialogElement) return;
        
        // Trouver tous les éléments focusables dans la modale
        const focusableElements = dialogElement.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        if (focusableElements.length === 0) return;
        
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        // Ajouter un gestionnaire pour maintenir le focus dans la modale
        dialogElement.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                // Si Shift+Tab est pressé et que le focus est sur le premier élément,
                // déplacer le focus vers le dernier élément
                if (e.shiftKey && document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                } 
                // Si Tab est pressé et que le focus est sur le dernier élément,
                // déplacer le focus vers le premier élément
                else if (!e.shiftKey && document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        });
    }

    // Gestionnaires d'événements pour les boutons de la boîte de dialogue
    if (closeButton) {
        closeButton.addEventListener('click', closeLogoutDialog);
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', closeLogoutDialog);
    }

    if (logoutButton && logoutForm) {
        logoutButton.addEventListener('click', function() {
            try {
                logoutForm.submit();
            } catch (error) {
                console.error("Erreur lors de la soumission du formulaire de déconnexion:", error);
                // Afficher un message d'erreur à l'utilisateur
                alert("Une erreur est survenue lors de la déconnexion. Veuillez réessayer.");
            }
        });
    }
    
    // Amélioration de l'accessibilité pour les éléments de navigation
    enhanceNavigationAccessibility();
    
    // Fonction pour améliorer l'accessibilité de la navigation
    function enhanceNavigationAccessibility() {
        const navItems = document.querySelectorAll('nav ul li a');
        
        navItems.forEach(item => {
            // Ajouter des attributs aria supplémentaires si nécessaire
            if (item.parentElement.classList.contains('active')) {
                item.setAttribute('aria-current', 'page');
            }
            
            // Améliorer l'expérience au clavier
            item.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    item.click();
                }
            });
        });
    }
}); 