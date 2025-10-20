/**
 * Accordion & Collapsible Component JavaScript
 *
 * Handles expand/collapse interactions with smooth animations.
 *
 * @package     ArrayPress\WPFlyout
 * @version     1.0.0
 */

(function ($) {
    'use strict';

    /**
     * Accordion Handler
     */
    const Accordion = {
        /**
         * Initialize all accordions
         */
        init: function () {
            $('.wp-flyout-accordion').each(function () {
                Accordion.initAccordion($(this));
            });

            // Initialize on flyout open
            $(document).on('wpflyout:opened', function (e, data) {
                $(data.element).find('.wp-flyout-accordion').each(function () {
                    if (!$(this).data('accordion-initialized')) {
                        Accordion.initAccordion($(this));
                    }
                });
            });
        },

        /**
         * Initialize a single accordion
         */
        initAccordion: function ($accordion) {
            if ($accordion.data('accordion-initialized')) {
                return;
            }

            $accordion.data('accordion-initialized', true);

            const allowMultiple = $accordion.data('allow-multiple') === true;

            // Handle header clicks
            $accordion.on('click', '.accordion-header', function (e) {
                e.preventDefault();

                const $header = $(this);
                const $section = $header.closest('.accordion-section');
                const $content = $section.find('.accordion-content');
                const isOpen = $section.hasClass('is-open');

                // If not allowing multiple, close others
                if (!allowMultiple && !isOpen) {
                    Accordion.closeAll($accordion);
                }

                // Toggle this section
                if (isOpen) {
                    Accordion.closeSection($section, $content);
                } else {
                    Accordion.openSection($section, $content);
                }
            });

            // Trigger initialized event
            $accordion.trigger('accordion:initialized', {
                allowMultiple: allowMultiple,
                sectionCount: $accordion.find('.accordion-section').length
            });
        },

        /**
         * Open a section
         */
        openSection: function ($section, $content) {
            $section.addClass('is-open');
            $content.slideDown(300, function () {
                $section.trigger('accordion:opened');
            });

            // Update ARIA
            $section.find('.accordion-header').attr('aria-expanded', 'true');
        },

        /**
         * Close a section
         */
        closeSection: function ($section, $content) {
            $section.removeClass('is-open');
            $content.slideUp(300, function () {
                $section.trigger('accordion:closed');
            });

            // Update ARIA
            $section.find('.accordion-header').attr('aria-expanded', 'false');
        },

        /**
         * Close all sections in an accordion
         */
        closeAll: function ($accordion) {
            $accordion.find('.accordion-section.is-open').each(function () {
                const $section = $(this);
                const $content = $section.find('.accordion-content');
                Accordion.closeSection($section, $content);
            });
        }
    };

    /**
     * Collapsible Handler
     */
    const Collapsible = {
        /**
         * Initialize all collapsibles
         */
        init: function () {
            $('.wp-flyout-collapsible').each(function () {
                Collapsible.initCollapsible($(this));
            });

            // Initialize on flyout open
            $(document).on('wpflyout:opened', function (e, data) {
                $(data.element).find('.wp-flyout-collapsible').each(function () {
                    if (!$(this).data('collapsible-initialized')) {
                        Collapsible.initCollapsible($(this));
                    }
                });
            });
        },

        /**
         * Initialize a single collapsible
         */
        initCollapsible: function ($collapsible) {
            if ($collapsible.data('collapsible-initialized')) {
                return;
            }

            $collapsible.data('collapsible-initialized', true);

            // Handle header click
            $collapsible.on('click', '.collapsible-header', function (e) {
                e.preventDefault();

                const $header = $(this);
                const $content = $collapsible.find('.collapsible-content');
                const isOpen = $collapsible.hasClass('is-open');

                if (isOpen) {
                    Collapsible.close($collapsible, $content);
                } else {
                    Collapsible.open($collapsible, $content);
                }
            });

            // Trigger initialized event
            $collapsible.trigger('collapsible:initialized');
        },

        /**
         * Open collapsible
         */
        open: function ($collapsible, $content) {
            $collapsible.addClass('is-open');
            $content.slideDown(300, function () {
                $collapsible.trigger('collapsible:opened');
            });

            // Update ARIA
            $collapsible.find('.collapsible-header').attr('aria-expanded', 'true');
        },

        /**
         * Close collapsible
         */
        close: function ($collapsible, $content) {
            $collapsible.removeClass('is-open');
            $content.slideUp(300, function () {
                $collapsible.trigger('collapsible:closed');
            });

            // Update ARIA
            $collapsible.find('.collapsible-header').attr('aria-expanded', 'false');
        }
    };

    // Initialize when ready
    $(function () {
        Accordion.init();
        Collapsible.init();
    });

    // Export
    window.WPFlyoutAccordion = Accordion;
    window.WPFlyoutCollapsible = Collapsible;

})(jQuery);