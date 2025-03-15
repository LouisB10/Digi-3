/**
 * Script pour la gestion de l'authentification
 * Gère la connexion et l'inscription
 */
document.addEventListener("DOMContentLoaded", function () {
  console.log("Script d'authentification chargé");
  
  // Éléments DOM
  const loginSection = document.getElementById("loginSection");
  const registerSection = document.getElementById("registerSection");
  const switchToRegister = document.getElementById("switchToRegister");
  const switchToLogin = document.getElementById("switchToLogin");
  
  console.log("Sections:", { 
    loginSection: loginSection ? "trouvé" : "non trouvé", 
    registerSection: registerSection ? "trouvé" : "non trouvé",
    switchToRegister: switchToRegister ? "trouvé" : "non trouvé",
    switchToLogin: switchToLogin ? "trouvé" : "non trouvé"
  });
  
  const registerForm = document.getElementById("register_form");
  const passwordInput = document.querySelector("#register_form input[type='password']");
  const passwordRequirements = document.getElementById("password-requirements");

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
  showLoginSection();

  // Toggle password visibility
  const togglePasswordBtns = document.querySelectorAll('.toggle-password');
  togglePasswordBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      const targetId = this.getAttribute('data-target');
      const passwordInput = document.getElementById(targetId);
      
      if (passwordInput) {
        if (passwordInput.type === 'password') {
          passwordInput.type = 'text';
          this.setAttribute('aria-label', 'Masquer le mot de passe');
        } else {
          passwordInput.type = 'password';
          this.setAttribute('aria-label', 'Afficher le mot de passe');
        }
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
    console.log("Champ de mot de passe trouvé:", passwordInput.id);
    
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
        reqList = document.createElement('ul');
        passwordRequirements.appendChild(reqList);
        
        requirements.forEach(function(req) {
          const li = document.createElement('li');
          li.id = 'req-' + req.id;
          li.textContent = req.text;
          reqList.appendChild(li);
        });
      }
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
    console.log("Champ de mot de passe non trouvé");
  }

  // Gestion du formulaire d'inscription
  if (registerForm) {
    registerForm.addEventListener("submit", function (event) {
      // Ne pas empêcher la soumission par défaut du formulaire
      
      // Vérifier si le mot de passe est valide
      const password = passwordInput ? passwordInput.value : '';
      const validations = validatePassword(password);
      const allValid = validations.every(validation => validation.test);
      
      if (!allValid) {
        // Empêcher la soumission si le mot de passe n'est pas valide
        event.preventDefault();
        
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
      
      // Laisser le formulaire se soumettre normalement
    });
  }

  // Gestion des événements de navigation
  if (switchToRegister) {
    console.log("Ajout de l'événement pour switchToRegister");
    switchToRegister.addEventListener("click", function (event) {
      console.log("Clic sur switchToRegister");
      event.preventDefault();
      showRegisterSection();
    });
  }

  if (switchToLogin) {
    console.log("Ajout de l'événement pour switchToLogin");
    switchToLogin.addEventListener("click", function (event) {
      console.log("Clic sur switchToLogin");
      event.preventDefault();
      showLoginSection();
    });
  }
}); 