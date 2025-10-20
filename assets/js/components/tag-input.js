/**
 * Tag Input Component JavaScript
 *
 * Handles tag addition/removal with keyboard support.
 *
 * @package     ArrayPress\WPFlyout
 * @version     1.0.0
 */

(function ($) {
    'use strict';

    /**
     * Tag Input Handler
     */
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
            const config = {
                name: $container.data('name'),
                maxTags: parseInt($container.data('max-tags')) || 0,
                minTags: parseInt($container.data('min-tags')) || 0,
                maxLength: parseInt($container.data('max-length')) || 0,
                allowDuplicates: $container.data('allow-duplicates') === true,
                caseSensitive: $container.data('case-sensitive') === true,
                delimiter: $container.data('delimiter') || ',',
                autocomplete: $container.data('autocomplete') || []
            };

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
                if (e.key === 'Enter' || (e.key === config.delimiter && value)) {
                    e.preventDefault();
                    if (value) {
                        TagInput.addTag($container, value, config);
                        $(this).val('');
                    }
                }

                // Backspace on empty input removes last tag
                if (e.key === 'Backspace' && !value) {
                    const $tags = $container.find('.tag-item');
                    if ($tags.length > 0) {
                        TagInput.removeTag($container, $tags.last(), config);
                    }
                }

                // Escape clears input
                if (e.key === 'Escape') {
                    $(this).val('').blur();
                }
            });

            // Handle paste
            $input.on('paste', function (e) {
                e.preventDefault();
                const pastedText = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
                const tags = pastedText.split(config.delimiter).map(t => t.trim()).filter(t => t);

                tags.forEach(tag => {
                    TagInput.addTag($container, tag, config);
                });

                $(this).val('');
            });

            // Remove tag on click
            $container.on('click', '.tag-remove', function () {
                TagInput.removeTag($container, $(this).closest('.tag-item'), config);
            });

            // Handle autocomplete if available
            if (config.autocomplete.length > 0) {
                TagInput.initAutocomplete($container, $input, config);
            }

            // Check initial state
            TagInput.updateState($container, config);
        },

        /**
         * Add a tag
         */
        addTag: function ($container, value, config) {
            value = value.trim();

            // Validate
            if (!value) {
                return false;
            }

            // Check max length
            if (config.maxLength > 0 && value.length > config.maxLength) {
                TagInput.showError($container, 'Tag is too long (max ' + config.maxLength + ' characters)');
                return false;
            }

            // Check max tags
            const currentCount = $container.find('.tag-item').length;
            if (config.maxTags > 0 && currentCount >= config.maxTags) {
                TagInput.showError($container, 'Maximum ' + config.maxTags + ' tags allowed');
                return false;
            }

            // Check duplicates
            if (!config.allowDuplicates) {
                const existingTags = TagInput.getTags($container, config);
                const compareValue = config.caseSensitive ? value : value.toLowerCase();
                const exists = existingTags.some(tag => {
                    const compareTag = config.caseSensitive ? tag : tag.toLowerCase();
                    return compareTag === compareValue;
                });

                if (exists) {
                    TagInput.showError($container, 'Tag already exists');
                    return false;
                }
            }

            // Create tag element
            const $tag = $('<span class="tag-item" data-tag="' + value + '">' +
                '<span class="tag-text">' + $('<div>').text(value).html() + '</span>' +
                '<button type="button" class="tag-remove" aria-label="Remove tag">' +
                '<span class="dashicons dashicons-no-alt"></span>' +
                '</button>' +
                '</span>');

            // Create hidden input
            const $hidden = $('<input type="hidden" name="' + config.name + '[]" value="' + value + '" data-tag-value>');

            // Add to container
            $container.find('.tag-input-field').before($tag);
            $container.append($hidden);

            // Animate in
            $tag.hide().fadeIn(200);

            // Update state
            TagInput.updateState($container, config);

            // Trigger event
            $container.trigger('tag:added', {tag: value, count: currentCount + 1});

            return true;
        },

        /**
         * Remove a tag
         */
        removeTag: function ($container, $tag, config) {
            const value = $tag.data('tag');

            // Remove with animation
            $tag.fadeOut(200, function () {
                $(this).remove();

                // Remove hidden input
                $container.find('input[data-tag-value][value="' + value + '"]').remove();

                // Update state
                TagInput.updateState($container, config);

                // Trigger event
                $container.trigger('tag:removed', {
                    tag: value,
                    count: $container.find('.tag-item').length
                });
            });
        },

        /**
         * Get all current tags
         */
        getTags: function ($container, config) {
            const tags = [];
            $container.find('.tag-item').each(function () {
                tags.push($(this).data('tag'));
            });
            return tags;
        },

        /**
         * Update component state
         */
        updateState: function ($container, config) {
            const count = $container.find('.tag-item').length;

            // Max tags reached
            if (config.maxTags > 0 && count >= config.maxTags) {
                $container.addClass('max-reached');
            } else {
                $container.removeClass('max-reached');
            }

            // Min tags validation
            if (config.minTags > 0 && count < config.minTags) {
                $container.addClass('below-min');
            } else {
                $container.removeClass('below-min');
            }
        },

        /**
         * Show error message
         */
        showError: function ($container, message) {
            // Visual feedback
            $container.addClass('has-error');
            setTimeout(function () {
                $container.removeClass('has-error');
            }, 300);

            // Trigger event for custom handling
            $container.trigger('tag:error', {message: message});

            console.warn('Tag Input:', message);
        },

        /**
         * Initialize autocomplete
         */
        initAutocomplete: function ($container, $input, config) {
            const $dropdown = $container.find('.tag-autocomplete');
            let currentIndex = -1;

            $input.on('input', function () {
                const value = $(this).val().trim().toLowerCase();

                if (!value) {
                    $dropdown.hide().empty();
                    return;
                }

                // Filter suggestions
                const matches = config.autocomplete.filter(item => {
                    return item.toLowerCase().includes(value);
                });

                if (matches.length === 0) {
                    $dropdown.hide().empty();
                    return;
                }

                // Show suggestions
                $dropdown.empty();
                matches.forEach((item, index) => {
                    const $item = $('<div class="tag-autocomplete-item">' + item + '</div>');
                    $item.on('click', function () {
                        TagInput.addTag($container, item, config);
                        $input.val('');
                        $dropdown.hide();
                    });
                    $dropdown.append($item);
                });

                $dropdown.show();
                currentIndex = -1;
            });

            // Keyboard navigation in dropdown
            $input.on('keydown', function (e) {
                if (!$dropdown.is(':visible')) {
                    return;
                }

                const $items = $dropdown.find('.tag-autocomplete-item');

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    currentIndex = Math.min(currentIndex + 1, $items.length - 1);
                    $items.removeClass('selected').eq(currentIndex).addClass('selected');
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    currentIndex = Math.max(currentIndex - 1, 0);
                    $items.removeClass('selected').eq(currentIndex).addClass('selected');
                } else if (e.key === 'Enter' && currentIndex >= 0) {
                    e.preventDefault();
                    $items.eq(currentIndex).click();
                }
            });

            // Hide dropdown on blur
            $input.on('blur', function () {
                setTimeout(function () {
                    $dropdown.hide();
                }, 200);
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