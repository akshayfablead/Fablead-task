(function (window, document, $) {
    'use strict';

    const alertBox = document.getElementById('customer-alert');
    const tableBody = document.getElementById('customers-table-body');
    const form = document.getElementById('customer-form');
    const details = document.getElementById('customer-details');
    const workspace = document.querySelector('.customer-workspace');
    const customerId = workspace?.dataset.customerId || null;
    const routes = {
        index: workspace?.dataset.customerIndexUrl,
        store: workspace?.dataset.customerStoreUrl,
        show: workspace?.dataset.customerShowUrl,
        update: workspace?.dataset.customerUpdateUrl,
        delete: workspace?.dataset.customerDeleteUrl,
    };

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
        return routes.show.replace('__CUSTOMER_ID__', encodeURIComponent(id));
    }

    function routeForCustomer(route, id) {
        return route.replace('__CUSTOMER_ID__', encodeURIComponent(id));
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

    function showRequestError(xhr, targetForm = null) {
        const body = xhr.responseJSON || {};
        const errors = Object.values(body.errors || {}).flat();
        const message = errors.length
            ? errors.join(' ')
            : body.message || 'Request failed. Please try again.';

        if (targetForm && xhr.status === 422) {
            const errorBox = targetForm.querySelector('.form-errors');

            if (errorBox) {
                errorBox.classList.remove('d-none');
                errorBox.textContent = message;
                return;
            }
        }

        showAlert(message, 'danger');
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

    function renderCustomers(customers) {
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
    }

    function loadCustomers() {
        if (!tableBody) {
            return;
        }

        $.ajax({
            url: routes.index,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                renderCustomers(response.data || []);
            },
            error: function (xhr) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-danger py-4">
                            Customers could not be loaded.
                        </td>
                    </tr>
                `;
                showRequestError(xhr);
            },
        });
    }

    function loadCustomer() {
        if (!customerId || (!details && !form)) {
            return;
        }

        $.ajax({
            url: customerUrl(customerId),
            type: 'GET',
            dataType: 'json',
            success: function (response) {
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
            },
            error: function (xhr) {
                showRequestError(xhr);
            },
        });
    }

    function submitCustomer(event) {
        event.preventDefault();
        hideAlert();
        form.querySelector('.form-errors')?.classList.add('d-none');
        setSaving(true);

        const isEdit = form.dataset.mode === 'edit';

        $.ajax({
            url: isEdit
                ? routeForCustomer(routes.update, customerId)
                : routes.store,
            type: isEdit ? 'PUT' : 'POST',
            data: JSON.stringify(formData()),
            contentType: 'application/json; charset=UTF-8',
            dataType: 'json',
            success: function (response) {
                showAlert(response.message);

                if (!isEdit) {
                    form.reset();
                }
            },
            error: function (xhr) {
                showRequestError(xhr, form);
            },
            complete: function () {
                setSaving(false);
            },
        });
    }

    function deleteCustomer(button) {
        const id = button.dataset.id;

        if (!id || !window.confirm('Delete this customer permanently?')) {
            return;
        }

        button.disabled = true;
        button.textContent = 'Deleting...';
        hideAlert();

        $.ajax({
            url: routeForCustomer(routes.delete, id),
            type: 'DELETE',
            dataType: 'json',
            success: function (response) {
                showAlert(response.message);
                loadCustomers();
            },
            error: function (xhr) {
                showRequestError(xhr);
                button.disabled = false;
                button.textContent = 'Delete';
            },
        });
    }

    $(document).on('click', '.customer-delete', function () {
        deleteCustomer(this);
    });

    if (form) {
        $(form).on('submit', submitCustomer);
    }

    loadCustomers();
    loadCustomer();
})(window, document, window.jQuery);
