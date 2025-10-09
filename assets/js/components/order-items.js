/**
 * WP Flyout Order Items Component
 */
(function($) {
    'use strict';

    const OrderItems = {
        init: function() {
            // Use body delegation for dynamic content
            $('body').on('click', '.wp-flyout-order-items [data-action="add-product"]', this.handleAdd.bind(this));
            $('body').on('click', '.wp-flyout-order-items [data-action="remove-item"]', this.handleRemove.bind(this));
            $('body').on('change', '.wp-flyout-order-items [data-action="update-quantity"]', this.handleQuantityChange.bind(this));

            // Enter key on selector
            $('body').on('keypress', '.wp-flyout-order-items .product-selector', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $(this).siblings('[data-action="add-product"]').click();
                }
            });

            // Initialize on flyout open
            $(document).on('wpflyout:opened', function(e, data) {
                OrderItems.initComponent($(data.element));
            });
        },

        initComponent: function($container) {
            $container.find('.wp-flyout-order-items').each(function() {
                const $component = $(this);
                if ($component.data('mode') === 'edit') {
                    OrderItems.recalculateTotals($component);
                }
            });
        },

        handleAdd: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const $component = $button.closest('.wp-flyout-order-items');
            const $selector = $component.find('.product-selector');
            const productData = $selector.val();

            if (!productData) {
                $selector.focus();
                return;
            }

            // Parse product data
            let product;
            try {
                product = JSON.parse(productData);
            } catch (err) {
                console.error('Invalid product data');
                return;
            }

            // Check max items
            const maxItems = parseInt($component.data('max-items')) || 0;
            if (maxItems > 0) {
                const currentCount = $component.find('.order-item').length;
                if (currentCount >= maxItems) {
                    alert('Maximum ' + maxItems + ' items allowed');
                    return;
                }
            }

            // Check if product already exists
            const existingItem = this.findExistingItem($component, product);
            if (existingItem) {
                // Increment quantity
                const $qtyInput = existingItem.find('.quantity-input');
                const currentQty = parseInt($qtyInput.val()) || 1;
                $qtyInput.val(currentQty + 1).trigger('change');
                $selector.val('').focus();
                return;
            }

            // Add new item
            this.addItemToTable($component, product);
            $selector.val('').focus();
        },

        findExistingItem: function($component, product) {
            let $found = null;
            $component.find('.order-item').each(function() {
                const $row = $(this);
                const productId = $row.find('[name*="[product_id]"]').val();
                const priceId = $row.find('[name*="[price_id]"]').val();

                if ((product.product_id && productId === product.product_id) ||
                    (product.price_id && priceId === product.price_id)) {
                    $found = $row;
                    return false;
                }
            });
            return $found;
        },

        addItemToTable: function($component, product) {
            let $tbody = $component.find('.order-items-list');
            const $emptyMessage = $component.find('.order-items-empty');

            // Create table if needed
            if ($emptyMessage.length) {
                const showQty = $component.find('.order-item-template').html().includes('column-quantity');
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

            // Get template
            const template = $component.find('.order-item-template').html();
            if (!template) {
                console.error('Order item template not found');
                return;
            }

            // Prepare data
            const index = $tbody.find('.order-item').length;
            const price = parseFloat(product.price) || 0;
            const currency = $component.data('currency') || '$';
            const currencyPos = $component.data('currency-position') || 'before';

            const priceFormatted = this.formatCurrency(price, currency, currencyPos);
            const subtotalFormatted = priceFormatted;

            const thumbnailHtml = product.thumbnail ?
                `<img src="${product.thumbnail}" alt="${product.name}" class="product-thumbnail">` :
                `<div class="product-thumbnail-placeholder"><span class="dashicons dashicons-format-image"></span></div>`;

            // Replace template variables
            let html = template
                .replace(/{{index}}/g, index)
                .replace(/{{product_id}}/g, product.product_id || product.id || '')
                .replace(/{{price_id}}/g, product.price_id || '')
                .replace(/{{name}}/g, product.name || '')
                .replace(/{{price}}/g, price)
                .replace(/{{price_formatted}}/g, priceFormatted)
                .replace(/{{subtotal_formatted}}/g, subtotalFormatted)
                .replace(/{{thumbnail_html}}/g, thumbnailHtml);

            // Add to table
            const $newRow = $(html);
            $tbody.append($newRow);

            // Highlight briefly
            $newRow.css('background', '#ffffcc');
            setTimeout(() => $newRow.css('background', ''), 1000);

            // Recalculate
            this.recalculateTotals($component);
        },

        handleRemove: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const $row = $button.closest('.order-item');
            const $component = $button.closest('.wp-flyout-order-items');
            const $tbody = $row.closest('.order-items-list');

            // Check min items
            const minItems = parseInt($component.data('min-items')) || 0;
            if (minItems > 0) {
                const currentCount = $tbody.find('.order-item').length;
                if (currentCount <= minItems) {
                    alert('Minimum ' + minItems + ' items required');
                    return;
                }
            }

            $row.fadeOut(300, () => {
                $row.remove();
                this.reindexItems($component);

                // Check if empty
                if ($tbody.find('.order-item').length === 0) {
                    const emptyText = $component.data('empty-text') || 'No products added yet.';
                    $component.find('.order-items-table').html(
                        `<div class="order-items-empty"><p>${emptyText}</p></div>`
                    );
                }

                this.recalculateTotals($component);
            });
        },

        handleQuantityChange: function(e) {
            const $input = $(e.currentTarget);
            const $component = $input.closest('.wp-flyout-order-items');
            const $row = $input.closest('.order-item');
            const quantity = Math.max(1, parseInt($input.val()) || 1);

            $input.val(quantity);

            // Update row subtotal
            const price = parseFloat($row.find('.column-price').data('price')) || 0;
            const subtotal = price * quantity;
            const currency = $component.data('currency') || '$';
            const currencyPos = $component.data('currency-position') || 'before';

            $row.find('.item-subtotal').text(this.formatCurrency(subtotal, currency, currencyPos));

            this.recalculateTotals($component);
        },

        reindexItems: function($component) {
            const namePrefix = $component.data('name-prefix') || 'order_items';

            $component.find('.order-item').each(function(index) {
                const $item = $(this);
                $item.attr('data-index', index);

                $item.find('input').each(function() {
                    const name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                    }
                });
            });
        },

        recalculateTotals: function($component) {
            let subtotal = 0;
            const currency = $component.data('currency') || '$';
            const currencyPos = $component.data('currency-position') || 'before';

            $component.find('.order-item').each(function() {
                const $row = $(this);
                const price = parseFloat($row.find('.column-price').data('price')) || 0;
                const quantity = parseInt($row.find('.quantity-input').val()) || 1;
                subtotal += price * quantity;
            });

            // Update displays
            $component.find('.subtotal-amount')
                .text(this.formatCurrency(subtotal, currency, currencyPos))
                .attr('data-value', subtotal);

            // In edit mode, recalculate total
            if ($component.data('mode') === 'edit') {
                $component.find('.total-amount').text(this.formatCurrency(subtotal, currency, currencyPos));
            }

            // Trigger event
            $component.trigger('orderitems:updated', { subtotal: subtotal });
        },

        formatCurrency: function(amount, symbol, position) {
            const formatted = amount.toFixed(2);
            return position === 'after' ? formatted + symbol : symbol + formatted;
        }
    };

    // Initialize
    $(function() {
        OrderItems.init();
    });

    // Export
    window.WPFlyoutOrderItems = OrderItems;

})(jQuery);