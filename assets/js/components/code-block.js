/**
 * WP Flyout Code Block Component JavaScript
 *
 * Handles copy to clipboard functionality.
 *
 * @package     ArrayPress\WPFlyout
 * @version     1.0.0
 */
(function($) {
    'use strict';

    $(document).on('click', '.code-block-copy', function(e) {
        e.preventDefault();

        const $button = $(this);
        const $code = $button.siblings('.code-block-pre').find('.code-block-code');
        const text = $code.text();

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
            });
        } else {
            // Fallback method
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();

            // Show success
            $button.addClass('copied');
            $button.find('.copy-text').text('Copied!');

            setTimeout(() => {
                $button.removeClass('copied');
                $button.find('.copy-text').text('Copy');
            }, 2000);
        }
    });

})(jQuery);