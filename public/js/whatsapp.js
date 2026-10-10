(function (window, document, $) {
    'use strict';

    const alertBox = document.getElementById('whatsapp-alert');
    const customerSelect = document.getElementById('customer-select');
    const customerContext = document.getElementById('customer-context');
    const phoneInput = document.getElementById('whatsapp-to');
    const messageForm = document.getElementById('whatsapp-message-form');
    const templateForm = document.getElementById('whatsapp-template-form');
    const workspace = document.querySelector('.whatsapp-workspace');

    let customers = [];

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

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function setFormErrors(form, message) {
        const errorBox = form?.querySelector('.form-errors');

        if (!errorBox) {
            return;
        }

        errorBox.classList.remove('d-none');
        errorBox.textContent = message;
    }

    function clearFormErrors(form) {
        const errorBox = form?.querySelector('.form-errors');

        if (!errorBox) {
            return;
        }

        errorBox.classList.add('d-none');
        errorBox.textContent = '';
    }

    function showRequestError(xhr, form) {
        const body = xhr.responseJSON || {};
        const errors = Object.values(body.errors || {}).flat();
        const message = errors.length
            ? errors.join(' ')
            : body.message || 'Request failed. Please try again.';

        setFormErrors(form, message);
    }

    function setButtonState(button, isSending, defaultText) {
        if (!button) {
            return;
        }

        button.disabled = isSending;
        button.textContent = isSending ? 'Sending...' : defaultText;
    }

    function selectedPhone() {
        return phoneInput?.value.trim() || '';
    }

    function renderCustomerOptions() {
        if (!customerSelect) {
            return;
        }

        if (!customers.length) {
            customerSelect.innerHTML = '<option value="">No customers found</option>';
            return;
        }

        customerSelect.innerHTML = [
            '<option value="">Select a customer...</option>',
            ...customers.map((customer) => `
                <option value="${escapeHtml(customer.id)}">
                    ${escapeHtml(customer.name || customer.phone || customer.id)}
                </option>
            `),
        ].join('');
    }

    function showCustomerContext(customer) {
        if (!customerContext) {
            return;
        }

        if (!customer) {
            customerContext.classList.add('d-none');
            return;
        }

        customerContext.querySelector('[data-field="name"]').textContent = customer.name || 'Unnamed customer';
        customerContext.querySelector('[data-field="email"]').textContent = customer.email || 'No email saved';
        customerContext.classList.remove('d-none');
    }

    function loadCustomers() {
        if (!customerSelect) {
            return;
        }

        $.ajax({
            url: workspace.dataset.customersUrl,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                customers = response.data || [];
                renderCustomerOptions();
            },
            error: function (xhr) {
                customerSelect.innerHTML = '<option value="">Customers unavailable</option>';
                const body = xhr.responseJSON || {};
                showAlert(body.message || 'Customers could not be loaded.', 'danger');
            },
        });
    }

    function handleCustomerChange() {
        const customer = customers.find((item) => item.id === customerSelect.value);

        if (phoneInput && customer?.phone) {
            phoneInput.value = customer.phone;
        }

        showCustomerContext(customer);
    }

    function submitMessage(event) {
        event.preventDefault();
        hideAlert();
        clearFormErrors(messageForm);

        const button = document.getElementById('whatsapp-send-message');
        setButtonState(button, true, 'Send Message');

        $.ajax({
            url: workspace.dataset.whatsappSendUrl,
            type: 'POST',
            data: JSON.stringify({
                to: selectedPhone(),
                message: messageForm.elements.message.value.trim(),
            }),
            contentType: 'application/json; charset=UTF-8',
            dataType: 'json',
            success: function (response) {
                showAlert(response.message);
                messageForm.reset();
            },
            error: function (xhr) {
                showRequestError(xhr, messageForm);
            },
            complete: function () {
                setButtonState(button, false, 'Send Message');
            },
        });
    }

    function templateComponents() {
        const raw = templateForm.elements.components.value.trim();

        if (!raw) {
            return [];
        }

        const parsed = JSON.parse(raw);

        if (!Array.isArray(parsed)) {
            throw new Error('Components JSON must be an array.');
        }

        return parsed;
    }

    function submitTemplate(event) {
        event.preventDefault();
        hideAlert();
        clearFormErrors(templateForm);

        let components;

        try {
            components = templateComponents();
        } catch (error) {
            setFormErrors(templateForm, error.message);
            return;
        }

        const button = document.getElementById('whatsapp-send-template');
        setButtonState(button, true, 'Send Template');

        $.ajax({
            url: workspace.dataset.whatsappTemplateUrl,
            type: 'POST',
            data: JSON.stringify({
                to: selectedPhone(),
                template_name: templateForm.elements.template_name.value.trim(),
                language_code: templateForm.elements.language_code.value.trim() || 'en_US',
                components,
            }),
            contentType: 'application/json; charset=UTF-8',
            dataType: 'json',
            success: function (response) {
                showAlert(response.message);
            },
            error: function (xhr) {
                showRequestError(xhr, templateForm);
            },
            complete: function () {
                setButtonState(button, false, 'Send Template');
            },
        });
    }

    $(customerSelect).on('change', handleCustomerChange);
    $(messageForm).on('submit', submitMessage);
    $(templateForm).on('submit', submitTemplate);

    loadCustomers();
})(window, document, window.jQuery);
