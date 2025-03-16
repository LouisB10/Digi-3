/**
 * Script pour la gestion du header et de la boîte de dialogue de déconnexion
 */

document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la boîte de dialogue de déconnexion
    const logoutDialog = document.getElementById('logout-dialog');
    const closeButton = logoutDialog?.querySelector('.close');
    const cancelButton = logoutDialog?.querySelector('.logout-dialog-buttons button:first-child');
    const logoutButton = logoutDialog?.querySelector('.logout-dialog-buttons button:last-child');
    const logoutForm = document.querySelector('.logout-form');

    // Fonction pour afficher la boîte de dialogue de déconnexion
    window.showLogoutDialog = function(event) {
        if (event) {
            event.preventDefault();
        }
        if (logoutDialog) {
            logoutDialog.removeAttribute('hidden');
        }
        return false;
    };

    // Fonction pour fermer la boîte de dialogue de déconnexion
    function closeLogoutDialog() {
        if (logoutDialog) {
            logoutDialog.setAttribute('hidden', 'true');
        }
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
            logoutForm.submit();
        });
    }

    // Fermer la boîte de dialogue lorsque l'utilisateur clique en dehors
    document.addEventListener('click', function(event) {
        if (logoutDialog && !logoutDialog.hasAttribute('hidden')) {
            const dialogContent = logoutDialog.querySelector('.logout-dialog-content');
            if (dialogContent && !dialogContent.contains(event.target) && event.target !== logoutDialog) {
                closeLogoutDialog();
            }
        }
    });
}); 