/**
 * assets/js/parameter/users.js
 * Gestion des utilisateurs dans les paramètres
 */

let userIdToDelete = null;
let lastFocusedElement = null;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    const userModal = document.getElementById('userModal');
    const deleteConfirmModal = document.getElementById('deleteConfirmModal');
    const userForm = document.getElementById('userForm');
    const csrfToken = document.getElementById('csrf_token').value;
    let currentUserId = null;

    // Initialisation
    initModals();
    initAddButton();
    initEditButtons();
    initDeleteButtons();
    initKeyboardNavigation();

    // Initialisation des modales
    function initModals() {
        // S'assurer que les modales sont cachées au chargement
        hideModal(userModal);
        hideModal(deleteConfirmModal);

        // Gestionnaires pour fermer les modales
        document.querySelectorAll('.close, .btn-cancel').forEach(button => {
            button.addEventListener('click', function() {
                const modal = this.closest('.modal');
                hideModal(modal);
            });
        });

        // Gestionnaire pour soumettre le formulaire utilisateur
        userModal.querySelector('.btn-submit').addEventListener('click', function() {
            submitUserForm();
        });

        // Gestionnaire pour confirmer la suppression
        deleteConfirmModal.querySelector('.btn-danger').addEventListener('click', function() {
            deleteUser(currentUserId);
        });
    }

    // Initialisation du bouton d'ajout
    function initAddButton() {
        const addUserBtn = document.querySelector('.btn-add');
        if (addUserBtn) {
            addUserBtn.addEventListener('click', function() {
                openAddUserModal();
            });
        }
    }

    // Initialisation des boutons d'édition
    function initEditButtons() {
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                openEditUserModal(userId);
            });
        });
    }

    // Initialisation des boutons de suppression
    function initDeleteButtons() {
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const userName = this.getAttribute('data-user-name');
                openDeleteConfirmModal(userId, userName);
            });
        });
    }

    // Initialisation de la navigation au clavier
    function initKeyboardNavigation() {
        // Fermer les modales avec Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                if (userModal && !userModal.hidden) {
                    hideModal(userModal);
                } else if (deleteConfirmModal && !deleteConfirmModal.hidden) {
                    hideModal(deleteConfirmModal);
                }
            }
        });

        // Soumettre le formulaire avec Enter dans le formulaire
        userForm.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') {
                event.preventDefault();
                submitUserForm();
            }
        });
    }

    // Afficher une modale
    function showModal(modal) {
        if (modal) {
            lastFocusedElement = document.activeElement;
            modal.hidden = false;
            modal.setAttribute('aria-modal', 'true');
            
            // Focus sur le premier élément focusable
            const focusableElements = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            if (focusableElements.length > 0) {
                focusableElements[0].focus();
            }
        }
    }

    // Cacher une modale
    function hideModal(modal) {
        if (modal) {
            modal.hidden = true;
            modal.setAttribute('aria-modal', 'false');
            
            // Restaurer le focus
            if (lastFocusedElement) {
                lastFocusedElement.focus();
            }
        }
    }

    // Afficher une alerte
    function showAlert(type, message) {
        const alertContainer = document.createElement('div');
        alertContainer.className = `alert alert-${type}`;
        alertContainer.setAttribute('role', 'alert');
        alertContainer.textContent = message;
        
        document.querySelector('main').prepend(alertContainer);
        
        // Supprimer l'alerte après 5 secondes
        setTimeout(() => {
            alertContainer.remove();
        }, 5000);
    }

    // Ouvrir la modale d'ajout d'utilisateur
    function openAddUserModal() {
        lastFocusedElement = document.activeElement;
        userForm.reset();
        document.getElementById('userId').value = '';
        document.getElementById('userModalTitle').textContent = 'Ajouter un utilisateur';
        document.querySelector('.password-hint').style.display = 'none';
        showModal(userModal);
    }

    // Ouvrir la modale d'édition d'utilisateur
    function openEditUserModal(userId) {
        lastFocusedElement = document.activeElement;
        document.getElementById('userModalTitle').textContent = 'Modifier un utilisateur';
        document.querySelector('.password-hint').style.display = 'block';
        
        // Récupérer les données de l'utilisateur
        fetch(`/parameter/users/${userId}/edit`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors de la récupération des données');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Remplir le formulaire avec les données
                document.getElementById('userId').value = data.user.id;
                document.getElementById('firstName').value = data.user.firstName;
                document.getElementById('lastName').value = data.user.lastName;
                document.getElementById('email').value = data.user.email;
                document.getElementById('role').value = data.user.role;
                document.getElementById('password').value = '';
                
                showModal(userModal);
            } else {
                throw new Error(data.message || 'Erreur lors de la récupération des données');
            }
        })
        .catch(error => {
            showAlert('error', 'Erreur: ' + error.message);
        });
    }

    // Ouvrir la modale de confirmation de suppression
    function openDeleteConfirmModal(userId, userName) {
        lastFocusedElement = document.activeElement;
        currentUserId = userId;
        document.getElementById('userToDeleteName').textContent = userName;
        showModal(deleteConfirmModal);
    }

    // Soumettre le formulaire utilisateur
    function submitUserForm() {
        const formData = new FormData(userForm);
        const userId = document.getElementById('userId').value;
        const url = userId ? `/parameter/users/${userId}/update` : '/parameter/users/create';
        
        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || 'Une erreur est survenue');
                });
            }
            return response.json();
        })
        .then(data => {
            hideModal(userModal);
            showAlert('success', data.message);
            // Recharger la page pour afficher les changements
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        })
        .catch(error => {
            showAlert('error', 'Erreur: ' + error.message);
        });
    }

    // Supprimer un utilisateur
    function deleteUser(userId) {
        fetch(`/parameter/users/${userId}/delete`, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || 'Une erreur est survenue');
                });
            }
            return response.json();
        })
        .then(data => {
            hideModal(deleteConfirmModal);
            showAlert('success', data.message);
            // Recharger la page pour afficher les changements
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        })
        .catch(error => {
            hideModal(deleteConfirmModal);
            showAlert('error', 'Erreur: ' + error.message);
        });
    }
}); 