<?php
/**
 * Order Items Component
 *
 * Manages order line items with product selection, quantities, and pricing.
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Traits\Renderable;

/**
 * Class OrderItems
 */
class OrderItems {
	use Renderable;

	/**
	 * Order items array
	 * @var array
	 */
	private array $items = [];

	/**
	 * Available products for selection
	 * @var array
	 */
	private array $products = [];

	/**
	 * Component configuration
	 * @var array
	 */
	private array $config = [
		'mode'              => 'edit', // 'edit' or 'view'
		'name_prefix'       => 'order_items',
		'currency_symbol'   => '$',
		'currency_position' => 'before', // 'before' or 'after'
		'subtotal'          => 0,
		'discount'          => 0,
		'discount_code'     => '',
		'tax'               => 0,
		'total'             => 0,
		'show_quantity'     => true,
		'max_items'         => 0, // 0 = unlimited
		'min_items'         => 0,
		'class'             => 'wp-flyout-order-items',
		'empty_text'        => 'No products added yet.',
		'add_text'          => 'Add Product',
		'select_text'       => 'Select a product...',
		'data'              => []
	];

	/**
	 * Constructor
	 */
	public function __construct( array $items = [], array $config = [] ) {
		$this->items  = $items;
		$this->config = array_merge( $this->config, $config );

		if ( isset( $config['products'] ) ) {
			$this->products = $config['products'];
			unset( $this->config['products'] );
		}
	}

	/**
	 * Add an item
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
	 * Set products list
	 */
	public function set_products( array $products ): self {
		$this->products = $products;

		return $this;
	}

	/**
	 * Calculate subtotal
	 */
	private function calculate_subtotal(): float {
		$total = 0;
		foreach ( $this->items as $item ) {
			$total += ( $item['price'] ?? 0 ) * ( $item['quantity'] ?? 1 );
		}

		return $total;
	}

	/**
	 * Format currency
	 */
	private function format_currency( float $amount ): string {
		$formatted = number_format( $amount, 2 );
		if ( $this->config['currency_position'] === 'after' ) {
			return $formatted . $this->config['currency_symbol'];
		}

		return $this->config['currency_symbol'] . $formatted;
	}

	/**
	 * Render the component
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
             data-currency="<?php echo esc_attr( $this->config['currency_symbol'] ); ?>"
             data-currency-position="<?php echo esc_attr( $this->config['currency_position'] ); ?>"
			<?php echo $this->render_data_attributes(); ?>>

			<?php if ( $is_edit && ! empty( $this->products ) ) : ?>
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
	 * Render data attributes
	 */
	private function render_data_attributes(): string {
		if ( empty( $this->config['data'] ) ) {
			return '';
		}

		$attrs = [];
		foreach ( $this->config['data'] as $key => $value ) {
			$attrs[] = sprintf( 'data-%s="%s"', esc_attr( $key ), esc_attr( $value ) );
		}

		return implode( ' ', $attrs );
	}

	/**
	 * Render product selector
	 */
	private function render_product_selector(): string {
		ob_start();
		?>
        <div class="order-items-selector">
            <select class="product-selector" data-action="select-product">
                <option value=""><?php echo esc_html( $this->config['select_text'] ); ?></option>
				<?php foreach ( $this->products as $product ) : ?>
                    <option value="<?php echo esc_attr( json_encode( $product ) ); ?>">
						<?php echo esc_html( $product['name'] ); ?>
						<?php if ( isset( $product['price'] ) ) : ?>
                            - <?php echo esc_html( $this->format_currency( $product['price'] ) ); ?>
						<?php endif; ?>
                    </option>
				<?php endforeach; ?>
            </select>
            <button type="button" class="button" data-action="add-product">
                <span class="dashicons dashicons-plus-alt"></span>
				<?php echo esc_html( $this->config['add_text'] ); ?>
            </button>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render items table
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
                        <th class="column-product">Product</th>
						<?php if ( $this->config['show_quantity'] ) : ?>
                            <th class="column-quantity">Qty</th>
						<?php endif; ?>
                        <th class="column-price">Price</th>
                        <th class="column-subtotal">Subtotal</th>
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
	 */
	private function render_item_row( array $item, int $index ): string {
		$is_edit  = $this->config['mode'] === 'edit';
		$subtotal = ( $item['price'] ?? 0 ) * ( $item['quantity'] ?? 1 );

		ob_start();
		?>
        <tr class="order-item" data-index="<?php echo $index; ?>">
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
                    <button type="button" class="button-link" data-action="remove-item" title="Remove">
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
                <span class="label">Subtotal:</span>
                <span class="value subtotal-amount" data-value="<?php echo esc_attr( (string) $subtotal ); ?>">
					<?php echo esc_html( $this->format_currency( $subtotal ) ); ?>
				</span>
            </div>

			<?php if ( $discount > 0 ) : ?>
                <div class="totals-row discount">
					<span class="label">
						Discount
						<?php if ( ! empty( $this->config['discount_code'] ) ) : ?>
                            <span class="discount-code">(<?php echo esc_html( $this->config['discount_code'] ); ?>)</span>
						<?php endif; ?>
					</span>
                    <span class="value">-<?php echo esc_html( $this->format_currency( $discount ) ); ?></span>
                </div>
			<?php endif; ?>

			<?php if ( $tax > 0 ) : ?>
                <div class="totals-row tax">
                    <span class="label">Tax:</span>
                    <span class="value"><?php echo esc_html( $this->format_currency( $tax ) ); ?></span>
                </div>
			<?php endif; ?>

            <div class="totals-row total">
                <span class="label">Total:</span>
                <span class="value total-amount"><?php echo esc_html( $this->format_currency( $total ) ); ?></span>
            </div>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render template for JavaScript
	 */
	private function render_template(): string {
		ob_start();
		?>
        <script type="text/template" class="order-item-template">
            <tr class="order-item" data-index="{{index}}">
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
                    <button type="button" class="button-link" data-action="remove-item" title="Remove">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </td>
            </tr>
        </script>
		<?php
		return ob_get_clean();
	}
}