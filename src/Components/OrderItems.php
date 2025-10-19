<?php
/**
 * Order Items Component
 *
 * Manages order line items with AJAX product selection, quantities, and pricing.
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Traits\Renderable;
use ArrayPress\WPFlyout\Traits\DataAttributes;
use ArrayPress\WPFlyout\Traits\CurrencyFormatter;

/**
 * Class OrderItems
 *
 * Manages order line items with AJAX-powered product search.
 *
 * @since 1.0.0
 */
class OrderItems {
    use Renderable;
    use DataAttributes;
    use CurrencyFormatter;

    /**
     * Order items array
     *
     * @since 1.0.0
     * @var array
     */
    private array $items = [];

    /**
     * Component configuration
     *
     * @since 1.0.0
     * @var array
     */
    private array $config = [
            'mode'          => 'edit', // 'edit' or 'view'
            'name_prefix'   => 'order_items',
            'currency'      => 'USD', // Currency code for formatting
            'subtotal'      => 0,
            'discount'      => 0,
            'discount_code' => '',
            'tax'           => 0,
            'total'         => 0,
            'show_quantity' => true,
            'max_items'     => 0, // 0 = unlimited
            'min_items'     => 0,
            'class'         => 'wp-flyout-order-items',
            'empty_text'    => '',
            'add_text'      => '',
            'select_text'   => '',
            'ajax_action'   => 'search_products', // AJAX action for product search
            'ajax_nonce'    => '',
            'data'          => []
    ];

    /**
     * Constructor
     *
     * @param array $items  Initial items
     * @param array $config Configuration options
     *
     * @since 1.0.0
     *
     */
    public function __construct( array $items = [], array $config = [] ) {
        $this->items = $items;

        // Set default translatable strings
        $defaults = [
                'empty_text'  => __( 'No products added yet.', 'arraypress' ),
                'add_text'    => __( 'Add', 'arraypress' ),
                'select_text' => __( 'Search for products...', 'arraypress' ),
        ];

        $this->config = array_merge( $this->config, $defaults, $config );

        // Auto-generate nonce if not provided
        if ( empty( $this->config['ajax_nonce'] ) && ! empty( $this->config['ajax_action'] ) ) {
            $this->config['ajax_nonce'] = wp_create_nonce( $this->config['ajax_action'] );
        }
    }

    /**
     * Add an item
     *
     * @param array $item Item data
     *
     * @return self
     * @since 1.0.0
     *
     */
    public function add_item( array $item ): self {
        $this->items[] = array_merge( [
                'id'         => '',
                'product_id' => '',
                'price_id'   => '',
                'name'       => '',
                'price'      => 0,
                'quantity'   => 1,
                'thumbnail'  => ''
        ], $item );

        return $this;
    }

    /**
     * Calculate subtotal
     *
     * @return int|float Calculated subtotal (accepts both int cents and float dollars)
     * @since 1.0.0
     *
     */
    private function calculate_subtotal() {
        $total = 0;
        foreach ( $this->items as $item ) {
            $total += ( $item['price'] ?? 0 ) * ( $item['quantity'] ?? 1 );
        }

        return $total;
    }

    /**
     * Render the component
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    public function render(): string {
        $is_edit = $this->config['mode'] === 'edit';

        ob_start();
        ?>
        <div class="<?php echo esc_attr( $this->config['class'] ); ?>"
             data-mode="<?php echo esc_attr( $this->config['mode'] ); ?>"
             data-name-prefix="<?php echo esc_attr( $this->config['name_prefix'] ); ?>"
             data-max-items="<?php echo esc_attr( (string) $this->config['max_items'] ); ?>"
             data-min-items="<?php echo esc_attr( (string) $this->config['min_items'] ); ?>"
             data-currency="<?php echo esc_attr( $this->config['currency'] ); ?>"
             data-ajax-action="<?php echo esc_attr( $this->config['ajax_action'] ); ?>"
                <?php echo $this->render_data_attributes(); ?>>

            <?php if ( $is_edit ) : ?>
                <?php echo $this->render_product_selector(); ?>
            <?php endif; ?>

            <?php echo $this->render_items_table(); ?>
            <?php echo $this->render_totals(); ?>

            <?php if ( $is_edit ) : ?>
                <?php echo $this->render_template(); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render product selector with AJAX
     *
     * @return string Generated HTML
     * @since 2.0.0
     *
     */
    private function render_product_selector(): string {
        ob_start();
        ?>
        <div class="order-items-selector">
            <div class="product-selector-wrapper">
                <select class="product-ajax-select"
                        data-ajax="<?php echo esc_attr( $this->config['ajax_action'] ); ?>"
                        data-placeholder="<?php echo esc_attr( $this->config['select_text'] ); ?>"
                        data-nonce="<?php echo esc_attr( $this->config['ajax_nonce'] ); ?>"
                        data-min-length="2">
                </select>
                <button type="button" class="button" data-action="add-product">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php echo esc_html( $this->config['add_text'] ); ?>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render items table
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    private function render_items_table(): string {
        $is_edit = $this->config['mode'] === 'edit';

        ob_start();
        ?>
        <div class="order-items-table">
            <?php if ( empty( $this->items ) ) : ?>
                <div class="order-items-empty">
                    <p><?php echo esc_html( $this->config['empty_text'] ); ?></p>
                </div>
            <?php else : ?>
                <table>
                    <thead>
                    <tr>
                        <th class="column-product"><?php esc_html_e( 'Product', 'arraypress' ); ?></th>
                        <?php if ( $this->config['show_quantity'] ) : ?>
                            <th class="column-quantity"><?php esc_html_e( 'Qty', 'arraypress' ); ?></th>
                        <?php endif; ?>
                        <th class="column-price"><?php esc_html_e( 'Price', 'arraypress' ); ?></th>
                        <th class="column-subtotal"><?php esc_html_e( 'Subtotal', 'arraypress' ); ?></th>
                        <?php if ( $is_edit ) : ?>
                            <th class="column-actions"></th>
                        <?php endif; ?>
                    </tr>
                    </thead>
                    <tbody class="order-items-list">
                    <?php foreach ( $this->items as $index => $item ) : ?>
                        <?php echo $this->render_item_row( $item, $index ); ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single item row
     *
     * @param array $item  Item data
     * @param int   $index Item index
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    private function render_item_row( array $item, int $index ): string {
        $is_edit  = $this->config['mode'] === 'edit';
        $subtotal = ( $item['price'] ?? 0 ) * ( $item['quantity'] ?? 1 );

        ob_start();
        ?>
        <tr class="order-item" data-index="<?php echo $index; ?>"
            data-product-id="<?php echo esc_attr( $item['product_id'] ?? '' ); ?>">
            <td class="column-product">
                <div class="product-info">
                    <?php if ( ! empty( $item['thumbnail'] ) ) : ?>
                        <img src="<?php echo esc_url( $item['thumbnail'] ); ?>"
                             alt="<?php echo esc_attr( $item['name'] ); ?>"
                             class="product-thumbnail">
                    <?php else : ?>
                        <div class="product-thumbnail-placeholder">
                            <span class="dashicons dashicons-format-image"></span>
                        </div>
                    <?php endif; ?>
                    <span class="product-name"><?php echo esc_html( $item['name'] ); ?></span>
                </div>
                <?php if ( $is_edit ) : ?>
                    <?php echo $this->render_item_hidden_fields( $item, $index ); ?>
                <?php endif; ?>
            </td>

            <?php if ( $this->config['show_quantity'] ) : ?>
                <td class="column-quantity">
                    <?php if ( $is_edit ) : ?>
                        <input type="number"
                               name="<?php echo esc_attr( $this->config['name_prefix'] ); ?>[<?php echo $index; ?>][quantity]"
                               value="<?php echo esc_attr( (string) $item['quantity'] ); ?>"
                               min="1"
                               class="quantity-input"
                               data-action="update-quantity">
                    <?php else : ?>
                        <?php echo esc_html( $item['quantity'] ); ?>
                    <?php endif; ?>
                </td>
            <?php endif; ?>

            <td class="column-price" data-price="<?php echo esc_attr( (string) $item['price'] ); ?>">
                <?php echo esc_html( $this->format_currency( $item['price'] ) ); ?>
            </td>

            <td class="column-subtotal">
                <span class="item-subtotal"><?php echo esc_html( $this->format_currency( $subtotal ) ); ?></span>
            </td>

            <?php if ( $is_edit ) : ?>
                <td class="column-actions">
                    <button type="button" class="button-link" data-action="remove-item"
                            title="<?php echo esc_attr__( 'Remove', 'arraypress' ); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </td>
            <?php endif; ?>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Render hidden fields for an item
     *
     * @param array $item  Item data
     * @param int   $index Item index
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    private function render_item_hidden_fields( array $item, int $index ): string {
        $prefix = $this->config['name_prefix'];
        ob_start();
        ?>
        <input type="hidden" name="<?php echo esc_attr( $prefix ); ?>[<?php echo $index; ?>][product_id]"
               value="<?php echo esc_attr( $item['product_id'] ?? '' ); ?>">
        <input type="hidden" name="<?php echo esc_attr( $prefix ); ?>[<?php echo $index; ?>][price_id]"
               value="<?php echo esc_attr( $item['price_id'] ?? '' ); ?>">
        <input type="hidden" name="<?php echo esc_attr( $prefix ); ?>[<?php echo $index; ?>][name]"
               value="<?php echo esc_attr( $item['name'] ?? '' ); ?>">
        <input type="hidden" name="<?php echo esc_attr( $prefix ); ?>[<?php echo $index; ?>][price]"
               value="<?php echo esc_attr( (string) $item['price'] ); ?>">
        <?php
        return ob_get_clean();
    }

    /**
     * Render totals section
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    private function render_totals(): string {
        $subtotal = $this->config['subtotal'] ?: $this->calculate_subtotal();
        $discount = $this->config['discount'];
        $tax      = $this->config['tax'];
        $total    = $this->config['total'] ?: ( $subtotal - $discount + $tax );

        ob_start();
        ?>
        <div class="order-items-totals">
            <div class="totals-row">
                <span class="label"><?php esc_html_e( 'Subtotal:', 'arraypress' ); ?></span>
                <span class="value subtotal-amount" data-value="<?php echo esc_attr( (string) $subtotal ); ?>">
					<?php echo esc_html( $this->format_currency( $subtotal ) ); ?>
				</span>
            </div>

            <?php if ( $discount > 0 ) : ?>
                <div class="totals-row discount">
					<span class="label">
						<?php esc_html_e( 'Discount', 'arraypress' ); ?>
                        <?php if ( ! empty( $this->config['discount_code'] ) ) : ?>
                            <span class="discount-code">(<?php echo esc_html( $this->config['discount_code'] ); ?>
                                )</span>
                        <?php endif; ?>
					</span>
                    <span class="value">-<?php echo esc_html( $this->format_currency( $discount ) ); ?></span>
                </div>
            <?php endif; ?>

            <?php if ( $tax > 0 ) : ?>
                <div class="totals-row tax">
                    <span class="label"><?php esc_html_e( 'Tax:', 'arraypress' ); ?></span>
                    <span class="value"><?php echo esc_html( $this->format_currency( $tax ) ); ?></span>
                </div>
            <?php endif; ?>

            <div class="totals-row total">
                <span class="label"><?php esc_html_e( 'Total:', 'arraypress' ); ?></span>
                <span class="value total-amount"><?php echo esc_html( $this->format_currency( $total ) ); ?></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render template for JavaScript
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    private function render_template(): string {
        ob_start();
        ?>
        <script type="text/template" class="order-item-template">
            <tr class="order-item" data-index="{{index}}" data-product-id="{{product_id}}">
                <td class="column-product">
                    <div class="product-info">
                        {{thumbnail_html}}
                        <span class="product-name">{{name}}</span>
                    </div>
                    <input type="hidden"
                           name="<?php echo esc_attr( $this->config['name_prefix'] ); ?>[{{index}}][product_id]"
                           value="{{product_id}}">
                    <input type="hidden"
                           name="<?php echo esc_attr( $this->config['name_prefix'] ); ?>[{{index}}][price_id]"
                           value="{{price_id}}">
                    <input type="hidden" name="<?php echo esc_attr( $this->config['name_prefix'] ); ?>[{{index}}][name]"
                           value="{{name}}">
                    <input type="hidden"
                           name="<?php echo esc_attr( $this->config['name_prefix'] ); ?>[{{index}}][price]"
                           value="{{price}}">
                </td>
                <?php if ( $this->config['show_quantity'] ) : ?>
                    <td class="column-quantity">
                        <input type="number"
                               name="<?php echo esc_attr( $this->config['name_prefix'] ); ?>[{{index}}][quantity]"
                               value="1" min="1" class="quantity-input" data-action="update-quantity">
                    </td>
                <?php endif; ?>
                <td class="column-price" data-price="{{price}}">{{price_formatted}}</td>
                <td class="column-subtotal">
                    <span class="item-subtotal">{{subtotal_formatted}}</span>
                </td>
                <td class="column-actions">
                    <button type="button" class="button-link" data-action="remove-item"
                            title="<?php echo esc_attr__( 'Remove', 'arraypress' ); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </td>
            </tr>
        </script>
        <?php
        return ob_get_clean();
    }

}