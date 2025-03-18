/**
 * Script pour la gestion de l'authentification
 * Gère la connexion, l'inscription et la réinitialisation de mot de passe
 */

document.addEventListener("DOMContentLoaded", function () {
  // Éléments DOM
  const loginSection = document.getElementById("loginSection");
  const registerSection = document.getElementById("registerSection");
  const switchToRegister = document.getElementById("switchToRegister");
  const switchToLogin = document.getElementById("switchToLogin");
  const registerForm = document.getElementById("register_form");
  const formState = document.getElementById("formState");
  const passwordRequirements = document.getElementById("password-requirements");
  
  // Gestion des messages flash
  initFlashMessages();
  
  // Déterminer si nous sommes sur la page d'inscription ou de réinitialisation
  const isResetPasswordPage = window.location.href.includes('reset-password');
  const isAuthPage = (loginSection && registerSection);
  
  // Déterminer quel champ de mot de passe utiliser - approche plus robuste
  let passwordInput;
  if (isResetPasswordPage) {
    // Sur la page de réinitialisation
    passwordInput = document.querySelector('input[type="password"][id$="_first"]');
  } else if (isAuthPage) {
    // Sur la page d'authentification - recherche plus robuste qui ne dépend pas de l'ID exact
    passwordInput = document.querySelector('input[name$="[password][first]"]');
  }

  // Définition des fonctions de navigation entre les sections (sur la page d'authentification)
  if (isAuthPage) {
    function showRegisterSection() {
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
      if (registerSection) registerSection.classList.add('hidden');
      if (loginSection) loginSection.classList.remove('hidden');
      document.title = "Digi-3 - Connexion";
      
      // Focus sur le premier champ du formulaire pour l'accessibilité
      setTimeout(() => {
        const firstInput = loginSection.querySelector('input:not([type="hidden"])');
        if (firstInput) firstInput.focus();
      }, 100);
    }

    // Vérifier s'il y a des erreurs ou des messages flash pour déterminer quelle section afficher
    if (formState && formState.dataset.hasErrors === 'true') {
      // Si l'URL contient 'app_register', c'est une erreur d'inscription
      if (window.location.href.includes('app_register')) {
        showRegisterSection();
      } else {
        // Vérifier si un message flash concerne l'inscription
        const flashMessages = document.querySelectorAll('.flash-message');
        let shouldShowRegister = false;
        
        flashMessages.forEach(message => {
          if (message.textContent.includes('inscription') || 
              message.textContent.includes('compte') || 
              message.textContent.includes('email est déjà utilisé')) {
            shouldShowRegister = true;
          }
        });
        
        if (shouldShowRegister) {
          showRegisterSection();
        } else {
          showLoginSection();
        }
      }
    } else {
      // Afficher la section de connexion par défaut
      showLoginSection();
    }
    
    // Gestion des événements de navigation
    if (switchToRegister) {
      switchToRegister.addEventListener("click", function (event) {
        event.preventDefault();
        showRegisterSection();
      });
    }

    if (switchToLogin) {
      switchToLogin.addEventListener("click", function (event) {
        event.preventDefault();
        showLoginSection();
      });
    }
  }

  // Toggle password visibility (sur toutes les pages d'auth)
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

  // Validation en temps réel du mot de passe (pour l'inscription et réinitialisation)
  if (passwordInput && passwordRequirements) {
    const requirements = [
      { id: 'length', regex: /.{8,}/ },
      { id: 'uppercase', regex: /[A-Z]/ },
      { id: 'lowercase', regex: /[a-z]/ },
      { id: 'number', regex: /[0-9]/ },
      { id: 'special', regex: /[^A-Za-z0-9]/ }
    ];
    
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
      
      // Sur la page de réinitialisation, mettre à jour la validité du bouton
      if (isResetPasswordPage) {
        const submitBtn = document.querySelector('.auth-submit');
        if (submitBtn) {
          submitBtn.disabled = !isValid && password.length > 0;
        }
      }
    });
    
    // Déclencher l'événement input pour initialiser les styles
    const event = new Event('input');
    passwordInput.dispatchEvent(event);
  }

  // Gestion du formulaire d'inscription
  if (registerForm) {
    registerForm.addEventListener("submit", function (event) {
      // Validation du mot de passe
      if (passwordInput) {
        const password = passwordInput.value;
        let isValid = true;
        
        const requirements = [
          { id: 'length', regex: /.{8,}/ },
          { id: 'uppercase', regex: /[A-Z]/ },
          { id: 'lowercase', regex: /[a-z]/ },
          { id: 'number', regex: /[0-9]/ },
          { id: 'special', regex: /[^A-Za-z0-9]/ }
        ];
        
        requirements.forEach(function(req) {
          if (!req.regex.test(password)) {
            isValid = false;
          }
        });
        
        if (!isValid) {
          // Empêcher la soumission si le mot de passe n'est pas valide
          event.preventDefault();
          
          // Afficher un message d'erreur
          const errorContainer = document.getElementById('registerErrors');
          if (errorContainer) {
            const existingError = errorContainer.querySelector('.error');
            if (existingError) {
              existingError.remove();
            }
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error';
            errorDiv.setAttribute('role', 'alert');
            errorDiv.textContent = 'Veuillez respecter toutes les exigences du mot de passe.';
            errorContainer.appendChild(errorDiv);
          }
          return;
        }
      }

      // Désactiver le bouton pendant la soumission
      const submitButton = this.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = 'Inscription en cours...';
      }
    });
  }
  
  /**
   * Initialise la gestion des messages flash
   */
  function initFlashMessages() {
    const flashMessages = document.querySelectorAll('.flash-message');
    if (flashMessages.length === 0) return;
    
    // Configurer le délai d'auto-fermeture des messages
    const autoDismissDelay = 6000; // 6 secondes
    
    // Ajouter les événements pour chaque message flash
    flashMessages.forEach(message => {
      // Auto-fermeture après délai
      const timeoutId = setTimeout(() => {
        dismissFlashMessage(message);
      }, autoDismissDelay);
      
      // Événement pour le bouton de fermeture
      const closeButton = message.querySelector('.flash-close');
      if (closeButton) {
        closeButton.addEventListener('click', () => {
          clearTimeout(timeoutId);
          dismissFlashMessage(message);
        });
      }
      
      // Arrêter le timer au survol
      message.addEventListener('mouseenter', () => {
        clearTimeout(timeoutId);
      });
      
      // Redémarrer le timer quand la souris quitte le message
      message.addEventListener('mouseleave', () => {
        const newTimeoutId = setTimeout(() => {
          dismissFlashMessage(message);
        }, autoDismissDelay);
      });
    });
  }
  
  /**
   * Ferme un message flash avec animation
   */
  function dismissFlashMessage(messageElement) {
    messageElement.classList.add('fade-out');
    
    messageElement.addEventListener('animationend', () => {
      messageElement.remove();
    });
  }
});
