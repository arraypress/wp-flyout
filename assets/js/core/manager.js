/**
 * Simple WP Flyout Manager - No BS version
 * Just make flyouts work with AJAX, period.
 */
(function($) {
    'use strict';

    $(document).ready(function() {

        // Handle all flyout triggers
        $(document).on('click', '.wp-flyout-trigger', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const handler = $btn.data('flyout-handler');
            const manager = $btn.data('flyout-manager');
            const nonce = $btn.data('flyout-nonce');

            // Collect data attributes
            const data = {};
            $.each(this.dataset, function(key, value) {
                if (key !== 'flyoutHandler' && key !== 'flyoutManager' && key !== 'flyoutNonce') {
                    data[key] = value;
                }
            });

            // Show loading
            $btn.prop('disabled', true);
            const originalText = $btn.text();
            $btn.text('Loading...');

            // Load flyout content
            $.post(ajaxurl, {
                action: 'wp_flyout_' + manager,
                handler: handler,
                handler_action: 'load',
                nonce: nonce,
                ...data
            })
                .done(function(response) {
                    $btn.prop('disabled', false).text(originalText);

                    if (response.success && response.data.html) {
                        // Add flyout HTML to page
                        $('.wp-flyout').remove(); // Remove any existing
                        $('body').append(response.data.html);

                        // Find the flyout ID from the HTML
                        const $flyout = $('.wp-flyout').last();
                        const flyoutId = $flyout.attr('id');

                        // Open it
                        WPFlyout.open(flyoutId);

                        // Bind save button
                        $flyout.on('click', '.wp-flyout-save', function() {
                            const $form = $flyout.find('form').first();
                            if ($form.length) {
                                const formData = $form.serialize();

                                $.post(ajaxurl, {
                                    action: 'wp_flyout_' + manager,
                                    handler: handler,
                                    handler_action: 'save',
                                    nonce: nonce,
                                    form_data: formData,
                                    ...data
                                })
                                    .done(function(saveResponse) {
                                        if (saveResponse.success) {
                                            // Show success message
                                            alert(saveResponse.data.message || 'Saved!');

                                            // Close flyout
                                            WPFlyout.close(flyoutId);

                                            // Reload page if needed
                                            if (saveResponse.data.reload) {
                                                location.reload();
                                            }
                                        } else {
                                            alert(saveResponse.data || 'Save failed');
                                        }
                                    })
                                    .fail(function() {
                                        alert('Save failed');
                                    });
                            }
                        });

                    } else {
                        alert(response.data || 'Failed to load');
                    }
                })
                .fail(function() {
                    $btn.prop('disabled', false).text(originalText);
                    alert('Failed to load flyout');
                });
        });

    });

})(jQuery);