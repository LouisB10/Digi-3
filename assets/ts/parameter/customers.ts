/**
 * assets/ts/parameter/customers.ts
 * Gestion des clients dans les paramètres
 */

// Importation des types et services
import { CustomerResponse, ApiResponse } from '../types/api';
import { showModal, hideModal, initModalCloseEvents } from '../services/modal';
import { showAlert } from '../services/notification';

// Type pour Customer laissé en commentaire pour une utilisation future
// interface Customer {
//    id: string;
//    name: string;
//    reference: string;
//    address: string;
//    postalCode: string;
//    city: string;
//    country: string;
//    vat: string;
//    siren: string;
//}

// Variable globale pour stocker l'ID du client actuel
let currentCustomerId: string | null = null;
// Cette variable est commentée car elle n'est pas utilisée actuellement
// let lastFocusedElement: HTMLElement | null = null;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    const customerModal = document.getElementById('customerModal') as HTMLElement;
    const deleteConfirmModal = document.getElementById('deleteConfirmModal') as HTMLElement;
    const customerForm = document.getElementById('customerForm') as HTMLFormElement;
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
        hideModal(customerModal);
        hideModal(deleteConfirmModal);

        // Initialiser les événements de fermeture pour les deux modales
        initModalCloseEvents(customerModal);
        initModalCloseEvents(deleteConfirmModal);

        // Gestionnaire pour soumettre le formulaire client
        const submitBtn = customerModal.querySelector('.btn-submit') as HTMLButtonElement;
        submitBtn.addEventListener('click', function() {
            submitCustomerForm();
        });

        // Gestionnaire pour confirmer la suppression
        const deleteBtn = deleteConfirmModal.querySelector('.btn-danger') as HTMLButtonElement;
        deleteBtn.addEventListener('click', function() {
            if (currentCustomerId) {
                deleteCustomer(currentCustomerId);
            }
        });
    }

    // Initialisation du bouton d'ajout
    function initAddButton(): void {
        const addCustomerBtn = document.querySelector('.btn-add') as HTMLButtonElement;
        if (addCustomerBtn) {
            addCustomerBtn.addEventListener('click', function() {
                openAddCustomerModal();
            });
        }
    }

    // Initialisation des boutons d'édition
    function initEditButtons(): void {
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function(this: HTMLElement) {
                const customerId = this.getAttribute('data-customer-id');
                if (customerId) {
                    openEditCustomerModal(customerId);
                }
            });
        });
    }

    // Initialisation des boutons de suppression
    function initDeleteButtons(): void {
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function(this: HTMLElement) {
                const customerId = this.getAttribute('data-customer-id');
                const customerName = this.getAttribute('data-customer-name');
                if (customerId && customerName) {
                    openDeleteConfirmModal(customerId, customerName);
                }
            });
        });
    }

    // Initialisation de la navigation au clavier
    function initKeyboardNavigation(): void {
        // Soumettre le formulaire avec Enter dans le formulaire
        customerForm.addEventListener('keydown', function(event: KeyboardEvent) {
            if (event.key === 'Enter' && (event.target as HTMLElement).tagName !== 'TEXTAREA') {
                event.preventDefault();
                submitCustomerForm();
            }
        });
    }

    // Ouvrir la modale d'ajout de client
    function openAddCustomerModal(): void {
        customerForm.reset();
        (document.getElementById('customerId') as HTMLInputElement).value = '';
        const modalTitle = document.getElementById('customerModalTitle');
        if (modalTitle) {
            modalTitle.textContent = 'Ajouter un client';
        }
        showModal(customerModal);
    }

    // Ouvrir la modale d'édition de client
    function openEditCustomerModal(customerId: string): void {
        const modalTitle = document.getElementById('customerModalTitle');
        if (modalTitle) {
            modalTitle.textContent = 'Modifier un client';
        }
        
        // Récupérer les données du client
        fetch(`/parameter/customers/${customerId}/edit`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur lors de la récupération des données');
            }
            return response.json() as Promise<CustomerResponse>;
        })
        .then(data => {
            if (data.success && data.customer) {
                // Remplir le formulaire avec les données
                (document.getElementById('customerId') as HTMLInputElement).value = data.customer.id;
                (document.getElementById('customerName') as HTMLInputElement).value = data.customer.name;
                (document.getElementById('customerReference') as HTMLInputElement).value = data.customer.reference;
                (document.getElementById('customerAddressStreet') as HTMLInputElement).value = data.customer.address;
                (document.getElementById('customerAddressZipcode') as HTMLInputElement).value = data.customer.zipcode;
                (document.getElementById('customerAddressCity') as HTMLInputElement).value = data.customer.city;
                (document.getElementById('customerAddressCountry') as HTMLInputElement).value = data.customer.country;
                (document.getElementById('customerVat') as HTMLInputElement).value = data.customer.vat || '';
                (document.getElementById('customerSiren') as HTMLInputElement).value = data.customer.siren || '';
                
                showModal(customerModal);
            } else {
                throw new Error(data.message || 'Erreur lors de la récupération des données');
            }
        })
        .catch(error => {
            showAlert('error', error.message);
        });
    }

    // Ouvrir la modale de confirmation de suppression
    function openDeleteConfirmModal(customerId: string, customerName: string): void {
        currentCustomerId = customerId;
        const customerNameElement = document.getElementById('customerToDeleteName');
        if (customerNameElement) {
            customerNameElement.textContent = customerName;
        }
        showModal(deleteConfirmModal);
    }

    // Soumettre le formulaire client
    function submitCustomerForm(): void {
        const formData = new FormData(customerForm);
        const customerId = (document.getElementById('customerId') as HTMLInputElement).value;
        const url = customerId ? `/parameter/customers/${customerId}/update` : '/parameter/customers/create';
        
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
            hideModal(customerModal);
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

    // Supprimer un client
    function deleteCustomer(customerId: string): void {
        fetch(`/parameter/customers/${customerId}/delete`, {
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