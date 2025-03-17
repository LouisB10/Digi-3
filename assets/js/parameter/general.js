document.addEventListener("DOMContentLoaded", () => {
  console.log("Script parameter/general.js chargé");
  
  // Gestion de la mise à jour de la photo de profil
  const fileInput = document.getElementById("file");
  if (fileInput) {
    console.log("Input file trouvé:", fileInput);
    
    fileInput.addEventListener("change", function (event) {
      const file = event.target.files[0];
      if (file) {
        console.log("Fichier sélectionné:", file.name, "type:", file.type, "taille:", file.size);
        
        // Vérifier le type de fichier
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
          alert("Type de fichier non autorisé. Veuillez sélectionner une image (JPG, PNG, GIF ou WEBP).");
          fileInput.value = ''; // Réinitialiser l'input
          return;
        }
        
        // Vérifier la taille du fichier (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
          alert("Le fichier est trop volumineux. La taille maximale est de 5 Mo.");
          fileInput.value = ''; // Réinitialiser l'input
          return;
        }

        const formData = new FormData();
        formData.append("profile_picture", file);
        
        // Récupérer le token CSRF si disponible
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        console.log("CSRF Token trouvé:", csrfToken ? "Oui" : "Non");
        
        if (csrfToken) {
          formData.append('_token', csrfToken);
        } else {
          console.warn("Aucun token CSRF trouvé. L'upload pourrait échouer.");
        }

        // Récupérer l'URL depuis l'attribut data-url
        const updateUrl = fileInput.getAttribute("data-url");
        console.log("URL pour l'upload:", updateUrl);
        
        // Afficher un indicateur de chargement
        const outputImg = document.getElementById("output");
        if (outputImg) {
          outputImg.style.opacity = "0.5"; // Assombrir l'image pendant le chargement
        }

        fetch(updateUrl, {
          method: "POST",
          body: formData,
          // Ne pas définir Content-Type avec FormData, le navigateur le fait automatiquement
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
          .then((response) => {
            console.log("Réponse du serveur:", response.status, response.statusText);
            if (!response.ok) {
              if (response.status === 413) {
                throw new Error("Le fichier est trop volumineux pour le serveur.");
              } else if (response.status === 403) {
                throw new Error("Accès refusé. Veuillez vous reconnecter et réessayer.");
              } else {
                return response.json().then(data => {
                  throw new Error(data.error || `Erreur HTTP: ${response.status}`);
                }).catch(e => {
                  throw new Error(`Erreur HTTP: ${response.status}`);
                });
              }
            }
            return response.json();
          })
          .then((data) => {
            console.log("Données récupérées:", data);
            if (data.success) {
              // Ajouter un timestamp pour éviter le cache du navigateur
              const timestamp = new Date().getTime();
              
              if (outputImg) {
                console.log("Image à mettre à jour:", outputImg);
                console.log("Nouvelle URL:", data.newProfilePictureUrl + "?t=" + timestamp);
                
                outputImg.style.opacity = "1"; // Restaurer l'opacité
                outputImg.src = data.newProfilePictureUrl + "?t=" + timestamp;
                alert("Photo de profil mise à jour avec succès!");
              } else {
                console.error("Élément d'image de sortie non trouvé");
                alert("Photo mise à jour, mais l'affichage n'a pas pu être actualisé. Veuillez rafraîchir la page.");
              }
            } else {
              console.error("Erreur retournée par le serveur:", data.error);
              alert("Erreur: " + data.error);
            }
            
            // Réinitialiser l'input file pour permettre de sélectionner à nouveau le même fichier
            fileInput.value = '';
          })
          .catch((error) => {
            console.error("Erreur lors de l'upload:", error);
            if (outputImg) {
              outputImg.style.opacity = "1"; // Restaurer l'opacité en cas d'erreur
            }
            alert("Erreur lors de la mise à jour de la photo de profil: " + error.message);
            fileInput.value = ''; // Réinitialiser l'input
          });
      }
    });
  } else {
    console.error("Input file non trouvé");
  }

  // Gestion des boutons pour afficher/masquer les mots de passe
  const togglePasswordButtons = document.querySelectorAll('.toggle-password');
  if (togglePasswordButtons.length > 0) {
    console.log("Boutons de mot de passe trouvés:", togglePasswordButtons.length);
    
    togglePasswordButtons.forEach(function(toggle) {
      toggle.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const passwordInput = document.getElementById(targetId);
        const eyeIcon = this.querySelector('.eye-icon');
        
        console.log("Clic sur le bouton de mot de passe pour:", targetId);
        
        if (passwordInput.type === 'password') {
          passwordInput.type = 'text';
          eyeIcon.src = eyeIcon.getAttribute('data-eye-off');
          console.log("Mot de passe visible");
        } else {
          passwordInput.type = 'password';
          eyeIcon.src = eyeIcon.getAttribute('data-eye');
          console.log("Mot de passe masqué");
        }
      });
    });
  } else {
    console.log("Aucun bouton de mot de passe trouvé");
  }
  
  // Gestion de la mise à jour de l'email via AJAX
  const emailForm = document.getElementById("email_form");
  if (emailForm) {
    emailForm.addEventListener("submit", function(event) {
      event.preventDefault();
      
      const emailInput = document.getElementById("email_form_email");
      const passwordInput = document.getElementById("email_form_password");
      const updateUrl = emailForm.getAttribute("action") || "/parameter/general/update-email";
      
      const data = {
        email: emailInput.value,
        password: passwordInput.value
      };
      
      fetch(updateUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Mettre à jour l'affichage de l'email
            const emailDisplay = document.querySelector(".account-info .title p:nth-child(3)");
            if (emailDisplay) {
              emailDisplay.textContent = emailInput.value;
            }
            
            // Afficher un message de succès
            showMessage(emailForm, "success", data.message);
            
            // Réinitialiser le formulaire
            passwordInput.value = "";
          } else {
            showMessage(emailForm, "error", data.message);
          }
        })
        .catch(error => {
          console.error("Erreur:", error);
          showMessage(emailForm, "error", "Une erreur est survenue. Veuillez réessayer.");
        });
    });
  }
  
  // Gestion de la mise à jour du mot de passe via AJAX
  const passwordForm = document.getElementById("password_form");
  if (passwordForm) {
    passwordForm.addEventListener("submit", function(event) {
      event.preventDefault();
      
      const actualPasswordInput = document.getElementById("password_form_actual_password");
      const newPasswordInput = document.getElementById("password_form_password");
      const confirmPasswordInput = document.getElementById("password_form_confirm_password");
      const updateUrl = passwordForm.getAttribute("action") || "/parameter/general/update-password";
      
      const data = {
        currentPassword: actualPasswordInput.value,
        newPassword: newPasswordInput.value,
        confirmPassword: confirmPasswordInput.value
      };
      
      fetch(updateUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Afficher un message de succès
            showMessage(passwordForm, "success", data.message);
            
            // Réinitialiser le formulaire
            actualPasswordInput.value = "";
            newPasswordInput.value = "";
            confirmPasswordInput.value = "";
          } else {
            showMessage(passwordForm, "error", data.message);
          }
        })
        .catch(error => {
          console.error("Erreur:", error);
          showMessage(passwordForm, "error", "Une erreur est survenue. Veuillez réessayer.");
        });
    });
  }
  
  // Fonction utilitaire pour afficher des messages
  function showMessage(form, type, message) {
    // Supprimer les anciens messages
    const existingMessages = form.parentNode.querySelectorAll(".alert");
    existingMessages.forEach(msg => msg.remove());
    
    // Créer et ajouter le nouveau message
    const messageDiv = document.createElement("div");
    messageDiv.className = `alert alert-${type === "success" ? "success" : "danger"}`;
    messageDiv.textContent = message;
    
    // Ajouter après le formulaire
    form.parentNode.appendChild(messageDiv);
    
    // Faire défiler jusqu'au message
    messageDiv.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }
}); 