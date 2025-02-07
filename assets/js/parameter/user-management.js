import { CrudManager } from './crud-manager';

const USER_SELECTORS = {
    modal: '#userModal',
    deleteConfirmModal: '#deleteConfirmModal',
    modalTitle: '#userModalTitle',
    form: '#userForm',
    saveBtn: '#saveUser',
    confirmDeleteBtn: '#confirmDelete',
    toDeleteName: '#userToDeleteName',
    btnAdd: '.btn-add',
    btnEdit: '.btn-edit',
    btnDelete: '.btn-delete',
    pageData: '#user-page-data'
};

export class UserManager extends CrudManager {
    constructor() {
        super({
            entityName: 'user',
            entityLabel: 'utilisateur',
            baseUrl: '/parameter/users',
            selectors: USER_SELECTORS
        });
    }

    extractDataFromButton(button) {
        return {
            id: button.dataset.userId,
            firstName: button.dataset.userFirstname,
            lastName: button.dataset.userLastname,
            email: button.dataset.userEmail,
            role: button.dataset.userRole
        };
    }

    getEntityName(button) {
        const row = button.closest('tr');
        return `${row.querySelector('td:nth-child(3)').textContent} ${row.querySelector('td:nth-child(2)').textContent}`;
    }

    fillFormWithData(data) {
        const form = document.querySelector(this.config.selectors.form);
        if (!form) return;

        form.querySelector('#firstName').value = data.firstName;
        form.querySelector('#lastName').value = data.lastName;
        form.querySelector('#email').value = data.email;
        form.querySelector('#role').value = data.role;
    }
}

// Initialisation
new UserManager(); 