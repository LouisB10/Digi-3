/**
 * assets/ts/parameter/general.ts
 * Gestion des paramètres généraux et de la photo de profil
 */

// Importation des types et services
import { ProfileResponse, ApiResponse } from '../types/api';
import { showAlert } from '../services/notification';

document.addEventListener("DOMContentLoaded", (): void => {
  // Gestion de la mise à jour de la photo de profil
  initProfilePictureUpload();
  
  // Gestion des formulaires
  initForms();
  
  // Initialisation des toggles de mot de passe
  initPasswordToggles();
  
  /**
   * Initialise le gestionnaire de téléchargement de la photo de profil
   */
  function initProfilePictureUpload(): void {
    const fileInput = document.getElementById("file") as HTMLInputElement;
    if (fileInput) {
      fileInput.addEventListener("change", function (event: Event): void {
        const target = event.target as HTMLInputElement;
        const file = target.files?.[0];
        
        if (file) {
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
          
          if (csrfToken) {
            formData.append('_token', csrfToken);
          }

          // Récupérer l'URL depuis l'attribut data-url
          const updateUrl = fileInput.getAttribute("data-url");
          if (!updateUrl) {
            showAlert('error', "Erreur lors du téléchargement de l'image");
            return;
          }
          
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
    const emailForm = document.getElementById("email_form") as HTMLFormElement;
    if (emailForm) {
      emailForm.addEventListener("submit", function(event: Event): void {
        event.preventDefault();
        submitForm(this, "update-email");
      });
    }
    
    // Formulaire de mise à jour du mot de passe
    const passwordForm = document.getElementById("password_form") as HTMLFormElement;
    if (passwordForm) {
      passwordForm.addEventListener("submit", function(event: Event): void {
        event.preventDefault();
        submitForm(this, "update-password");
      });
    }
  }
  
  /**
   * Initialise les toggles de mot de passe
   */
  function initPasswordToggles(): void {
    // Pour chaque toggle, trouver le champ de mot de passe associé par sa position dans le DOM
    document.querySelectorAll<HTMLElement>('.toggle-password').forEach(toggle => {
      // Chercher l'input password le plus proche dans le même conteneur
      const container = toggle.closest('.password-container');
      if (!container) {
        return;
      }
      
      const passwordInput = container.querySelector('input[type="password"]');
      if (!passwordInput) {
        return;
      }
      
      // Ajouter l'écouteur d'événement
      toggle.addEventListener('click', function(this: HTMLElement) {
        // Obtenir le champ directement depuis le conteneur parent
        const container = this.closest('.password-container');
        if (!container) return;
        
        const input = container.querySelector('input[type="password"]') as HTMLInputElement;
        if (!input) return;
        
        const img = this.querySelector('img');
        if (!img) {
          return;
        }
        
        const eyeIcon = img.getAttribute('data-eye');
        const eyeOffIcon = img.getAttribute('data-eye-off');
        
        if (input.type === 'password') {
          input.type = 'text';
          if (eyeOffIcon) img.src = eyeOffIcon;
          this.setAttribute('aria-label', 'Masquer le mot de passe');
        } else {
          input.type = 'password';
          if (eyeIcon) img.src = eyeIcon;
          this.setAttribute('aria-label', 'Afficher le mot de passe');
        }
      });
    });
  }
  
  /**
   * Soumet un formulaire via AJAX
   * @param form - Le formulaire à soumettre
   * @param action - Type d'action (update-email ou update-password)
   */
  function submitForm(form: HTMLFormElement, action: string): void {
    // Récupérer le token CSRF
    const csrfFieldNames = ['_token', 'email_token', 'password_token', form.id + '_token'];
    let csrfToken: string | null = null;

    // Essayer de trouver le token dans le formulaire
    for (const name of csrfFieldNames) {
      const csrfField = form.querySelector(`input[name="${name}"]`) as HTMLInputElement | null;
      if (csrfField && csrfField.value) {
        csrfToken = csrfField.value;
        break;
      }
    }

    // Si non trouvé, chercher dans les balises meta
    if (!csrfToken) {
      const metaSelectors = [
        'meta[name="csrf-token"]',
        'meta[name="csrf-param"]',
        'meta[name="csrf_token"]'
      ];
      
      for (const selector of metaSelectors) {
        const metaTag = document.querySelector(selector);
        if (metaTag && metaTag.getAttribute('content')) {
          csrfToken = metaTag.getAttribute('content');
          break;
        }
      }
    }

    // Si toujours pas trouvé, afficher une erreur
    if (!csrfToken) {
      showAlert('error', "Token CSRF manquant");
      return;
    }
    
    // Récupérer l'URL depuis l'attribut action du formulaire
    const url = form.getAttribute("action");
    if (!url) {
      showAlert('error', "Aucune URL trouvée pour le formulaire");
      return;
    }
    
    // Désactiver le bouton de soumission pendant le traitement
    const submitButton = form.querySelector('button[type="submit"]') as HTMLButtonElement;
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.innerHTML = '<span class="spinner"></span> Traitement...';
    }
    
    // Construire un objet JSON pour les données
    const formData: Record<string, any> = {};
    
    // Ajouter le token CSRF et les données du formulaire
    if (action === 'update-email') {
      formData['email_token'] = csrfToken;
      
      // Récupérer les champs du formulaire d'email
      const emailField = form.querySelector('#email_update_email') as HTMLInputElement;
      const passwordField = form.querySelector('#email_update_password') as HTMLInputElement;
      
      if (!emailField || !passwordField) {
        showAlert('error', "Formulaire incomplet");
        return;
      }
      
      formData['email'] = emailField.value;
      formData['password'] = passwordField.value;
      
    } else if (action === 'update-password') {
      formData['password_token'] = csrfToken;
      
      // Récupérer les champs du formulaire de mot de passe
      const actualPasswordField = form.querySelector('#password_form_actual_password') as HTMLInputElement;
      const newPasswordField = form.querySelector('#password_form_password') as HTMLInputElement;
      const confirmPasswordField = form.querySelector('#password_form_confirm_password') as HTMLInputElement;
      
      if (!actualPasswordField || !newPasswordField || !confirmPasswordField) {
        showAlert('error', "Formulaire incomplet");
        return;
      }
      
      formData['currentPassword'] = actualPasswordField.value;
      formData['newPassword'] = newPasswordField.value;
      formData['confirmPassword'] = confirmPasswordField.value;
    }
    
    // Envoyer les données au serveur en JSON
    fetch(url, {
      method: "POST",
      body: JSON.stringify(formData),
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => {
      if (!response.ok) {
        // Essayer de lire le corps de la réponse pour plus de détails
        return response.text().then(text => {
          let errorMessage = `Erreur ${response.status}`;
          try {
            // Essayer de parser la réponse comme JSON
            const errorData = JSON.parse(text);
            if (errorData.message) {
              errorMessage = errorData.message;
            } else if (errorData.error) {
              errorMessage = errorData.error;
            }
          } catch (e) {
            // Si ce n'est pas du JSON, essayer de trouver des indices dans le HTML
            if (text.includes('<title>')) {
              const titleMatch = text.match(/<title>(.*?)<\/title>/);
              if (titleMatch && titleMatch[1]) {
                errorMessage = titleMatch[1].trim();
              }
            }
          }
          throw new Error(errorMessage);
        });
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
        showAlert('error', data.message || "Une erreur est survenue");
      }
    })
    .catch(error => {
      showAlert('error', error.message || "Erreur lors de la soumission du formulaire");
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