/**
 * Tag Input Component - Simplified
 *
 * Handles tag addition/removal with keyboard support.
 * No duplicates allowed, no min/max limits.
 *
 * @package     ArrayPress\WPFlyout
 * @version     2.0.0
 */

(function ($) {
    'use strict';

    const TagInput = {
        /**
         * Initialize all tag inputs
         */
        init: function () {
            $('.wp-flyout-tag-input').each(function () {
                TagInput.initInput($(this));
            });

            // Initialize on flyout open
            $(document).on('wpflyout:opened', function (e, data) {
                $(data.element).find('.wp-flyout-tag-input').each(function () {
                    if (!$(this).data('tag-input-initialized')) {
                        TagInput.initInput($(this));
                    }
                });
            });
        },

        /**
         * Initialize a single tag input
         */
        initInput: function ($container) {
            if ($container.data('tag-input-initialized')) {
                return;
            }

            $container.data('tag-input-initialized', true);

            const $input = $container.find('.tag-input-field');
            const name = $container.data('name') || 'tags';

            // Click on container focuses input
            $container.find('.tag-input-container').on('click', function (e) {
                if (e.target === this) {
                    $input.focus();
                }
            });

            // Handle input keydown
            $input.on('keydown', function (e) {
                const value = $(this).val().trim();

                // Enter or comma to add tag
                if ((e.key === 'Enter' || e.key === ',') && value) {
                    e.preventDefault();
                    TagInput.addTag($container, value, name);
                    $(this).val('');
                }

                // Backspace on empty input removes last tag
                if (e.key === 'Backspace' && !value) {
                    const $lastTag = $container.find('.tag-item').last();
                    if ($lastTag.length) {
                        TagInput.removeTag($container, $lastTag);
                    }
                }

                // Escape clears input
                if (e.key === 'Escape') {
                    $(this).val('').blur();
                }
            });

            // Handle paste - split by commas
            $input.on('paste', function (e) {
                e.preventDefault();
                const pastedText = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
                const tags = pastedText.split(',').map(t => t.trim()).filter(t => t);

                tags.forEach(tag => {
                    TagInput.addTag($container, tag, name);
                });

                $(this).val('');
            });

            // Remove tag on click
            $container.on('click', '.tag-remove', function () {
                TagInput.removeTag($container, $(this).closest('.tag-item'));
            });
        },

        /**
         * Add a tag
         */
        addTag: function ($container, value, name) {
            value = value.trim();

            if (!value) {
                return false;
            }

            // Check for duplicates (case-insensitive)
            const exists = $container.find('.tag-item').filter(function() {
                return $(this).data('tag').toLowerCase() === value.toLowerCase();
            }).length > 0;

            if (exists) {
                // Visual feedback
                $container.find('.tag-input-field').addClass('error');
                setTimeout(function () {
                    $container.find('.tag-input-field').removeClass('error');
                }, 300);
                return false;
            }

            // Create tag element
            const $tag = $('<span class="tag-item" data-tag="' + value + '">' +
                '<span class="tag-text">' + $('<div>').text(value).html() + '</span>' +
                '<button type="button" class="tag-remove" aria-label="Remove">' +
                '<span class="dashicons dashicons-no-alt"></span>' +
                '</button>' +
                '</span>');

            // Create hidden input
            const $hidden = $('<input type="hidden" name="' + name + '[]" value="' + value + '">');

            // Add to container
            $container.find('.tag-input-field').before($tag);
            $container.append($hidden);

            // Animate in
            $tag.hide().fadeIn(200);

            return true;
        },

        /**
         * Remove a tag
         */
        removeTag: function ($container, $tag) {
            const value = $tag.data('tag');

            // Remove with animation
            $tag.fadeOut(200, function () {
                $(this).remove();
                // Remove corresponding hidden input
                $container.find('input[type="hidden"][value="' + value + '"]').remove();
            });
        }
    };

    // Initialize when ready
    $(function () {
        TagInput.init();
    });

    // Export
    window.WPFlyoutTagInput = TagInput;

})(jQuery);