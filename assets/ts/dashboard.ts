/**
 * assets/ts/dashboard.ts
 * Script pour le tableau de bord
 */

document.addEventListener('DOMContentLoaded', function() {
    // Récupérer le bouton calendrier et la section du calendrier
    const calendarBtn = document.getElementById('calendar-toggle') as HTMLButtonElement;
    const calendarSection = document.querySelector('.calendar') as HTMLElement;
    
    // Ajouter un écouteur d'événement pour le clic sur le bouton
    if (calendarBtn && calendarSection) {
        // Par défaut, le calendrier est visible
        calendarBtn.classList.add('active-link');
        calendarBtn.setAttribute('aria-expanded', 'true');
        
        calendarBtn.addEventListener('click', function(e: MouseEvent) {
            e.preventDefault(); // Empêcher le comportement par défaut du lien
            
            // Basculer la visibilité du calendrier
            if (calendarSection.style.display === 'none') {
                calendarSection.style.display = 'block';
                calendarBtn.classList.add('active-link');
                calendarBtn.setAttribute('aria-expanded', 'true');
            } else {
                calendarSection.style.display = 'none';
                calendarBtn.classList.remove('active-link');
                calendarBtn.setAttribute('aria-expanded', 'false');
            }
        });
        
        // Configuration initiale pour l'accessibilité
        calendarBtn.setAttribute('aria-controls', 'calendar-section');
    }

    // Initialiser les barres de progression
    initProgressBars();
    
    // Initialiser les graphiques si présents
    initCharts();
});

/**
 * Initialise les barres de progression
 */
function initProgressBars(): void {
    const progressBars = document.querySelectorAll<HTMLElement>('.progress');
    
    progressBars.forEach(bar => {
        const percentage = bar.getAttribute('data-percentage');
        if (percentage) {
            bar.style.width = percentage + '%';
            
            // Amélioration de l'accessibilité
            bar.setAttribute('role', 'progressbar');
            bar.setAttribute('aria-valuenow', percentage);
            bar.setAttribute('aria-valuemin', '0');
            bar.setAttribute('aria-valuemax', '100');
            
            // Ajouter une description textuelle pour les lecteurs d'écran
            const label = bar.closest('.progress-container')?.querySelector('.progress-label');
            if (label) {
                const labelId = 'progress-label-' + Math.random().toString(36).substr(2, 9);
                label.id = labelId;
                bar.setAttribute('aria-labelledby', labelId);
            }
        }
    });
}

/**
 * Initialise les graphiques du tableau de bord si des bibliothèques de graphiques sont utilisées
 */
function initCharts(): void {
    // Si une bibliothèque de graphiques comme Chart.js est utilisée
    // Cette fonction peut être étendue en fonction des besoins
    
    const chartContainers = document.querySelectorAll('.chart-container');
    if (chartContainers.length > 0) {
        console.log('Conteneurs de graphiques détectés. Prêt pour l\'initialisation des graphiques.');
        
        // Exemple d'initialisation de graphique (à personnaliser selon la bibliothèque utilisée)
        // if (window.Chart) {
        //     // Initialiser les graphiques
        // }
    }
} 