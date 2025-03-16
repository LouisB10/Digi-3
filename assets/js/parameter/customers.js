/**
 * assets/js/parameter/customers.js
 * Gestion des clients dans les paramètres
 */

let customerIdToDelete = null;

/**
 * Ouvre la modal d'ajout de client
 */
function openAddCustomerModal() {
    document.getElementById('customerModalTitle').textContent = 'Ajouter un client';
    document.getElementById('customerForm').reset();
    document.getElementById('customerId').value = '';
    document.getElementById('logo-preview').innerHTML = '';
    document.getElementById('customerModal').hidden = false;
}

/**
 * Ouvre la modal d'édition de client
 * @param {number} customerId - ID du client à modifier
 */
function openEditCustomerModal(customerId) {
    document.getElementById('customerModalTitle').textContent = 'Modifier un client';
    document.getElementById('customerId').value = customerId;
    
    // Charger les données du client via AJAX
    fetch(`/parameter/customers/${customerId}/edit`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('name').value = data.customer.name;
            document.getElementById('email').value = data.customer.email;
            document.getElementById('phone').value = data.customer.phone;
            document.getElementById('address').value = data.customer.address;
            document.getElementById('sector').value = data.customer.sector;
            document.getElementById('website').value = data.customer.website;
            document.getElementById('notes').value = data.customer.notes;
            
            // Afficher l'aperçu du logo
            if (data.customer.logo) {
                const logoPreview = document.getElementById('logo-preview');
                logoPreview.innerHTML = '';
                const img = document.createElement('img');
                img.src = data.customer.logo;
                img.alt = 'Logo actuel';
                logoPreview.appendChild(img);
            }
            
            document.getElementById('customerModal').hidden = false;
        } else {
            alert('Erreur lors du chargement des données du client');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors du chargement des données');
    });
}

/**
 * Ferme la modal de client
 */
function closeCustomerModal() {
    document.getElementById('customerModal').hidden = true;
}

/**
 * Enregistre les données du client
 */
function saveCustomer() {
    const form = document.getElementById('customerForm');
    const formData = new FormData(form);
    const customerId = document.getElementById('customerId').value;
    
    const url = customerId ? `/parameter/customers/${customerId}/update` : '/parameter/customers/create';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeCustomerModal();
            window.location.reload();
        } else {
            alert(data.message || 'Une erreur est survenue');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de l\'enregistrement');
    });
}

/**
 * Affiche la confirmation de suppression d'un client
 * @param {number} customerId - ID du client à supprimer
 * @param {string} customerName - Nom du client à supprimer
 */
function confirmDeleteCustomer(customerId, customerName) {
    customerIdToDelete = customerId;
    document.getElementById('customerToDeleteName').textContent = customerName;
    document.getElementById('deleteConfirmModal').hidden = false;
}

/**
 * Ferme la modal de confirmation de suppression
 */
function closeDeleteModal() {
    document.getElementById('deleteConfirmModal').hidden = true;
}

/**
 * Supprime le client
 */
function deleteCustomer() {
    if (!customerIdToDelete) return;
    
    fetch(`/parameter/customers/${customerIdToDelete}/delete`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeDeleteModal();
            window.location.reload();
        } else {
            alert(data.message || 'Une erreur est survenue lors de la suppression');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de la suppression');
    });
}

// Initialisation des événements
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la recherche
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchType = document.getElementById('searchType').value;
            const searchQuery = document.getElementById('searchQuery').value;
            
            fetch(`/parameter/customers/search?type=${searchType}&query=${encodeURIComponent(searchQuery)}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCustomerTable(data.customers);
                } else {
                    alert('Erreur lors de la recherche');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la recherche');
            });
        });
    }
    
    // Prévisualisation du logo lors de l'upload
    const logoInput = document.getElementById('logo');
    if (logoInput) {
        logoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const logoPreview = document.getElementById('logo-preview');
                logoPreview.innerHTML = '';
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Aperçu du logo';
                logoPreview.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    }
});

/**
 * Met à jour le tableau des clients avec les résultats de recherche
 * @param {Array} customers - Liste des clients à afficher
 */
function updateCustomerTable(customers) {
    const tableBody = document.getElementById('parameter_table');
    if (!tableBody) return;
    
    tableBody.innerHTML = '';
    
    customers.forEach(customer => {
        const row = document.createElement('tr');
        
        // Logo
        const logoCell = document.createElement('td');
        const logoImg = document.createElement('img');
        logoImg.src = customer.logo;
        logoImg.alt = `Logo de ${customer.name}`;
        logoImg.className = 'customer-logo';
        logoCell.appendChild(logoImg);
        
        // Nom, email, téléphone, secteur
        const nameCell = document.createElement('td');
        nameCell.textContent = customer.name;
        
        const emailCell = document.createElement('td');
        emailCell.textContent = customer.email;
        
        const phoneCell = document.createElement('td');
        phoneCell.textContent = customer.phone;
        
        const sectorCell = document.createElement('td');
        sectorCell.textContent = customer.sector;
        
        // Actions
        const actionsCell = document.createElement('td');
        actionsCell.className = 'actions';
        
        // Vérifier si l'utilisateur a le rôle ROLE_PROJECT_MANAGER
        if (document.body.dataset.userRole === 'ROLE_PROJECT_MANAGER' || 
            document.body.dataset.userRole === 'ROLE_RESPONSABLE' || 
            document.body.dataset.userRole === 'ROLE_ADMIN') {
            const editBtn = document.createElement('button');
            editBtn.className = 'edit-btn';
            editBtn.setAttribute('aria-label', `Modifier ${customer.name}`);
            editBtn.onclick = function() { openEditCustomerModal(customer.id); };
            
            const editImg = document.createElement('img');
            editImg.src = '/build/images/settings/edit.png';
            editImg.alt = 'Modifier';
            
            editBtn.appendChild(editImg);
            actionsCell.appendChild(editBtn);
        }
        
        // Vérifier si l'utilisateur a le rôle ROLE_RESPONSABLE
        if (document.body.dataset.userRole === 'ROLE_RESPONSABLE' || 
            document.body.dataset.userRole === 'ROLE_ADMIN') {
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'delete-btn';
            deleteBtn.setAttribute('aria-label', `Supprimer ${customer.name}`);
            deleteBtn.onclick = function() { confirmDeleteCustomer(customer.id, customer.name); };
            
            const deleteImg = document.createElement('img');
            deleteImg.src = '/build/images/settings/delete.png';
            deleteImg.alt = 'Supprimer';
            
            deleteBtn.appendChild(deleteImg);
            actionsCell.appendChild(deleteBtn);
        }
        
        // Ajouter les cellules à la ligne
        row.appendChild(logoCell);
        row.appendChild(nameCell);
        row.appendChild(emailCell);
        row.appendChild(phoneCell);
        row.appendChild(sectorCell);
        row.appendChild(actionsCell);
        
        // Ajouter la ligne au tableau
        tableBody.appendChild(row);
    });
} 