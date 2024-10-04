document.addEventListener('DOMContentLoaded', () => {
    const searchForm = document.getElementById('searchForm');
    const parametersContainer = document.getElementById('parametersContainer');

    searchForm.addEventListener('submit', (event) => {
        event.preventDefault(); // Empêche le rechargement de la page lors de la soumission du formulaire

        const searchTerm = document.getElementById('searchInput').value; // Récupère la valeur de la recherche
        console.log("L'utilisateur a recherché "+ searchTerm)

        fetch('/parameter/search', {
            method: 'POST', 
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, // Déclare le type de contenu
            body: new URLSearchParams({ 'search_term': searchTerm }) // Formate les données pour l'envoi
        })
        .then(response => response.ok ? response.json() : Promise.reject('Erreur réseau : ' + response.status)) // Vérifie si la réponse est OK, sinon rejette l'erreur
        .then(data => {
            parametersContainer.innerHTML = ''; // Vide l'affichage actuel

            if (Array.isArray(data.parameters)) {
                console.log("ad"); // Vérifie que les paramètres sont un tableau
                data.parameters.forEach(parameter => { // Pour chaque paramètre, crée un nouvel élément
                    const parameterItem = document.createElement('div');
                   
                    parameterItem.classList.add('parameter-item');
                    parameterItem.innerHTML = `<h3>${parameter.paramKey}</h3><p>${parameter.paramValue}</p>`;
                    parametersContainer.appendChild(parameterItem); // Ajoute l'élément au conteneur
                });
            } else {
                console.error('Aucun paramètre trouvé ou format incorrect.');
            }
        })
        .catch(error => console.error('Erreur:', error)); // Capture toute erreur et l'affiche dans la console
    });
}); 
