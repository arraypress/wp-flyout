/**
 * WP Flyout Core JavaScript
 *
 * Handles flyout open/close mechanics and provides API for Manager integration
 *
 * @version 1.0.0
 */
(function ($) {
    'use strict';

    /**
     * WP Flyout Core
     */
    window.WPFlyout = window.WPFlyout || {};

    // Default configuration
    WPFlyout.defaults = {
        width: 'medium',
        position: 'right',
        closeOnEscape: true,
        closeOnOverlay: true,
        animationDuration: 300
    };

    // Active flyouts tracking
    WPFlyout.active = [];

    /**
     * Open a flyout
     *
     * @param {Object} options Flyout options
     */
    WPFlyout.open = function (options) {
        const settings = $.extend({}, WPFlyout.defaults, options);

        // Create unique ID if not provided
        const flyoutId = settings.id || 'flyout-' + Date.now();

        // Check if already exists
        let $flyout = $('#' + flyoutId);
        if ($flyout.length === 0) {
            $flyout = WPFlyout.create(flyoutId, settings);
        }

        // Update content if provided
        if (settings.content) {
            WPFlyout.setContent($flyout, settings.content);
        }

        // Update title if provided
        if (settings.title) {
            WPFlyout.setTitle($flyout, settings.title);
        }

        // Update width class
        WPFlyout.setWidth($flyout, settings.width);

        // Show overlay
        WPFlyout.showOverlay();

        // Add active class
        $flyout.addClass('active');

        // Track active flyout
        WPFlyout.active.push(flyoutId);

        // Bind events
        WPFlyout.bindEvents($flyout, settings);

        // Trigger opened event
        $(document).trigger('wpflyout:opened', {
            element: $flyout[0],
            id: flyoutId,
            settings: settings
        });

        return $flyout;
    };

    /**
     * Create flyout element
     */
    WPFlyout.create = function (id, settings) {
        const html = `
            <div id="${id}" class="wp-flyout wp-flyout-${settings.position} wp-flyout-${settings.width}">
                <div class="wp-flyout-header">
                    <h2></h2>
                    <button type="button" class="wp-flyout-close" aria-label="Close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="wp-flyout-content">
                    <div class="wp-flyout-body"></div>
                </div>
                <div class="wp-flyout-footer" style="display: none;"></div>
            </div>
        `;

        return $(html).appendTo('body');
    };

    /**
     * Open flyout from HTML content
     */
    WPFlyout.openFromHTML = function (html, config) {
        const settings = $.extend({}, WPFlyout.defaults, config);

        // Parse the HTML to see if it's already a flyout structure
        const $temp = $('<div>').html(html);
        const $existingFlyout = $temp.find('.wp-flyout').first();

        if ($existingFlyout.length) {
            // Use existing flyout structure
            const flyoutId = $existingFlyout.attr('id') || 'flyout-' + Date.now();
            $existingFlyout.attr('id', flyoutId);

            // Remove any existing instance
            $('#' + flyoutId).remove();

            // Append to body
            $existingFlyout.appendTo('body');

            // Show overlay
            WPFlyout.showOverlay();

            // Add active class
            $existingFlyout.addClass('active');

            // Track active flyout
            WPFlyout.active.push(flyoutId);

            // Bind events
            WPFlyout.bindEvents($existingFlyout, settings);

            // Trigger opened event
            $(document).trigger('wpflyout:opened', {
                element: $existingFlyout[0],
                id: flyoutId,
                settings: settings
            });

            return $existingFlyout;
        } else {
            // Treat as content for new flyout
            return WPFlyout.open($.extend(settings, {
                content: html
            }));
        }
    };

    /**
     * Close a flyout
     */
    WPFlyout.close = function (flyout) {
        const $flyout = typeof flyout === 'string' ? $('#' + flyout) : $(flyout);

        if (!$flyout.length) return;

        const flyoutId = $flyout.attr('id');

        // Trigger closing event
        const event = $.Event('wpflyout:closing');
        $(document).trigger(event, {
            element: $flyout[0],
            id: flyoutId
        });

        if (event.isDefaultPrevented()) {
            return;
        }

        // Remove active class
        $flyout.removeClass('active');

        // Remove from active tracking
        WPFlyout.active = WPFlyout.active.filter(id => id !== flyoutId);

        // Hide overlay if no more active flyouts
        if (WPFlyout.active.length === 0) {
            WPFlyout.hideOverlay();
        }

        // Remove after animation
        setTimeout(function () {
            $flyout.remove();

            // Trigger closed event
            $(document).trigger('wpflyout:closed', {
                id: flyoutId
            });
        }, WPFlyout.defaults.animationDuration);
    };

    /**
     * Close all flyouts
     */
    WPFlyout.closeAll = function () {
        const flyouts = [...WPFlyout.active];
        flyouts.forEach(id => WPFlyout.close(id));
    };

    /**
     * Set flyout content
     */
    WPFlyout.setContent = function ($flyout, content) {
        $flyout.find('.wp-flyout-body').html(content);
    };

    /**
     * Set flyout title
     */
    WPFlyout.setTitle = function ($flyout, title) {
        $flyout.find('.wp-flyout-header h2').text(title);
    };

    /**
     * Set flyout width
     */
    WPFlyout.setWidth = function ($flyout, width) {
        $flyout.removeClass('wp-flyout-small wp-flyout-medium wp-flyout-large wp-flyout-full')
            .addClass('wp-flyout-' + width);
    };

    /**
     * Show loading state
     */
    WPFlyout.showLoading = function (flyout, message) {
        const $flyout = typeof flyout === 'string' ? $('#' + flyout) : $(flyout);
        const loadingHtml = `
            <div class="wp-flyout-loading">
                <span class="spinner is-active"></span>
                <p>${message || 'Loading...'}</p>
            </div>
        `;

        WPFlyout.setContent($flyout, loadingHtml);
    };

    /**
     * Show error state
     */
    WPFlyout.showError = function (flyout, message) {
        const $flyout = typeof flyout === 'string' ? $('#' + flyout) : $(flyout);
        const errorHtml = `
            <div class="notice notice-error">
                <p>${message || 'An error occurred'}</p>
            </div>
        `;

        WPFlyout.setContent($flyout, errorHtml);
    };

    /**
     * Show message
     */
    WPFlyout.showMessage = function (flyout, message, type) {
        const $flyout = typeof flyout === 'string' ? $('#' + flyout) : $(flyout);
        const $content = $flyout.find('.wp-flyout-content');

        // Remove existing notices
        $content.find('.wp-flyout-notice').remove();

        // Add new notice
        const noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
        const $notice = $(`
            <div class="notice ${noticeClass} wp-flyout-notice is-dismissible">
                <p>${message}</p>
            </div>
        `).prependTo($content);

        // Auto dismiss success messages
        if (type === 'success') {
            setTimeout(() => {
                $notice.fadeOut(() => $notice.remove());
            }, 3000);
        }
    };

    /**
     * Bind form auto-submit
     */
    WPFlyout.bindFormAutoSubmit = function (flyout, handler) {
        const $flyout = typeof flyout === 'string' ? $('#' + flyout) : $(flyout);

        $flyout.find('form').off('submit.wpflyout').on('submit.wpflyout', function (e) {
            e.preventDefault();

            if (handler && typeof handler === 'function') {
                handler($(this));
            }

            // Trigger form submit event
            $(document).trigger('wpflyout:formsubmit', {
                element: $flyout[0],
                form: this
            });
        });
    };

    /**
     * Track dirty state
     */
    WPFlyout.trackDirty = function (flyout) {
        const $flyout = typeof flyout === 'string' ? $('#' + flyout) : $(flyout);

        $flyout.data('isDirty', false);

        $flyout.find('form :input').on('change.dirty input.dirty', function () {
            $flyout.data('isDirty', true);
        });

        return $flyout;
    };

    /**
     * Check if flyout has unsaved changes
     */
    WPFlyout.isDirty = function (flyout) {
        const $flyout = typeof flyout === 'string' ? $('#' + flyout) : $(flyout);
        return $flyout.data('isDirty') === true;
    };

    /**
     * Reset dirty state
     */
    WPFlyout.resetDirty = function (flyout) {
        const $flyout = typeof flyout === 'string' ? $('#' + flyout) : $(flyout);
        $flyout.data('isDirty', false);
    };

    /**
     * Show overlay
     */
    WPFlyout.showOverlay = function () {
        let $overlay = $('.wp-flyout-overlay');

        if ($overlay.length === 0) {
            $overlay = $('<div class="wp-flyout-overlay"></div>').appendTo('body');
        }

        // Small delay to ensure transition works
        setTimeout(function () {
            $overlay.addClass('active');
        }, 10);
    };

    /**
     * Hide overlay
     */
    WPFlyout.hideOverlay = function () {
        const $overlay = $('.wp-flyout-overlay');

        $overlay.removeClass('active');

        setTimeout(function () {
            $overlay.remove();
        }, WPFlyout.defaults.animationDuration);
    };

    /**
     * Bind flyout events
     */
    WPFlyout.bindEvents = function ($flyout, settings) {
        const flyoutId = $flyout.attr('id');

        // Close button
        $flyout.find('.wp-flyout-close').off('click.wpflyout').on('click.wpflyout', function () {
            WPFlyout.close($flyout);
        });

        // Overlay click
        if (settings.closeOnOverlay) {
            $('.wp-flyout-overlay').off('click.wpflyout-' + flyoutId).on('click.wpflyout-' + flyoutId, function () {
                // Check for dirty state
                if (WPFlyout.isDirty($flyout)) {
                    if (!confirm('You have unsaved changes. Are you sure you want to close?')) {
                        return;
                    }
                }
                WPFlyout.close($flyout);
            });
        }

        // ESC key
        if (settings.closeOnEscape) {
            $(document).off('keydown.wpflyout-' + flyoutId).on('keydown.wpflyout-' + flyoutId, function (e) {
                if (e.key === 'Escape') {
                    // Only close the topmost flyout
                    if (WPFlyout.active[WPFlyout.active.length - 1] === flyoutId) {
                        // Check for dirty state
                        if (WPFlyout.isDirty($flyout)) {
                            if (!confirm('You have unsaved changes. Are you sure you want to close?')) {
                                return;
                            }
                        }
                        WPFlyout.close($flyout);
                    }
                }
            });
        }

        // Tab navigation
        WPFlyout.bindTabNavigation($flyout);
    };

    /**
     * Bind tab navigation
     */
    WPFlyout.bindTabNavigation = function ($flyout) {
        $flyout.find('.wp-flyout-tabs a').off('click.tabs').on('click.tabs', function (e) {
            e.preventDefault();

            const $tab = $(this);
            const target = $tab.attr('href');

            // Update active states
            $flyout.find('.wp-flyout-tabs li').removeClass('active');
            $tab.parent().addClass('active');

            // Show target panel
            $flyout.find('.wp-flyout-tab-panel').removeClass('active');
            $(target).addClass('active');

            // Trigger tab change event
            $(document).trigger('wpflyout:tabchange', {
                element: $flyout[0],
                tab: target
            });
        });
    };

    /**
     * Get active flyout
     */
    WPFlyout.getActive = function () {
        if (WPFlyout.active.length === 0) return null;
        return $('#' + WPFlyout.active[WPFlyout.active.length - 1]);
    };

    /**
     * Refresh element via AJAX
     */
    WPFlyout.refreshElement = function (selector) {
        const $element = $(selector);
        if (!$element.length) return;

        // Add loading state
        $element.css('opacity', '0.5');

        // Reload the page content
        $.get(window.location.href, function (html) {
            const $newElement = $(html).find(selector);
            if ($newElement.length) {
                $element.replaceWith($newElement);

                // Trigger refresh event
                $(document).trigger('wpflyout:refreshed', {
                    selector: selector
                });
            }
        }).always(function () {
            $element.css('opacity', '1');
        });
    };

    /**
     * Initialize on ready
     */
    $(document).ready(function () {
        // Clean up on page unload
        $(window).on('beforeunload', function () {
            if (WPFlyout.active.length > 0) {
                const $dirty = WPFlyout.active.map(id => $('#' + id))
                    .filter($f => WPFlyout.isDirty($f));

                if ($dirty.length > 0) {
                    return 'You have unsaved changes. Are you sure you want to leave?';
                }
            }
        });
    });

})(jQuery);