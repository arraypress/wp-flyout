/**
 * WP Flyout Range Slider Component JavaScript
 *
 * Handles dual-handle range slider interactions and value synchronization.
 *
 * @package     ArrayPress\WPFlyout
 * @version     1.0.0
 */

(function ($) {
    'use strict';

    /**
     * Range Slider Handler
     */
    const RangeSlider = {
        /**
         * Initialize all range sliders
         */
        init: function () {
            $('.wp-flyout-range-slider').each(function () {
                RangeSlider.initSlider($(this));
            });

            // Initialize on flyout open
            $(document).on('wpflyout:opened', function (e, data) {
                $(data.element).find('.wp-flyout-range-slider').each(function () {
                    RangeSlider.initSlider($(this));
                });
            });
        },

        /**
         * Initialize a single slider
         */
        initSlider: function ($container) {
            const $minSlider = $container.find('.range-slider-min');
            const $maxSlider = $container.find('.range-slider-max');
            const $minInput = $container.find('.range-input-min');
            const $maxInput = $container.find('.range-input-max');
            const $minDisplay = $container.find('.range-display-min');
            const $maxDisplay = $container.find('.range-display-max');
            const $fill = $container.find('.range-slider-fill');

            const min = parseFloat($container.data('min'));
            const max = parseFloat($container.data('max'));
            const prefix = $container.data('prefix') || '';
            const suffix = $container.data('suffix') || '';

            // Update fill position
            const updateFill = () => {
                const minVal = parseFloat($minSlider.val());
                const maxVal = parseFloat($maxSlider.val());
                const minPercent = ((minVal - min) / (max - min)) * 100;
                const maxPercent = ((maxVal - min) / (max - min)) * 100;

                $fill.css({
                    'left': minPercent + '%',
                    'right': (100 - maxPercent) + '%'
                });
            };

            // Format display value
            const formatValue = (value) => {
                return prefix + value + suffix;
            };

            // Handle min slider
            $minSlider.on('input', function () {
                let minVal = parseFloat($(this).val());
                let maxVal = parseFloat($maxSlider.val());

                if (minVal > maxVal) {
                    $(this).val(maxVal);
                    minVal = maxVal;
                }

                if ($minInput.length) {
                    $minInput.val(minVal);
                }
                if ($minDisplay.length) {
                    $minDisplay.text(formatValue(minVal));
                }

                updateFill();
            });

            // Handle max slider
            $maxSlider.on('input', function () {
                let maxVal = parseFloat($(this).val());
                let minVal = parseFloat($minSlider.val());

                if (maxVal < minVal) {
                    $(this).val(minVal);
                    maxVal = minVal;
                }

                if ($maxInput.length) {
                    $maxInput.val(maxVal);
                }
                if ($maxDisplay.length) {
                    $maxDisplay.text(formatValue(maxVal));
                }

                updateFill();
            });

            // Handle min input
            $minInput.on('change', function () {
                let minVal = parseFloat($(this).val());
                let maxVal = parseFloat($maxInput.val() || $maxSlider.val());

                // Clamp to range
                minVal = Math.max(min, Math.min(max, minVal));

                // Don't exceed max
                if (minVal > maxVal) {
                    minVal = maxVal;
                }

                $(this).val(minVal);
                $minSlider.val(minVal);
                updateFill();
            });

            // Handle max input
            $maxInput.on('change', function () {
                let maxVal = parseFloat($(this).val());
                let minVal = parseFloat($minInput.val() || $minSlider.val());

                // Clamp to range
                maxVal = Math.max(min, Math.min(max, maxVal));

                // Don't go below min
                if (maxVal < minVal) {
                    maxVal = minVal;
                }

                $(this).val(maxVal);
                $maxSlider.val(maxVal);
                updateFill();
            });

            // Initial fill update
            updateFill();
        }
    };

    // Initialize when ready
    $(function () {
        RangeSlider.init();
    });

    // Export
    window.WPFlyoutRangeSlider = RangeSlider;

})(jQuery);