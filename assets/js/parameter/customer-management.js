import { CrudManager } from './crud-manager';

const CUSTOMER_SELECTORS = {
    modal: '#customerModal',
    deleteConfirmModal: '#deleteConfirmModal',
    modalTitle: '#customerModalTitle',
    form: '#customerForm',
    saveBtn: '#saveCustomer',
    confirmDeleteBtn: '#confirmDelete',
    toDeleteName: '#customerToDeleteName',
    btnAdd: '.btn-add',
    btnEdit: '.btn-edit',
    btnDelete: '.btn-delete',
    pageData: '#customer-page-data'
};

export class CustomerManager extends CrudManager {
    constructor() {
        super({
            entityName: 'customer',
            entityLabel: 'client',
            baseUrl: '/parameter/customers',
            selectors: CUSTOMER_SELECTORS
        });
    }

    extractDataFromButton(button) {
        return {
            id: button.dataset.customerId,
            name: button.dataset.customerName,
            street: button.dataset.customerStreet,
            zipcode: button.dataset.customerZipcode,
            city: button.dataset.customerCity,
            country: button.dataset.customerCountry,
            vat: button.dataset.customerVat,
            siren: button.dataset.customerSiren
        };
    }

    getEntityName(button) {
        return button.closest('tr').querySelector('td:first-child').textContent;
    }

    fillFormWithData(data) {
        const form = document.querySelector(this.config.selectors.form);
        if (!form) return;

        form.querySelector('#customerName').value = data.name;
        form.querySelector('#customerAddressStreet').value = data.street;
        form.querySelector('#customerAddressZipcode').value = data.zipcode;
        form.querySelector('#customerAddressCity').value = data.city;
        form.querySelector('#customerAddressCountry').value = data.country;
        form.querySelector('#customerVAT').value = data.vat || '';
        form.querySelector('#customerSIREN').value = data.siren || '';
    }
}

// Initialisation
new CustomerManager(); 