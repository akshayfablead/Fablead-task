(function (window, document) {
    'use strict';

    const directEndpoint = '/api/whatsapp/send';
    const templateEndpoint = '/api/whatsapp/send-template';
    const customersEndpoint = '/api/customers';

    const alertBox = document.getElementById('whatsapp-alert');
    const customerSelect = document.getElementById('customer-select');
    const customerContext = document.getElementById('customer-context');
    const phoneInput = document.getElementById('whatsapp-to');
    const messageForm = document.getElementById('whatsapp-message-form');
    const templateForm = document.getElementById('whatsapp-template-form');

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

    function validationMessage(error) {
        const errors = Object.values(error.body?.errors || {}).flat();

        return errors.length ? errors.join(' ') : error.message;
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

    async function loadCustomers() {
        if (!customerSelect) {
            return;
        }

        try {
            const response = await window.crmApi.get(customersEndpoint);
            customers = response.data || [];
            renderCustomerOptions();
        } catch (error) {
            customerSelect.innerHTML = '<option value="">Customers unavailable</option>';
            showAlert(error.message, 'danger');
        }
    }

    function handleCustomerChange() {
        const customer = customers.find((item) => item.id === customerSelect.value);

        if (phoneInput && customer?.phone) {
            phoneInput.value = customer.phone;
        }

        showCustomerContext(customer);
    }

    async function submitMessage(event) {
        event.preventDefault();
        hideAlert();
        clearFormErrors(messageForm);

        const button = document.getElementById('whatsapp-send-message');
        setButtonState(button, true, 'Send Message');

        try {
            const response = await window.crmApi.post(directEndpoint, {
                to: selectedPhone(),
                message: messageForm.elements.message.value.trim(),
            });

            showAlert(response.message);
            messageForm.reset();
        } catch (error) {
            setFormErrors(messageForm, validationMessage(error));
        } finally {
            setButtonState(button, false, 'Send Message');
        }
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

    async function submitTemplate(event) {
        event.preventDefault();
        hideAlert();
        clearFormErrors(templateForm);

        const button = document.getElementById('whatsapp-send-template');
        setButtonState(button, true, 'Send Template');

        try {
            const response = await window.crmApi.post(templateEndpoint, {
                to: selectedPhone(),
                template_name: templateForm.elements.template_name.value.trim(),
                language_code: templateForm.elements.language_code.value.trim() || 'en_US',
                components: templateComponents(),
            });

            showAlert(response.message);
        } catch (error) {
            setFormErrors(templateForm, validationMessage(error));
        } finally {
            setButtonState(button, false, 'Send Template');
        }
    }

    customerSelect?.addEventListener('change', handleCustomerChange);
    messageForm?.addEventListener('submit', submitMessage);
    templateForm?.addEventListener('submit', submitTemplate);

    loadCustomers();
})(window, document);
