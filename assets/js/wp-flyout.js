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
     * @since 3.0.0
     * @type {Object}
     */
    const WPFlyout = {

        /**
         * Active flyout instances
         *
         * @since 3.0.0
         * @type {Object}
         */
        instances: {},

        /**
         * Initialize flyout system
         *
         * @since 3.0.0
         * @return {void}
         */
        init: function () {
            this.bindGlobalEvents();
        },

        /**
         * Open a flyout
         *
         * @since 3.0.0
         *
         * @param {string} id Flyout element ID.
         * @return {void}
         */
        open: function (id) {
            const $flyout = $('#' + id);

            if (!$flyout.length) {
                console.error('WP Flyout: Element not found:', id);
                return;
            }

            // Create overlay if needed.
            if (!$('.wp-flyout-overlay').length) {
                $('body').append('<div class="wp-flyout-overlay"></div>');
            }

            // Store instance.
            this.instances[id] = $flyout;

            // Add body class.
            $('body').addClass('wp-flyout-open');

            // Activate with delay for animation.
            setTimeout(function () {
                $('.wp-flyout-overlay').addClass('active');
                $flyout.addClass('active');
            }, 10);

            // Initialize tabs if present.
            this.initTabs($flyout);

            // Focus management.
            setTimeout(function () {
                $flyout.find('input:visible, select:visible, textarea:visible')
                    .not(':disabled')
                    .first()
                    .focus();
            }, 350);

            // Trigger event.
            $(document).trigger('wpflyout:opened', {
                id: id,
                element: $flyout
            });
        },

        /**
         * Close a flyout
         *
         * @since 3.0.0
         *
         * @param {string} id Flyout element ID.
         * @return {void}
         */
        close: function (id) {
            const $flyout = this.instances[id];

            if (!$flyout) {
                return;
            }

            // Start close animation.
            $flyout.removeClass('active');

            // Clean up after animation.
            setTimeout(() => {
                $flyout.remove();
                delete this.instances[id];

                // Remove overlay if no more flyouts.
                if (Object.keys(this.instances).length === 0) {
                    $('.wp-flyout-overlay').removeClass('active');
                    $('body').removeClass('wp-flyout-open');

                    setTimeout(function () {
                        $('.wp-flyout-overlay').remove();
                    }, 300);
                }

                // Trigger event.
                $(document).trigger('wpflyout:closed', {id: id});
            }, 300);
        },

        /**
         * Close all open flyouts
         *
         * @since 3.0.0
         * @return {void}
         */
        closeAll: function () {
            Object.keys(this.instances).forEach(id => {
                this.close(id);
            });
        },

        /**
         * Initialize tab switching
         *
         * @since 3.0.0
         *
         * @param {jQuery} $flyout Flyout element.
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

                // Update active states.
                $flyout.find('.wp-flyout-tab').removeClass('active').attr('aria-selected', 'false');
                $tab.addClass('active').attr('aria-selected', 'true');

                // Switch content.
                $flyout.find('.wp-flyout-tab-content').removeClass('active');
                $flyout.find('#tab-' + tabId).addClass('active');

                // Trigger event.
                $(document).trigger('wpflyout:tab-changed', {
                    flyoutId: $flyout.attr('id'),
                    tabId: tabId
                });
            });
        },

        /**
         * Bind global event handlers
         *
         * @since 3.0.0
         * @return {void}
         */
        bindGlobalEvents: function () {
            const self = this;

            // Close button.
            $(document).on('click', '.wp-flyout-close', function () {
                const flyoutId = $(this).closest('.wp-flyout').attr('id');
                self.close(flyoutId);
            });

            // Overlay click.
            $(document).on('click', '.wp-flyout-overlay', function () {
                const lastId = Object.keys(self.instances).pop();
                if (lastId) {
                    self.close(lastId);
                }
            });

            // Escape key.
            $(document).on('keydown', function (e) {
                if (e.key === 'Escape' || e.keyCode === 27) {
                    const lastId = Object.keys(self.instances).pop();
                    if (lastId) {
                        self.close(lastId);
                    }
                }
            });
        }
    };

    // Initialize on ready.
    $(document).ready(function () {
        WPFlyout.init();
    });

    // Export to global.
    window.WPFlyout = WPFlyout;

})(jQuery, window, document);