/**
 * Theme LMS Frontend Scripts
 *
 * @package Theme_LMS
 * @since 1.0.0
 */

(function() {
    'use strict';

    /**
     * Theme LMS Frontend
     */
    const ThemeLMS = {
        /**
         * Initialize.
         */
        init: function() {
            this.bindEvents();
            this.initProgressTracking();
            this.initPdfNavigation();
        },

        /**
         * Bind event handlers.
         */
        bindEvents: function() {
            // Mark complete buttons.
            document.querySelectorAll('.lms-complete-btn').forEach(function(btn) {
                btn.addEventListener('click', ThemeLMS.handleComplete);
            });

            // Sidebar toggle.
            document.querySelectorAll('[data-toggle="sidebar"]').forEach(function(btn) {
                btn.addEventListener('click', ThemeLMS.toggleSidebar);
            });

            // Section toggles.
            document.querySelectorAll('[data-toggle="section"]').forEach(function(btn) {
                btn.addEventListener('click', ThemeLMS.toggleSection);
            });
        },

        /**
         * Initialize PDF navigation for multiple embedded PDFs.
         */
        initPdfNavigation: function() {
            document.querySelectorAll('.lms-pdf-nav-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const index = this.dataset.index;
                    const container = this.closest('.lms-embedded-materials');
                    
                    // Update active button
                    container.querySelectorAll('.lms-pdf-nav-btn').forEach(function(b) {
                        b.classList.remove('active');
                    });
                    this.classList.add('active');
                    
                    // Update active PDF section
                    container.querySelectorAll('.lms-pdf-section').forEach(function(section) {
                        section.classList.remove('active');
                    });
                    container.querySelector('.lms-pdf-section[data-pdf-index="' + index + '"]').classList.add('active');
                });
            });
        },

        /**
         * Handle mark complete.
         */
        handleComplete: function(e) {
            const btn = e.currentTarget;
            const itemId = btn.dataset.itemId;
            const itemType = btn.dataset.itemType || 'lesson';

            btn.disabled = true;
            btn.textContent = themeLMS.strings.completed + '...';

            fetch(themeLMS.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'theme_lms_complete_item',
                    item_id: itemId,
                    item_type: itemType,
                    nonce: themeLMS.nonce,
                }),
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    btn.textContent = themeLMS.strings.completed;
                    btn.classList.add('is-complete');
                    
                    // Update progress bar if present.
                    if (data.data && data.data.progress_percent !== undefined) {
                        ThemeLMS.updateProgressBar(data.data.progress_percent);
                    }

                    // Navigate to next item if available.
                    if (data.data && data.data.next_item && data.data.next_item.url) {
                        setTimeout(function() {
                            window.location.href = data.data.next_item.url;
                        }, 500);
                    }
                } else {
                    btn.disabled = false;
                    btn.textContent = themeLMS.strings.markComplete;
                    console.error('Failed to mark complete:', data);
                }
            })
            .catch(function(error) {
                btn.disabled = false;
                btn.textContent = themeLMS.strings.markComplete;
                console.error('Error:', error);
            });
        },

        /**
         * Toggle sidebar.
         */
        toggleSidebar: function() {
            const sidebar = document.querySelector('.lms-sidebar');
            if (sidebar) {
                const currentState = sidebar.dataset.state || 'expanded';
                sidebar.dataset.state = currentState === 'expanded' ? 'collapsed' : 'expanded';
            }
        },

        /**
         * Toggle section.
         */
        toggleSection: function(e) {
            const section = e.currentTarget.closest('.lms-nav__section');
            const lessons = section.querySelector('.lms-nav__lessons');
            if (lessons) {
                lessons.style.display = lessons.style.display === 'none' ? '' : 'none';
            }
        },

        /**
         * Update progress bar.
         */
        updateProgressBar: function(percent) {
            document.querySelectorAll('.lms-progress-bar__fill').forEach(function(bar) {
                bar.style.width = percent + '%';
            });
            document.querySelectorAll('.lms-progress-bar__text').forEach(function(text) {
                text.textContent = percent + '% complete';
            });
        },

        /**
         * Initialize progress tracking.
         */
        initProgressTracking: function() {
            // Track time spent.
            if (typeof themeLMS !== 'undefined' && themeLMS.courseId) {
                this.trackTimeSpent();
            }
        },

        /**
         * Track time spent on page.
         */
        trackTimeSpent: function() {
            let startTime = Date.now();
            
            window.addEventListener('beforeunload', function() {
                const timeSpent = Math.round((Date.now() - startTime) / 1000);
                
                // Send beacon with time data.
                if (navigator.sendBeacon && typeof themeLMS !== 'undefined') {
                    const data = new FormData();
                    data.append('action', 'theme_lms_track_time');
                    data.append('time_spent', timeSpent);
                    data.append('course_id', themeLMS.courseId);
                    data.append('nonce', themeLMS.nonce);
                    
                    navigator.sendBeacon(themeLMS.ajaxUrl, data);
                }
            });
        }
    };

    // Initialize on DOM ready.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { ThemeLMS.init(); });
    } else {
        ThemeLMS.init();
    }

    // Expose globally.
    window.ThemeLMS = ThemeLMS;
})();
