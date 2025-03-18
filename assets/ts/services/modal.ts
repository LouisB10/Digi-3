/**
 * assets/ts/services/modal.ts
 * Service de gestion des modales
 */

// Stockage d'éléments du focus
let lastFocusedElement: HTMLElement | null = null;

/**
 * Affiche une modale
 * @param modal - L'élément HTML de la modale
 */
export function showModal(modal: HTMLElement): void {
  if (modal) {
    // Stocker l'élément actuellement focusé pour le restaurer plus tard
    lastFocusedElement = document.activeElement as HTMLElement;
    
    // Afficher la modale
    modal.hidden = false;
    modal.setAttribute('aria-modal', 'true');
    
    // Focus sur le premier élément focusable
    const focusableElements = modal.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    
    if (focusableElements.length > 0) {
      (focusableElements[0] as HTMLElement).focus();
    }
  }
}

/**
 * Cache une modale
 * @param modal - L'élément HTML de la modale
 */
export function hideModal(modal: HTMLElement): void {
  if (modal) {
    // Cacher la modale
    modal.hidden = true;
    modal.setAttribute('aria-modal', 'false');
    
    // Restaurer le focus
    if (lastFocusedElement) {
      lastFocusedElement.focus();
    }
  }
}

/**
 * Initialise les événements de fermeture pour une modale
 * @param modal - L'élément HTML de la modale
 */
export function initModalCloseEvents(modal: HTMLElement): void {
  if (!modal) return;
  
  // Gestionnaires pour les boutons de fermeture
  const closeButtons = modal.querySelectorAll('.close, .btn-cancel');
  closeButtons.forEach(button => {
    button.addEventListener('click', function() {
      hideModal(modal);
    });
  });
  
  // Fermer avec Escape
  modal.addEventListener('keydown', function(event: KeyboardEvent) {
    if (event.key === 'Escape') {
      hideModal(modal);
    }
  });
} 