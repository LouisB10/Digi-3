document.addEventListener("DOMContentLoaded", () => {

  document.querySelectorAll('.parameter-item').forEach((item) => {
    const paramDateTo = new Date(item.dataset.paramDateTo); // Assure-toi que cette valeur est dans ton HTML
    const currentDate = new Date();

    if (paramDateTo < currentDate) {
        // Si la date de fin est passée, désactive les boutons supprimer et modifier
        item.querySelector('#deleteBtn').disabled = true;
        item.querySelector('#editBtn').disabled = true;
    }
});


  const searchForm = document.getElementById("searchForm");
  const parameterTable = document.getElementById("parameter_table");
  const deleteBtns = document.querySelectorAll("#deleteBtn");
  const createForm = document.getElementById("createForm");

  searchForm.addEventListener("input", (event) => {
    event.preventDefault(); // Empêche le rechargement de la page lors de la soumission du formulaire

    const formData = new FormData(searchForm); // Récupère toutes les données du formulaire

    fetch("/parameter/search", {
      method: "POST",
      body: formData, // Envoie les données du formulaire
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Erreur réseau : " + response.status);
        }
        return response.json();
      })
      .then((data) => {
        // Vérifie si des paramètres ont été trouvés
        if (data.parameters.length > 0) {
          parameterTable.innerHTML = data.html; // Met à jour le tableau avec les résultats
        } else {
          // Si aucun paramètre n'est trouvé, afficher un message ou un tableau vide
          parameterTable.innerHTML =
            '<tr><td colspan="5">Aucun paramètre trouvé.</td></tr>';
        }
      })
      .catch((error) => console.error("Erreur:", error));
  });

  deleteBtns.forEach((button) => {
    button.addEventListener("click", () => {
      const parameterId = button.getAttribute("data-id"); // Récupère l'ID du paramètre
      // Récupérer les critères de recherche
      const searchTerm = document.querySelector(
        "#search_form_searchTerm"
      ).value;
      const showAll = document.querySelector("#search_form_showAll").checked;
      const dateSelect = document.querySelector(
        "#search_form_dateSelect"
      ).value;

      if (confirm("Êtes-vous sûr de vouloir supprimer ce paramètre ?")) {
        // Envoie une requête AJAX pour supprimer le paramètre
        fetch(`/parameter/delete/${parameterId}`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest", // Indique que c'est une requête AJAX
          },
          body: JSON.stringify({
            searchTerm: searchTerm,
            showAll: showAll,
            dateSelect: dateSelect,
          }),
        })
          .then((response) => {
            if (!response.ok) {
              throw new Error(
                "Erreur reponse lors de la suppression : " + response.status
              );
            }
            return response.json();
          })
          .then((data) => {
            if (data.success) {
              document.querySelector("#parameter_table").innerHTML = data.html;
            } else {
              alert("Erreur data lors de la suppression : " + data.message);
            }
          })
          .catch((error) => console.error("Erreur:", error));
      }
    });
  });

  createForm.addEventListener("submit", (event) => {
    event.preventDefault();

    const formData = new FormData(createForm);
    for (let [key, value] of formData.entries()) {
      console.log(`${key}: ${value}`);
    }
    fetch("/parameter/create", {
      method: "POST",
      body: formData,
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(
            "Erreur reponse lors de la création : " + response.status
          );
        }
        return response.json();
      })
      .then((data) => {
        console.log(data);
        if (data.success) {
          parameterTable.innerHTML = data.html; // Met à jour le tableau avec les résultats
          createForm.reset();
        } else {
          console.error("Erreur lors de la création du paramètre.");
        }
      })
      .catch((error) => console.error("Erreur:", error));
  });

  
}); 