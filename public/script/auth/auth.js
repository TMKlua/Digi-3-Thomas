
document.addEventListener("DOMContentLoaded", function () {
const forgotPasswordLink = document.getElementById("forgotPasswordLink");
const resetPasswordModal = document.getElementById("resetPasswordModal");
const closeModal = document.querySelector(".modal .close");
const resetPasswordForm = document.getElementById("resetPasswordForm");
const resetPasswordResponse = document.getElementById("resetPasswordResponse");

// Afficher la modale
function showModal() {
    const modal = document.querySelector('.modal');
    modal.style.display = 'flex'; // Affiche la modale avec display: flex pour la centrer
}

// Masquer la modale
function hideModal() {
    const modal = document.querySelector('.modal');
    modal.style.display = 'none'; // Cache la modale
}

// Fonction pour fermer la modale
function closeModalFunc() {
    resetPasswordModal.style.display = "none";
    resetPasswordResponse.style.display = "none";
    resetPasswordResponse.innerHTML = "";
}

// Afficher la modale quand on clique sur "Mot de passe oublié"
forgotPasswordLink.addEventListener("click", function (event) {
    event.preventDefault();
    showModal();
});

// Fermer la modale quand on clique sur la croix
closeModal.addEventListener("click", closeModalFunc);

// Fermer la modale si on clique en dehors de son contenu
window.addEventListener("click", function (event) {
    if (event.target === resetPasswordModal) {
        closeModalFunc();
    }
});

// Empêcher la fermeture de la modale quand on clique dedans
resetPasswordModal.querySelector(".modal-content").addEventListener("click", function(event) {
    event.stopPropagation();
});

// Intercepter la soumission du formulaire de réinitialisation
resetPasswordForm.addEventListener("submit", function(event) {
    event.preventDefault();
    
    const formData = new FormData(resetPasswordForm);
    
    fetch(resetPasswordForm.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        resetPasswordResponse.style.display = "block";
        if (data.success) {
            resetPasswordResponse.innerHTML = `<div style="color: green;">${data.message}</div>`;
            resetPasswordForm.reset();
        } else {
            resetPasswordResponse.innerHTML = `<div style="color: red;">${data.message}</div>`;
        }
    })
    .catch(error => {
        resetPasswordResponse.style.display = "block";
        resetPasswordResponse.innerHTML = `<div style="color: red;">Une erreur s'est produite. Veuillez réessayer.</div>`;
        console.error('Error:', error);
    });
});

// Fonction pour basculer la visibilité du mot de passe
window.togglePasswordVisibility = function(inputId) {
    const passwordInput = document.getElementById(inputId);
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
    } else {
        passwordInput.type = "password";
    }
};
});

document.addEventListener("DOMContentLoaded", function () {
// Éléments DOM
const loginSection = document.getElementById("loginSection");
const registerSection = document.getElementById("registerSection");
const switchToRegister = document.getElementById("switchToRegister");
const switchToLogin = document.getElementById("switchToLogin");
const registerForm = document.getElementById("register_form");
const passwordInput = document.getElementById("register_password");
const passwordStrengthBar = document.getElementById("passwordStrengthBar");
const passwordStrength = document.getElementById("passwordStrength");
const passwordErrorContainer = document.getElementById("passwordErrorContainer");

// Fonction pour basculer la visibilité du mot de passe
window.togglePasswordVisibility = function(inputId) {
    const passwordInput = document.getElementById(inputId);
    const icon = passwordInput.parentElement.querySelector('.toggle-password img');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.src = '/img/icons/eye-off.png'; // Change l'icône si tu en as une différente
    } else {
        passwordInput.type = 'password';
        icon.src = '/img/icons/eye.png';
    }
}

function validatePassword(password) {
    const constraints = {
        minLength: 8,
        requireSpecialChar: true,
        requireNumber: true,
        requireUppercase: true
    };

    const errors = [];

    if (password.length < constraints.minLength) {
        errors.push('Le mot de passe doit contenir au moins 8 caractères');
    }

    if (constraints.requireSpecialChar && !/[^a-zA-Z\d]/.test(password)) {
        errors.push('Le mot de passe doit contenir au moins un caractère spécial');
    }

    if (constraints.requireNumber && !/\d/.test(password)) {
        errors.push('Le mot de passe doit contenir au moins un chiffre');
    }

    if (constraints.requireUppercase && !/[A-Z]/.test(password)) {
        errors.push('Le mot de passe doit contenir au moins une majuscule');
    }

    return errors;
}

function checkPassword() {
    const password = document.getElementById("password").value;
    const errors = validatePassword(password);
    const errorContainer = document.getElementById("passwordErrors");
    
    // Affichage des erreurs
    if (errors.length > 0) {
        errorContainer.innerHTML = errors.join('<br>');
    } else {
        errorContainer.innerHTML = '';
    }
}

// Fonction pour évaluer la force du mot de passe
function updatePasswordStrength(password) {
    let strength = 0;

    if (password.length >= 8) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;

    const width = (strength / 4) * 100;
    passwordStrength.style.width = width + "%";

    // Ajout de la transition de couleur directement dans le JavaScript
    passwordStrength.style.transition = "background-color 0.5s ease";  // Transition de couleur fluide

    // Logique pour changer la couleur de la barre en fonction de la force du mot de passe
    if (strength === 0) {
        passwordStrength.style.backgroundColor = "red";
    } else if (strength === 1) {
        passwordStrength.style.backgroundColor = "orange";
    } else if (strength === 2) {
        passwordStrength.style.backgroundColor = "yellow";
    } else {
        passwordStrength.style.backgroundColor = "green";
    }
    
    // Afficher la barre avec un fondu
    passwordStrengthBar.style.opacity = (password.length > 0) ? 1 : 0;
}

// Validation en temps réel du mot de passe
passwordInput.addEventListener('input', function() {
    const password = this.value;
    const errors = validatePassword(password);

    // Afficher la force du mot de passe
    updatePasswordStrength(password);

    // Supprimer les messages d'erreur existants
    passwordErrorContainer.innerHTML = "";

    if (errors.length > 0) {
        const errorContainer = document.createElement('div');
        errorContainer.className = 'password-error';
        errorContainer.style.color = 'red';
        errorContainer.style.marginTop = '5px';
        errorContainer.innerHTML = errors.join('<br>');
        passwordErrorContainer.appendChild(errorContainer);
    }
});

// Gestion du formulaire d'inscription
registerForm.addEventListener("submit", function (event) {
    event.preventDefault();
    const password = passwordInput.value;
    const passwordErrors = validatePassword(password);

    if (passwordErrors.length > 0) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error';
        errorDiv.style.color = 'red';
        errorDiv.textContent = passwordErrors.join(', ');
        this.insertBefore(errorDiv, this.firstChild);
        return;
    }

    const submitButton = this.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.innerHTML = 'Inscription en cours...';

    const formData = new FormData(this);

    fetch("{{ path('app_register') }}", {
        method: 'POST',
        body: formData
    })    
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            submitButton.innerHTML = 'Inscription réussie !';
            submitButton.style.backgroundColor = '#4CAF50';
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1000);
        } else {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error';
            errorDiv.style.color = 'red';
            errorDiv.textContent = data.errors;
            this.insertBefore(errorDiv, this.firstChild);
        }
    })
    .catch(error => {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error';
        errorDiv.style.color = 'red';
        errorDiv.textContent = 'Une erreur est survenue. Veuillez réessayer.';
        this.insertBefore(errorDiv, this.firstChild);
    });
});

// Gestion des sections login/register
switchToRegister.addEventListener("click", function (e) {
    e.preventDefault();
    loginSection.style.display = "none";
    registerSection.style.display = "flex";
});

switchToLogin.addEventListener("click", function (e) {
    e.preventDefault();
    registerSection.style.display = "none";
    loginSection.style.display = "flex";
});
});

