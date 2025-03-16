/**
 * Script pour la gestion de l'authentification
 * Gère la connexion et l'inscription
 */

// Log directement dans la console, sans attendre DOMContentLoaded
console.log("Script auth.js chargé");

document.addEventListener("DOMContentLoaded", function () {
  console.log("DOM chargé - Script d'authentification initialisé");
  
  // Éléments DOM
  const loginSection = document.getElementById("loginSection");
  const registerSection = document.getElementById("registerSection");
  const switchToRegister = document.getElementById("switchToRegister");
  const switchToLogin = document.getElementById("switchToLogin");
  const registerForm = document.getElementById("register_form");
  const passwordInput = document.getElementById("register_form_password");
  const passwordRequirements = document.getElementById("password-requirements");
  
  console.log("Éléments DOM récupérés:", {
    loginSection: loginSection ? "trouvé" : "non trouvé",
    registerSection: registerSection ? "trouvé" : "non trouvé",
    switchToRegister: switchToRegister ? "trouvé" : "non trouvé",
    switchToLogin: switchToLogin ? "trouvé" : "non trouvé",
    registerForm: registerForm ? "trouvé" : "non trouvé",
    passwordInput: passwordInput ? "trouvé" : "non trouvé",
    passwordRequirements: passwordRequirements ? "trouvé" : "non trouvé"
  });

  // Définition des fonctions de navigation entre les sections
  function showRegisterSection() {
    console.log("Affichage de la section d'inscription");
    if (loginSection) loginSection.classList.add('hidden');
    if (registerSection) registerSection.classList.remove('hidden');
    document.title = "Digi-3 - Inscription";
    
    // Focus sur le premier champ du formulaire pour l'accessibilité
    setTimeout(() => {
      const firstInput = registerSection.querySelector('input:not([type="hidden"])');
      if (firstInput) firstInput.focus();
    }, 100);
  }

  function showLoginSection() {
    console.log("Affichage de la section de connexion");
    if (registerSection) registerSection.classList.add('hidden');
    if (loginSection) loginSection.classList.remove('hidden');
    document.title = "Digi-3 - Connexion";
    
    // Focus sur le premier champ du formulaire pour l'accessibilité
    setTimeout(() => {
      const firstInput = loginSection.querySelector('input:not([type="hidden"])');
      if (firstInput) firstInput.focus();
    }, 100);
  }

  // Afficher la section de connexion par défaut
  console.log("Affichage de la section de connexion par défaut");
  showLoginSection();

  // Toggle password visibility
  const togglePasswordBtns = document.querySelectorAll('.toggle-password');
  console.log("Boutons de toggle de mot de passe trouvés:", togglePasswordBtns.length);
  
  togglePasswordBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      const targetId = this.getAttribute('data-target');
      console.log("Toggle password pour l'élément:", targetId);
      const passwordInput = document.getElementById(targetId);
      
      if (passwordInput) {
        if (passwordInput.type === 'password') {
          passwordInput.type = 'text';
          this.setAttribute('aria-label', 'Masquer le mot de passe');
        } else {
          passwordInput.type = 'password';
          this.setAttribute('aria-label', 'Afficher le mot de passe');
        }
      } else {
        console.error("Élément de mot de passe non trouvé:", targetId);
      }
    });
  });

  // Ajouter la gestion des événements clavier pour les boutons accessibles
  document.querySelectorAll('[role="button"]').forEach(button => {
    button.addEventListener('keydown', function(event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        this.click();
      }
    });
  });

  // Fonction de validation du mot de passe
  function validatePassword(password) {
    const constraints = {
      minLength: 8,
      requireSpecialChar: true,
      requireNumber: true,
      requireUppercase: true
    };

    const validations = [
      {
        test: password.length >= constraints.minLength,
        message: 'Au moins 8 caractères'
      },
      {
        test: /[^a-zA-Z\d]/.test(password),
        message: 'Au moins un caractère spécial'
      },
      {
        test: /\d/.test(password),
        message: 'Au moins un chiffre'
      },
      {
        test: /[A-Z]/.test(password),
        message: 'Au moins une majuscule'
      }
    ];

    return validations;
  }

  // Validation en temps réel du mot de passe
  if (passwordInput) {
    console.log("Configuration de la validation du mot de passe");
    // Définir les exigences de mot de passe
    const requirements = [
      { id: 'length', regex: /.{8,}/, text: 'Au moins 8 caractères' },
      { id: 'uppercase', regex: /[A-Z]/, text: 'Au moins une majuscule' },
      { id: 'lowercase', regex: /[a-z]/, text: 'Au moins une minuscule' },
      { id: 'number', regex: /[0-9]/, text: 'Au moins un chiffre' },
      { id: 'special', regex: /[^A-Za-z0-9]/, text: 'Au moins un caractère spécial' }
    ];
    
    // Créer la liste des exigences si elle n'existe pas
    if (passwordRequirements) {
      let reqList = passwordRequirements.querySelector('ul');
      if (!reqList) {
        console.log("Création de la liste des exigences de mot de passe");
        reqList = document.createElement('ul');
        passwordRequirements.appendChild(reqList);
        
        requirements.forEach(function(req) {
          const li = document.createElement('li');
          li.id = 'req-' + req.id;
          li.textContent = req.text;
          reqList.appendChild(li);
        });
      }
    } else {
      console.warn("Élément passwordRequirements non trouvé");
    }
    
    passwordInput.addEventListener('input', function() {
      const password = this.value;
      let isValid = true;
      
      requirements.forEach(function(req) {
        const reqElement = document.getElementById('req-' + req.id);
        if (reqElement) {
          const meetsRequirement = req.regex.test(password);
          
          reqElement.classList.toggle('valid', meetsRequirement);
          reqElement.classList.toggle('invalid', !meetsRequirement);
          
          if (!meetsRequirement) {
            isValid = false;
          }
        }
      });
    });
  } else {
    console.warn("Élément de mot de passe non trouvé pour la validation");
  }

  // Gestion du formulaire d'inscription
  if (registerForm) {
    console.log("Configuration du gestionnaire d'événements pour le formulaire d'inscription");
    registerForm.addEventListener("submit", function (event) {
      console.log("Soumission du formulaire d'inscription");
      // Ne pas empêcher la soumission par défaut du formulaire
      
      // Vérifier si le mot de passe est valide
      const password = passwordInput.value;
      const validations = validatePassword(password);
      const allValid = validations.every(validation => validation.test);
      
      console.log("Validation du mot de passe:", allValid);
      
      if (!allValid) {
        // Empêcher la soumission si le mot de passe n'est pas valide
        event.preventDefault();
        console.warn("Soumission empêchée - Mot de passe invalide");
        
        // Afficher un message d'erreur
        const existingError = this.querySelector('.error');
        if (existingError) {
          existingError.remove();
        }
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error';
        errorDiv.setAttribute('role', 'alert');
        errorDiv.textContent = 'Veuillez corriger les erreurs dans le mot de passe.';
        this.insertBefore(errorDiv, this.firstChild);
        return;
      }

      // Désactiver le bouton pendant la soumission
      const submitButton = this.querySelector('button[type="submit"]');
      submitButton.disabled = true;
      submitButton.innerHTML = 'Inscription en cours...';
      console.log("Formulaire soumis avec succès");
      
      // Laisser le formulaire se soumettre normalement
    });
  } else {
    console.warn("Formulaire d'inscription non trouvé");
  }

  // Gestion des événements de navigation
  if (switchToRegister) {
    console.log("Configuration du bouton pour passer à l'inscription");
    switchToRegister.addEventListener("click", function (event) {
      console.log("Clic sur le bouton pour passer à l'inscription");
      event.preventDefault();
      showRegisterSection();
    });
  } else {
    console.warn("Bouton pour passer à l'inscription non trouvé");
  }

  if (switchToLogin) {
    console.log("Configuration du bouton pour passer à la connexion");
    switchToLogin.addEventListener("click", function (event) {
      console.log("Clic sur le bouton pour passer à la connexion");
      event.preventDefault();
      showLoginSection();
    });
  } else {
    console.warn("Bouton pour passer à la connexion non trouvé");
  }
});
