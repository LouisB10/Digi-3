/**
 * assets/js/dashboard.js
 * Script pour le tableau de bord
 */

document.addEventListener('DOMContentLoaded', function() {
    // Récupérer le bouton calendrier et la section du calendrier
    const calendarBtn = document.getElementById('calendar-toggle');
    const calendarSection = document.querySelector('.calendar');
    
    // Ajouter un écouteur d'événement pour le clic sur le bouton
    if (calendarBtn && calendarSection) {
        calendarBtn.addEventListener('click', function(e) {
            e.preventDefault(); // Empêcher le comportement par défaut du lien
            
            // Basculer la classe active sur la section calendrier
            calendarSection.classList.toggle('active');
            
            // Changer le style du bouton pour indiquer qu'il est actif
            if (calendarSection.classList.contains('active')) {
                calendarBtn.classList.add('active-link');
            } else {
                calendarBtn.classList.remove('active-link');
            }
        });
    }

    // Initialiser les barres de progression
    initProgressBars();
});

/**
 * Initialise les barres de progression
 */
function initProgressBars() {
    const progressBars = document.querySelectorAll('.progress');
    
    progressBars.forEach(bar => {
        const percentage = bar.getAttribute('data-percentage');
        if (percentage) {
            bar.style.width = percentage + '%';
        }
    });
}