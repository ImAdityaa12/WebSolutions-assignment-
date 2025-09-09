/**
 * Content Loader Utility Functions
 * Handles loading and rendering of JSON data for dynamic content
 */

class ContentLoader {
    constructor() {
        this.cache = new Map();
        this.loadingStates = new Map();
    }

    /**
     * Fetch JSON data from a file with error handling and caching
     * @param {string} url - The URL of the JSON file to fetch
     * @param {boolean} useCache - Whether to use cached data if available
     * @returns {Promise<Object>} The parsed JSON data
     */
    async fetchJSON(url, useCache = true) {
        // Return cached data if available and caching is enabled
        if (useCache && this.cache.has(url)) {
            return this.cache.get(url);
        }

        // Prevent multiple simultaneous requests for the same URL
        if (this.loadingStates.has(url)) {
            return this.loadingStates.get(url);
        }

        const loadingPromise = this._performFetch(url);
        this.loadingStates.set(url, loadingPromise);

        try {
            const data = await loadingPromise;
            this.cache.set(url, data);
            this.loadingStates.delete(url);
            return data;
        } catch (error) {
            this.loadingStates.delete(url);
            throw error;
        }
    }

    /**
     * Internal method to perform the actual fetch operation
     * @private
     */
    async _performFetch(url) {
        try {
            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error(`Error fetching JSON from ${url}:`, error);
            throw new Error(`Failed to load data from ${url}: ${error.message}`);
        }
    }

    /**
     * Render services from JSON data
     * @param {Array} services - Array of service objects
     * @param {string} containerId - ID of the container element
     */
    renderServices(services, containerId = 'services-container') {
        const container = document.getElementById(containerId);
        if (!container) {
            console.error(`Container with ID '${containerId}' not found`);
            return;
        }

        try {
            const servicesHTML = services.map(service => this._createServiceCard(service)).join('');
            container.innerHTML = servicesHTML;

            // Add animation classes after rendering
            this._animateElements(container.querySelectorAll('.service-card'));
        } catch (error) {
            console.error('Error rendering services:', error);
            this._showErrorMessage(container, 'Failed to load services');
        }
    }

    /**
     * Create HTML for a service card
     * @private
     */
    _createServiceCard(service) {
        const featuresHTML = service.features
            ? service.features.map(feature => `<li>${this._escapeHtml(feature)}</li>`).join('')
            : '';

        return `
            <div class="service-card" data-service-id="${service.id}">
                <div class="service-icon">
                    <span class="icon">${service.icon}</span>
                </div>
                <h3 class="service-title">${this._escapeHtml(service.title)}</h3>
                <p class="service-description">${this._escapeHtml(service.description)}</p>
                ${featuresHTML ? `
                    <div class="service-features">
                        <h4>Key Features:</h4>
                        <ul class="feature-list">
                            ${featuresHTML}
                        </ul>
                    </div>
                ` : ''}
                ${service.timeline ? `
                    <div class="service-meta">
                        <span class="service-timeline">⏱️ ${this._escapeHtml(service.timeline)}</span>
                        ${service.price_range ? `<span class="service-price">${this._escapeHtml(service.price_range)}</span>` : ''}
                    </div>
                ` : ''}
            </div>
        `;
    }

    /**
     * Render team members from JSON data
     * @param {Array} teamMembers - Array of team member objects
     * @param {string} containerId - ID of the container element
     */
    renderTeam(teamMembers, containerId = 'team-container') {
        const container = document.getElementById(containerId);
        if (!container) {
            console.error(`Container with ID '${containerId}' not found`);
            return;
        }

        try {
            const teamHTML = teamMembers.map(member => this._createTeamCard(member)).join('');
            container.innerHTML = teamHTML;

            // Add animation classes after rendering
            this._animateElements(container.querySelectorAll('.team-card'));
        } catch (error) {
            console.error('Error rendering team:', error);
            this._showErrorMessage(container, 'Failed to load team information');
        }
    }

    /**
     * Create HTML for a team member card
     * @private
     */
    _createTeamCard(member) {
        const socialLinksHTML = member.social
            ? Object.entries(member.social).map(([platform, url]) =>
                `<a href="${this._escapeHtml(url)}" target="_blank" rel="noopener noreferrer" aria-label="${this._escapeHtml(member.name)} on ${platform}">
                    ${this._getSocialIcon(platform)}
                </a>`
            ).join('')
            : '';

        const skillsHTML = member.skills
            ? member.skills.map(skill => `<span class="skill-tag">${this._escapeHtml(skill)}</span>`).join('')
            : '';

        return `
            <div class="team-card" data-member-id="${member.id}">
                <div class="team-image">
                    <img src="${this._escapeHtml(member.image)}" alt="${this._escapeHtml(member.name)}" 
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjRjNGNEY2Ii8+CjxjaXJjbGUgY3g9IjUwIiBjeT0iNDAiIHI9IjE1IiBmaWxsPSIjOTNBM0I4Ii8+CjxwYXRoIGQ9Ik0yMCA4MEM0MCA2MCA2MCA2MCA4MCA4MEgyMFoiIGZpbGw9IiM5M0EzQjgiLz4KPC9zdmc+'" />
                </div>
                <div class="team-info">
                    <h3 class="team-name">${this._escapeHtml(member.name)}</h3>
                    <p class="team-position">${this._escapeHtml(member.position)}</p>
                    <p class="team-bio">${this._escapeHtml(member.bio)}</p>
                    ${skillsHTML ? `
                        <div class="team-skills">
                            ${skillsHTML}
                        </div>
                    ` : ''}
                    ${socialLinksHTML ? `
                        <div class="team-social">
                            <div class="social-links">
                                ${socialLinksHTML}
                            </div>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    /**
     * Render testimonials from JSON data
     * @param {Array} testimonials - Array of testimonial objects
     * @param {string} containerId - ID of the container element
     * @param {boolean} featuredOnly - Whether to show only featured testimonials
     */
    renderTestimonials(testimonials, containerId = 'testimonials-container', featuredOnly = false) {
        const container = document.getElementById(containerId);
        if (!container) {
            console.error(`Container with ID '${containerId}' not found`);
            return;
        }

        try {
            const filteredTestimonials = featuredOnly
                ? testimonials.filter(testimonial => testimonial.featured)
                : testimonials;

            const testimonialsHTML = filteredTestimonials.map(testimonial =>
                this._createTestimonialCard(testimonial)
            ).join('');

            container.innerHTML = testimonialsHTML;

            // Add animation classes after rendering
            this._animateElements(container.querySelectorAll('.testimonial-card'));
        } catch (error) {
            console.error('Error rendering testimonials:', error);
            this._showErrorMessage(container, 'Failed to load testimonials');
        }
    }

    /**
     * Create HTML for a testimonial card
     * @private
     */
    _createTestimonialCard(testimonial) {
        const starsHTML = '★'.repeat(testimonial.rating) + '☆'.repeat(5 - testimonial.rating);

        return `
            <div class="testimonial-card" data-testimonial-id="${testimonial.id}">
                <div class="testimonial-rating">
                    <span class="stars">${starsHTML}</span>
                </div>
                <blockquote class="testimonial-comment">
                    "${this._escapeHtml(testimonial.comment)}"
                </blockquote>
                <div class="testimonial-author">
                    <div class="author-info">
                        <h4 class="author-name">${this._escapeHtml(testimonial.client_name)}</h4>
                        <p class="author-position">${this._escapeHtml(testimonial.client_position)}</p>
                        <p class="author-company">${this._escapeHtml(testimonial.company)}</p>
                    </div>
                    ${testimonial.project_type ? `
                        <div class="testimonial-meta">
                            <span class="project-type">${this._escapeHtml(testimonial.project_type)}</span>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    /**
     * Show loading state in a container
     * @param {HTMLElement} container - The container element
     * @param {string} message - Loading message
     */
    showLoading(container, message = 'Loading...') {
        container.innerHTML = `
            <div class="loading">
                <div class="spinner"></div>
                <p>${this._escapeHtml(message)}</p>
            </div>
        `;
    }

    /**
     * Show error message in a container
     * @private
     */
    _showErrorMessage(container, message) {
        container.innerHTML = `
            <div class="error-message">
                <p>⚠️ ${this._escapeHtml(message)}</p>
                <button onclick="location.reload()" class="btn btn-secondary">Try Again</button>
            </div>
        `;
    }

    /**
     * Animate elements with staggered delay
     * @private
     */
    _animateElements(elements) {
        elements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';

            setTimeout(() => {
                element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    /**
     * Get social media icon
     * @private
     */
    _getSocialIcon(platform) {
        const icons = {
            linkedin: '💼',
            github: '🐙',
            twitter: '🐦',
            dribbble: '🏀',
            behance: '🎨',
            codepen: '✏️',
            stackoverflow: '📚'
        };
        return icons[platform.toLowerCase()] || '🔗';
    }

    /**
     * Escape HTML to prevent XSS attacks
     * @private
     */
    _escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Clear cache for a specific URL or all cache
     * @param {string} url - Optional URL to clear from cache
     */
    clearCache(url = null) {
        if (url) {
            this.cache.delete(url);
        } else {
            this.cache.clear();
        }
    }
}

// Create global instance
const contentLoader = new ContentLoader();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ContentLoader;
}