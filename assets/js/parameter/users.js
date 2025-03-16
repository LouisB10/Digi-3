/**
 * assets/js/parameter/users.js
 * Gestion des utilisateurs dans les paramètres
 */

let userIdToDelete = null;

/**
 * Ouvre la modal d'ajout d'utilisateur
 */
function openAddUserModal() {
    document.getElementById('userModalTitle').textContent = 'Ajouter un utilisateur';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('userModal').hidden = false;
}

/**
 * Ouvre la modal d'édition d'utilisateur
 * @param {number} userId - ID de l'utilisateur à modifier
 */
function openEditUserModal(userId) {
    document.getElementById('userModalTitle').textContent = 'Modifier un utilisateur';
    document.getElementById('userId').value = userId;
    
    // Charger les données de l'utilisateur via AJAX
    fetch(`/parameter/users/${userId}/edit`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('firstName').value = data.user.firstName;
            document.getElementById('lastName').value = data.user.lastName;
            document.getElementById('email').value = data.user.email;
            document.getElementById('password').value = '';
            document.getElementById('role').value = data.user.role;
            document.getElementById('userModal').hidden = false;
        } else {
            alert('Erreur lors du chargement des données de l\'utilisateur');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors du chargement des données');
    });
}

/**
 * Ferme la modal d'utilisateur
 */
function closeUserModal() {
    document.getElementById('userModal').hidden = true;
}

/**
 * Enregistre les données de l'utilisateur
 */
function saveUser() {
    const form = document.getElementById('userForm');
    const formData = new FormData(form);
    const userId = document.getElementById('userId').value;
    
    const url = userId ? `/parameter/users/${userId}/update` : '/parameter/users/create';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeUserModal();
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
 * Affiche la confirmation de suppression d'un utilisateur
 * @param {number} userId - ID de l'utilisateur à supprimer
 * @param {string} userName - Nom de l'utilisateur à supprimer
 */
function confirmDeleteUser(userId, userName) {
    userIdToDelete = userId;
    document.getElementById('userToDeleteName').textContent = userName;
    document.getElementById('deleteConfirmModal').hidden = false;
}

/**
 * Ferme la modal de confirmation de suppression
 */
function closeDeleteModal() {
    document.getElementById('deleteConfirmModal').hidden = true;
}

/**
 * Supprime l'utilisateur
 */
function deleteUser() {
    if (!userIdToDelete) return;
    
    fetch(`/parameter/users/${userIdToDelete}/delete`, {
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
            
            fetch(`/parameter/users/search?type=${searchType}&query=${encodeURIComponent(searchQuery)}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateUserTable(data.users);
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
    
    // Gestion de l'affichage/masquage du mot de passe
    document.querySelectorAll('.toggle-password').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.querySelector('img').src = '/build/images/icons/eye-off.png';
            } else {
                passwordInput.type = 'password';
                this.querySelector('img').src = '/build/images/icons/eye.png';
            }
        });
    });
});

/**
 * Met à jour le tableau des utilisateurs avec les résultats de recherche
 * @param {Array} users - Liste des utilisateurs à afficher
 */
function updateUserTable(users) {
    const tableBody = document.getElementById('parameter_table');
    if (!tableBody) return;
    
    tableBody.innerHTML = '';
    
    users.forEach(user => {
        const row = document.createElement('tr');
        
        // Avatar
        const avatarCell = document.createElement('td');
        const avatarImg = document.createElement('img');
        avatarImg.src = user.avatar;
        avatarImg.alt = `Avatar de ${user.firstName}`;
        avatarImg.className = 'user-avatar';
        avatarCell.appendChild(avatarImg);
        
        // Nom, prénom, email
        const lastNameCell = document.createElement('td');
        lastNameCell.textContent = user.lastName;
        
        const firstNameCell = document.createElement('td');
        firstNameCell.textContent = user.firstName;
        
        const emailCell = document.createElement('td');
        emailCell.textContent = user.email;
        
        // Rôle
        const roleCell = document.createElement('td');
        roleCell.textContent = user.roleLabel;
        
        // Actions
        const actionsCell = document.createElement('td');
        actionsCell.className = 'actions';
        
        // Vérifier si l'utilisateur a le rôle ROLE_RESPONSABLE
        if (document.body.dataset.userRole === 'ROLE_RESPONSABLE' || 
            document.body.dataset.userRole === 'ROLE_ADMIN') {
            const editBtn = document.createElement('button');
            editBtn.className = 'edit-btn';
            editBtn.setAttribute('aria-label', `Modifier ${user.firstName} ${user.lastName}`);
            editBtn.onclick = function() { openEditUserModal(user.id); };
            
            const editImg = document.createElement('img');
            editImg.src = '/build/images/settings/edit.png';
            editImg.alt = 'Modifier';
            
            editBtn.appendChild(editImg);
            actionsCell.appendChild(editBtn);
        }
        
        // Vérifier si l'utilisateur a le rôle ROLE_ADMIN
        if (document.body.dataset.userRole === 'ROLE_ADMIN') {
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'delete-btn';
            deleteBtn.setAttribute('aria-label', `Supprimer ${user.firstName} ${user.lastName}`);
            deleteBtn.onclick = function() { confirmDeleteUser(user.id, `${user.firstName} ${user.lastName}`); };
            
            const deleteImg = document.createElement('img');
            deleteImg.src = '/build/images/settings/delete.png';
            deleteImg.alt = 'Supprimer';
            
            deleteBtn.appendChild(deleteImg);
            actionsCell.appendChild(deleteBtn);
        }
        
        // Ajouter les cellules à la ligne
        row.appendChild(avatarCell);
        row.appendChild(lastNameCell);
        row.appendChild(firstNameCell);
        row.appendChild(emailCell);
        row.appendChild(roleCell);
        row.appendChild(actionsCell);
        
        // Ajouter la ligne au tableau
        tableBody.appendChild(row);
    });
} 