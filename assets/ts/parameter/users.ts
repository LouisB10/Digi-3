/**
 * assets/ts/parameter/users.ts
 * Gestion des utilisateurs dans les paramètres
 */

// Importation des types et services
import { UserResponse, ApiResponse } from '../types/api';
import { showModal, hideModal, initModalCloseEvents } from '../services/modal';
import { showAlert } from '../services/notification';

// Variables globales
let currentUserId: string | null = null;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    const userModal = document.getElementById('userModal') as HTMLElement;
    const deleteConfirmModal = document.getElementById('deleteConfirmModal') as HTMLElement;
    const userForm = document.getElementById('userForm') as HTMLFormElement;
    const csrfToken = (document.getElementById('csrf_token') as HTMLInputElement).value;

    // Initialisation
    initModals();
    initAddButton();
    initEditButtons();
    initDeleteButtons();
    initKeyboardNavigation();

    // Initialisation des modales
    function initModals(): void {
        // S'assurer que les modales sont cachées au chargement
        hideModal(userModal);
        hideModal(deleteConfirmModal);

        // Initialiser les événements de fermeture pour les deux modales
        initModalCloseEvents(userModal);
        initModalCloseEvents(deleteConfirmModal);

        // Gestionnaire pour soumettre le formulaire utilisateur
        const submitBtn = userModal.querySelector('.btn-submit') as HTMLButtonElement;
        submitBtn.addEventListener('click', function() {
            submitUserForm();
        });

        // Gestionnaire pour confirmer la suppression
        const deleteBtn = deleteConfirmModal.querySelector('.btn-danger') as HTMLButtonElement;
        deleteBtn.addEventListener('click', function() {
            if (currentUserId) {
                deleteUser(currentUserId);
            }
        });
    }

    // Initialisation du bouton d'ajout
    function initAddButton(): void {
        const addUserBtn = document.querySelector('.btn-add') as HTMLButtonElement;
        if (addUserBtn) {
            addUserBtn.addEventListener('click', function() {
                openAddUserModal();
            });
        }
    }

    // Initialisation des boutons d'édition
    function initEditButtons(): void {
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function(this: HTMLElement) {
                const userId = this.getAttribute('data-user-id');
                if (userId) {
                    openEditUserModal(userId);
                }
            });
        });
    }

    // Initialisation des boutons de suppression
    function initDeleteButtons(): void {
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function(this: HTMLElement) {
                const userId = this.getAttribute('data-user-id');
                const userName = this.getAttribute('data-user-name');
                if (userId && userName) {
                    openDeleteConfirmModal(userId, userName);
                }
            });
        });
    }

    // Initialisation de la navigation au clavier
    function initKeyboardNavigation(): void {
        // Soumettre le formulaire avec Enter dans le formulaire
        userForm.addEventListener('keydown', function(event: KeyboardEvent) {
            if (event.key === 'Enter' && (event.target as HTMLElement).tagName !== 'TEXTAREA') {
                event.preventDefault();
                submitUserForm();
            }
        });
    }

    // Ouvrir la modale d'ajout d'utilisateur
    function openAddUserModal(): void {
        userForm.reset();
        (document.getElementById('userId') as HTMLInputElement).value = '';
        const modalTitle = document.getElementById('userModalTitle');
        if (modalTitle) {
            modalTitle.textContent = 'Ajouter un utilisateur';
        }
        
        // Afficher le champ de mot de passe comme requis pour un nouvel utilisateur
        const passwordField = document.getElementById('password') as HTMLInputElement;
        const passwordHint = document.querySelector('.password-hint') as HTMLElement;
        
        if (passwordField) {
            passwordField.required = true;
        }
        
        if (passwordHint) {
            passwordHint.style.display = 'none';
        }
        
        showModal(userModal);
    }

    // Ouvrir la modale d'édition d'utilisateur
    function openEditUserModal(userId: string): void {
        const modalTitle = document.getElementById('userModalTitle');
        if (modalTitle) {
            modalTitle.textContent = 'Modifier un utilisateur';
        }
        
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
            return response.json() as Promise<UserResponse>;
        })
        .then(data => {
            if (data.success && data.user) {
                // Remplir le formulaire avec les données
                (document.getElementById('userId') as HTMLInputElement).value = data.user.id;
                (document.getElementById('firstName') as HTMLInputElement).value = data.user.firstName;
                (document.getElementById('lastName') as HTMLInputElement).value = data.user.lastName;
                (document.getElementById('email') as HTMLInputElement).value = data.user.email;
                
                const roleSelect = document.getElementById('role') as HTMLSelectElement;
                if (roleSelect) {
                    // Chercher et sélectionner l'option correspondant au rôle de l'utilisateur
                    const options = Array.from(roleSelect.options);
                    const optionToSelect = options.find(option => option.value === data.user?.role);
                    
                    if (optionToSelect) {
                        roleSelect.value = optionToSelect.value;
                    }
                }
                
                // Rendre le champ de mot de passe non requis pour l'édition
                const passwordField = document.getElementById('password') as HTMLInputElement;
                const passwordHint = document.querySelector('.password-hint') as HTMLElement;
                
                if (passwordField) {
                    passwordField.required = false;
                    passwordField.value = '';
                }
                
                if (passwordHint) {
                    passwordHint.style.display = 'block';
                }
                
                showModal(userModal);
            } else {
                throw new Error(data.message || 'Erreur lors de la récupération des données');
            }
        })
        .catch(error => {
            showAlert('error', error.message);
        });
    }

    // Ouvrir la modale de confirmation de suppression
    function openDeleteConfirmModal(userId: string, userName: string): void {
        currentUserId = userId;
        const userNameElement = document.getElementById('userToDeleteName');
        if (userNameElement) {
            userNameElement.textContent = userName;
        }
        showModal(deleteConfirmModal);
    }

    // Soumettre le formulaire utilisateur
    function submitUserForm(): void {
        const formData = new FormData(userForm);
        const userId = (document.getElementById('userId') as HTMLInputElement).value;
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
            return response.json() as Promise<ApiResponse>;
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
    function deleteUser(userId: string): void {
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
            return response.json() as Promise<ApiResponse>;
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
            showAlert('error', 'Erreur: ' + error.message);
        });
    }
}); 