/**
 * assets/ts/services/notification.ts
 * Service de gestion des notifications et alertes
 */

/**
 * Types d'alertes supportés
 */
export type AlertType = 'success' | 'error' | 'warning' | 'info';

/**
 * Affiche une alerte
 * @param type - Type d'alerte (success, error, warning, info)
 * @param message - Message à afficher
 * @param duration - Durée d'affichage en millisecondes
 * @returns L'élément d'alerte créé
 */
export function showAlert(type: AlertType, message: string, duration: number = 5000): HTMLDivElement {
  // Créer l'élément d'alerte
  const alertContainer = document.createElement('div');
  alertContainer.className = `alert alert-${type}`;
  alertContainer.setAttribute('role', 'alert');
  alertContainer.textContent = message;
  
  // Trouver l'élément parent où ajouter l'alerte (main ou body si main n'existe pas)
  const parentElement = document.querySelector('main') || document.body;
  
  // Ajouter l'alerte au début du contenu
  parentElement.prepend(alertContainer);
  
  // Faire défiler vers le haut pour voir l'alerte
  window.scrollTo({ top: 0, behavior: 'smooth' });
  
  // Ajouter une classe pour l'animation d'entrée
  setTimeout(() => {
    alertContainer.classList.add('show');
  }, 10);
  
  // Ajouter le bouton de fermeture
  const closeButton = document.createElement('button');
  closeButton.className = 'alert-close';
  closeButton.innerHTML = '&times;';
  closeButton.setAttribute('aria-label', 'Fermer');
  alertContainer.appendChild(closeButton);
  
  // Gérer la fermeture de l'alerte
  closeButton.addEventListener('click', () => {
    closeAlert(alertContainer);
  });
  
  // Supprimer l'alerte après la durée spécifiée
  if (duration > 0) {
    setTimeout(() => {
      closeAlert(alertContainer);
    }, duration);
  }
  
  return alertContainer;
}

/**
 * Ferme une alerte avec animation
 * @param alertElement - L'élément d'alerte à fermer
 */
function closeAlert(alertElement: HTMLElement): void {
  // Ajouter la classe pour l'animation de sortie
  alertElement.classList.add('fade-out');
  
  // Supprimer l'élément après la fin de l'animation
  setTimeout(() => {
    if (alertElement.parentNode) {
      alertElement.parentNode.removeChild(alertElement);
    }
  }, 300); // Correspondant à la durée de l'animation CSS
} 