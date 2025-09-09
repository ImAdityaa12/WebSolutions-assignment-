/**
 * Form Validation JavaScript
 * Handles client-side form validation with real-time feedback
 */

class FormValidator {
    constructor(formId) {
        this.form = document.getElementById(formId);
        this.errors = {};
        this.isSubmitting = false;

        if (this.form) {
            this.init();
        }
    }

    /**
     * Initialize form validation
     */
    init() {
        this.setupEventListeners();
        this.setupValidationRules();
    }

    /**
     * Set up event listeners for form validation
     */
    setupEventListeners() {
        // Form submission
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));

        // Real-time validation on input
        const inputs = this.form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            // Validate on blur (when user leaves field)
            input.addEventListener('blur', () => this.validateField(input));

            // Clear errors on input (when user starts typing)
            input.addEventListener('input', () => this.clearFieldError(input));

            // Special handling for email field
            if (input.type === 'email') {
                input.addEventListener('input', this.debounce(() => this.validateField(input), 500));
            }
        });
    }

    /**
     * Set up validation rules for different field types
     */
    setupValidationRules() {
        this.rules = {
            firstName: {
                required: true,
                minLength: 2,
                maxLength: 50,
                pattern: /^[a-zA-Z\s'-]+$/,
                message: 'First name must be 2-50 characters and contain only letters, spaces, hyphens, and apostrophes'
            },
            lastName: {
                required: true,
                minLength: 2,
                maxLength: 50,
                pattern: /^[a-zA-Z\s'-]+$/,
                message: 'Last name must be 2-50 characters and contain only letters, spaces, hyphens, and apostrophes'
            },
            email: {
                required: true,
                pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                message: 'Please enter a valid email address'
            },
            phone: {
                required: false,
                pattern: /^[\+]?[1-9][\d]{0,15}$/,
                message: 'Please enter a valid phone number'
            },
            subject: {
                required: true,
                message: 'Please select a subject'
            },
            message: {
                required: true,
                minLength: 10,
                maxLength: 1000,
                message: 'Message must be between 10 and 1000 characters'
            }
        };
    }

    /**
     * Handle form submission
     */
    async handleSubmit(e) {
        e.preventDefault();

        if (this.isSubmitting) {
            return;
        }

        // Validate all fields
        const isValid = this.validateAllFields();

        if (!isValid) {
            this.showFormMessage('Please correct the errors below.', 'error');
            this.focusFirstError();
            return;
        }

        // Show loading state
        this.setSubmittingState(true);

        try {
            // Submit form data
            const formData = new FormData(this.form);
            const response = await this.submitForm(formData);

            if (response.success) {
                this.showFormMessage(response.message || 'Thank you! Your message has been sent successfully.', 'success');
                this.resetForm();
            } else {
                this.showFormMessage(response.message || 'There was an error sending your message. Please try again.', 'error');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.showFormMessage('There was an error sending your message. Please try again later.', 'error');
        } finally {
            this.setSubmittingState(false);
        }
    }

    /**
     * Submit form data to server
     */
    async submitForm(formData) {
        // Check if we have a PHP server available
        try {
            const response = await fetch('php/contact-handler.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            // If PHP server is not available, use mock handler
            console.warn('PHP server not available, using mock handler:', error.message);

            // Simulate form processing
            const mockResponse = await this.mockSubmitForm(formData);
            return mockResponse;
        }
    }

    /**
     * Mock form submission for testing without PHP
     */
    async mockSubmitForm(formData) {
        // Simulate network delay
        await new Promise(resolve => setTimeout(resolve, 1000));

        // Extract form data
        const data = {};
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }

        // Simple validation
        const errors = {};
        const required = ['firstName', 'lastName', 'email', 'subject', 'message'];

        required.forEach(field => {
            if (!data[field] || data[field].trim() === '') {
                errors[field] = `${field} is required`;
            }
        });

        // Email validation
        if (data.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
            errors.email = 'Please enter a valid email address';
        }

        // Return mock response
        if (Object.keys(errors).length === 0) {
            return {
                success: true,
                message: 'Thank you! Your message has been received. (Mock response - install XAMPP for real email sending)',
                timestamp: new Date().toISOString()
            };
        } else {
            return {
                success: false,
                message: 'Please correct the errors in your form.',
                errors: errors,
                timestamp: new Date().toISOString()
            };
        }
    }

    /**
     * Validate all form fields
     */
    validateAllFields() {
        let isValid = true;
        this.errors = {};

        const inputs = this.form.querySelectorAll('input[required], select[required], textarea[required]');
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        // Also validate non-required fields that have values
        const optionalInputs = this.form.querySelectorAll('input:not([required]), select:not([required]), textarea:not([required])');
        optionalInputs.forEach(input => {
            if (input.value.trim()) {
                this.validateField(input);
            }
        });

        return isValid;
    }

    /**
     * Validate a single field
     */
    validateField(field) {
        const fieldName = field.name;
        const value = field.value.trim();
        const rules = this.rules[fieldName];

        if (!rules) {
            return true; // No rules defined for this field
        }

        // Clear previous errors
        delete this.errors[fieldName];

        // Required field validation
        if (rules.required && !value) {
            this.setFieldError(field, `${this.getFieldLabel(field)} is required`);
            return false;
        }

        // Skip other validations if field is empty and not required
        if (!value && !rules.required) {
            this.clearFieldError(field);
            return true;
        }

        // Length validations
        if (rules.minLength && value.length < rules.minLength) {
            this.setFieldError(field, `${this.getFieldLabel(field)} must be at least ${rules.minLength} characters`);
            return false;
        }

        if (rules.maxLength && value.length > rules.maxLength) {
            this.setFieldError(field, `${this.getFieldLabel(field)} must be no more than ${rules.maxLength} characters`);
            return false;
        }

        // Pattern validation
        if (rules.pattern && !rules.pattern.test(value)) {
            this.setFieldError(field, rules.message);
            return false;
        }

        // Special validation for email
        if (fieldName === 'email') {
            if (!this.isValidEmail(value)) {
                this.setFieldError(field, 'Please enter a valid email address');
                return false;
            }
        }

        // Special validation for phone
        if (fieldName === 'phone' && value) {
            if (!this.isValidPhone(value)) {
                this.setFieldError(field, 'Please enter a valid phone number (e.g., +1234567890 or 1234567890)');
                return false;
            }
        }

        // Field is valid
        this.clearFieldError(field);
        return true;
    }

    /**
     * Validate email format
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Validate phone number format
     */
    isValidPhone(phone) {
        // Remove all non-digit characters except +
        const cleaned = phone.replace(/[^\d+]/g, '');

        // Check if it's a valid format
        const phoneRegex = /^(\+?1?)?[\d]{10,15}$/;
        return phoneRegex.test(cleaned);
    }

    /**
     * Set error for a field
     */
    setFieldError(field, message) {
        this.errors[field.name] = message;

        // Add error class to field
        field.classList.add('error');
        field.classList.remove('success');

        // Show error message
        const errorElement = document.getElementById(`${field.name}-error`);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.add('active');
        }

        // Update ARIA attributes
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', `${field.name}-error`);
    }

    /**
     * Clear error for a field
     */
    clearFieldError(field) {
        delete this.errors[field.name];

        // Remove error class
        field.classList.remove('error');

        // Hide error message
        const errorElement = document.getElementById(`${field.name}-error`);
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.classList.remove('active');
        }

        // Update ARIA attributes
        field.setAttribute('aria-invalid', 'false');
        field.removeAttribute('aria-describedby');
    }

    /**
     * Set success state for a field
     */
    setFieldSuccess(field) {
        field.classList.add('success');
        field.classList.remove('error');
        this.clearFieldError(field);
    }

    /**
     * Get field label text
     */
    getFieldLabel(field) {
        const label = this.form.querySelector(`label[for="${field.id}"]`);
        if (label) {
            return label.textContent.replace('*', '').trim();
        }

        // Fallback to field name
        return field.name.charAt(0).toUpperCase() + field.name.slice(1);
    }

    /**
     * Show form message (success or error)
     */
    showFormMessage(message, type) {
        const messagesContainer = document.getElementById('formMessages');
        if (!messagesContainer) return;

        messagesContainer.innerHTML = `<p>${message}</p>`;
        messagesContainer.className = `form-messages ${type}`;
        messagesContainer.style.display = 'block';

        // Scroll to message
        messagesContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                messagesContainer.style.display = 'none';
            }, 5000);
        }
    }

    /**
     * Set form submitting state
     */
    setSubmittingState(isSubmitting) {
        this.isSubmitting = isSubmitting;
        const submitBtn = this.form.querySelector('button[type="submit"]');

        if (submitBtn) {
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');

            if (isSubmitting) {
                submitBtn.disabled = true;
                if (btnText) btnText.style.display = 'none';
                if (btnLoading) btnLoading.style.display = 'inline-block';
            } else {
                submitBtn.disabled = false;
                if (btnText) btnText.style.display = 'inline';
                if (btnLoading) btnLoading.style.display = 'none';
            }
        }
    }

    /**
     * Reset form to initial state
     */
    resetForm() {
        this.form.reset();
        this.errors = {};

        // Clear all field errors
        const fields = this.form.querySelectorAll('input, select, textarea');
        fields.forEach(field => {
            this.clearFieldError(field);
            field.classList.remove('success');
        });

        // Hide form messages after a delay
        setTimeout(() => {
            const messagesContainer = document.getElementById('formMessages');
            if (messagesContainer) {
                messagesContainer.style.display = 'none';
            }
        }, 3000);
    }

    /**
     * Focus on first field with error
     */
    focusFirstError() {
        const firstErrorField = this.form.querySelector('.error');
        if (firstErrorField) {
            firstErrorField.focus();
        }
    }

    /**
     * Debounce function to limit validation calls
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Get form data as object
     */
    getFormData() {
        const formData = new FormData(this.form);
        const data = {};

        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }

        return data;
    }

    /**
     * Check if form has errors
     */
    hasErrors() {
        return Object.keys(this.errors).length > 0;
    }

    /**
     * Get all current errors
     */
    getErrors() {
        return { ...this.errors };
    }
}

// Initialize form validation when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    // Initialize contact form validation
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        window.contactFormValidator = new FormValidator('contactForm');
    }
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FormValidator;
}