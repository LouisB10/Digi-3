/**
 * assets/ts/project/management.ts
 * Script pour la gestion des projets et des tâches avec le tableau Kanban
 */

// Export pour signaler que c'est un module
export {};

// Interfaces pour les données manipulées
interface TaskData {
    id: string;
    rank: number;
}

interface KanbanUpdateData {
    taskId: string;
    newColumn: string;
    taskOrder: TaskData[];
}

// Déclaration des jetons CSRF globaux
declare global {
    interface Window {
        CSRF_TOKENS: {
            project: string;
            task: string;
            delete: string;
        };
        showCreateForm: typeof showCreateForm;
        showCreateTaskForm: typeof showCreateTaskForm;
        showTaskForm: typeof showTaskForm;
        showDeletePopup: (projectId: string) => void;
        openTaskModal: typeof openTaskModal;
        closeTaskModal: typeof closeTaskModal;
    }
}

// Variables globales pour le script
let projectIdToDelete: string | null = null;

/**
 * Affiche le formulaire de création de projet
 */
function showCreateForm(): void {
    const form = document.getElementById('createProjectForm');
    if (form) {
        form.style.display = 'block';
        console.log('Formulaire de création de projet affiché');
        
        // Vérifier que le jeton CSRF est présent et valide
        const csrfToken = window.CSRF_TOKENS?.project;
        if (!csrfToken) {
            console.error('Jeton CSRF du projet non disponible dans les variables globales');
            return;
        }
        
        // S'assurer que le formulaire a un champ caché pour le jeton CSRF
        const formElement = form.querySelector('form') as HTMLFormElement;
        if (formElement) {
            let tokenInput = formElement.querySelector('input[name="_token"]') as HTMLInputElement;
            if (!tokenInput) {
                // Créer le champ si nécessaire
                tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = '_token';
                formElement.appendChild(tokenInput);
            }
            // Définir ou mettre à jour la valeur du jeton
            tokenInput.value = csrfToken;
            console.log('Jeton CSRF du projet défini avec succès dans le formulaire');
        }
    } else {
        console.error('Formulaire de création de projet non trouvé');
    }
}
 
/**
 * Affiche le formulaire de création de tâche
 */
function showCreateTaskForm(): void {
    const form = document.getElementById('createTaskForm');
    if (form) {
        form.style.display = 'block';
        console.log('Formulaire de création de tâche affiché');
        
        // Vérifier que le jeton CSRF est présent et valide
        const csrfToken = window.CSRF_TOKENS?.task;
        if (!csrfToken) {
            console.error('Jeton CSRF de tâche non disponible dans les variables globales');
            return;
        }
        
        // S'assurer que le formulaire a un champ caché pour le jeton CSRF
        const formElement = form.querySelector('form') as HTMLFormElement;
        if (formElement) {
            let tokenInput = formElement.querySelector('input[name="_token"]') as HTMLInputElement;
            if (!tokenInput) {
                // Créer le champ si nécessaire
                tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = '_token';
                formElement.appendChild(tokenInput);
            }
            // Définir ou mettre à jour la valeur du jeton
            tokenInput.value = csrfToken;
            console.log('Jeton CSRF de tâche défini avec succès dans le formulaire');
        }
    } else {
        console.error('Formulaire de création de tâche non trouvé');
    }
}
 
/**
 * Initialisation des gestionnaires d'événements pour la suppression de projet
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les boutons de création
    const createProjectBtn = document.getElementById('createProjectBtn');
    if (createProjectBtn) {
        console.log('Bouton de création de projet trouvé');
        createProjectBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showCreateForm();
        });
    } else {
        console.log('Bouton de création de projet non trouvé');
    }
    
    const createTaskBtn = document.getElementById('createTaskBtn');
    if (createTaskBtn) {
        console.log('Bouton de création de tâche trouvé');
        createTaskBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showCreateTaskForm();
        });
    } else {
        console.log('Bouton de création de tâche non trouvé');
    }
    
    // Gérer la soumission des formulaires
    const projectForm = document.getElementById('projectForm') as HTMLFormElement;
    if (projectForm) {
        console.log('Formulaire de projet trouvé, ajout du gestionnaire de soumission');
        projectForm.addEventListener('submit', function(e) {
            // S'assurer que le jeton CSRF est présent dans le formulaire
            const csrfToken = this.querySelector('input[name="_token"]') as HTMLInputElement;
            if (!csrfToken || !csrfToken.value) {
                e.preventDefault();
                console.error('Soumission du formulaire de projet annulée : jeton CSRF manquant');
                
                // Utiliser le jeton depuis les variables globales
                const tokenValue = window.CSRF_TOKENS?.project;
                if (tokenValue) {
                    // Créer ou mettre à jour le champ du jeton
                    if (!csrfToken) {
                        const newToken = document.createElement('input');
                        newToken.type = 'hidden';
                        newToken.name = '_token';
                        newToken.value = tokenValue;
                        this.appendChild(newToken);
                    } else {
                        csrfToken.value = tokenValue;
                    }
                    console.log('Jeton CSRF ajouté au formulaire de projet, tentative de soumission');
                    setTimeout(() => this.submit(), 50);
                } else {
                    alert('Impossible de soumettre le formulaire : jeton de sécurité manquant.');
                }
            }
        });
    }
    
    const taskForm = document.getElementById('taskForm') as HTMLFormElement;
    if (taskForm) {
        console.log('Formulaire de tâche trouvé, ajout du gestionnaire de soumission');
        taskForm.addEventListener('submit', function(e) {
            // S'assurer que le jeton CSRF est présent dans le formulaire
            const csrfToken = this.querySelector('input[name="_token"]') as HTMLInputElement;
            if (!csrfToken || !csrfToken.value) {
                e.preventDefault();
                console.error('Soumission du formulaire de tâche annulée : jeton CSRF manquant');
                
                // Utiliser le jeton depuis les variables globales
                const tokenValue = window.CSRF_TOKENS?.task;
                if (tokenValue) {
                    // Créer ou mettre à jour le champ du jeton
                    if (!csrfToken) {
                        const newToken = document.createElement('input');
                        newToken.type = 'hidden';
                        newToken.name = '_token';
                        newToken.value = tokenValue;
                        this.appendChild(newToken);
                    } else {
                        csrfToken.value = tokenValue;
                    }
                    console.log('Jeton CSRF ajouté au formulaire de tâche, tentative de soumission');
                    setTimeout(() => this.submit(), 50);
                } else {
                    alert('Impossible de soumettre le formulaire : jeton de sécurité manquant.');
                }
            }
        });
    }
    
    window.showDeletePopup = function(projectId: string): void {
        projectIdToDelete = projectId;
        const popup = document.getElementById('deletePopup');
        if (popup) popup.style.display = 'flex';
        
        // Assurer que le jeton CSRF est présent
        const csrfTokenInput = document.getElementById('delete-project-token') as HTMLInputElement;
        if (!csrfTokenInput || !csrfTokenInput.value) {
            console.error('Jeton CSRF manquant pour la suppression du projet');
            const csrfMeta = document.querySelector('meta[name="csrf-token-delete"]');
            if (csrfMeta && csrfMeta.getAttribute('content')) {
                if (csrfTokenInput) {
                    csrfTokenInput.value = csrfMeta.getAttribute('content') || '';
                    console.log('Jeton CSRF pour la suppression récupéré depuis les méta-données');
                }
            }
        } else {
            console.log('Jeton CSRF pour la suppression disponible');
        }
    };
 
    const confirmDelete = document.getElementById('confirmDelete');
    if (confirmDelete) {
        confirmDelete.onclick = function(): void {
            if (!projectIdToDelete) {
                console.error('Aucun ID de projet à supprimer.');
                return;
            }
     
            const form = document.createElement('form');
            form.method = 'post';
            form.action = `/management-project/delete/${projectIdToDelete}`;
            
            // Ajouter le jeton CSRF
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            
            // Récupérer le jeton CSRF depuis les variables globales
            const tokenValue = window.CSRF_TOKENS?.delete;
            if (tokenValue) {
                csrfInput.value = tokenValue;
                form.appendChild(csrfInput);
                document.body.appendChild(form);
                form.submit();
            } else {
                console.error('Aucun jeton CSRF trouvé pour la suppression de projet');
                alert('Impossible de supprimer le projet : jeton de sécurité manquant.');
            }
        };
    }
 
    const cancelDelete = document.getElementById('cancelDelete');
    if (cancelDelete) {
        cancelDelete.onclick = function(): void {
            const popup = document.getElementById('deletePopup');
            if (popup) popup.style.display = 'none';
        };
    }
});
 
/**
 * Affiche le formulaire de tâche
 */
function showTaskForm(): void {
    const form = document.getElementById('createTaskForm');
    if (form) form.style.display = 'block';
}
 
/**
 * Affiche la fenêtre de confirmation de suppression
 * @param projectId - L'ID du projet à supprimer
 */
function showDeletePopup(projectId: string): void {
    projectIdToDelete = projectId;
    const popup = document.getElementById('deletePopup');
    if (popup) popup.style.display = 'flex';
    
    // Assurer que le jeton CSRF est présent
    const csrfTokenInput = document.getElementById('delete-project-token') as HTMLInputElement;
    if (!csrfTokenInput || !csrfTokenInput.value) {
        console.error('Jeton CSRF manquant pour la suppression du projet');
        const csrfMeta = document.querySelector('meta[name="csrf-token-delete"]');
        if (csrfMeta && csrfMeta.getAttribute('content')) {
            if (csrfTokenInput) {
                csrfTokenInput.value = csrfMeta.getAttribute('content') || '';
                console.log('Jeton CSRF pour la suppression récupéré depuis les méta-données');
            }
        }
    } else {
        console.log('Jeton CSRF pour la suppression disponible');
    }
}
 
/**
 * Initialisation du Kanban et de la gestion du drag and drop
 */
document.addEventListener('DOMContentLoaded', function() {
    const columns = document.querySelectorAll<HTMLElement>('.kanban-column');
    const tasksContainers = document.querySelectorAll<HTMLElement>('.kanban-tasks');
    let draggedItem: HTMLElement | null = null;
 
    // Initialisation du drag and drop pour les cartes de tâches
    document.querySelectorAll<HTMLElement>('.task-card').forEach(task => {
        task.addEventListener('dragstart', handleDragStart);
        task.addEventListener('dragend', handleDragEnd);
        // Ajouter un écouteur de clic pour ouvrir la modal de détail
        task.addEventListener('click', function() {
            openTaskModal(this);
        });
    });
    
    // Écouteur pour le bouton de fermeture de la modal
    const closeButton = document.querySelector<HTMLElement>('.task-modal-close');
    if (closeButton) {
        closeButton.addEventListener('click', closeTaskModal);
    }
    
    // Fermer la modal si on clique en dehors du contenu
    const taskModal = document.getElementById('taskDetailModal');
    if (taskModal) {
        taskModal.addEventListener('click', function(event) {
            if (event.target === taskModal) {
                closeTaskModal();
            }
        });
    }
    
    columns.forEach(column => {
        column.addEventListener('dragover', handleDragOver);
        column.addEventListener('drop', handleDrop);
    });
 
    tasksContainers.forEach(container => {
        container.addEventListener('dragover', handleDragOver);
        container.addEventListener('drop', handleDrop);
        container.addEventListener('dragenter', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });
        container.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
        });
    });
    
    /**
     * Gère le début du glisser-déposer
     */
    function handleDragStart(this: HTMLElement, e: DragEvent): void {
        draggedItem = this;
        if (e.dataTransfer) {
            e.dataTransfer.setData('text/plain', this.getAttribute('data-task-id') || '');
            setTimeout(() => {
                this.style.opacity = '0.5';
            }, 0);
        }
    }
 
    /**
     * Gère la fin du glisser-déposer
     */
    function handleDragEnd(this: HTMLElement): void {
        this.style.opacity = '1';
        draggedItem = null;
        document.querySelectorAll<HTMLElement>('.drag-over').forEach(element => {
            element.classList.remove('drag-over');
        });
    }
 
    /**
     * Gère le survol pendant le glisser-déposer
     */
    function handleDragOver(e: DragEvent): void {
        e.preventDefault();
    }
 
    /**
     * Gère le dépôt d'un élément
     */
    function handleDrop(this: HTMLElement, e: DragEvent): void {
        e.preventDefault();
        e.stopPropagation();
 
        const taskContainer = this.querySelector<HTMLElement>('.kanban-tasks') || this;
        const columnStatus = this.getAttribute('data-column') || '';
        
        if (!e.dataTransfer) return;
        const taskId = e.dataTransfer.getData('text/plain');
        const task = draggedItem || document.querySelector<HTMLElement>(`.task-card[data-task-id="${taskId}"]`);
 
        if (!task) return;
 
        const y = e.clientY;
        const siblings = Array.from(taskContainer.querySelectorAll<HTMLElement>('.task-card:not([data-task-id="' + taskId + '"])'));
        const sibling = siblings.find(sibling => {
            const box = sibling.getBoundingClientRect();
            return y <= box.top + box.height / 2;
        });
 
        if (sibling) {
            taskContainer.insertBefore(task, sibling);
        } else {
            taskContainer.appendChild(task);
        }
 
        const tasksInColumn = taskContainer.querySelectorAll<HTMLElement>('.task-card');
        const taskIds: TaskData[] = Array.from(tasksInColumn).map((taskElement, index) => ({
            id: taskElement.getAttribute('data-task-id') || '',
            rank: index + 1
        }));
 
        // Utiliser le jeton CSRF global pour l'en-tête de la requête
        const csrfToken = window.CSRF_TOKENS?.task || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        fetch('/management-project/update-task-position', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                taskId,
                newColumn: columnStatus,
                taskOrder: taskIds
            } as KanbanUpdateData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                displayError(data.error);
            } else {
                console.log('Mise à jour réussie:', data.success);
            }
        })
        .catch(error => {
            displayError('Erreur de connexion au serveur.');
            console.error('Erreur:', error);
        });
    }
 
    /**
     * Affiche un message d'erreur temporaire
     */
    function displayError(message: string): void {
        let errorBox = document.querySelector<HTMLElement>('.error-box');
        if (!errorBox) {
            errorBox = document.createElement('div');
            errorBox.className = 'error-box';
            errorBox.style.position = 'fixed';
            errorBox.style.top = '20px';
            errorBox.style.right = '20px';
            errorBox.style.padding = '15px 20px';
            errorBox.style.backgroundColor = '#e74c3c';
            errorBox.style.color = 'white';
            errorBox.style.borderRadius = '5px';
            errorBox.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.3)';
            errorBox.style.zIndex = '9999';
            document.body.appendChild(errorBox);
        }
        errorBox.innerText = message;
        errorBox.style.display = 'block';
        setTimeout(() => {
            if (errorBox) errorBox.style.display = 'none';
        }, 3000);
    }
});

/**
 * Ouvre la modal de détail de tâche
 */
function openTaskModal(taskElement: HTMLElement): void {
    // Récupérer les données de la tâche depuis les attributs data-
    const id = taskElement.getAttribute('data-task-id') || '';
    const name = taskElement.getAttribute('data-name') || '';
    const description = taskElement.getAttribute('data-description') || '';
    const type = taskElement.getAttribute('data-type') || '';
    const status = taskElement.getAttribute('data-status') || '';
    const complexity = taskElement.getAttribute('data-complexity') || '';
    const dateFrom = taskElement.getAttribute('data-date-from') || '';
    const dateTo = taskElement.getAttribute('data-date-to') || '';
    
    // Remplir les détails de la tâche
    const titleElem = document.getElementById('taskTitle');
    if (titleElem) titleElem.textContent = name;
    
    const descriptionElem = document.getElementById('taskDescription');
    if (descriptionElem) descriptionElem.textContent = description;
    
    const typeElem = document.getElementById('taskType');
    if (typeElem) typeElem.textContent = type;
    
    const statusElem = document.getElementById('taskStatus');
    if (statusElem) statusElem.textContent = status;
    
    const categoryElem = document.getElementById('taskCategory');
    if (categoryElem) categoryElem.textContent = complexity;
    
    const dateFromElem = document.getElementById('taskDateFrom');
    if (dateFromElem) dateFromElem.textContent = dateFrom;
    
    const dateToElem = document.getElementById('taskDateTo');
    if (dateToElem) dateToElem.textContent = dateTo;
    
    // Configurer le lien de retour
    const projectLinkElem = document.getElementById('taskProjectLink') as HTMLAnchorElement | null;
    if (projectLinkElem) projectLinkElem.href = window.location.href;
    
    // Afficher la modal avec flexbox
    const modal = document.getElementById('taskDetailModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.setAttribute('data-current-task-id', id);
    }
    
    // Empêcher le défilement de la page
    document.body.style.overflow = 'hidden';
}
 
/**
 * Ferme la modal de détail de tâche
 */
function closeTaskModal(): void {
    const modal = document.getElementById('taskDetailModal');
    if (modal) modal.style.display = 'none';
    // Réactiver le défilement
    document.body.style.overflow = 'auto';
}

window.showCreateForm = showCreateForm;
window.showCreateTaskForm = showCreateTaskForm;
window.showTaskForm = showTaskForm;
window.showDeletePopup = showDeletePopup;
window.openTaskModal = openTaskModal;
window.closeTaskModal = closeTaskModal; 