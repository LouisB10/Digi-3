/**
 * assets/js/header.js
 * Script pour la gestion du header et de la boîte de dialogue de déconnexion
 * Améliore l'accessibilité et la gestion des événements
 */

document.addEventListener('DOMContentLoaded', function() {
    // Référence au formulaire de déconnexion
    const logoutForm = document.querySelector('.logout-form');
    const logoutDialog = document.getElementById('logout-dialog');
    const closeButton = logoutDialog.querySelector('.close');
    const cancelButton = logoutDialog.querySelector('.logout-dialog-buttons button:first-child');
    const confirmButton = logoutDialog.querySelector('.logout-dialog-buttons button:last-child');
    
    // Variables pour la gestion du focus
    let lastFocusedElement = null;
    
    // Fonction pour afficher la boîte de dialogue de déconnexion
    function showLogoutDialog(event) {
        if (event) {
            event.preventDefault();
        }
        logoutDialog.removeAttribute('hidden');
        return false;
    }
    
    // Fonction pour masquer la boîte de dialogue
    function hideLogoutDialog() {
        logoutDialog.setAttribute('hidden', '');
    }
    
    // Fonction pour soumettre le formulaire de déconnexion
    function submitLogoutForm() {
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
    
    // Exposer la fonction showLogoutDialog globalement pour la compatibilité avec l'ancien code
    window.showLogoutDialog = showLogoutDialog;
    
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