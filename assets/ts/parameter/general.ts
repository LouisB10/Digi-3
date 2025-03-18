/**
 * assets/ts/parameter/general.ts
 * Gestion des paramètres généraux et de la photo de profil
 */

// Importation des types et services
import { ProfileResponse, ApiResponse } from '../types/api';
import { showAlert } from '../services/notification';

document.addEventListener("DOMContentLoaded", (): void => {
  console.log("Script parameter/general.ts chargé");
  
  // Gestion de la mise à jour de la photo de profil
  initProfilePictureUpload();
  
  // Gestion des formulaires
  initForms();
  
  /**
   * Initialise le gestionnaire de téléchargement de la photo de profil
   */
  function initProfilePictureUpload(): void {
    const fileInput = document.getElementById("file") as HTMLInputElement;
    if (fileInput) {
      console.log("Input file trouvé:", fileInput);
      
      fileInput.addEventListener("change", function (event: Event): void {
        const target = event.target as HTMLInputElement;
        const file = target.files?.[0];
        
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
          const csrfMetaTag = document.querySelector('meta[name="csrf-token"]');
          const csrfToken = csrfMetaTag ? csrfMetaTag.getAttribute('content') : null;
          console.log("CSRF Token trouvé:", csrfToken ? "Oui" : "Non");
          
          if (csrfToken) {
            formData.append('_token', csrfToken);
          } else {
            console.warn("Aucun token CSRF trouvé. L'upload pourrait échouer.");
          }

          // Récupérer l'URL depuis l'attribut data-url
          const updateUrl = fileInput.getAttribute("data-url");
          if (!updateUrl) {
            console.error("Aucune URL trouvée pour l'upload de l'image");
            return;
          }
          
          console.log("URL pour l'upload:", updateUrl);
          
          // Afficher un indicateur de chargement
          const outputImg = document.getElementById("output") as HTMLImageElement;
          if (outputImg) {
            outputImg.style.opacity = "0.5"; // Assombrir l'image pendant le chargement
          }
          
          // Créer un message de chargement
          const loadingMsg = document.createElement("div");
          loadingMsg.className = "loading-message";
          loadingMsg.textContent = "Chargement de l'image...";
          
          const profilePic = document.querySelector(".profile-pic");
          if (profilePic) {
            profilePic.appendChild(loadingMsg);
          }

          // Envoyer l'image au serveur
          fetch(updateUrl, {
            method: "POST",
            body: formData,
          })
          .then(response => {
            if (!response.ok) {
              throw new Error("Erreur lors de l'upload de l'image");
            }
            return response.json() as Promise<ProfileResponse>;
          })
          .then(data => {
            if (data.success && data.newImagePath) {
              // Mettre à jour l'image affichée
              if (outputImg) {
                outputImg.src = data.newImagePath;
                outputImg.style.opacity = "1"; // Rétablir l'opacité
              }
              
              // Afficher un message de succès
              showAlert('success', data.message || "Image mise à jour avec succès");
            } else {
              throw new Error(data.message || "Erreur lors de l'upload de l'image");
            }
          })
          .catch(error => {
            console.error("Erreur:", error);
            showAlert('error', error.message);
          })
          .finally(() => {
            // Supprimer le message de chargement
            if (loadingMsg.parentNode) {
              loadingMsg.parentNode.removeChild(loadingMsg);
            }
            
            // Rétablir l'opacité de l'image
            if (outputImg) {
              outputImg.style.opacity = "1";
            }
          });
        }
      });
    }
  }
  
  /**
   * Initialise les gestionnaires d'événements pour les formulaires
   */
  function initForms(): void {
    // Formulaire de mise à jour de l'email
    const emailForm = document.getElementById("updateEmailForm") as HTMLFormElement;
    if (emailForm) {
      emailForm.addEventListener("submit", function(event: Event): void {
        event.preventDefault();
        submitForm(this, "update-email");
      });
    }
    
    // Formulaire de mise à jour du mot de passe
    const passwordForm = document.getElementById("updatePasswordForm") as HTMLFormElement;
    if (passwordForm) {
      passwordForm.addEventListener("submit", function(event: Event): void {
        event.preventDefault();
        submitForm(this, "update-password");
      });
    }
  }
  
  /**
   * Soumet un formulaire via AJAX
   * @param form - Le formulaire à soumettre
   * @param action - Type d'action (update-email ou update-password)
   */
  function submitForm(form: HTMLFormElement, action: string): void {
    // Récupérer l'URL depuis l'attribut action du formulaire
    const url = form.getAttribute("action");
    if (!url) {
      showAlert('error', "Aucune URL trouvée pour le formulaire");
      return;
    }
    
    // Récupérer le token CSRF
    const csrfField = form.querySelector('input[name="_token"]') as HTMLInputElement;
    const csrfToken = csrfField ? csrfField.value : null;
    
    if (!csrfToken) {
      showAlert('error', "Token CSRF manquant");
      return;
    }
    
    // Préparer les données du formulaire
    const formData = new FormData(form);
    
    // Désactiver le bouton de soumission pendant le traitement
    const submitButton = form.querySelector('button[type="submit"]') as HTMLButtonElement;
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.innerHTML = '<span class="spinner"></span> Traitement...';
    }
    
    // Envoyer les données au serveur
    fetch(url, {
      method: "POST",
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => {
      if (!response.ok) {
        throw new Error("Erreur lors de la soumission du formulaire");
      }
      return response.json() as Promise<ApiResponse>;
    })
    .then(data => {
      if (data.success) {
        showAlert('success', data.message);
        
        // Réinitialiser le formulaire dans le cas du mot de passe
        if (action === "update-password") {
          form.reset();
        }
      } else {
        showAlert('error', data.message);
      }
    })
    .catch(error => {
      console.error("Erreur:", error);
      showAlert('error', error.message);
    })
    .finally(() => {
      // Réactiver le bouton de soumission
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.innerHTML = 'Enregistrer';
      }
    });
  }
}); 