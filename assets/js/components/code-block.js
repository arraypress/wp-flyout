/**
 * WP Flyout Code Block Component JavaScript
 *
 * Handles copy to clipboard functionality.
 *
 * @package     ArrayPress\WPFlyout
 * @version     1.0.0
 */

(function ($) {
    'use strict';

    // Copy to clipboard functionality
    $(document).on('click', '.code-block-copy', function (e) {
        e.preventDefault();

        const $button = $(this);
        const $code = $button.siblings('.code-block-pre').find('.code-block-code');

        // Get the text content
        const text = $code[0].textContent || $code[0].innerText;

        // Copy to clipboard
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                // Show success
                $button.addClass('copied');
                $button.find('.copy-text').text('Copied!');

                // Reset after 2 seconds
                setTimeout(() => {
                    $button.removeClass('copied');
                    $button.find('.copy-text').text('Copy');
                }, 2000);
            }).catch(() => {
                // Fallback if promise fails
                fallbackCopy(text, $button);
            });
        } else {
            // Fallback method for older browsers
            fallbackCopy(text, $button);
        }
    });

    /**
     * Fallback copy method using textarea
     *
     * @param {string} text Text to copy
     * @param {jQuery} $button Button element
     */
    function fallbackCopy(text, $button) {
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

            // Show success
            $button.addClass('copied');
            $button.find('.copy-text').text('Copied!');

            setTimeout(() => {
                $button.removeClass('copied');
                $button.find('.copy-text').text('Copy');
            }, 2000);
        } catch (err) {
            console.error('Failed to copy text:', err);
        }

        $temp.remove();
    }

})(jQuery);