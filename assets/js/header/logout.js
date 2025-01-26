export class LogoutManager {
    constructor() {
        this.logoutDialog = document.getElementById('logout-dialog');
        this.logoutForm = document.querySelector('.logout-form');
        this.init();
    }

    init() {
        // Fermeture avec la touche Echap
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this.logoutDialog.style.display === 'block') {
                this.closeLogoutDialog();
            }
        });

        // Initialisation des fonctions globales
        window.showLogoutDialog = this.showLogoutDialog.bind(this);
        window.closeLogoutDialog = this.closeLogoutDialog.bind(this);
        window.confirmLogout = this.confirmLogout.bind(this);
    }

    showLogoutDialog(event) {
        event.preventDefault();
        this.logoutDialog.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Empêche le scroll
        return false;
    }

    closeLogoutDialog() {
        this.logoutDialog.style.display = 'none';
        document.body.style.overflow = ''; // Réactive le scroll
    }

    confirmLogout() {
        const btnConfirm = document.querySelector('.logout-dialog-buttons button:last-child');
        btnConfirm.disabled = true;
        btnConfirm.textContent = 'Déconnexion...';
        
        setTimeout(() => {
            this.logoutForm.submit();
        }, 500);
    }
}

// Initialisation au chargement du DOM
document.addEventListener('DOMContentLoaded', () => {
    new LogoutManager();
}); 