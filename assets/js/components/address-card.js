/**
 * WP Flyout Address Card Component JavaScript
 *
 * Handles copy to clipboard functionality for address cards.
 *
 * @package     ArrayPress\WPFlyout
 * @version     1.0.0
 */

(function ($) {
    'use strict';

    /**
     * Address Card Handler
     */
    const AddressCard = {
        /**
         * Initialize all address cards
         */
        init: function () {
            // Bind copy action
            $(document).on('click', '.wp-flyout-address-card [data-action="copy-address"]', function (e) {
                e.preventDefault();
                AddressCard.handleCopy($(this));
            });

            // Initialize on flyout open
            $(document).on('wpflyout:opened', function (e, data) {
                // Nothing specific needed on open for address cards
                // But we could initialize something if needed
            });
        },

        /**
         * Handle copy address action
         */
        handleCopy: function ($button) {
            const $card = $button.closest('.wp-flyout-address-card');
            const $content = $card.find('.address-content');

            // Get the text content, removing extra whitespace
            let text = $content.text()
                .replace(/\s+/g, ' ')
                .trim();

            // Copy to clipboard
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    AddressCard.showCopySuccess($button);
                }).catch(() => {
                    // Fallback if promise fails
                    AddressCard.fallbackCopy(text, $button);
                });
            } else {
                // Fallback method for older browsers
                AddressCard.fallbackCopy(text, $button);
            }
        },

        /**
         * Show copy success feedback
         */
        showCopySuccess: function ($button) {
            const originalText = $button.text().trim();
            const originalHtml = $button.html();

            // Update button text
            $button.html('<span class="dashicons dashicons-yes"></span> Copied!');
            $button.css('color', '#00a32a');

            // Reset after 2 seconds
            setTimeout(() => {
                $button.html(originalHtml);
                $button.css('color', '');
            }, 2000);
        },

        /**
         * Fallback copy method using textarea
         */
        fallbackCopy: function (text, $button) {
            const $temp = $('<textarea>');
            $temp.css({
                position: 'fixed',
                left: '-9999px',
                top: '0'
            });
            $('body').append($temp);
            $temp.val(text).select();

            try {
                document.execCommand('copy');
                AddressCard.showCopySuccess($button);
            } catch (err) {
                console.error('Failed to copy address:', err);
                // Show error feedback
                const originalHtml = $button.html();
                $button.html('<span class="dashicons dashicons-no"></span> Failed');
                $button.css('color', '#d63638');

                setTimeout(() => {
                    $button.html(originalHtml);
                    $button.css('color', '');
                }, 2000);
            }

            $temp.remove();
        }
    };

    // Initialize when ready
    $(function () {
        AddressCard.init();
    });

    // Export
    window.WPFlyoutAddressCard = AddressCard;

})(jQuery);