/**
 * WP Flyout Core JavaScript
 *
 * Handles flyout UI mechanics - opening, closing, and tab management.
 * Business logic should be handled by the implementing plugin.
 *
 * @package WPFlyout
 * @version 3.0.0
 */
(function ($, window, document) {
    'use strict';

    /**
     * WP Flyout Manager
     *
     * @since 1.0.0
     * @type {Object}
     */
    const WPFlyout = {

        /**
         * Active flyout instances
         *
         * @since 1.0.0
         * @type {Object}
         */
        instances: {},

        /**
         * Configuration
         *
         * @since 1.0.0
         * @type {Object}
         */
        config: {
            animationDelay: 10,
            animationDuration: 300,
            focusDelay: 350
        },

        /**
         * Initialize flyout system
         *
         * @since 1.0.0
         * @return {void}
         */
        init: function () {
            this.bindGlobalEvents();
        },

        /**
         * Open a flyout
         *
         * @since 1.0.0
         * @param {string} id Flyout element ID
         * @return {boolean} Success status
         */
        open: function (id) {
            const $flyout = $('#' + id);

            if (!$flyout.length) {
                return false;
            }

            // Create overlay if needed
            this.ensureOverlay();

            // Store instance
            this.instances[id] = $flyout;

            // Add body class
            $('body').addClass('wp-flyout-open');

            // Activate with delay for animation
            setTimeout(() => {
                $('.wp-flyout-overlay').addClass('active');
                $flyout.addClass('active');
            }, this.config.animationDelay);

            // Initialize tabs if present
            this.initTabs($flyout);

            // Focus management and trigger events after animation
            setTimeout(() => {
                // Focus first input
                $flyout.find('input:visible, select:visible, textarea:visible')
                    .not(':disabled')
                    .first()
                    .focus();

                // Trigger events
                const eventData = {
                    id: id,
                    element: $flyout[0]
                };

                $(document).trigger('wpflyout:opened', eventData);
                $flyout.trigger('flyout:ready');
            }, this.config.focusDelay);

            return true;
        },

        /**
         * Close a flyout
         *
         * @since 1.0.0
         * @param {string} id Flyout element ID
         * @return {boolean} Success status
         */
        close: function (id) {
            const $flyout = this.instances[id];

            if (!$flyout) {
                return false;
            }

            // Start close animation
            $flyout.removeClass('active');

            // Clean up after animation
            setTimeout(() => {
                $flyout.remove();
                delete this.instances[id];

                // Remove overlay if no more flyouts
                if (this.isEmpty()) {
                    this.removeOverlay();
                }

                // Trigger event
                $(document).trigger('wpflyout:closed', {id: id});
            }, this.config.animationDuration);

            return true;
        },

        /**
         * Close all open flyouts
         *
         * @since 1.0.0
         * @return {void}
         */
        closeAll: function () {
            Object.keys(this.instances).forEach(id => this.close(id));
        },

        /**
         * Get the last opened flyout ID
         *
         * @since 1.0.0
         * @return {string|null}
         */
        getLastId: function () {
            const keys = Object.keys(this.instances);
            return keys.length ? keys[keys.length - 1] : null;
        },

        /**
         * Check if there are no active flyouts
         *
         * @since 1.0.0
         * @return {boolean}
         */
        isEmpty: function () {
            return Object.keys(this.instances).length === 0;
        },

        /**
         * Ensure overlay exists
         *
         * @since 1.0.0
         * @return {void}
         */
        ensureOverlay: function () {
            if (!$('.wp-flyout-overlay').length) {
                $('body').append('<div class="wp-flyout-overlay"></div>');
            }
        },

        /**
         * Remove overlay
         *
         * @since 1.0.0
         * @return {void}
         */
        removeOverlay: function () {
            $('.wp-flyout-overlay').removeClass('active');
            $('body').removeClass('wp-flyout-open');

            setTimeout(() => {
                $('.wp-flyout-overlay').remove();
            }, this.config.animationDuration);
        },

        /**
         * Initialize tab switching
         *
         * @since 1.0.0
         * @param {jQuery} $flyout Flyout element
         * @return {void}
         */
        initTabs: function ($flyout) {
            $flyout.off('click.flyout-tabs').on('click.flyout-tabs', '.wp-flyout-tab', function (e) {
                e.preventDefault();

                const $tab = $(this);

                if ($tab.hasClass('disabled')) {
                    return;
                }

                const tabId = $tab.data('tab');

                // Update active states
                $flyout.find('.wp-flyout-tab')
                    .removeClass('active')
                    .attr('aria-selected', 'false');

                $tab.addClass('active')
                    .attr('aria-selected', 'true');

                // Switch content
                $flyout.find('.wp-flyout-tab-content').removeClass('active');
                $flyout.find('#tab-' + tabId).addClass('active');

                // Trigger event
                $(document).trigger('wpflyout:tab-changed', {
                    flyoutId: $flyout.attr('id'),
                    tabId: tabId
                });
            });
        },

        /**
         * Bind global event handlers
         *
         * @since 1.0.0
         * @return {void}
         */
        bindGlobalEvents: function () {
            const self = this;

            // Close button
            $(document).on('click', '.wp-flyout-close', function () {
                const flyoutId = $(this).closest('.wp-flyout').attr('id');
                self.close(flyoutId);
            });

            // Overlay click
            $(document).on('click', '.wp-flyout-overlay', function () {
                const lastId = self.getLastId();
                if (lastId) {
                    self.close(lastId);
                }
            });

            // Escape key
            $(document).on('keydown', function (e) {
                if (e.key === 'Escape' || e.keyCode === 27) {
                    const lastId = self.getLastId();
                    if (lastId) {
                        self.close(lastId);
                    }
                }
            });
        }
    };

    // Initialize on ready
    $(document).ready(function () {
        WPFlyout.init();
    });

    // Export to global
    window.WPFlyout = WPFlyout;

})(jQuery, window, document);