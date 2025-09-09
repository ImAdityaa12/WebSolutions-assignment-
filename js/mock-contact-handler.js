/**
 * Mock Contact Handler for Testing Frontend
 * Simulates PHP backend responses
 */

// Mock server endpoint
window.mockContactHandler = function (formData) {
    return new Promise((resolve) => {
        // Simulate network delay
        setTimeout(() => {
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

            // Simulate response
            if (Object.keys(errors).length === 0) {
                resolve({
                    ok: true,
                    json: () => Promise.resolve({
                        success: true,
                        message: 'Thank you! Your message has been sent successfully. (This is a mock response - PHP server needed for real submission)',
                        timestamp: new Date().toISOString()
                    })
                });
            } else {
                resolve({
                    ok: false,
                    status: 400,
                    json: () => Promise.resolve({
                        success: false,
                        message: 'Please correct the errors in your form.',
                        errors: errors,
                        timestamp: new Date().toISOString()
                    })
                });
            }
        }, 1000); // 1 second delay to simulate network
    });
};