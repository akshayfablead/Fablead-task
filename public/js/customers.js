(function (window, document) {
    'use strict';

    const apiBase = '/api/customers';
    const alertBox = document.getElementById('customer-alert');
    const tableBody = document.getElementById('customers-table-body');
    const form = document.getElementById('customer-form');
    const details = document.getElementById('customer-details');
    const workspace = document.querySelector('[data-customer-id]');
    const customerId = workspace?.dataset.customerId || null;

    function showAlert(message, type = 'success') {
        if (!alertBox) {
            return;
        }

        alertBox.className = `alert alert-${type}`;
        alertBox.textContent = message;
    }

    function hideAlert() {
        if (!alertBox) {
            return;
        }

        alertBox.className = 'alert d-none';
        alertBox.textContent = '';
    }

    function formatDate(value) {
        if (!value) {
            return '-';
        }

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleString();
    }

    function customerUrl(id) {
        return `${apiBase}/${encodeURIComponent(id)}`;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function formData() {
        return Object.fromEntries(new FormData(form).entries());
    }

    function showFormErrors(error) {
        const errorBox = form?.querySelector('.form-errors');

        if (!errorBox) {
            return;
        }

        const errors = Object.values(error.body?.errors || {}).flat();

        errorBox.classList.remove('d-none');
        errorBox.textContent = errors.length
            ? errors.join(' ')
            : error.message;
    }

    function setSaving(isSaving) {
        const button = document.getElementById('customer-save');

        if (!button) {
            return;
        }

        button.disabled = isSaving;
        button.textContent = isSaving ? 'Saving...' : (
            form?.dataset.mode === 'edit'
                ? 'Save Changes'
                : 'Add Customer'
        );
    }

    async function loadCustomers() {
        if (!tableBody) {
            return;
        }

        try {
            const response = await window.crmApi.get(apiBase);
            const customers = response.data || [];

            if (!customers.length) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted-strong py-4">
                            No customers found.
                        </td>
                    </tr>
                `;

                return;
            }

            tableBody.innerHTML = customers.map((customer) => `
                <tr>
                    <td>
                        <div class="fw-semibold">${escapeHtml(customer.name)}</div>
                        <div class="small text-muted-strong">${escapeHtml(customer.id)}</div>
                    </td>
                    <td>${escapeHtml(customer.email)}</td>
                    <td>${escapeHtml(customer.phone)}</td>
                    <td>${escapeHtml(formatDate(customer.created_at))}</td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="/customers/${encodeURIComponent(customer.id)}">
                            View
                        </a>
                        <a class="btn btn-sm btn-outline-primary" href="/customers/${encodeURIComponent(customer.id)}/edit">
                            Edit
                        </a>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-danger customer-delete"
                            data-id="${escapeHtml(customer.id)}"
                        >
                            Delete
                        </button>
                    </td>
                </tr>
            `).join('');
        } catch (error) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-danger py-4">
                        Customers could not be loaded.
                    </td>
                </tr>
            `;
            showAlert(error.message, 'danger');
        }
    }

    async function loadCustomer() {
        if (!customerId || (!details && !form)) {
            return;
        }

        try {
            const response = await window.crmApi.get(customerUrl(customerId));
            const customer = response.data;

            if (details) {
                details.querySelector('[data-field="name"]').textContent = customer.name || '-';
                details.querySelector('[data-field="email"]').textContent = customer.email || '-';
                details.querySelector('[data-field="phone"]').textContent = customer.phone || '-';
                details.querySelector('[data-field="created_at"]').textContent = formatDate(customer.created_at);
                details.querySelector('[data-field="updated_at"]').textContent = formatDate(customer.updated_at);
            }

            if (form) {
                form.elements.name.value = customer.name || '';
                form.elements.email.value = customer.email || '';
                form.elements.phone.value = customer.phone || '';
            }
        } catch (error) {
            showAlert(error.message, 'danger');
        }
    }

    async function submitCustomer(event) {
        event.preventDefault();
        hideAlert();
        form.querySelector('.form-errors')?.classList.add('d-none');
        setSaving(true);

        try {
            const isEdit = form.dataset.mode === 'edit';
            const response = isEdit
                ? await window.crmApi.put(customerUrl(customerId), formData())
                : await window.crmApi.post(apiBase, formData());

            showAlert(response.message);

            if (!isEdit) {
                form.reset();
            }
        } catch (error) {
            if (error.status === 422) {
                showFormErrors(error);
            } else {
                showAlert(error.message, 'danger');
            }
        } finally {
            setSaving(false);
        }
    }

    async function deleteCustomer(button) {
        const id = button.dataset.id;

        if (!id || !window.confirm('Delete this customer permanently?')) {
            return;
        }

        button.disabled = true;
        button.textContent = 'Deleting...';
        hideAlert();

        try {
            const response = await window.crmApi.delete(customerUrl(id));
            showAlert(response.message);
            await loadCustomers();
        } catch (error) {
            showAlert(error.message, 'danger');
            button.disabled = false;
            button.textContent = 'Delete';
        }
    }

    document.addEventListener('click', function (event) {
        const button = event.target.closest('.customer-delete');

        if (button) {
            deleteCustomer(button);
        }
    });

    if (form) {
        form.addEventListener('submit', submitCustomer);
    }

    loadCustomers();
    loadCustomer();
})(window, document);
