/**
 * assets/ts/header.ts
 * Script pour la gestion du header et de la boîte de dialogue de déconnexion
 * Améliore l'accessibilité et la gestion des événements
 */

// Exposer la fonction showLogoutDialog globalement
export {}; // Nécessaire pour indiquer que c'est un module

declare global {
  interface Window {
    showLogoutDialog: (event?: Event) => boolean;
  }
}

document.addEventListener('DOMContentLoaded', function() {
    // Référence au formulaire de déconnexion
    const logoutForm = document.querySelector('.logout-form') as HTMLFormElement;
    const logoutDialog = document.getElementById('logout-dialog') as HTMLElement;
    
    // Si les éléments n'existent pas, ne pas continuer l'exécution
    if (!logoutForm || !logoutDialog) return;
    
    const closeButton = logoutDialog.querySelector('.close') as HTMLElement;
    const cancelButton = document.getElementById('cancel-logout') || 
                        logoutDialog.querySelector('.logout-dialog-buttons button:first-child') as HTMLButtonElement;
    const confirmButton = document.getElementById('confirm-logout') || 
                         logoutDialog.querySelector('.logout-dialog-buttons button:last-child') as HTMLButtonElement;
    
    // Variables pour la gestion du focus
    let lastFocusedElement: HTMLElement | null = null;
    
    /**
     * Affiche la boîte de dialogue de déconnexion
     * @param event - L'événement de soumission du formulaire
     * @returns false pour empêcher la soumission du formulaire
     */
    function showLogoutDialog(event?: Event): boolean {
        if (event) {
            event.preventDefault();
        }
        
        // Sauvegarder l'élément qui avait le focus
        lastFocusedElement = document.activeElement as HTMLElement;
        
        // Afficher la boîte de dialogue
        logoutDialog.removeAttribute('hidden');
        
        // Mettre le focus sur le premier bouton
        if (cancelButton) {
            cancelButton.focus();
        }
        
        return false;
    }
    
    /**
     * Masque la boîte de dialogue de déconnexion
     */
    function hideLogoutDialog(): void {
        logoutDialog.setAttribute('hidden', '');
        
        // Restaurer le focus
        if (lastFocusedElement) {
            lastFocusedElement.focus();
        }
    }
    
    /**
     * Soumet le formulaire de déconnexion
     */
    function submitLogoutForm(): void {
        logoutForm.submit();
    }
    
    // Ajouter les écouteurs d'événements
    if (logoutForm) {
        logoutForm.addEventListener('submit', showLogoutDialog);
    }
    
    if (closeButton) {
        closeButton.addEventListener('click', hideLogoutDialog);
    }
    
    if (cancelButton) {
        cancelButton.addEventListener('click', hideLogoutDialog);
    }
    
    if (confirmButton) {
        confirmButton.addEventListener('click', submitLogoutForm);
    }
    
    // Gérer la touche Escape pour fermer la boîte de dialogue
    logoutDialog.addEventListener('keydown', function(event: KeyboardEvent) {
        if (event.key === 'Escape') {
            hideLogoutDialog();
        }
    });
    
    // Exposer la fonction showLogoutDialog globalement pour la compatibilité avec l'ancien code
    window.showLogoutDialog = showLogoutDialog;
    
    // Amélioration de l'accessibilité pour les éléments de navigation
    enhanceNavigationAccessibility();
    
    /**
     * Améliore l'accessibilité des éléments de navigation
     */
    function enhanceNavigationAccessibility(): void {
        const navItems = document.querySelectorAll('nav ul li a');
        
        navItems.forEach(item => {
            // Ajouter des attributs aria supplémentaires si nécessaire
            if (item.parentElement?.classList.contains('active')) {
                item.setAttribute('aria-current', 'page');
            }
            
            // Améliorer l'expérience au clavier
            item.addEventListener('keydown', (e: Event) => {
                const keyEvent = e as KeyboardEvent;
                if (keyEvent.key === 'Enter' || keyEvent.key === ' ') {
                    e.preventDefault();
                    (item as HTMLElement).click();
                }
            });
        });
    }
}); 