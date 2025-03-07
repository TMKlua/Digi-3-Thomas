/**
 * Classe de gestion de l'authentification
 * Gère les formulaires de connexion, d'inscription et de réinitialisation de mot de passe
 */
export class AuthManager {
    constructor() {
        // Éléments DOM
        this.loginSection = document.getElementById("loginSection");
        this.registerSection = document.getElementById("registerSection");
        this.switchToLoginLink = document.getElementById("switchToLogin");
        this.switchToRegisterLink = document.getElementById("switchToRegister");
        this.forgotPasswordLink = document.getElementById("forgotPasswordLink");
        this.resetPasswordModal = document.getElementById("resetPasswordModal");
        this.closeModalBtn = this.resetPasswordModal?.querySelector(".close");
        this.resetPasswordForm = document.getElementById("resetPasswordForm");
        this.registerForm = document.getElementById("register_form");
        this.registerPasswordInput = document.getElementById("register_password");
        
        // Contraintes de validation du mot de passe
        this.passwordConstraints = {
            minLength: 8,
            requireSpecialChar: true,
            requireNumber: true,
            requireUppercase: true
        };
        
        this.init();
    }
    
    /**
     * Initialise les gestionnaires d'événements
     */
    init() {
        // Afficher la section de connexion par défaut
        if (this.loginSection) {
            this.loginSection.style.display = "flex";
        }

        // Gérer le changement entre connexion et inscription
        if (this.switchToLoginLink) {
            this.switchToLoginLink.addEventListener("click", this.showLoginSection.bind(this));
        }

        if (this.switchToRegisterLink) {
            this.switchToRegisterLink.addEventListener("click", this.showRegisterSection.bind(this));
        }

        // Gérer l'ouverture de la modale de réinitialisation
        if (this.forgotPasswordLink && this.resetPasswordModal) {
            this.forgotPasswordLink.addEventListener("click", this.showResetModal.bind(this));
        }

        // Gérer la fermeture de la modale
        if (this.closeModalBtn) {
            this.closeModalBtn.addEventListener("click", this.closeResetModal.bind(this));
        }

        // Gérer la soumission du formulaire de réinitialisation
        if (this.resetPasswordForm) {
            this.resetPasswordForm.addEventListener("submit", this.handleResetFormSubmit.bind(this));
        }
        
        // Validation du mot de passe en temps réel
        if (this.registerPasswordInput) {
            this.registerPasswordInput.addEventListener('input', this.validatePasswordLive.bind(this));
            
            // Créer l'indicateur de force du mot de passe
            this.createPasswordStrengthIndicator();
        }
        
        // Gérer la soumission du formulaire d'inscription
        if (this.registerForm) {
            this.registerForm.addEventListener('submit', this.handleRegisterFormSubmit.bind(this));
        }
        
        // Initialiser la fonction globale pour la visibilité du mot de passe
        window.togglePasswordVisibility = AuthManager.togglePasswordVisibility;
    }
    
    /**
     * Crée l'indicateur de force du mot de passe
     */
    createPasswordStrengthIndicator() {
        // Créer le conteneur de l'indicateur
        const strengthContainer = document.createElement('div');
        strengthContainer.className = 'password-strength-container';
        strengthContainer.style.marginTop = '5px';
        
        // Créer la barre de progression
        const strengthBar = document.createElement('div');
        strengthBar.className = 'password-strength-bar';
        strengthBar.style.height = '5px';
        strengthBar.style.backgroundColor = '#e0e0e0';
        strengthBar.style.borderRadius = '3px';
        strengthBar.style.overflow = 'hidden';
        strengthBar.style.marginBottom = '5px';
        
        // Créer l'indicateur de progression
        const strengthIndicator = document.createElement('div');
        strengthIndicator.className = 'password-strength-indicator';
        strengthIndicator.style.height = '100%';
        strengthIndicator.style.width = '0%';
        strengthIndicator.style.backgroundColor = '#ff4d4d';
        strengthIndicator.style.transition = 'width 0.3s, background-color 0.3s';
        
        // Créer le texte de l'indicateur
        const strengthText = document.createElement('div');
        strengthText.className = 'password-strength-text';
        strengthText.style.fontSize = '12px';
        strengthText.style.color = '#666';
        strengthText.setAttribute('aria-live', 'polite');
        
        // Assembler les éléments
        strengthBar.appendChild(strengthIndicator);
        strengthContainer.appendChild(strengthBar);
        strengthContainer.appendChild(strengthText);
        
        // Ajouter l'indicateur après l'input du mot de passe
        this.registerPasswordInput.parentNode.appendChild(strengthContainer);
        
        // Stocker les références pour une utilisation ultérieure
        this.strengthIndicator = strengthIndicator;
        this.strengthText = strengthText;
    }
    
    /**
     * Affiche la section de connexion
     * @param {Event} e - L'événement de clic
     */
    showLoginSection(e) {
        e.preventDefault();
        this.registerSection.style.display = "none";
        this.loginSection.style.display = "flex";
        document.getElementById("login_email").focus();
    }
    
    /**
     * Affiche la section d'inscription
     * @param {Event} e - L'événement de clic
     */
    showRegisterSection(e) {
        e.preventDefault();
        this.loginSection.style.display = "none";
        this.registerSection.style.display = "flex";
        document.getElementById("first_name").focus();
    }
    
    /**
     * Affiche la modale de réinitialisation de mot de passe
     * @param {Event} e - L'événement de clic
     */
    showResetModal(e) {
        e.preventDefault();
        this.resetPasswordModal.style.display = "block";
        this.resetPasswordModal.removeAttribute("hidden");
        document.getElementById("reset_email").focus();
        
        // Piéger le focus dans la modale
        const focusableElements = this.resetPasswordModal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        this.resetPasswordModal.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeResetModal();
            }
            
            if (e.key === 'Tab') {
                if (e.shiftKey && document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                } else if (!e.shiftKey && document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        });
    }
    
    /**
     * Ferme la modale de réinitialisation de mot de passe
     */
    closeResetModal() {
        if (this.resetPasswordModal) {
            this.resetPasswordModal.style.display = "none";
            this.resetPasswordModal.setAttribute("hidden", "true");
            this.forgotPasswordLink.focus();
        }
    }
    
    /**
     * Gère la soumission du formulaire de réinitialisation
     * @param {Event} e - L'événement de soumission
     */
    handleResetFormSubmit(e) {
        e.preventDefault();
        const email = document.getElementById("reset_email").value;
        
        // Ici, vous pouvez ajouter le code pour envoyer la demande de réinitialisation
        alert("Un lien de réinitialisation a été envoyé à " + email);
        
        this.closeResetModal();
    }
    
    /**
     * Valide le mot de passe en temps réel
     * @param {Event} e - L'événement input
     */
    validatePasswordLive(e) {
        const password = e.target.value;
        const errors = this.validatePassword(password);
        
        // Supprimer les erreurs existantes
        const existingErrors = this.registerForm.querySelectorAll('.password-error');
        existingErrors.forEach(error => error.remove());
        
        // Afficher les nouvelles erreurs s'il y en a
        if (errors.length > 0) {
            const errorContainer = document.createElement('div');
            errorContainer.className = 'password-error';
            errorContainer.setAttribute('role', 'alert');
            errorContainer.setAttribute('aria-live', 'polite');
            errorContainer.style.color = 'red';
            errorContainer.style.marginTop = '5px';
            errorContainer.innerHTML = errors.join('<br>');
            e.target.parentNode.appendChild(errorContainer);
        }
        
        // Mettre à jour l'indicateur de force du mot de passe
        this.updatePasswordStrength(password);
    }
    
    /**
     * Met à jour l'indicateur de force du mot de passe
     * @param {string} password - Le mot de passe à évaluer
     */
    updatePasswordStrength(password) {
        if (!this.strengthIndicator || !this.strengthText) return;
        
        // Calculer la force du mot de passe (0-100)
        const strength = this.calculatePasswordStrength(password);
        
        // Mettre à jour la largeur de l'indicateur
        this.strengthIndicator.style.width = `${strength}%`;
        
        // Mettre à jour la couleur de l'indicateur
        if (strength < 30) {
            this.strengthIndicator.style.backgroundColor = '#ff4d4d'; // Rouge
            this.strengthText.textContent = 'Mot de passe faible';
        } else if (strength < 60) {
            this.strengthIndicator.style.backgroundColor = '#ffa64d'; // Orange
            this.strengthText.textContent = 'Mot de passe moyen';
        } else if (strength < 80) {
            this.strengthIndicator.style.backgroundColor = '#ffff4d'; // Jaune
            this.strengthText.textContent = 'Mot de passe bon';
        } else {
            this.strengthIndicator.style.backgroundColor = '#4dff4d'; // Vert
            this.strengthText.textContent = 'Mot de passe fort';
        }
    }
    
    /**
     * Calcule la force du mot de passe (0-100)
     * @param {string} password - Le mot de passe à évaluer
     * @returns {number} - La force du mot de passe (0-100)
     */
    calculatePasswordStrength(password) {
        if (!password) return 0;
        
        let score = 0;
        
        // Longueur (jusqu'à 30 points)
        score += Math.min(30, password.length * 3);
        
        // Caractères spéciaux (20 points)
        if (/[^a-zA-Z\d]/.test(password)) {
            score += 20;
        }
        
        // Chiffres (20 points)
        if (/\d/.test(password)) {
            score += 20;
        }
        
        // Majuscules (20 points)
        if (/[A-Z]/.test(password)) {
            score += 20;
        }
        
        // Minuscules (10 points)
        if (/[a-z]/.test(password)) {
            score += 10;
        }
        
        return Math.min(100, score);
    }
    
    /**
     * Valide un mot de passe selon les contraintes définies
     * @param {string} password - Le mot de passe à valider
     * @returns {string[]} - Un tableau des erreurs trouvées
     */
    validatePassword(password) {
        const errors = [];
        
        if (password.length < this.passwordConstraints.minLength) {
            errors.push(`Le mot de passe doit contenir au moins ${this.passwordConstraints.minLength} caractères`);
        }
        
        if (this.passwordConstraints.requireSpecialChar && !/[^a-zA-Z\d]/.test(password)) {
            errors.push('Le mot de passe doit contenir au moins un caractère spécial');
        }
        
        if (this.passwordConstraints.requireNumber && !/\d/.test(password)) {
            errors.push('Le mot de passe doit contenir au moins un chiffre');
        }
        
        if (this.passwordConstraints.requireUppercase && !/[A-Z]/.test(password)) {
            errors.push('Le mot de passe doit contenir au moins une majuscule');
        }
        
        return errors;
    }
    
    /**
     * Gère la soumission du formulaire d'inscription
     * @param {Event} e - L'événement de soumission
     */
    handleRegisterFormSubmit(e) {
        e.preventDefault();
        
        const password = this.registerPasswordInput.value;
        const passwordErrors = this.validatePassword(password);
        
        // Supprimer les erreurs existantes
        const existingErrors = this.registerForm.querySelectorAll('.error');
        existingErrors.forEach(error => error.remove());
        
        // Vérifier si le mot de passe est valide
        if (passwordErrors.length > 0) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error';
            errorDiv.setAttribute('role', 'alert');
            errorDiv.style.color = 'red';
            errorDiv.textContent = passwordErrors.join(', ');
            this.registerForm.insertBefore(errorDiv, this.registerForm.firstChild);
            return;
        }
        
        // Désactiver le bouton de soumission pendant le traitement
        const submitButton = this.registerForm.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = 'Inscription en cours...';
        
        // Soumettre le formulaire
        this.registerForm.submit();
    }
    
    /**
     * Fonction pour afficher/masquer le mot de passe
     * @param {string} inputId - L'ID de l'élément input du mot de passe
     */
    static togglePasswordVisibility(inputId) {
        const passwordInput = document.getElementById(inputId);
        if (!passwordInput) return;
        
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Annoncer le changement pour les lecteurs d'écran
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.classList.add('sr-only');
        announcement.textContent = type === 'text' ? 'Mot de passe visible' : 'Mot de passe masqué';
        document.body.appendChild(announcement);
        
        setTimeout(() => {
            document.body.removeChild(announcement);
        }, 1000);
    }
}

// Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', () => {
    // Vérifier si nous sommes sur la page d'authentification
    if (document.getElementById('loginSection') || document.getElementById('registerSection')) {
        new AuthManager();
    }
}); 