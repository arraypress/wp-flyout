/**
 * WP Flyout Order Items Component
 *
 * Manages order line items with AJAX product search, quantity management,
 * dynamic pricing calculations, and full CRUD operations with event hooks.
 *
 * @package WPFlyout
 * @version 2.0.0
 * @requires WPAjaxSelect
 */
(function ($) {
    'use strict';

    /**
     * Order Items component controller
     *
     * @namespace OrderItems
     */
    const OrderItems = {

        /**
         * Initialize the Order Items component
         *
         * Sets up event listeners for add/remove/quantity actions and
         * initializes components when flyouts open or on page load.
         *
         * @since 1.0.0
         * @return {void}
         */
        init: function () {
            const self = this;

            // Use delegation for dynamic content
            $(document)
                .on('click', '.wp-flyout-order-items [data-action="add-product"]', function (e) {
                    self.handleAdd(e);
                })
                .on('click', '.wp-flyout-order-items [data-action="remove-item"]', function (e) {
                    self.handleRemove(e);
                })
                .on('change', '.wp-flyout-order-items [data-action="update-quantity"]', function (e) {
                    self.handleQuantityChange(e);
                })
                .on('wpflyout:opened', function (e, data) {
                    self.initComponent($(data.element));
                });

            // Initialize existing components on page load
            $(function () {
                $('.wp-flyout-order-items').each(function () {
                    self.initComponent($(this).parent());
                });
            });
        },

        /**
         * Initialize Order Items component instance
         *
         * Sets up AJAX select for product search and calculates
         * initial totals for existing items.
         *
         * @since 1.0.0
         * @param {jQuery} $container - Container element to search within
         * @fires orderitems:initialized
         * @return {void}
         */
        initComponent: function ($container) {
            const self = this;

            $container.find('.wp-flyout-order-items').each(function () {
                const $component = $(this);
                const $select = $component.find('.product-ajax-select');

                // Initialize AJAX select if present and not already initialized
                if ($select.length && !$select.data('wpAjaxSelectInitialized')) {
                    const ajaxSelect = new WPAjaxSelect($select[0]);
                    $select.data('wpAjaxSelect', ajaxSelect);
                }

                // Calculate totals for edit mode
                if ($component.data('mode') === 'edit') {
                    self.recalculateTotals($component);
                }

                // Trigger initialization event
                $component.trigger('orderitems:initialized', {
                    component: $component[0],
                    itemCount: $component.find('.order-item').length
                });
            });
        },

        /**
         * Handle add product action
         *
         * Validates selection, checks limits, handles duplicates,
         * and fetches product details via AJAX.
         *
         * @since 1.0.0
         * @param {jQuery.Event} e - Click event
         * @fires orderitems:beforeadd
         * @return {void}
         */
        handleAdd: function (e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $component = $button.closest('.wp-flyout-order-items');
            const $select = $component.find('.product-ajax-select');
            const productId = $select.val();

            // Validate selection
            if (!productId) {
                $component.trigger('orderitems:error', {
                    type: 'no_selection',
                    message: 'Please select a product first'
                });
                alert('Please select a product first');
                return;
            }

            // Check max items limit
            const maxItems = parseInt($component.data('max-items')) || 0;
            const currentCount = $component.find('.order-item').length;

            if (maxItems > 0 && currentCount >= maxItems) {
                $component.trigger('orderitems:maxreached', {
                    max: maxItems,
                    current: currentCount
                });
                alert('Maximum ' + maxItems + ' items allowed');
                return;
            }

            // Fire before add event (allows cancellation)
            const beforeAddEvent = $.Event('orderitems:beforeadd');
            $component.trigger(beforeAddEvent, {
                productId: productId,
                currentCount: currentCount
            });

            if (beforeAddEvent.isDefaultPrevented()) {
                return;
            }

            // Check for existing item
            const existingItem = this.findExistingItem($component, productId);
            if (existingItem.length) {
                // Increment quantity instead of adding duplicate
                const $qtyInput = existingItem.find('.quantity-input');
                const currentQty = parseInt($qtyInput.val()) || 1;
                const newQty = currentQty + 1;

                $qtyInput.val(newQty).trigger('change');
                this.clearAjaxSelect($select);

                $component.trigger('orderitems:quantityincremented', {
                    productId: productId,
                    oldQuantity: currentQty,
                    newQuantity: newQty,
                    row: existingItem[0]
                });
                return;
            }

            // Fetch product details
            this.fetchProductDetails($component, productId);
        },

        /**
         * Fetch product details via AJAX
         *
         * Retrieves full product information from server and adds
         * to order table on success.
         *
         * @since 1.0.0
         * @param {jQuery} $component - Order items component
         * @param {string} productId - Product identifier
         * @fires orderitems:fetchstart
         * @fires orderitems:fetchsuccess
         * @fires orderitems:fetcherror
         * @return {void}
         */
        fetchProductDetails: function ($component, productId) {
            const self = this;
            const $button = $component.find('[data-action="add-product"]');
            const originalHtml = $button.html();

            // Show loading state
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Loading...');

            // Get configuration
            const ajaxUrl = window.ajaxurl || '/wp-admin/admin-ajax.php';
            const config = window.wpFlyoutConfig?.components?.orderItems || {};

            // Trigger fetch start event
            $component.trigger('orderitems:fetchstart', {
                productId: productId
            });

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: config.action || 'get_product_details',
                    product_id: String(productId),
                    _wpnonce: config.nonce || ''
                },
                success: function (response) {
                    if (response.success && response.data) {
                        self.addItemToTable($component, response.data);
                        self.clearAjaxSelect($component.find('.product-ajax-select'));

                        $component.trigger('orderitems:fetchsuccess', {
                            productId: productId,
                            product: response.data
                        });
                    } else {
                        const errorMsg = response.data || 'Product details not found';

                        $component.trigger('orderitems:fetcherror', {
                            productId: productId,
                            error: errorMsg
                        });

                        alert('Error: ' + errorMsg);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);

                    $component.trigger('orderitems:fetcherror', {
                        productId: productId,
                        error: error,
                        xhr: xhr
                    });

                    alert('Error loading product details: ' + error);
                },
                complete: function () {
                    $button.prop('disabled', false).html(originalHtml);
                }
            });
        },

        /**
         * Clear AJAX select input
         *
         * Resets the product selection field using the AJAX select API
         * or manual DOM manipulation as fallback.
         *
         * @since 1.0.0
         * @param {jQuery} $select - Select element to clear
         * @return {void}
         */
        clearAjaxSelect: function ($select) {
            const instance = $select.data('wpAjaxSelect');

            if (instance && instance.clear) {
                instance.clear();
            } else {
                // Manual fallback
                const $wrapper = $select.next('.wp-ajax-select');
                if ($wrapper.length) {
                    $wrapper.find('.wp-ajax-select-input').val('').prop('readonly', false);
                    $wrapper.find('.wp-ajax-select-clear').hide();
                    $wrapper.removeClass('wp-ajax-select-has-value');
                }
                $select.val('').trigger('change');
            }
        },

        /**
         * Find existing order item by product ID
         *
         * Searches for an order item that matches the given product ID
         * to prevent duplicates.
         *
         * @since 1.0.0
         * @param {jQuery} $component - Order items component
         * @param {string} productId - Product identifier to find
         * @return {jQuery} Matching row or empty jQuery object
         */
        findExistingItem: function ($component, productId) {
            let $found = null;

            $component.find('.order-item').each(function () {
                const $row = $(this);
                const rowProductId = $row.data('product-id') ||
                    $row.find('[name*="[product_id]"]').val();

                if (rowProductId == productId) {
                    $found = $row;
                    return false; // Break loop
                }
            });

            return $found ? $($found) : $();
        },

        /**
         * Add item to order table
         *
         * Creates table if needed, generates row from template,
         * and adds product to the order with animation.
         *
         * @since 1.0.0
         * @param {jQuery} $component - Order items component
         * @param {Object} product - Product data object
         * @fires orderitems:added
         * @return {void}
         */
        addItemToTable: function ($component, product) {
            let $tbody = $component.find('.order-items-list');
            const $emptyMessage = $component.find('.order-items-empty');

            // Create table structure if starting from empty state
            if ($emptyMessage.length) {
                const template = $component.find('.order-item-template').html();
                const showQty = template && template.includes('column-quantity');

                const tableHtml = `
                    <table>
                        <thead>
                            <tr>
                                <th class="column-product">Product</th>
                                ${showQty ? '<th class="column-quantity">Qty</th>' : ''}
                                <th class="column-price">Price</th>
                                <th class="column-subtotal">Subtotal</th>
                                <th class="column-actions"></th>
                            </tr>
                        </thead>
                        <tbody class="order-items-list"></tbody>
                    </table>
                `;

                $component.find('.order-items-table').html(tableHtml);
                $tbody = $component.find('.order-items-list');
            }

            // Get row template
            const template = $component.find('.order-item-template').html();
            if (!template) {
                console.error('Order Items: Template not found');
                return;
            }

            // Prepare data for template
            const index = $tbody.find('.order-item').length;
            const price = parseFloat(product.price) || 0;
            const currency = $component.data('currency') || '$';
            const currencyPos = $component.data('currency-position') || 'before';

            const priceFormatted = this.formatCurrency(price, currency, currencyPos);
            const thumbnailHtml = product.thumbnail ?
                '<img src="' + this.escapeHtml(product.thumbnail) + '" alt="' +
                this.escapeHtml(product.name) + '" class="product-thumbnail">' :
                '<div class="product-thumbnail-placeholder">' +
                '<span class="dashicons dashicons-format-image"></span></div>';

            // Replace template placeholders
            let html = template
                .replace(/{{index}}/g, index)
                .replace(/{{product_id}}/g, product.product_id || product.id || '')
                .replace(/{{price_id}}/g, product.price_id || '')
                .replace(/{{name}}/g, this.escapeHtml(product.name || ''))
                .replace(/{{price}}/g, price)
                .replace(/{{price_formatted}}/g, priceFormatted)
                .replace(/{{subtotal_formatted}}/g, priceFormatted)
                .replace(/{{thumbnail_html}}/g, thumbnailHtml);

            // Add row with animation
            const $newRow = $(html);
            $tbody.append($newRow);

            // Visual feedback
            $newRow.css('background', '#ffffcc');
            setTimeout(function () {
                $newRow.css('background', '');
            }, 1000);

            // Update totals
            this.recalculateTotals($component);

            // Trigger added event
            $component.trigger('orderitems:added', {
                product: product,
                row: $newRow[0],
                index: index,
                price: price,
                quantity: 1
            });
        },

        /**
         * Handle remove item action
         *
         * Removes an order item with animation and validation
         * for minimum item requirements.
         *
         * @since 1.0.0
         * @param {jQuery.Event} e - Click event
         * @fires orderitems:beforeremove
         * @fires orderitems:removed
         * @return {void}
         */
        handleRemove: function (e) {
            e.preventDefault();
            const self = this;

            const $button = $(e.currentTarget);
            const $row = $button.closest('.order-item');
            const $component = $button.closest('.wp-flyout-order-items');
            const $tbody = $row.closest('.order-items-list');

            // Get item data before removal
            const productId = $row.data('product-id') ||
                $row.find('[name*="[product_id]"]').val();
            const itemIndex = $row.index();

            // Check minimum items requirement
            const minItems = parseInt($component.data('min-items')) || 0;
            const currentCount = $tbody.find('.order-item').length;

            if (minItems > 0 && currentCount <= minItems) {
                $component.trigger('orderitems:minreached', {
                    min: minItems,
                    current: currentCount
                });
                alert('Minimum ' + minItems + ' items required');
                return;
            }

            // Fire before remove event (allows cancellation)
            const beforeRemoveEvent = $.Event('orderitems:beforeremove');
            $component.trigger(beforeRemoveEvent, {
                productId: productId,
                row: $row[0],
                index: itemIndex
            });

            if (beforeRemoveEvent.isDefaultPrevented()) {
                return;
            }

            // Animate removal
            $row.fadeOut(300, function () {
                $row.remove();
                self.reindexItems($component);

                // Check if now empty
                if ($tbody.find('.order-item').length === 0) {
                    const emptyHtml = '<div class="order-items-empty">' +
                        '<p>No products added yet.</p></div>';
                    $component.find('.order-items-table').html(emptyHtml);
                }

                self.recalculateTotals($component);

                // Trigger removed event
                $component.trigger('orderitems:removed', {
                    productId: productId,
                    index: itemIndex,
                    remainingCount: $tbody.find('.order-item').length
                });
            });
        },

        /**
         * Handle quantity change
         *
         * Updates item quantity, recalculates subtotals and totals.
         *
         * @since 1.0.0
         * @param {jQuery.Event} e - Change event
         * @fires orderitems:quantitychanged
         * @return {void}
         */
        handleQuantityChange: function (e) {
            const $input = $(e.currentTarget);
            const $component = $input.closest('.wp-flyout-order-items');
            const $row = $input.closest('.order-item');

            const oldQuantity = parseInt($input.data('previous-value')) || 1;
            const quantity = Math.max(1, parseInt($input.val()) || 1);

            $input.val(quantity);
            $input.data('previous-value', quantity);

            // Update row subtotal
            const price = parseFloat($row.find('.column-price').data('price')) || 0;
            const subtotal = price * quantity;
            const currency = $component.data('currency') || '$';
            const currencyPos = $component.data('currency-position') || 'before';

            $row.find('.item-subtotal').text(this.formatCurrency(subtotal, currency, currencyPos));

            // Recalculate totals
            this.recalculateTotals($component);

            // Trigger quantity changed event
            $component.trigger('orderitems:quantitychanged', {
                productId: $row.data('product-id'),
                row: $row[0],
                oldQuantity: oldQuantity,
                newQuantity: quantity,
                price: price,
                subtotal: subtotal
            });
        },

        /**
         * Reindex form field names
         *
         * Updates array indices in field names after add/remove/sort
         * to maintain proper form submission structure.
         *
         * @since 1.0.0
         * @param {jQuery} $component - Order items component
         * @fires orderitems:reindexed
         * @return {void}
         */
        reindexItems: function ($component) {
            const namePrefix = $component.data('name-prefix') || 'order_items';

            $component.find('.order-item').each(function (index) {
                const $item = $(this);
                $item.attr('data-index', index);

                // Update all input names with new index
                $item.find('input').each(function () {
                    const name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                    }
                });
            });

            // Trigger reindex event
            $component.trigger('orderitems:reindexed', {
                count: $component.find('.order-item').length
            });
        },

        /**
         * Recalculate order totals
         *
         * Calculates subtotal, applies discounts/tax if configured,
         * and updates all total displays.
         *
         * @since 1.0.0
         * @param {jQuery} $component - Order items component
         * @fires orderitems:updated
         * @return {void}
         */
        recalculateTotals: function ($component) {
            let subtotal = 0;
            const currency = $component.data('currency') || '$';
            const currencyPos = $component.data('currency-position') || 'before';

            // Calculate subtotal from all items
            $component.find('.order-item').each(function () {
                const $row = $(this);
                const price = parseFloat($row.find('.column-price').data('price')) || 0;
                const quantity = parseInt($row.find('.quantity-input').val()) || 1;
                subtotal += price * quantity;
            });

            // Update subtotal display
            $component.find('.subtotal-amount')
                .text(this.formatCurrency(subtotal, currency, currencyPos))
                .attr('data-value', subtotal);

            // Update total in edit mode
            if ($component.data('mode') === 'edit') {
                // Could apply discount/tax calculations here if needed
                const total = subtotal;
                $component.find('.total-amount')
                    .text(this.formatCurrency(total, currency, currencyPos));
            }

            // Trigger updated event
            $component.trigger('orderitems:updated', {
                subtotal: subtotal,
                itemCount: $component.find('.order-item').length
            });
        },

        /**
         * Format currency display
         *
         * Formats a numeric amount as currency with proper symbol placement.
         *
         * @since 1.0.0
         * @param {number} amount - Amount to format
         * @param {string} symbol - Currency symbol
         * @param {string} position - Symbol position ('before' or 'after')
         * @return {string} Formatted currency string
         */
        formatCurrency: function (amount, symbol, position) {
            const formatted = amount.toFixed(2);
            return position === 'after' ? formatted + symbol : symbol + formatted;
        },

        /**
         * Escape HTML for security
         *
         * Prevents XSS by escaping special HTML characters.
         *
         * @since 1.0.0
         * @param {string} text - Text to escape
         * @return {string} Escaped HTML string
         */
        escapeHtml: function (text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
    };

    // Initialize on document ready
    $(function () {
        OrderItems.init();
    });

    // Export for external use
    window.WPFlyoutOrderItems = OrderItems;

})(jQuery);