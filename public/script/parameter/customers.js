document.addEventListener('DOMContentLoaded', function() {
    // Add Customer
    document.getElementById('saveNewCustomer').addEventListener('click', function() {
        const formData = new FormData(document.getElementById('addCustomerForm'));
        
        fetch("{{ path('app_parameter_customer_add') }}", {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de l\'ajout du client');
        });
    });

    // Delete Customer
    let customerToDelete = null;
    
    document.querySelectorAll('.delete-customer').forEach(button => {
        button.addEventListener('click', function() {
            customerToDelete = this.dataset.customerId;
        });
    });

    document.getElementById('confirmDelete').addEventListener('click', function() {
        if (customerToDelete) {
            fetch(`{{ path('app_parameter_customer_delete', {'id': '__id__'}) }}`.replace('__id__', customerToDelete), {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Une erreur est survenue lors de la suppression');
            });
        }
    });
});
