/**
 * assets/ts/parameter/config.ts
 * Gestion de la configuration dans les paramètres
 */

// Export pour indiquer qu'il s'agit d'un module
export {};

// Variables globales
let backupIdToRestore: string | null = null;
let backupIdToDelete: string | null = null;

/**
 * Initialisation des écouteurs d'événements
 */
document.addEventListener('DOMContentLoaded', function() {
    initTabs();
    initForms();
    initPasswordToggles();
    initEventListeners();
});

/**
 * Initialisation des écouteurs d'événements pour remplacer les onclick
 */
function initEventListeners(): void {
    // Bouton pour tester la configuration email
    const testEmailConfigBtn = document.getElementById('test-email-config-btn');
    if (testEmailConfigBtn) {
        testEmailConfigBtn.addEventListener('click', testEmailConfig);
    }
    
    // Bouton pour créer une sauvegarde manuelle
    const createManualBackupBtn = document.getElementById('create-manual-backup-btn');
    if (createManualBackupBtn) {
        createManualBackupBtn.addEventListener('click', createManualBackup);
    }
    
    // Boutons de téléchargement des sauvegardes
    document.querySelectorAll<HTMLButtonElement>('.download-btn').forEach(button => {
        button.addEventListener('click', function() {
            const backupId = this.getAttribute('data-backup-id');
            if (backupId) {
                downloadBackup(backupId);
            }
        });
    });
    
    // Boutons de restauration des sauvegardes
    document.querySelectorAll<HTMLButtonElement>('.restore-btn').forEach(button => {
        button.addEventListener('click', function() {
            const backupId = this.getAttribute('data-backup-id');
            const backupName = this.getAttribute('data-backup-name');
            if (backupId && backupName) {
                confirmRestoreBackup(backupId, backupName);
            }
        });
    });
    
    // Boutons de suppression des sauvegardes
    document.querySelectorAll<HTMLButtonElement>('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const backupId = this.getAttribute('data-backup-id');
            const backupName = this.getAttribute('data-backup-name');
            if (backupId && backupName) {
                confirmDeleteBackup(backupId, backupName);
            }
        });
    });
    
    // Boutons de fermeture des modales
    const restoreModalClose = document.querySelector<HTMLButtonElement>('.restore-modal-close');
    if (restoreModalClose) {
        restoreModalClose.addEventListener('click', closeRestoreModal);
    }
    
    const cancelRestoreBtn = document.querySelector<HTMLButtonElement>('.cancel-restore');
    if (cancelRestoreBtn) {
        cancelRestoreBtn.addEventListener('click', closeRestoreModal);
    }
    
    const confirmRestoreBtn = document.querySelector<HTMLButtonElement>('.confirm-restore');
    if (confirmRestoreBtn) {
        confirmRestoreBtn.addEventListener('click', restoreBackup);
    }
    
    const deleteBackupModalClose = document.querySelector<HTMLButtonElement>('.delete-backup-modal-close');
    if (deleteBackupModalClose) {
        deleteBackupModalClose.addEventListener('click', closeDeleteBackupModal);
    }
    
    const cancelDeleteBackupBtn = document.querySelector<HTMLButtonElement>('.cancel-delete-backup');
    if (cancelDeleteBackupBtn) {
        cancelDeleteBackupBtn.addEventListener('click', closeDeleteBackupModal);
    }
    
    const confirmDeleteBackupBtn = document.querySelector<HTMLButtonElement>('.confirm-delete-backup');
    if (confirmDeleteBackupBtn) {
        confirmDeleteBackupBtn.addEventListener('click', deleteBackup);
    }
    
    const testEmailModalClose = document.querySelector<HTMLButtonElement>('.test-email-modal-close');
    if (testEmailModalClose) {
        testEmailModalClose.addEventListener('click', closeTestEmailModal);
    }
    
    const cancelTestEmailBtn = document.querySelector<HTMLButtonElement>('.cancel-test-email');
    if (cancelTestEmailBtn) {
        cancelTestEmailBtn.addEventListener('click', closeTestEmailModal);
    }
    
    const confirmTestEmailBtn = document.querySelector<HTMLButtonElement>('.confirm-test-email');
    if (confirmTestEmailBtn) {
        confirmTestEmailBtn.addEventListener('click', sendTestEmail);
    }
}

/**
 * Récupère un token CSRF spécifique
 * @param tokenId - L'identifiant du token CSRF
 * @returns Le token CSRF ou une chaîne vide
 */
function getCsrfToken(tokenId: string): string {
    const metaElement = document.querySelector(`meta[name="csrf-token-${tokenId}"]`);
    return metaElement ? metaElement.getAttribute('content') || '' : '';
}

/**
 * Initialisation des onglets
 */
function initTabs(): void {
    document.querySelectorAll<HTMLElement>('.tab-btn').forEach(button => {
        button.addEventListener('click', function(this: HTMLElement) {
            // Retirer la classe active de tous les boutons et contenus
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Mettre à jour les attributs aria
            document.querySelectorAll('.tab-btn').forEach(btn => btn.setAttribute('aria-selected', 'false'));
            this.setAttribute('aria-selected', 'true');
            
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            
            // Afficher le contenu correspondant
            const tabId = this.getAttribute('data-tab');
            if (tabId) {
                const tabContent = document.getElementById(`${tabId}-tab`);
                if (tabContent) {
                    tabContent.classList.add('active');
                }
            }
        });
    });
}

/**
 * Type pour les réponses d'API
 */
interface ApiResponse {
    success: boolean;
    message?: string;
}

/**
 * Initialisation des formulaires
 */
function initForms(): void {
    document.querySelectorAll<HTMLFormElement>('.config-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formId = this.id;
            const formData = new FormData(this);
            let endpoint = '';
            
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
            .then((data: ApiResponse) => {
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
 * @param message - Message à afficher
 */
function showSuccessMessage(message: string): void {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success';
    alertDiv.setAttribute('role', 'alert');
    alertDiv.textContent = message;
    
    // Insérer le message avant le premier formulaire
    const configTabs = document.querySelector('.config-tabs');
    if (configTabs && configTabs.parentNode) {
        configTabs.parentNode.insertBefore(alertDiv, configTabs);
    
        // Faire disparaître le message après 3 secondes
        setTimeout(() => {
            alertDiv.remove();
        }, 3000);
    }
}

/**
 * Initialisation des toggles de mot de passe
 */
function initPasswordToggles(): void {
    document.querySelectorAll<HTMLElement>('.toggle-password').forEach(toggle => {
        toggle.addEventListener('click', function(this: HTMLElement) {
            const targetId = this.getAttribute('data-target');
            if (!targetId) return;
            
            const passwordInput = document.getElementById(targetId) as HTMLInputElement | null;
            if (!passwordInput) return;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                const img = this.querySelector('img');
                if (img) img.src = '/build/images/icons/eye-off.png';
            } else {
                passwordInput.type = 'password';
                const img = this.querySelector('img');
                if (img) img.src = '/build/images/icons/eye.png';
            }
        });
    });
}

/**
 * Crée une sauvegarde manuelle
 */
function createManualBackup(): void {
    const metaTokenElement = document.querySelector('meta[name="csrf-token"]');
    const defaultToken = metaTokenElement ? metaTokenElement.getAttribute('content') || '' : '';
    
    fetch('/parameter/config/backup/create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken('backup-create') || defaultToken
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then((data: ApiResponse) => {
                throw new Error(data.message || 'Une erreur est survenue');
            });
        }
        return response.json() as Promise<ApiResponse>;
    })
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
        alert('Une erreur est survenue lors de la création de la sauvegarde: ' + error.message);
    });
}

/**
 * Télécharge une sauvegarde
 * @param backupId - ID de la sauvegarde à télécharger
 */
function downloadBackup(backupId: string): void {
    window.location.href = `/parameter/config/backup/${backupId}/download`;
}

/**
 * Affiche la confirmation de restauration d'une sauvegarde
 * @param backupId - ID de la sauvegarde à restaurer
 * @param backupName - Nom de la sauvegarde à restaurer
 */
function confirmRestoreBackup(backupId: string, backupName: string): void {
    backupIdToRestore = backupId;
    const nameElement = document.getElementById('backupToRestoreName');
    if (nameElement) nameElement.textContent = backupName;
    
    const modal = document.getElementById('restoreConfirmModal');
    if (modal) modal.hidden = false;
}

/**
 * Ferme la modal de confirmation de restauration
 */
function closeRestoreModal(): void {
    const modal = document.getElementById('restoreConfirmModal');
    if (modal) modal.hidden = true;
}

/**
 * Restaure une sauvegarde
 */
function restoreBackup(): void {
    if (!backupIdToRestore) return;
    
    const metaTokenElement = document.querySelector('meta[name="csrf-token"]');
    const defaultToken = metaTokenElement ? metaTokenElement.getAttribute('content') || '' : '';
    
    fetch(`/parameter/config/backup/${backupIdToRestore}/restore`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken('backup-restore') || defaultToken
        }
    })
    .then(response => response.json() as Promise<ApiResponse>)
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
 * @param backupId - ID de la sauvegarde à supprimer
 * @param backupName - Nom de la sauvegarde à supprimer
 */
function confirmDeleteBackup(backupId: string, backupName: string): void {
    backupIdToDelete = backupId;
    const nameElement = document.getElementById('backupToDeleteName');
    if (nameElement) nameElement.textContent = backupName;
    
    const modal = document.getElementById('deleteBackupConfirmModal');
    if (modal) modal.hidden = false;
}

/**
 * Ferme la modal de confirmation de suppression
 */
function closeDeleteBackupModal(): void {
    const modal = document.getElementById('deleteBackupConfirmModal');
    if (modal) modal.hidden = true;
}

/**
 * Supprime une sauvegarde
 */
function deleteBackup(): void {
    if (!backupIdToDelete) return;
    
    const metaTokenElement = document.querySelector('meta[name="csrf-token"]');
    const defaultToken = metaTokenElement ? metaTokenElement.getAttribute('content') || '' : '';
    
    fetch(`/parameter/config/backup/${backupIdToDelete}/delete`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken('backup-delete') || defaultToken
        }
    })
    .then(response => response.json() as Promise<ApiResponse>)
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
function testEmailConfig(): void {
    const modal = document.getElementById('testEmailModal');
    if (modal) modal.hidden = false;
}

/**
 * Ferme la modal de test d'email
 */
function closeTestEmailModal(): void {
    const modal = document.getElementById('testEmailModal');
    if (modal) modal.hidden = true;
}

/**
 * Envoie un email de test
 */
function sendTestEmail(): void {
    const emailInput = document.getElementById('testEmailAddress') as HTMLInputElement | null;
    if (!emailInput || !emailInput.value) {
        alert('Veuillez saisir une adresse email');
        return;
    }
    
    // Récupérer les valeurs du formulaire email
    const emailForm = document.getElementById('emailForm') as HTMLFormElement | null;
    if (!emailForm) return;
    
    const formData = new FormData(emailForm);
    formData.append('testEmailAddress', emailInput.value);
    
    fetch('/parameter/config/email/test', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json() as Promise<ApiResponse>)
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

// Exposer les fonctions au niveau global pour les appels depuis le HTML
declare global {
    interface Window {
        createManualBackup: typeof createManualBackup;
        downloadBackup: typeof downloadBackup;
        confirmRestoreBackup: typeof confirmRestoreBackup;
        closeRestoreModal: typeof closeRestoreModal;
        restoreBackup: typeof restoreBackup;
        confirmDeleteBackup: typeof confirmDeleteBackup;
        closeDeleteBackupModal: typeof closeDeleteBackupModal;
        deleteBackup: typeof deleteBackup;
        testEmailConfig: typeof testEmailConfig;
        closeTestEmailModal: typeof closeTestEmailModal;
        sendTestEmail: typeof sendTestEmail;
    }
}

// Assigner les fonctions à l'objet window
window.createManualBackup = createManualBackup;
window.downloadBackup = downloadBackup;
window.confirmRestoreBackup = confirmRestoreBackup;
window.closeRestoreModal = closeRestoreModal;
window.restoreBackup = restoreBackup;
window.confirmDeleteBackup = confirmDeleteBackup;
window.closeDeleteBackupModal = closeDeleteBackupModal;
window.deleteBackup = deleteBackup;
window.testEmailConfig = testEmailConfig;
window.closeTestEmailModal = closeTestEmailModal;
window.sendTestEmail = sendTestEmail; 