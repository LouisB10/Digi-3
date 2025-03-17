/**
 * assets/js/parameter/customers.js
 * Gestion des clients dans les paramètres
 */

let lastFocusedElement = null;

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    const customerModal = document.getElementById('customerModal');
    const deleteConfirmModal = document.getElementById('deleteConfirmModal');
    const customerForm = document.getElementById('customerForm');
    const csrfToken = document.getElementById('csrf_token').value;
    let currentCustomerId = null;

    // Initialisation
    initModals();
    initAddButton();
    initEditButtons();
    initDeleteButtons();
    initKeyboardNavigation();

    // Initialisation des modales
    function initModals() {
        // S'assurer que les modales sont cachées au chargement
        hideModal(customerModal);
        hideModal(deleteConfirmModal);

        // Gestionnaires pour fermer les modales
        document.querySelectorAll('.close, .btn-cancel').forEach(button => {
            button.addEventListener('click', function() {
                const modal = this.closest('.modal');
                hideModal(modal);
            });
        });

        // Gestionnaire pour soumettre le formulaire client
        customerModal.querySelector('.btn-submit').addEventListener('click', function() {
            submitCustomerForm();
        });

        // Gestionnaire pour confirmer la suppression
        deleteConfirmModal.querySelector('.btn-danger').addEventListener('click', function() {
            deleteCustomer(currentCustomerId);
        });
    }

    // Initialisation du bouton d'ajout
    function initAddButton() {
        const addCustomerBtn = document.querySelector('.btn-add');
        if (addCustomerBtn) {
            addCustomerBtn.addEventListener('click', function() {
                openAddCustomerModal();
            });
        }
    }

    // Initialisation des boutons d'édition
    function initEditButtons() {
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const customerId = this.getAttribute('data-customer-id');
                openEditCustomerModal(customerId);
            });
        });
    }

    // Initialisation des boutons de suppression
    function initDeleteButtons() {
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                const customerId = this.getAttribute('data-customer-id');
                const customerName = this.getAttribute('data-customer-name');
                openDeleteConfirmModal(customerId, customerName);
            });
        });
    }

    // Initialisation de la navigation au clavier
    function initKeyboardNavigation() {
        // Fermer les modales avec Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                if (customerModal && !customerModal.hidden) {
                    hideModal(customerModal);
                } else if (deleteConfirmModal && !deleteConfirmModal.hidden) {
                    hideModal(deleteConfirmModal);
                }
            }
        });

        // Soumettre le formulaire avec Enter dans le formulaire
        customerForm.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') {
                event.preventDefault();
                submitCustomerForm();
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

    // Ouvrir la modale d'ajout de client
    function openAddCustomerModal() {
        lastFocusedElement = document.activeElement;
        customerForm.reset();
        document.getElementById('customerId').value = '';
        document.getElementById('customerModalTitle').textContent = 'Ajouter un client';
        showModal(customerModal);
    }

    // Ouvrir la modale d'édition de client
    function openEditCustomerModal(customerId) {
        lastFocusedElement = document.activeElement;
        document.getElementById('customerModalTitle').textContent = 'Modifier un client';
        
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
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Remplir le formulaire avec les données
                document.getElementById('customerId').value = data.customer.id;
                document.getElementById('customerName').value = data.customer.name;
                document.getElementById('customerReference').value = data.customer.reference;
                document.getElementById('customerAddressStreet').value = data.customer.address;
                document.getElementById('customerAddressZipcode').value = data.customer.zipcode;
                document.getElementById('customerAddressCity').value = data.customer.city;
                document.getElementById('customerAddressCountry').value = data.customer.country;
                document.getElementById('customerVat').value = data.customer.vat || '';
                document.getElementById('customerSiren').value = data.customer.siren || '';
                
                showModal(customerModal);
            } else {
                throw new Error(data.message || 'Erreur lors de la récupération des données');
            }
        })
        .catch(error => {
            showAlert('error', 'Erreur: ' + error.message);
        });
    }

    // Ouvrir la modale de confirmation de suppression
    function openDeleteConfirmModal(customerId, customerName) {
        lastFocusedElement = document.activeElement;
        currentCustomerId = customerId;
        document.getElementById('customerToDeleteName').textContent = customerName;
        showModal(deleteConfirmModal);
    }

    // Soumettre le formulaire client
    function submitCustomerForm() {
        const formData = new FormData(customerForm);
        const customerId = document.getElementById('customerId').value;
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
            return response.json();
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
    function deleteCustomer(customerId) {
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