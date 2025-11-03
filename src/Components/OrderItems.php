<?php
/**
 * Order Items Component
 *
 * Manages order line items with AJAX product selection, quantities, and pricing.
 *
 * @package     ArrayPress\WPFlyout\Components\Interactive
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     3.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Interfaces\Renderable;
use ArrayPress\WPFlyout\Traits\CurrencyFormatter;
use ArrayPress\WPFlyout\Traits\HtmlAttributes;

class OrderItems implements Renderable {
    use CurrencyFormatter;
    use HtmlAttributes;

    /**
     * Component configuration
     *
     * @var array
     */
    private array $config;

    /**
     * Default configuration
     *
     * @var array
     */
    private const DEFAULTS = [
            'id'             => '',
            'name'           => 'order_items',
            'items'          => [],
            'mode'           => 'edit',
            'currency'       => 'USD',
            'show_quantity'  => true,
            'show_totals'    => true,
            'ajax_search'    => '',  // Changed from ajax_search
            'ajax_details'   => 'get_product_details', // Changed from ajax_details
            'placeholder'    => 'Search for products...',
            'empty_text'     => 'No products added yet.',
            'add_text'       => 'Add Product',
            'class'          => ''
    ];

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, self::DEFAULTS );

        // Auto-generate ID if not provided
        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'order-items-' . wp_generate_uuid4();
        }

        // Ensure items is array
        if ( ! is_array( $this->config['items'] ) ) {
            $this->config['items'] = [];
        }
    }

    /**
     * Calculate subtotal
     *
     * @return float
     */
    private function calculate_subtotal(): float {
        $total = 0;
        foreach ( $this->config['items'] as $item ) {
            $price    = $item['price'] ?? 0;
            $quantity = $item['quantity'] ?? 1;
            $total    += $price * $quantity;
        }

        return $total;
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        $classes = [ 'wp-flyout-order-items' ];
        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        $data = [
                'mode'           => $this->config['mode'],
                'name'           => $this->config['name'],
                'currency'       => $this->config['currency'],
                'details-action' => $this->config['ajax_details']
        ];

        $is_edit = $this->config['mode'] === 'edit';

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
                <?php echo $this->build_data_attributes( $data ); ?>>

            <?php if ( $is_edit && $this->config['ajax_search'] ) : ?>
                <?php $this->render_product_selector(); ?>
            <?php endif; ?>

            <div class="order-items-table">
                <?php $this->render_items_table(); ?>
            </div>

            <?php if ( $this->config['show_totals'] ) : ?>
                <?php $this->render_totals(); ?>
            <?php endif; ?>

            <?php $this->render_item_template(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render product selector
     */
    private function render_product_selector(): void {
        $nonce = wp_create_nonce( 'order_items_' . $this->config['ajax_search'] );
        ?>
        <div class="order-items-selector">
            <select class="product-ajax-select"
                    data-ajax="<?php echo esc_attr( $this->config['ajax_search'] ); ?>"
                    data-placeholder="<?php echo esc_attr( $this->config['placeholder'] ); ?>"
                    data-nonce="<?php echo esc_attr( $nonce ); ?>"
                    data-details-action="<?php echo esc_attr( $this->config['ajax_details'] ); ?>">
            </select>
            <button type="button" class="button" data-action="add-product">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php echo esc_html( $this->config['add_text'] ); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Render items table
     */
    private function render_items_table(): void {
        if ( empty( $this->config['items'] ) ) {
            ?>
            <div class="order-items-empty">
                <span class="dashicons dashicons-cart"></span>
                <p><?php echo esc_html( $this->config['empty_text'] ); ?></p>
            </div>
            <?php
            return;
        }

        $is_edit = $this->config['mode'] === 'edit';
        ?>
        <table>
            <thead>
            <tr>
                <th class="column-product"><?php esc_html_e( 'Product', 'wp-flyout' ); ?></th>
                <?php if ( $this->config['show_quantity'] ) : ?>
                    <th class="column-quantity"><?php esc_html_e( 'Qty', 'wp-flyout' ); ?></th>
                <?php endif; ?>
                <th class="column-price"><?php esc_html_e( 'Price', 'wp-flyout' ); ?></th>
                <th class="column-subtotal"><?php esc_html_e( 'Total', 'wp-flyout' ); ?></th>
                <?php if ( $is_edit ) : ?>
                    <th class="column-actions"></th>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody class="order-items-list">
            <?php foreach ( $this->config['items'] as $index => $item ) : ?>
                <?php $this->render_item_row( $item, $index ); ?>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render single item row
     *
     * @param array $item  Item data
     * @param int   $index Item index
     */
    private function render_item_row( array $item, int $index ): void {
        $is_edit  = $this->config['mode'] === 'edit';
        $subtotal = ( $item['price'] ?? 0 ) * ( $item['quantity'] ?? 1 );
        ?>
        <tr class="order-item" data-index="<?php echo $index; ?>"
            data-product-id="<?php echo esc_attr( $item['id'] ?? '' ); ?>">
            <td class="column-product">
                <div>
                    <?php if ( ! empty( $item['thumbnail'] ) ) : ?>
                        <img src="<?php echo esc_url( $item['thumbnail'] ); ?>"
                             alt="<?php echo esc_attr( $item['name'] ?? '' ); ?>"
                             class="product-thumbnail">
                    <?php else : ?>
                        <div class="product-thumbnail-placeholder">
                            <span class="dashicons dashicons-format-image"></span>
                        </div>
                    <?php endif; ?>
                    <span><?php echo esc_html( $item['name'] ?? '' ); ?></span>
                </div>
                <?php if ( $is_edit ) : ?>
                    <input type="hidden"
                           name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][product_id]"
                           value="<?php echo esc_attr( $item['id'] ?? '' ); ?>">
                    <input type="hidden"
                           name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][name]"
                           value="<?php echo esc_attr( $item['name'] ?? '' ); ?>">
                    <input type="hidden"
                           name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][price]"
                           value="<?php echo esc_attr( (string) ( $item['price'] ?? 0 ) ); ?>">
                <?php endif; ?>
            </td>

            <?php if ( $this->config['show_quantity'] ) : ?>
                <td class="column-quantity">
                    <?php if ( $is_edit ) : ?>
                        <input type="number"
                               name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][quantity]"
                               value="<?php echo esc_attr( (string) ( $item['quantity'] ?? 1 ) ); ?>"
                               min="1"
                               class="quantity-input small-text"
                               data-action="update-quantity">
                    <?php else : ?>
                        <?php echo esc_html( $item['quantity'] ?? 1 ); ?>
                    <?php endif; ?>
                </td>
            <?php endif; ?>

            <td class="column-price" data-price="<?php echo esc_attr( (string) ( $item['price'] ?? 0 ) ); ?>">
                <?php echo esc_html( $this->format_currency( $item['price'] ?? 0, $this->config['currency'] ) ); ?>
            </td>

            <td class="column-subtotal">
                <span class="item-subtotal"><?php echo esc_html( $this->format_currency( $subtotal, $this->config['currency'] ) ); ?></span>
            </td>

            <?php if ( $is_edit ) : ?>
                <td class="column-actions">
                    <button type="button" class="button-link" data-action="remove-item">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </td>
            <?php endif; ?>
        </tr>
        <?php
    }

    /**
     * Render totals section
     */
    private function render_totals(): void {
        $subtotal = $this->calculate_subtotal();
        ?>
        <div class="order-items-totals">
            <div class="total-row">
                <span class="total-label"><?php esc_html_e( 'Subtotal:', 'wp-flyout' ); ?></span>
                <span class="subtotal-amount total-value" data-value="<?php echo esc_attr( (string) $subtotal ); ?>">
					<?php echo esc_html( $this->format_currency( (int) $subtotal, $this->config['currency'] ) ); ?>
				</span>
            </div>
        </div>
        <?php
    }

    /**
     * Render JavaScript template for dynamic items
     */
    private function render_item_template(): void {
        if ( $this->config['mode'] !== 'edit' ) {
            return;
        }
        ?>
        <script type="text/template" class="order-item-template">
            <tr class="order-item" data-product-id="{{product_id}}">
                <td class="column-product">
                    <div>
                        {{thumbnail_html}}
                        <span>{{name}}</span>
                    </div>
                    <input type="hidden" name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][product_id]"
                           value="{{product_id}}">
                    <input type="hidden" name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][name]"
                           value="{{name}}">
                    <input type="hidden" name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][price]"
                           value="{{price}}">
                </td>
                <?php if ( $this->config['show_quantity'] ) : ?>
                    <td class="column-quantity">
                        <input type="number"
                               name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][quantity]"
                               value="1"
                               min="1"
                               class="quantity-input small-text"
                               data-action="update-quantity">
                    </td>
                <?php endif; ?>
                <td class="column-price" data-price="{{price}}">{{price_formatted}}</td>
                <td class="column-subtotal">
                    <span class="item-subtotal">{{subtotal_formatted}}</span>
                </td>
                <td class="column-actions">
                    <button type="button" class="button-link" data-action="remove-item">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </td>
            </tr>
        </script>
        <?php
    }

}