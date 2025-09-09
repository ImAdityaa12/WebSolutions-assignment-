/**
 * Performance Monitoring and Optimization
 * Tracks page performance and implements optimizations
 */

class PerformanceMonitor {
    constructor() {
        this.metrics = {};
        this.init();
    }

    /**
     * Initialize performance monitoring
     */
    init() {
        // Monitor page load performance
        this.trackPageLoad();

        // Implement lazy loading for images
        this.setupLazyLoading();

        // Optimize font loading
        this.optimizeFontLoading();

        // Monitor Core Web Vitals
        this.trackCoreWebVitals();

        // Setup resource hints
        this.setupResourceHints();
    }

    /**
     * Track page load performance
     */
    trackPageLoad() {
        window.addEventListener('load', () => {
            if ('performance' in window) {
                const navigation = performance.getEntriesByType('navigation')[0];

                this.metrics.pageLoad = {
                    domContentLoaded: navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart,
                    loadComplete: navigation.loadEventEnd - navigation.loadEventStart,
                    totalTime: navigation.loadEventEnd - navigation.fetchStart,
                    dnsLookup: navigation.domainLookupEnd - navigation.domainLookupStart,
                    tcpConnect: navigation.connectEnd - navigation.connectStart,
                    serverResponse: navigation.responseEnd - navigation.requestStart
                };

                // Log performance metrics in development
                if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                    console.log('Performance Metrics:', this.metrics.pageLoad);
                }
            }
        });
    }

    /**
     * Setup lazy loading for images
     */
    setupLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;

                        // Load the image
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                        }

                        // Add loaded class for fade-in effect
                        img.addEventListener('load', () => {
                            img.classList.add('loaded');
                        });

                        // Stop observing this image
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });

            // Observe all images with data-src attribute
            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        } else {
            // Fallback for browsers without IntersectionObserver
            document.querySelectorAll('img[data-src]').forEach(img => {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                img.classList.add('loaded');
            });
        }
    }

    /**
     * Optimize font loading
     */
    optimizeFontLoading() {
        // Preload critical fonts
        const fontPreloads = [
            'https://fonts.gstatic.com/s/inter/v12/UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuLyfAZ9hiA.woff2'
        ];

        fontPreloads.forEach(fontUrl => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'font';
            link.type = 'font/woff2';
            link.crossOrigin = 'anonymous';
            link.href = fontUrl;
            document.head.appendChild(link);
        });

        // Use font-display: swap for better performance
        if ('fonts' in document) {
            document.fonts.ready.then(() => {
                document.body.classList.add('fonts-loaded');
            });
        }
    }

    /**
     * Track Core Web Vitals
     */
    trackCoreWebVitals() {
        // Largest Contentful Paint (LCP)
        if ('PerformanceObserver' in window) {
            try {
                const lcpObserver = new PerformanceObserver((entryList) => {
                    const entries = entryList.getEntries();
                    const lastEntry = entries[entries.length - 1];
                    this.metrics.lcp = lastEntry.startTime;
                });
                lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });
            } catch (e) {
                // LCP not supported
            }

            // First Input Delay (FID)
            try {
                const fidObserver = new PerformanceObserver((entryList) => {
                    const entries = entryList.getEntries();
                    entries.forEach(entry => {
                        this.metrics.fid = entry.processingStart - entry.startTime;
                    });
                });
                fidObserver.observe({ entryTypes: ['first-input'] });
            } catch (e) {
                // FID not supported
            }

            // Cumulative Layout Shift (CLS)
            try {
                let clsValue = 0;
                const clsObserver = new PerformanceObserver((entryList) => {
                    const entries = entryList.getEntries();
                    entries.forEach(entry => {
                        if (!entry.hadRecentInput) {
                            clsValue += entry.value;
                        }
                    });
                    this.metrics.cls = clsValue;
                });
                clsObserver.observe({ entryTypes: ['layout-shift'] });
            } catch (e) {
                // CLS not supported
            }
        }
    }

    /**
     * Setup resource hints for better performance
     */
    setupResourceHints() {
        // DNS prefetch for external domains
        const externalDomains = [
            'fonts.googleapis.com',
            'fonts.gstatic.com'
        ];

        externalDomains.forEach(domain => {
            const link = document.createElement('link');
            link.rel = 'dns-prefetch';
            link.href = `//${domain}`;
            document.head.appendChild(link);
        });

        // Preconnect to critical external resources
        const preconnectDomains = [
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com'
        ];

        preconnectDomains.forEach(domain => {
            const link = document.createElement('link');
            link.rel = 'preconnect';
            link.href = domain;
            link.crossOrigin = 'anonymous';
            document.head.appendChild(link);
        });
    }

    /**
     * Optimize images for better performance
     */
    optimizeImages() {
        const images = document.querySelectorAll('img');

        images.forEach(img => {
            // Add loading="lazy" to images below the fold
            if (!img.hasAttribute('loading')) {
                const rect = img.getBoundingClientRect();
                if (rect.top > window.innerHeight) {
                    img.setAttribute('loading', 'lazy');
                }
            }

            // Add proper alt attributes if missing
            if (!img.hasAttribute('alt')) {
                img.setAttribute('alt', '');
            }

            // Optimize image dimensions
            if (img.naturalWidth && img.naturalHeight) {
                const aspectRatio = img.naturalHeight / img.naturalWidth;
                img.style.aspectRatio = `${img.naturalWidth} / ${img.naturalHeight}`;
            }
        });
    }

    /**
     * Monitor and report performance issues
     */
    reportPerformanceIssues() {
        // Check for performance issues
        const issues = [];

        if (this.metrics.lcp && this.metrics.lcp > 2500) {
            issues.push('LCP is slower than recommended (>2.5s)');
        }

        if (this.metrics.fid && this.metrics.fid > 100) {
            issues.push('FID is slower than recommended (>100ms)');
        }

        if (this.metrics.cls && this.metrics.cls > 0.1) {
            issues.push('CLS is higher than recommended (>0.1)');
        }

        // Log issues in development
        if (issues.length > 0 && (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')) {
            console.warn('Performance Issues Detected:', issues);
        }

        return issues;
    }

    /**
     * Get current performance metrics
     */
    getMetrics() {
        return { ...this.metrics };
    }

    /**
     * Optimize critical rendering path
     */
    optimizeCriticalRenderingPath() {
        // Inline critical CSS (this would be done at build time)
        const criticalCSS = `
            .navbar { position: fixed; top: 0; left: 0; right: 0; height: 4rem; background: rgba(255,255,255,0.95); z-index: 1030; }
            .hero { padding: 5rem 0; background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%); }
            .container { max-width: 1280px; margin: 0 auto; padding: 0 1rem; }
        `;

        // Create style element for critical CSS
        const style = document.createElement('style');
        style.textContent = criticalCSS;
        document.head.insertBefore(style, document.head.firstChild);
    }
}

// Initialize performance monitoring
const performanceMonitor = new PerformanceMonitor();

// Make it globally available for debugging
window.performanceMonitor = performanceMonitor;

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PerformanceMonitor;
}