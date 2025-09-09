/**
 * Main JavaScript File
 * Handles page-specific content loading and general site functionality
 */

class WebSolutionsApp {
    constructor() {
        this.currentPage = this.getCurrentPage();
        this.init();
    }

    /**
     * Initialize the application
     */
    init() {
        this.setupEventListeners();
        this.loadPageContent();
        this.initializeNavigation();
    }

    /**
     * Get the current page name from the URL
     * @returns {string} Current page name
     */
    getCurrentPage() {
        const path = window.location.pathname;
        const page = path.split('/').pop().replace('.html', '') || 'index';
        return page === 'index' ? 'home' : page;
    }

    /**
     * Set up global event listeners
     */
    setupEventListeners() {
        // DOM Content Loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.onDOMReady());
        } else {
            this.onDOMReady();
        }

        // Window load event
        window.addEventListener('load', () => this.onWindowLoad());

        // Resize event for responsive adjustments
        window.addEventListener('resize', this.debounce(() => this.onWindowResize(), 250));

        // Scroll event for animations and effects
        window.addEventListener('scroll', this.throttle(() => this.onScroll(), 16));
    }

    /**
     * Handle DOM ready event
     */
    onDOMReady() {
        console.log('DOM is ready');
        this.loadPageContent();
    }

    /**
     * Handle window load event
     */
    onWindowLoad() {
        console.log('Window loaded');
        this.initializeAnimations();
    }

    /**
     * Handle window resize event
     */
    onWindowResize() {
        // Handle responsive adjustments if needed
        this.updateNavigationState();
    }

    /**
     * Handle scroll event
     */
    onScroll() {
        this.updateNavigationOnScroll();
        this.handleScrollAnimations();
    }

    /**
     * Load content based on current page
     */
    async loadPageContent() {
        try {
            switch (this.currentPage) {
                case 'services':
                    await this.loadServicesPage();
                    break;
                case 'about':
                    await this.loadAboutPage();
                    break;
                case 'home':
                    await this.loadHomePage();
                    break;
                default:
                    console.log(`No specific content loading for page: ${this.currentPage}`);
            }
        } catch (error) {
            console.error('Error loading page content:', error);
            this.showGlobalError('Failed to load page content. Please refresh the page.');
        }
    }

    /**
     * Load services page content
     */
    async loadServicesPage() {
        const servicesContainer = document.getElementById('services-container');
        if (!servicesContainer) return;

        try {
            // Show loading state
            contentLoader.showLoading(servicesContainer, 'Loading our services...');

            // Fetch services data
            const data = await contentLoader.fetchJSON('assets/data/services.json');

            // Render services
            contentLoader.renderServices(data.services);

            console.log('Services loaded successfully');
        } catch (error) {
            console.error('Error loading services:', error);
            this.showErrorInContainer(servicesContainer, 'Unable to load services at this time.');
        }
    }

    /**
     * Load about page content
     */
    async loadAboutPage() {
        const teamContainer = document.getElementById('team-container');
        if (!teamContainer) return;

        try {
            // Show loading state
            contentLoader.showLoading(teamContainer, 'Loading our team...');

            // Fetch team data
            const data = await contentLoader.fetchJSON('assets/data/team.json');

            // Render team members
            contentLoader.renderTeam(data.team);

            // Update company stats if container exists
            this.updateCompanyStats(data.company_stats);

            console.log('Team loaded successfully');
        } catch (error) {
            console.error('Error loading team:', error);
            this.showErrorInContainer(teamContainer, 'Unable to load team information at this time.');
        }
    }

    /**
     * Load home page content (testimonials, etc.)
     */
    async loadHomePage() {
        // Load testimonials if container exists
        const testimonialsContainer = document.getElementById('testimonials-container');
        if (testimonialsContainer) {
            try {
                contentLoader.showLoading(testimonialsContainer, 'Loading testimonials...');

                const data = await contentLoader.fetchJSON('assets/data/testimonials.json');
                contentLoader.renderTestimonials(data.testimonials, 'testimonials-container', true);

                console.log('Home page testimonials loaded successfully');
            } catch (error) {
                console.error('Error loading testimonials:', error);
                this.showErrorInContainer(testimonialsContainer, 'Unable to load testimonials at this time.');
            }
        }
    }

    /**
     * Update company stats on about page
     */
    updateCompanyStats(stats) {
        if (!stats) return;

        const statElements = {
            'projects-completed': stats.projects_completed,
            'years-experience': new Date().getFullYear() - stats.founded_year,
            'client-satisfaction': stats.client_satisfaction,
            'team-size': stats.team_size
        };

        Object.entries(statElements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                this.animateNumber(element, 0, value, 2000);
            }
        });
    }

    /**
     * Initialize navigation functionality
     */
    initializeNavigation() {
        const navToggle = document.querySelector('.nav-toggle');
        const navMenu = document.querySelector('.nav-menu');

        if (navToggle && navMenu) {
            navToggle.addEventListener('click', () => {
                const isExpanded = navToggle.getAttribute('aria-expanded') === 'true';

                navToggle.setAttribute('aria-expanded', !isExpanded);
                navMenu.classList.toggle('active');

                // Prevent body scroll when menu is open
                document.body.style.overflow = !isExpanded ? 'hidden' : '';
            });

            // Close menu when clicking on nav links
            const navLinks = navMenu.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    navToggle.setAttribute('aria-expanded', 'false');
                    navMenu.classList.remove('active');
                    document.body.style.overflow = '';
                });
            });

            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
                    navToggle.setAttribute('aria-expanded', 'false');
                    navMenu.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        }

        // Update active nav link based on current page
        this.updateActiveNavLink();
    }

    /**
     * Update active navigation link
     */
    updateActiveNavLink() {
        const navLinks = document.querySelectorAll('.nav-link');
        const currentPath = window.location.pathname;

        navLinks.forEach(link => {
            link.classList.remove('active');
            link.removeAttribute('aria-current');

            const linkPath = new URL(link.href).pathname;
            if (linkPath === currentPath ||
                (currentPath === '/' && linkPath.endsWith('index.html'))) {
                link.classList.add('active');
                link.setAttribute('aria-current', 'page');
            }
        });
    }

    /**
     * Update navigation state on resize
     */
    updateNavigationState() {
        const navToggle = document.querySelector('.nav-toggle');
        const navMenu = document.querySelector('.nav-menu');

        if (window.innerWidth >= 768) {
            // Desktop view - reset mobile menu state
            if (navToggle) navToggle.setAttribute('aria-expanded', 'false');
            if (navMenu) navMenu.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    /**
     * Update navigation appearance on scroll
     */
    updateNavigationOnScroll() {
        const navbar = document.querySelector('.navbar');
        if (!navbar) return;

        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    }

    /**
     * Handle scroll-triggered animations
     */
    handleScrollAnimations() {
        const animatedElements = document.querySelectorAll('[data-animate]');

        animatedElements.forEach(element => {
            if (this.isElementInViewport(element)) {
                element.classList.add('animate-in');
            }
        });
    }

    /**
     * Initialize animations
     */
    initializeAnimations() {
        // Add data-animate attribute to elements that should animate on scroll
        const elementsToAnimate = document.querySelectorAll('.feature-card, .service-card, .team-card, .value-card');
        elementsToAnimate.forEach(element => {
            element.setAttribute('data-animate', 'true');
        });
    }

    /**
     * Check if element is in viewport
     */
    isElementInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    /**
     * Animate number counting
     */
    animateNumber(element, start, end, duration) {
        const startTime = performance.now();
        const difference = end - start;

        const step = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            const current = Math.floor(start + (difference * this.easeOutQuart(progress)));
            element.textContent = current.toLocaleString();

            if (progress < 1) {
                requestAnimationFrame(step);
            }
        };

        requestAnimationFrame(step);
    }

    /**
     * Easing function for animations
     */
    easeOutQuart(t) {
        return 1 - (--t) * t * t * t;
    }

    /**
     * Show error in a specific container
     */
    showErrorInContainer(container, message) {
        container.innerHTML = `
            <div class="error-message">
                <p>⚠️ ${message}</p>
                <button onclick="location.reload()" class="btn btn-secondary">Refresh Page</button>
            </div>
        `;
    }

    /**
     * Show global error message
     */
    showGlobalError(message) {
        // Create or update global error banner
        let errorBanner = document.getElementById('global-error');
        if (!errorBanner) {
            errorBanner = document.createElement('div');
            errorBanner.id = 'global-error';
            errorBanner.className = 'global-error-banner';
            document.body.insertBefore(errorBanner, document.body.firstChild);
        }

        errorBanner.innerHTML = `
            <div class="container">
                <p>${message}</p>
                <button onclick="this.parentElement.parentElement.remove()" class="close-btn">&times;</button>
            </div>
        `;
    }

    /**
     * Debounce function to limit function calls
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
     * Throttle function to limit function calls
     */
    throttle(func, limit) {
        let inThrottle;
        return function () {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}

// Initialize the application
const app = new WebSolutionsApp();

// Make app globally available for debugging
window.WebSolutionsApp = app;