/**
 * WordPress AJAX Select - Simple Arrays Only
 */

(function ($) {
    'use strict';

    class WPAjaxSelect {
        constructor(element, options = {}) {
            this.$select = $(element);

            // Parse all data-ajax-* attributes
            const dataOptions = this.parseDataAttributes();

            // Merge: defaults < data attributes < passed options
            this.options = $.extend({
                placeholder: 'Type to search...',
                ajax: null,
                nonce: null,
                ajaxUrl: null,
                minLength: 3,  // Changed to 3 - prevents unnecessary initial loads
                delay: 300,
                initialResults: 20,
                emptyOption: null,
                value: null,  // Initial value
                text: null    // Initial text
            }, dataOptions, options);

            this.searchTimeout = null;
            this.resultsLoaded = false;
            this.init();
        }

        parseDataAttributes() {
            const attrs = {};
            const data = this.$select.data();

            // Map data attributes to options
            // data-ajax="action" becomes ajax: "action"
            // data-placeholder="..." becomes placeholder: "..."
            Object.keys(data).forEach(key => {
                // Convert to camelCase and handle values
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
            if (!this.options.ajax) return;

            // Add empty option if configured and doesn't exist
            if (this.options.emptyOption !== null && this.$select.find('option[value=""]').length === 0) {
                this.$select.prepend(`<option value="">${this.options.emptyOption}</option>`);
            }

            // Handle initial value
            if (this.options.value) {
                if (this.options.text) {
                    // Both value and text provided - use them
                    if (!this.$select.find(`option[value="${this.options.value}"]`).length) {
                        this.$select.append(`<option value="${this.options.value}">${this.options.text}</option>`);
                    }
                    this.$select.val(this.options.value);
                } else {
                    // Only value provided - fetch the text
                    this.$select.val(this.options.value);
                    this.fetchInitialText(this.options.value);
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
                this.$clear.show();
            } else {
                this.$clear.hide();
            }

            this.bindEvents();
        }

        bindEvents() {
            // Type to search
            this.$input.on('input', () => {
                clearTimeout(this.searchTimeout);
                const term = this.$input.val();

                if (term.length === 0) {
                    // Empty input - show initial results
                    if (!this.resultsLoaded) {
                        this.loadResults('');
                    } else {
                        this.searchTimeout = setTimeout(() => {
                            this.search('');
                        }, this.options.delay);
                    }
                } else if (term.length < this.options.minLength) {
                    // Not enough characters - hide results
                    this.$results.empty().hide();

                } else {
                    // Enough characters - search
                    this.searchTimeout = setTimeout(() => {
                        this.search(term);
                    }, this.options.delay);
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

            // Focus - show initial results
            this.$input.on('focus', () => {
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

            // Keyboard nav
            this.$input.on('keydown', (e) => {
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
            // Priority: options > data attribute > globals
            return this.options.nonce || '';
        }

        fetchInitialText(value) {
            // Fetch text for the initial value
            const data = {
                action: this.options.ajax,
                search: '',  // Empty search
                initial_value: value,  // Pass the specific value to fetch
                limit: 1,
                _wpnonce: this.getNonce()
            };

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
                                value: key,
                                text: val
                            }));
                        }

                        // Find the matching item
                        const item = results.find(r => r.value === value);
                        if (item) {
                            // Add option and set value
                            if (!this.$select.find(`option[value="${value}"]`).length) {
                                this.$select.append(`<option value="${value}">${item.text}</option>`);
                            }
                            this.$select.val(value);

                            // Update input if it exists
                            if (this.$input) {
                                this.$input.val(item.text);
                                this.$clear.show();
                            }
                        }
                    }
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
                                value: key,
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
                this.$clear.show();
            }
            return this;
        }

        clear() {
            this.$select.val('').trigger('change');
            this.$input.val('');
            this.$clear.hide();
            this.resultsLoaded = false;
        }

        destroy() {
            this.$container.remove();
            this.$select.show();
        }
    }

    // jQuery plugin
    $.fn.wpAjaxSelect = function (options) {
        return this.each(function () {
            const instance = new WPAjaxSelect(this, options);
            $(this).data('wpAjaxSelect', instance);
        });
    };

    // Auto-initialize on document ready
    $(document).ready(function () {
        $('[data-ajax]').wpAjaxSelect();
    });

})(jQuery);