/**
 * WordPress AJAX Select Component
 *
 * @package ArrayPress\WPFlyout
 * @version 1.0.0
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

            // Parse all data-ajax-* attributes
            const dataOptions = this.parseDataAttributes();

            // Merge: defaults < data attributes < passed options
            this.options = $.extend({
                placeholder: 'Type to search...',
                ajax: null,
                nonce: null,
                ajaxUrl: null,
                minLength: 3,
                delay: 300,
                initialResults: 20,
                emptyOption: null,
                value: null,
                text: null
            }, dataOptions, options);

            this.searchTimeout = null;
            this.resultsLoaded = false;
            this.init();
        }

        parseDataAttributes() {
            const attrs = {};
            const data = this.$select.data();

            Object.keys(data).forEach(key => {
                let value = data[key];
                if (value === 'true') value = true;
                else if (value === 'false') value = false;
                else if (!isNaN(value) && value !== '') value = Number(value);
                attrs[key] = value;
            });

            return attrs;
        }

        init() {
            // Auto-initialize if ajax action is specified
            if (!this.options.ajax) {
                console.warn('WPAjaxSelect: No ajax action specified for', this.$select[0]);
                return;
            }

            // Add empty option if configured and doesn't exist
            if (this.options.emptyOption !== null && this.$select.find('option[value=""]').length === 0) {
                this.$select.prepend(`<option value="">${this.options.emptyOption}</option>`);
            }

            // Store whether we need to fetch initial text
            this.needsInitialFetch = false;

            // Handle initial value
            if (this.options.value) {
                if (this.options.text) {
                    // Both value and text provided - use them directly
                    if (!this.$select.find(`option[value="${this.options.value}"]`).length) {
                        this.$select.append(`<option value="${this.options.value}">${this.options.text}</option>`);
                    }
                    this.$select.val(this.options.value);
                } else {
                    // Only value provided - need to fetch the text
                    this.$select.val(this.options.value);
                    this.needsInitialFetch = true;
                }
            }

            this.$select.hide();

            // Build UI
            this.$container = $('<div class="wp-ajax-select">');
            this.$inputWrapper = $('<div class="wp-ajax-select-input-wrapper">');
            this.$input = $('<input type="text" class="wp-ajax-select-input">');
            this.$clear = $('<span class="wp-ajax-select-clear">×</span>');
            this.$arrow = $('<span class="wp-ajax-select-arrow"><svg width="12" height="12" viewBox="0 0 12 12"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"/></svg></span>');
            this.$results = $('<div class="wp-ajax-select-results">');

            this.$input.attr('placeholder', this.options.placeholder);

            this.$inputWrapper.append(this.$input).append(this.$clear).append(this.$arrow);
            this.$container.append(this.$inputWrapper).append(this.$results);
            this.$select.after(this.$container);

            // Set initial value if exists
            const selectedOption = this.$select.find('option:selected');
            if (selectedOption.length && selectedOption.val()) {
                const text = selectedOption.text().trim();
                this.$input.val(text);
                this.$input.prop('readonly', true);
                this.$container.addClass('wp-ajax-select-has-value');
                this.$clear.show();
            } else {
                this.$clear.hide();
            }

            this.bindEvents();

            // Fetch initial text if needed (after events are bound)
            if (this.needsInitialFetch) {
                this.fetchInitialText(this.options.value);
            }
        }

        bindEvents() {
            const self = this;

            // Type to search - only if not readonly
            this.$input.on('input', function () {
                if ($(this).prop('readonly')) {
                    return;
                }

                clearTimeout(self.searchTimeout);
                const term = $(this).val();

                if (term.length === 0) {
                    // Empty input - show initial results
                    if (!self.resultsLoaded) {
                        self.loadResults('');
                    } else {
                        self.searchTimeout = setTimeout(() => {
                            self.search('');
                        }, self.options.delay);
                    }
                } else if (term.length < self.options.minLength) {
                    // Not enough characters - hide results
                    self.$results.empty().hide();
                } else {
                    // Enough characters - search
                    self.searchTimeout = setTimeout(() => {
                        self.search(term);
                    }, self.options.delay);
                }
            });

            // Click on input when readonly - focus for keyboard access
            this.$input.on('click', function () {
                if ($(this).prop('readonly')) {
                    // Keep focus for keyboard shortcuts to work
                    $(this).focus();
                }
            });

            // Clear button
            this.$clear.on('click', (e) => {
                e.stopPropagation();
                this.clear();
                this.$input.focus();
            });

            // Click arrow toggles dropdown
            this.$arrow.on('click', (e) => {
                e.stopPropagation();

                // If has value, don't open dropdown
                if (this.$container.hasClass('wp-ajax-select-has-value')) {
                    this.$input.focus();
                    return;
                }

                this.$input.focus();

                if (this.$results.is(':visible')) {
                    this.closeDropdown();
                } else {
                    if (!this.resultsLoaded) {
                        this.loadResults('');
                    } else {
                        this.openDropdown();
                    }
                }
            });

            // Focus - show initial results only if not readonly
            this.$input.on('focus', () => {
                if (this.$input.prop('readonly')) {
                    return;
                }

                if (!this.resultsLoaded) {
                    this.loadResults('');
                } else if (this.$results.children().length) {
                    this.openDropdown();
                }
            });

            // Select item
            this.$results.on('click', '.wp-ajax-select-item', (e) => {
                const $item = $(e.currentTarget);
                this.select($item.data('value'), $item.text());
            });

            // Click outside closes
            $(document).on('click', (e) => {
                if (!this.$container[0].contains(e.target)) {
                    this.closeDropdown();
                }
            });

            // Keyboard navigation
            this.$input.on('keydown', (e) => {
                // Handle special keys when readonly (has selected value)
                if (this.$input.prop('readonly')) {
                    // Only process delete keys when input is focused
                    if (document.activeElement === this.$input[0]) {
                        if (e.which === 8 || e.which === 46) { // Backspace or Delete
                            e.preventDefault();
                            this.clear();
                            return;
                        }
                        if (e.which === 27) { // Escape
                            e.preventDefault();
                            this.clear();
                            return;
                        }
                    }

                    // Allow tab navigation
                    if (e.which === 9 || e.which === 16) {
                        return;
                    }

                    // Block other keys when readonly
                    e.preventDefault();
                    return;
                }

                const $items = this.$results.find('.wp-ajax-select-item');
                const $active = this.$results.find('.active');
                let index = $items.index($active);

                switch (e.which) {
                    case 40: // Down
                        e.preventDefault();
                        if (!this.$results.is(':visible')) {
                            if (!this.resultsLoaded) {
                                this.loadResults('');
                            } else {
                                this.openDropdown();
                            }
                        }
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
                        this.closeDropdown();
                        break;
                }
            });
        }

        openDropdown() {
            this.$results.show();
            this.$container.addClass('wp-ajax-select-open');
        }

        closeDropdown() {
            this.$results.hide();
            this.$container.removeClass('wp-ajax-select-open');
        }

        getAjaxUrl() {
            return this.options.ajaxUrl || this.options.url || window.ajaxurl || '/wp-admin/admin-ajax.php';
        }

        getNonce() {
            return this.options.nonce || '';
        }

        fetchInitialText(value) {
            if (!value) return;

            const data = {
                action: this.options.ajax,
                search: '',
                initial_value: value,
                limit: 1,
                _wpnonce: this.getNonce()
            };

            // Show loading state in input
            this.$input.val('Loading...');
            this.$input.prop('readonly', true);

            $.ajax({
                url: this.getAjaxUrl(),
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        let results = response.data || {};

                        // Convert to array format if needed
                        if (!Array.isArray(results) && typeof results === 'object') {
                            results = Object.entries(results).map(([key, val]) => ({
                                value: String(key),
                                text: val
                            }));
                        }

                        // Find the matching item
                        const item = results.find(r => String(r.value) === String(value));
                        if (item) {
                            // Add option and set value
                            if (!this.$select.find(`option[value="${value}"]`).length) {
                                this.$select.append(`<option value="${value}">${item.text}</option>`);
                            }
                            this.$select.val(value);

                            // Update input
                            this.$input.val(item.text);
                            this.$input.prop('readonly', true);
                            this.$container.addClass('wp-ajax-select-has-value');
                            this.$clear.show();
                        } else {
                            // Item not found - clear the field
                            this.$input.val('');
                            this.$input.prop('readonly', false);
                            this.$input.attr('placeholder', this.options.placeholder);
                        }
                    }
                },
                error: () => {
                    // Error fetching - clear the field
                    this.$input.val('');
                    this.$input.prop('readonly', false);
                    this.$input.attr('placeholder', this.options.placeholder);
                }
            });
        }

        loadResults(term) {
            this.resultsLoaded = true;
            this.search(term);
        }

        search(term) {
            if (!this.options.ajax) return;

            const data = {
                action: this.options.ajax,
                search: term,
                limit: term ? 0 : this.options.initialResults
            };

            // Add nonce if available
            const nonce = this.getNonce();
            if (nonce) {
                data._wpnonce = nonce;
            }

            // Show loading state
            this.$results.html('<div class="wp-ajax-select-loading">Loading...</div>');
            this.openDropdown();

            $.ajax({
                url: this.getAjaxUrl(),
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        let results = response.data || {};

                        // Convert simple key/value object to array format
                        if (!Array.isArray(results) && typeof results === 'object') {
                            results = Object.entries(results).map(([key, value]) => ({
                                value: String(key),
                                text: value
                            }));
                        }

                        this.showResults(results);
                    } else {
                        this.showResults([]);
                    }
                },
                error: () => {
                    this.showResults([]);
                }
            });
        }

        showResults(results) {
            this.$results.empty();

            if (!results.length) {
                this.$results.append('<div class="wp-ajax-select-empty">No results found</div>');
            } else {
                results.forEach(item => {
                    $('<div class="wp-ajax-select-item">')
                        .text(item.text)
                        .attr('data-value', item.value)
                        .appendTo(this.$results);
                });
            }

            this.openDropdown();
        }

        select(value, text) {
            // Add option if it doesn't exist
            if (!this.$select.find(`option[value="${value}"]`).length) {
                this.$select.append(`<option value="${value}">${text}</option>`);
            }

            this.$select.val(value).trigger('change');
            this.$input.val(text);

            // Make input read-only when value selected
            this.$input.prop('readonly', true);
            this.$container.addClass('wp-ajax-select-has-value');
            this.$clear.show();

            this.closeDropdown();
        }

        val(value) {
            if (value === undefined) {
                return this.$select.val();
            }

            const $option = this.$select.find(`option[value="${value}"]`);
            if ($option.length) {
                this.$select.val(value).trigger('change');
                this.$input.val($option.text());
                this.$input.prop('readonly', true);
                this.$container.addClass('wp-ajax-select-has-value');
                this.$clear.show();
            }
            return this;
        }

        clear() {
            this.$select.val('').trigger('change');
            this.$input.val('');

            // Remove read-only when cleared
            this.$input.prop('readonly', false);
            this.$container.removeClass('wp-ajax-select-has-value');
            this.$clear.hide();

            // Reset placeholder
            this.$input.attr('placeholder', this.options.placeholder);

            // Clear results but don't mark as not loaded
            this.$results.empty().hide();
            this.resultsLoaded = false;
        }

        destroy() {
            this.$container.remove();
            this.$select.show();
            this.$select.removeData('wpAjaxSelectInitialized');
        }
    }

    // jQuery plugin
    $.fn.wpAjaxSelect = function (options) {
        return this.each(function () {
            const instance = new WPAjaxSelect(this, options);
            $(this).data('wpAjaxSelect', instance);
        });
    };

    // Initialize on document ready
    $(document).ready(function () {
        $('[data-ajax]').wpAjaxSelect();
    });

    // Initialize when flyouts open
    $(document).on('wpflyout:opened', function (e, data) {
        // Find any uninitialized ajax selects in the flyout
        $(data.element).find('select[data-ajax]').each(function () {
            if (!$(this).data('wpAjaxSelectInitialized')) {
                new WPAjaxSelect(this);
            }
        });
    });

    // Export to window for manual initialization
    window.WPAjaxSelect = WPAjaxSelect;

})(jQuery);