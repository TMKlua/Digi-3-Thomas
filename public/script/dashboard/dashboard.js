document.addEventListener('DOMContentLoaded', function() {
    // Récupérer le bouton calendrier et la section du calendrier
    const calendarBtn = document.querySelector('.sidebar-nav a:nth-child(2)');
    const calendarSection = document.querySelector('.calendar');
    
    // Ajouter un écouteur d'événement pour le clic sur le bouton
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
});