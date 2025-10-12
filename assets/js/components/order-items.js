/**
 * WP Flyout Order Items Component
 * Works with AJAX Select for product search
 */
(function ($) {
    'use strict';

    const OrderItems = {
        init() {
            // Use body delegation for dynamic content
            $(document)
                .on('click', '.wp-flyout-order-items [data-action="add-product"]', e => this.handleAdd(e))
                .on('click', '.wp-flyout-order-items [data-action="remove-item"]', e => this.handleRemove(e))
                .on('change', '.wp-flyout-order-items [data-action="update-quantity"]', e => this.handleQuantityChange(e))
                .on('wpflyout:opened', (e, data) => this.initComponent($(data.element)));

            // Initialize on document ready
            $(() => {
                $('.wp-flyout-order-items').each((i, el) => {
                    this.initComponent($(el).parent());
                });
            });
        },

        initComponent($container) {
            $container.find('.wp-flyout-order-items').each((i, el) => {
                const $component = $(el);
                const $select = $component.find('.product-ajax-select');

                if ($select.length && !$select.data('wpAjaxSelectInitialized')) {
                    // Create instance
                    const ajaxSelect = new WPAjaxSelect($select[0]);
                    $select.data('wpAjaxSelect', ajaxSelect);
                }

                if ($component.data('mode') === 'edit') {
                    this.recalculateTotals($component);
                }
            });
        },

        handleAdd(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $component = $button.closest('.wp-flyout-order-items');
            const $select = $component.find('.product-ajax-select');
            const productId = $select.val();

            if (!productId) {
                alert('Please select a product first');
                return;
            }

            // Check max items
            const maxItems = parseInt($component.data('max-items')) || 0;
            if (maxItems > 0 && $component.find('.order-item').length >= maxItems) {
                alert(`Maximum ${maxItems} items allowed`);
                return;
            }

            // Check if product already exists
            const existingItem = this.findExistingItem($component, productId);
            if (existingItem) {
                // Increment quantity
                const $qtyInput = existingItem.find('.quantity-input');
                const currentQty = parseInt($qtyInput.val()) || 1;
                $qtyInput.val(currentQty + 1).trigger('change');
                this.clearAjaxSelect($select);
                return;
            }

            // Get product details via AJAX
            this.fetchProductDetails($component, productId);
        },

        async fetchProductDetails($component, productId) {
            const $button = $component.find('[data-action="add-product"]');
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Loading...');

            const ajaxUrl = window.ajaxurl || '/wp-admin/admin-ajax.php';
            const config = window.wpFlyoutConfig?.components?.orderItems || {};

            try {
                const response = await $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'get_product_details',
                        product_id: String(productId),
                        _wpnonce: config.nonce || ''
                    }
                });

                if (response.success && response.data) {
                    this.addItemToTable($component, response.data);
                    this.clearAjaxSelect($component.find('.product-ajax-select'));
                } else {
                    alert(`Error: ${response.data || 'Product details not found'}`);
                }
            } catch (error) {
                console.error('AJAX Error:', error);
                alert(`Error loading product details: ${error.statusText || error}`);
            } finally {
                $button.prop('disabled', false).html('<span class="dashicons dashicons-plus-alt"></span> Add');
            }
        },

        clearAjaxSelect($select) {
            const instance = $select.data('wpAjaxSelect');
            if (instance?.clear) {
                instance.clear();
            } else {
                // Fallback
                const $wrapper = $select.next('.wp-ajax-select');
                if ($wrapper.length) {
                    $wrapper.find('.wp-ajax-select-input').val('').prop('readonly', false);
                    $wrapper.find('.wp-ajax-select-clear').hide();
                    $wrapper.removeClass('wp-ajax-select-has-value');
                }
                $select.val('').trigger('change');
            }
        },

        findExistingItem($component, productId) {
            return $component.find('.order-item').filter((i, el) => {
                const $row = $(el);
                const rowProductId = $row.data('product-id') || $row.find('[name*="[product_id]"]').val();
                return rowProductId == productId;
            }).first();
        },

        addItemToTable($component, product) {
            let $tbody = $component.find('.order-items-list');
            const $emptyMessage = $component.find('.order-items-empty');

            // Create table if needed
            if ($emptyMessage.length) {
                const showQty = $component.find('.order-item-template').html()?.includes('column-quantity');
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
            const thumbnailHtml = product.thumbnail ?
                `<img src="${product.thumbnail}" alt="${product.name}" class="product-thumbnail">` :
                `<div class="product-thumbnail-placeholder"><span class="dashicons dashicons-format-image"></span></div>`;

            // Replace template variables
            const html = template
                .replace(/{{index}}/g, index)
                .replace(/{{product_id}}/g, product.product_id || product.id || '')
                .replace(/{{price_id}}/g, product.price_id || '')
                .replace(/{{name}}/g, product.name || '')
                .replace(/{{price}}/g, price)
                .replace(/{{price_formatted}}/g, priceFormatted)
                .replace(/{{subtotal_formatted}}/g, priceFormatted)
                .replace(/{{thumbnail_html}}/g, thumbnailHtml);

            // Add to table
            const $newRow = $(html);
            $tbody.append($newRow);

            // Highlight briefly
            $newRow.css('background', '#ffffcc');
            setTimeout(() => $newRow.css('background', ''), 1000);

            this.recalculateTotals($component);
        },

        handleRemove(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $row = $button.closest('.order-item');
            const $component = $button.closest('.wp-flyout-order-items');
            const $tbody = $row.closest('.order-items-list');

            // Check min items
            const minItems = parseInt($component.data('min-items')) || 0;
            const currentCount = $tbody.find('.order-item').length;

            if (minItems > 0 && currentCount <= minItems) {
                alert(`Minimum ${minItems} items required`);
                return;
            }

            $row.fadeOut(300, () => {
                $row.remove();
                this.reindexItems($component);

                // Check if empty
                if (!$tbody.find('.order-item').length) {
                    $component.find('.order-items-table').html(
                        '<div class="order-items-empty"><p>No products added yet.</p></div>'
                    );
                }

                this.recalculateTotals($component);
            });
        },

        handleQuantityChange(e) {
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

        reindexItems($component) {
            const namePrefix = $component.data('name-prefix') || 'order_items';

            $component.find('.order-item').each((index, el) => {
                const $item = $(el);
                $item.attr('data-index', index);

                $item.find('input').each((i, input) => {
                    const name = $(input).attr('name');
                    if (name) {
                        $(input).attr('name', name.replace(/\[\d+\]/, `[${index}]`));
                    }
                });
            });
        },

        recalculateTotals($component) {
            let subtotal = 0;
            const currency = $component.data('currency') || '$';
            const currencyPos = $component.data('currency-position') || 'before';

            $component.find('.order-item').each((i, el) => {
                const $row = $(el);
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
            $component.trigger('orderitems:updated', {subtotal});
        },

        formatCurrency(amount, symbol, position) {
            const formatted = amount.toFixed(2);
            return position === 'after' ? formatted + symbol : symbol + formatted;
        }
    };

    // Initialize
    $(() => OrderItems.init());

    // Export
    window.WPFlyoutOrderItems = OrderItems;

})(jQuery);