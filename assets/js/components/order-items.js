/**
 * Order Items JavaScript Component
 * Handles product selection, quantities, and dynamic total calculation
 */
(function ($) {
    'use strict';

    const OrderItems = {

        /**
         * Initialize the component globally
         */
        init: function () {
            // Initialize any order items already on the page
            $('.wp-flyout-order-items').each(function () {
                OrderItems.initComponent($(this));
            });
        },

        /**
         * Initialize within a specific container (called by flyout)
         */
        initIn: function ($container) {
            const $components = $container.find('.wp-flyout-order-items');
            const self = this;

            $components.each(function () {
                self.initComponent($(this));
            });
        },

        /**
         * Initialize a single component instance
         */
        initComponent: function ($component) {
            // Skip if already initialized
            if ($component.data('initialized')) {
                return;
            }

            const mode = $component.data('mode');

            if (mode === 'edit') {
                this.bindEditEvents($component);
                this.recalculateTotals($component);
            }

            // Mark as initialized
            $component.data('initialized', true);
        },

        /**
         * Bind events for edit mode
         */
        bindEditEvents: function ($component) {
            const self = this;

            // Remove existing handlers to prevent duplication
            $component.off('.orderitems');

            // Add product button
            $component.on('click.orderitems', '.add-product-btn', function (e) {
                e.preventDefault();
                self.handleAddProduct($component);
            });

            // Remove item button
            $component.on('click.orderitems', '.remove-item', function (e) {
                e.preventDefault();
                self.handleRemoveItem($component, $(this));
            });

            // Quantity change
            $component.on('change.orderitems', '.quantity-input', function () {
                self.handleQuantityChange($component, $(this));
            });

            // Enter key on selector
            $component.find('.product-selector').off('keypress.orderitems').on('keypress.orderitems', function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    self.handleAddProduct($component);
                }
            });
        },

        /**
         * Handle adding a product
         */
        handleAddProduct: function ($component) {
            const config = window.wpFlyoutConfig || {};
            const orderConfig = config.components?.orderItems || {};

            const $selector = $component.find('.product-selector');
            const priceId = $selector.val();

            if (!priceId) {
                $selector.focus();
                return;
            }

            // Check if already exists
            const $existingItem = $component.find('.order-item[data-price-id="' + priceId + '"]');
            if ($existingItem.length) {
                // Increment quantity
                const $quantityInput = $existingItem.find('.quantity-input');
                const currentQty = parseInt($quantityInput.val()) || 1;
                $quantityInput.val(currentQty + 1).trigger('change');
                $selector.val('').focus();
                return;
            }

            // Check max items using centralized config
            const maxItems = parseInt($component.data('max-items')) || orderConfig.maxItems || 0;
            const currentCount = $component.find('.order-item').length;
            if (maxItems > 0 && currentCount >= maxItems) {
                alert('Maximum number of items reached (' + maxItems + ')');
                return;
            }

            // Show loading
            const $button = $component.find('.add-product-btn');
            const originalText = $button.text();
            const loadingText = config.i18n?.loading || 'Loading...';
            $button.prop('disabled', true).text(loadingText);

            // Fetch product details via AJAX
            $.ajax({
                url: config.ajaxUrl || ajaxurl,
                type: 'POST',
                data: {
                    action: orderConfig.action || 'get_product_details',
                    price_id: priceId,
                    _wpnonce: orderConfig.nonce
                },
                success: function (response) {
                    if (response.success) {
                        OrderItems.addItemToTable($component, response.data);
                        $selector.val('').focus();
                    } else {
                        alert(response.data || 'Failed to load product details');
                    }
                },
                error: function () {
                    alert('Network error loading product');
                },
                complete: function () {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Add item to table
         */
        addItemToTable: function ($component, productData) {
            let $tbody = $component.find('.order-items-list');
            const $emptyMessage = $component.find('.order-items-empty');

            // Remove empty message and create table if needed
            if ($emptyMessage.length) {
                const tableHtml = `
                    <table>
                        <thead>
                            <tr>
                                <th class="column-product">Product</th>
                                <th class="column-quantity">Qty</th>
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

            // Get the template
            const template = $component.find('.order-item-template').html();
            if (!template) {
                console.error('Order item template not found');
                return;
            }

            // Calculate next index
            const nextIndex = $tbody.find('.order-item').length;

            // Format price
            const price = parseFloat(productData.price) || 0;
            const priceFormatted = price.toFixed(2);

            // Handle thumbnail - create HTML based on whether image exists
            const thumbnailHtml = productData.thumbnail
                ? `<img src="${productData.thumbnail}" alt="${productData.name || ''}" class="product-thumbnail">`
                : `<div class="product-thumbnail-placeholder"><span class="dashicons dashicons-format-image"></span></div>`;

            // Replace template variables
            let html = template
                .replace(/{{index}}/g, nextIndex)
                .replace(/{{product_id}}/g, productData.product_id || '')
                .replace(/{{price_id}}/g, productData.price_id || '')
                .replace(/{{name}}/g, productData.name || '')
                .replace(/{{price}}/g, price)
                .replace(/{{price_formatted}}/g, priceFormatted)
                .replace(/{{subtotal}}/g, priceFormatted)
                .replace(/{{thumbnail_html}}/g, thumbnailHtml);

            // Add to table
            $tbody.append(html);

            // Highlight new row
            const $newRow = $tbody.find('.order-item').last();
            $newRow.css('background', '#ffffcc');
            setTimeout(function () {
                $newRow.css('background', '');
            }, 1000);

            // Recalculate totals
            this.recalculateTotals($component);
        },

        /**
         * Handle removing an item
         */
        handleRemoveItem: function ($component, $button) {
            const config = window.wpFlyoutConfig || {};
            const $row = $button.closest('.order-item');

            $row.fadeOut(300, function () {
                $(this).remove();

                // Reindex remaining items
                OrderItems.reindexItems($component);

                // Check if empty
                if ($component.find('.order-item').length === 0) {
                    const emptyMessage = config.i18n?.noItems || 'No products added yet. Select a product above to get started.';
                    const emptyHtml = '<div class="order-items-empty"><p>' + emptyMessage + '</p></div>';
                    $component.find('.order-items-table').html(emptyHtml);
                }

                // Recalculate totals
                OrderItems.recalculateTotals($component);
            });
        },

        /**
         * Handle quantity change
         */
        handleQuantityChange: function ($component, $input) {
            const config = window.wpFlyoutConfig || {};
            const currency = config.currency || '$';

            const $row = $input.closest('.order-item');
            const quantity = parseInt($input.val()) || 1;

            if (quantity < 1) {
                $input.val(1);
                return;
            }

            // Update subtotal for this row
            const price = parseFloat($row.find('.column-price').data('price')) || 0;
            const subtotal = price * quantity;
            $row.find('.item-subtotal').text(currency + subtotal.toFixed(2));

            // Recalculate totals
            this.recalculateTotals($component);
        },

        /**
         * Reindex items after removal
         */
        reindexItems: function ($component) {
            const namePrefix = $component.data('name-prefix') || 'order_items';

            $component.find('.order-item').each(function (index) {
                const $item = $(this);
                $item.attr('data-index', index);

                // Update input names
                $item.find('input').each(function () {
                    const name = $(this).attr('name');
                    if (name) {
                        const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                        $(this).attr('name', newName);
                    }
                });
            });
        },

        /**
         * Recalculate totals
         */
        recalculateTotals: function ($component) {
            const config = window.wpFlyoutConfig || {};
            const currency = config.currency || '$';

            let subtotal = 0;

            // Calculate subtotal from all items
            $component.find('.order-item').each(function () {
                const $row = $(this);
                const price = parseFloat($row.find('.column-price').data('price')) || 0;
                const quantity = parseInt($row.find('.quantity-input').val()) || 1;
                subtotal += price * quantity;
            });

            // Update subtotal display
            const $subtotalElement = $component.find('.subtotal-amount');
            $subtotalElement
                .text(currency + subtotal.toFixed(2))
                .attr('data-value', subtotal);

            // In edit mode, total equals subtotal (no tax/discount)
            if ($component.data('mode') === 'edit') {
                $component.find('.total-amount').text(currency + subtotal.toFixed(2));
            }
        }
    };

    // Initialize on document ready for any existing components
    $(document).ready(function () {
        OrderItems.init();
    });

    // Export for external use
    window.WPOrderItems = OrderItems;

})(jQuery);