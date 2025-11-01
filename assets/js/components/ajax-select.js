/**
 * WordPress AJAX Select Component - Simplified
 *
 * Dynamic select dropdown with AJAX search functionality
 *
 * @package ArrayPress\WPFlyout
 * @version 2.0.0
 */
(function ($) {
    'use strict';

    class WPAjaxSelect {
        constructor(element, options = {}) {
            this.$select = $(element);

            // Skip if already initialized
            if (this.$select.data('wpAjaxSelectInitialized')) {
                return;
            }

            this.$select.data('wpAjaxSelectInitialized', true);

            // Parse data attributes
            const dataOptions = {};
            $.each(this.$select.data(), (key, value) => {
                dataOptions[key] = value;
            });

            // Merge options
            this.options = $.extend({
                placeholder: 'Type to search...',
                ajax: null,
                nonce: null,
                minLength: 2,
                delay: 300
            }, dataOptions, options);

            this.searchTimeout = null;
            this.init();
        }

        init() {
            // Must have ajax action
            if (!this.options.ajax) {
                console.warn('WPAjaxSelect: No ajax action specified');
                return;
            }

            this.$select.hide();

            // Build UI
            this.$container = $('<div class="wp-ajax-select">');
            this.$input = $('<input type="text" class="regular-text">');
            this.$clear = $('<button type="button" class="button-link wp-ajax-select-clear" style="display:none">×</button>');
            this.$results = $('<div class="wp-ajax-select-results" style="display:none">');

            this.$input.attr('placeholder', this.options.placeholder);

            this.$container
                .append(this.$input)
                .append(this.$clear)
                .append(this.$results);

            this.$select.after(this.$container);

            // Handle initial value - NO AJAX if we have the text!
            const $selected = this.$select.find('option:selected');
            if ($selected.length && $selected.val()) {
                this.setSelected($selected.val(), $selected.text());
            }

            this.bindEvents();
        }

        bindEvents() {
            const self = this;

            // Type to search
            this.$input.on('input', function () {
                // Skip if readonly (has value)
                if ($(this).prop('readonly')) return;

                clearTimeout(self.searchTimeout);
                const term = $(this).val();

                if (term.length < self.options.minLength) {
                    self.$results.hide();
                    return;
                }

                self.searchTimeout = setTimeout(() => {
                    self.search(term);
                }, self.options.delay);
            });

            // Clear button
            this.$clear.on('click', () => {
                this.clear();
                this.$input.focus();
            });

            // Select item
            this.$results.on('click', '.wp-ajax-select-item', (e) => {
                const $item = $(e.currentTarget);
                this.select($item.data('value'), $item.text());
            });

            // Click outside closes
            $(document).on('click', (e) => {
                if (!this.$container[0].contains(e.target)) {
                    this.$results.hide();
                }
            });

            // Keyboard navigation
            this.$input.on('keydown', (e) => {
                // Delete/Escape to clear when readonly
                if (this.$input.prop('readonly')) {
                    if (e.which === 8 || e.which === 46 || e.which === 27) {
                        e.preventDefault();
                        this.clear();
                    }
                    return;
                }

                const $items = this.$results.find('.wp-ajax-select-item');
                const $active = this.$results.find('.active');
                let index = $items.index($active);

                switch (e.which) {
                    case 40: // Down
                        e.preventDefault();
                        if ($items.length) {
                            index = (index + 1) % $items.length;
                            $items.removeClass('active').eq(index).addClass('active');
                        }
                        break;
                    case 38: // Up
                        e.preventDefault();
                        if ($items.length) {
                            index = index <= 0 ? $items.length - 1 : index - 1;
                            $items.removeClass('active').eq(index).addClass('active');
                        }
                        break;
                    case 13: // Enter
                        if ($active.length) {
                            e.preventDefault();
                            this.select($active.data('value'), $active.text());
                        }
                        break;
                    case 27: // Escape
                        this.$results.hide();
                        break;
                }
            });
        }

        search(term) {
            const data = {
                action: this.options.ajax,
                search: term,
                _wpnonce: this.options.nonce || ''
            };

            // Show loading
            this.$results.html('<div class="wp-ajax-select-loading">Loading...</div>').show();

            $.ajax({
                url: window.ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success && response.data) {
                        this.showResults(response.data);
                    } else {
                        this.$results.html('<div class="wp-ajax-select-empty">No results found</div>');
                    }
                },
                error: () => {
                    this.$results.html('<div class="wp-ajax-select-empty">Error loading results</div>');
                }
            });
        }

        showResults(results) {
            this.$results.empty();

            // Handle both array and object formats
            if (!Array.isArray(results)) {
                results = Object.entries(results).map(([value, text]) => ({
                    value: String(value),
                    text: String(text)
                }));
            }

            if (!results.length) {
                this.$results.html('<div class="wp-ajax-select-empty">No results found</div>');
            } else {
                results.forEach(item => {
                    $('<div class="wp-ajax-select-item">')
                        .text(item.text)
                        .attr('data-value', item.value)
                        .appendTo(this.$results);
                });
            }

            this.$results.show();
        }

        select(value, text) {
            // Add option if it doesn't exist
            if (!this.$select.find(`option[value="${value}"]`).length) {
                this.$select.append(`<option value="${value}">${text}</option>`);
            }

            this.$select.val(value).trigger('change');
            this.setSelected(value, text);
            this.$results.hide();
        }

        setSelected(value, text) {
            this.$input.val(text).prop('readonly', true);
            this.$clear.show();
        }

        clear() {
            this.$select.val('').trigger('change');
            this.$input.val('').prop('readonly', false);
            this.$clear.hide();
            this.$results.hide();
        }

        // Public method to set value programmatically
        val(value, text) {
            if (value === undefined) {
                return this.$select.val();
            }

            if (text) {
                // We have both - no need for AJAX
                this.select(value, text);
            } else {
                // Only have value - would need AJAX
                const $option = this.$select.find(`option[value="${value}"]`);
                if ($option.length) {
                    this.select(value, $option.text());
                }
            }

            return this;
        }
    }

    // jQuery plugin
    $.fn.wpAjaxSelect = function (options) {
        return this.each(function () {
            const instance = new WPAjaxSelect(this, options);
            $(this).data('wpAjaxSelect', instance);
        });
    };

    // Auto-initialize on ready
    $(document).ready(function () {
        $('[data-ajax]').wpAjaxSelect();
    });

    // Initialize in flyouts
    $(document).on('wpflyout:opened', function (e, data) {
        $(data.element).find('select[data-ajax]').each(function () {
            if (!$(this).data('wpAjaxSelectInitialized')) {
                new WPAjaxSelect(this);
            }
        });
    });

    // Export
    window.WPAjaxSelect = WPAjaxSelect;

})(jQuery);