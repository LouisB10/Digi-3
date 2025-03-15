document.addEventListener("DOMContentLoaded", () => {
  // Vérifier si les éléments existent avant d'y accéder
  const parameterItems = document.querySelectorAll('.parameter-item');
  if (parameterItems.length > 0) {
    parameterItems.forEach((item) => {
      const paramDateTo = new Date(item.dataset.paramDateTo);
      const currentDate = new Date();

      if (paramDateTo < currentDate) {
        // Si la date de fin est passée, désactive les boutons supprimer et modifier
        const deleteBtn = item.querySelector('#deleteBtn');
        const editBtn = item.querySelector('#editBtn');
        
        if (deleteBtn) deleteBtn.disabled = true;
        if (editBtn) editBtn.disabled = true;
      }
    });
  }

  const searchForm = document.getElementById("searchForm");
  const parameterTable = document.getElementById("parameter_table");
  const deleteBtns = document.querySelectorAll("#deleteBtn");
  const createForm = document.getElementById("createForm");

  if (searchForm) {
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
  }

  if (deleteBtns.length > 0) {
    deleteBtns.forEach((button) => {
      button.addEventListener("click", () => {
        const parameterId = button.getAttribute("data-id"); // Récupère l'ID du paramètre
        // Récupérer les critères de recherche
        const searchTermElement = document.querySelector("#search_form_searchTerm");
        const showAllElement = document.querySelector("#search_form_showAll");
        const dateSelectElement = document.querySelector("#search_form_dateSelect");
        
        const searchTerm = searchTermElement ? searchTermElement.value : '';
        const showAll = showAllElement ? showAllElement.checked : false;
        const dateSelect = dateSelectElement ? dateSelectElement.value : '';

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
                const tableElement = document.querySelector("#parameter_table");
                if (tableElement) tableElement.innerHTML = data.html;
              } else {
                alert("Erreur data lors de la suppression : " + data.message);
              }
            })
            .catch((error) => console.error("Erreur:", error));
        }
      });
    });
  }

  if (createForm) {
    createForm.addEventListener("submit", (event) => {
      event.preventDefault();

      const formData = new FormData(createForm);
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
          if (data.success && parameterTable) {
            parameterTable.innerHTML = data.html; // Met à jour le tableau avec les résultats
            createForm.reset();
          } else {
            console.error("Erreur lors de la création du paramètre.");
          }
        })
        .catch((error) => console.error("Erreur:", error));
    });
  }
});
