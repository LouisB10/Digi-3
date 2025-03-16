/**
 * assets/js/parameter/config.js
 * Gestion de la configuration dans les paramètres
 */

let backupIdToRestore = null;
let backupIdToDelete = null;

/**
 * Initialisation des onglets
 */
function initTabs() {
    document.querySelectorAll('.tab-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Retirer la classe active de tous les boutons et contenus
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            
            // Afficher le contenu correspondant
            const tabId = this.getAttribute('data-tab');
            document.getElementById(`${tabId}-tab`).classList.add('active');
        });
    });
}

/**
 * Initialisation des formulaires
 */
function initForms() {
    document.querySelectorAll('.config-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formId = this.id;
            const formData = new FormData(this);
            let endpoint;
            
            switch(formId) {
                case 'generalForm':
                    endpoint = '/parameter/config/general';
                    break;
                case 'emailForm':
                    endpoint = '/parameter/config/email';
                    break;
                case 'securityForm':
                    endpoint = '/parameter/config/security';
                    break;
                case 'backupForm':
                    endpoint = '/parameter/config/backup';
                    break;
            }
            
            fetch(endpoint, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessMessage(data.message || 'Configuration enregistrée avec succès');
                } else {
                    alert(data.message || 'Une erreur est survenue');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de l\'enregistrement');
            });
        });
    });
}

/**
 * Affiche un message de succès temporaire
 * @param {string} message - Message à afficher
 */
function showSuccessMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success';
    alertDiv.setAttribute('role', 'alert');
    alertDiv.textContent = message;
    
    // Insérer le message avant le premier formulaire
    const configTabs = document.querySelector('.config-tabs');
    configTabs.parentNode.insertBefore(alertDiv, configTabs);
    
    // Faire disparaître le message après 3 secondes
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

/**
 * Initialisation des toggles de mot de passe
 */
function initPasswordToggles() {
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
}

/**
 * Crée une sauvegarde manuelle
 */
function createManualBackup() {
    fetch('/parameter/config/backup/create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Sauvegarde créée avec succès');
            window.location.reload();
        } else {
            alert(data.message || 'Une erreur est survenue lors de la création de la sauvegarde');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de la création de la sauvegarde');
    });
}

/**
 * Télécharge une sauvegarde
 * @param {string} backupId - ID de la sauvegarde à télécharger
 */
function downloadBackup(backupId) {
    window.location.href = `/parameter/config/backup/${backupId}/download`;
}

/**
 * Affiche la confirmation de restauration d'une sauvegarde
 * @param {string} backupId - ID de la sauvegarde à restaurer
 * @param {string} backupName - Nom de la sauvegarde à restaurer
 */
function confirmRestoreBackup(backupId, backupName) {
    backupIdToRestore = backupId;
    document.getElementById('backupToRestoreName').textContent = backupName;
    document.getElementById('restoreConfirmModal').hidden = false;
}

/**
 * Ferme la modal de confirmation de restauration
 */
function closeRestoreModal() {
    document.getElementById('restoreConfirmModal').hidden = true;
}

/**
 * Restaure une sauvegarde
 */
function restoreBackup() {
    if (!backupIdToRestore) return;
    
    fetch(`/parameter/config/backup/${backupIdToRestore}/restore`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        closeRestoreModal();
        if (data.success) {
            alert(data.message || 'Sauvegarde restaurée avec succès');
            window.location.reload();
        } else {
            alert(data.message || 'Une erreur est survenue lors de la restauration');
        }
    })
    .catch(error => {
        closeRestoreModal();
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de la restauration');
    });
}

/**
 * Affiche la confirmation de suppression d'une sauvegarde
 * @param {string} backupId - ID de la sauvegarde à supprimer
 * @param {string} backupName - Nom de la sauvegarde à supprimer
 */
function confirmDeleteBackup(backupId, backupName) {
    backupIdToDelete = backupId;
    document.getElementById('backupToDeleteName').textContent = backupName;
    document.getElementById('deleteBackupConfirmModal').hidden = false;
}

/**
 * Ferme la modal de confirmation de suppression
 */
function closeDeleteBackupModal() {
    document.getElementById('deleteBackupConfirmModal').hidden = true;
}

/**
 * Supprime une sauvegarde
 */
function deleteBackup() {
    if (!backupIdToDelete) return;
    
    fetch(`/parameter/config/backup/${backupIdToDelete}/delete`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        closeDeleteBackupModal();
        if (data.success) {
            alert(data.message || 'Sauvegarde supprimée avec succès');
            window.location.reload();
        } else {
            alert(data.message || 'Une erreur est survenue lors de la suppression');
        }
    })
    .catch(error => {
        closeDeleteBackupModal();
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de la suppression');
    });
}

/**
 * Ouvre la modal de test d'email
 */
function testEmailConfig() {
    document.getElementById('testEmailModal').hidden = false;
}

/**
 * Ferme la modal de test d'email
 */
function closeTestEmailModal() {
    document.getElementById('testEmailModal').hidden = true;
}

/**
 * Envoie un email de test
 */
function sendTestEmail() {
    const emailAddress = document.getElementById('testEmailAddress').value;
    if (!emailAddress) {
        alert('Veuillez saisir une adresse email');
        return;
    }
    
    // Récupérer les valeurs du formulaire email
    const formData = new FormData(document.getElementById('emailForm'));
    formData.append('testEmailAddress', emailAddress);
    
    fetch('/parameter/config/email/test', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        closeTestEmailModal();
        if (data.success) {
            alert(data.message || 'Email de test envoyé avec succès');
        } else {
            alert(data.message || 'Une erreur est survenue lors de l\'envoi de l\'email');
        }
    })
    .catch(error => {
        closeTestEmailModal();
        console.error('Erreur:', error);
        alert('Une erreur est survenue lors de l\'envoi de l\'email');
    });
}

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initTabs();
    initForms();
    initPasswordToggles();
}); 