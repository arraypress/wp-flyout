<?php
/**
 * Line Items Component
 *
 * Manages line items with AJAX product selection, quantities, and pricing.
 * Used for creating orders/invoices with editable prices.
 *
 * @package     ArrayPress\WPFlyout\Components\Interactive
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     4.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Interfaces\Renderable;
use ArrayPress\WPFlyout\Traits\CurrencyFormatter;
use ArrayPress\WPFlyout\Traits\HtmlAttributes;

class LineItems implements Renderable {
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
		'name'           => 'line_items',
		'items'          => [],
		'currency'       => 'USD',
		'editable_price' => false,  // Allow manual price editing
		'ajax_search'    => '',     // AJAX action for product search
		'ajax_details'   => '',     // AJAX action for product details
		'placeholder'    => 'Search for products...',
		'empty_text'     => 'No items added yet.',
		'add_text'       => 'Add Item',
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
			$this->config['id'] = 'line-items-' . wp_generate_uuid4();
		}

		// Ensure items is array
		if ( ! is_array( $this->config['items'] ) ) {
			$this->config['items'] = [];
		}
	}

	/**
	 * Calculate total
	 *
	 * @return int Total in cents
	 */
	private function calculate_total(): int {
		$total = 0;
		foreach ( $this->config['items'] as $item ) {
			$price    = (int) ( $item['price'] ?? 0 );
			$quantity = (int) ( $item['quantity'] ?? 1 );
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
		$classes = [ 'wp-flyout-line-items' ];
		if ( ! empty( $this->config['class'] ) ) {
			$classes[] = $this->config['class'];
		}

		$data = [
			'name'           => $this->config['name'],
			'currency'       => $this->config['currency'],
			'editable-price' => $this->config['editable_price'] ? '1' : '0',
			'details-action' => $this->config['ajax_details']
		];

		ob_start();
		?>
		<div id="<?php echo esc_attr( $this->config['id'] ); ?>"
		     class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			<?php echo $this->build_data_attributes( $data ); ?>>

			<?php if ( $this->config['ajax_search'] ) : ?>
				<?php $this->render_product_selector(); ?>
			<?php endif; ?>

			<div class="line-items-table">
				<?php $this->render_items_table(); ?>
			</div>

			<?php $this->render_total(); ?>
			<?php $this->render_item_template(); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render product selector
	 */
	private function render_product_selector(): void {
		$nonce = wp_create_nonce( 'line_items_' . $this->config['ajax_search'] );
		?>
		<div class="line-items-selector">
			<select class="product-ajax-select"
			        data-ajax="<?php echo esc_attr( $this->config['ajax_search'] ); ?>"
			        data-placeholder="<?php echo esc_attr( $this->config['placeholder'] ); ?>"
			        data-nonce="<?php echo esc_attr( $nonce ); ?>">
			</select>
			<button type="button" class="button" data-action="add-item">
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
			<div class="line-items-empty">
				<span class="dashicons dashicons-cart"></span>
				<p><?php echo esc_html( $this->config['empty_text'] ); ?></p>
			</div>
			<?php
			return;
		}
		?>
		<table>
			<thead>
			<tr>
				<th class="column-item"><?php esc_html_e( 'Item', 'wp-flyout' ); ?></th>
				<th class="column-quantity"><?php esc_html_e( 'Qty', 'wp-flyout' ); ?></th>
				<th class="column-price"><?php esc_html_e( 'Price', 'wp-flyout' ); ?></th>
				<th class="column-total"><?php esc_html_e( 'Total', 'wp-flyout' ); ?></th>
				<th class="column-actions"></th>
			</tr>
			</thead>
			<tbody class="line-items-list">
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
		$price    = (int) ( $item['price'] ?? 0 );
		$quantity = (int) ( $item['quantity'] ?? 1 );
		$total    = $price * $quantity;
		?>
		<tr class="line-item" data-index="<?php echo $index; ?>"
		    data-item-id="<?php echo esc_attr( $item['id'] ?? '' ); ?>">

			<td class="column-item">
				<div>
					<?php if ( ! empty( $item['thumbnail'] ) ) : ?>
						<img src="<?php echo esc_url( $item['thumbnail'] ); ?>"
						     alt="<?php echo esc_attr( $item['name'] ?? '' ); ?>"
						     class="item-thumbnail">
					<?php else : ?>
						<div class="item-thumbnail-placeholder">
							<span class="dashicons dashicons-format-image"></span>
						</div>
					<?php endif; ?>
					<span><?php echo esc_html( $item['name'] ?? '' ); ?></span>
				</div>
				<input type="hidden"
				       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][id]"
				       value="<?php echo esc_attr( $item['id'] ?? '' ); ?>">
				<input type="hidden"
				       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][name]"
				       value="<?php echo esc_attr( $item['name'] ?? '' ); ?>">
			</td>

			<td class="column-quantity">
				<input type="number"
				       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][quantity]"
				       value="<?php echo esc_attr( (string) $quantity ); ?>"
				       min="1"
				       class="quantity-input small-text"
				       data-action="update-quantity">
			</td>

			<td class="column-price">
				<?php if ( $this->config['editable_price'] ) : ?>
					<input type="text"
					       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][price]"
					       value="<?php echo esc_attr( $this->format_currency( $price, $this->config['currency'] ) ); ?>"
					       class="price-input small-text"
					       data-cents="<?php echo esc_attr( (string) $price ); ?>"
					       data-action="update-price">
				<?php else : ?>
					<span data-price="<?php echo esc_attr( (string) $price ); ?>">
                        <?php echo esc_html( $this->format_currency( $price, $this->config['currency'] ) ); ?>
                    </span>
					<input type="hidden"
					       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][price]"
					       value="<?php echo esc_attr( (string) $price ); ?>">
				<?php endif; ?>
			</td>

			<td class="column-total">
				<span class="item-total"><?php echo esc_html( $this->format_currency( $total, $this->config['currency'] ) ); ?></span>
			</td>

			<td class="column-actions">
				<button type="button" class="button-link" data-action="remove-item">
					<span class="dashicons dashicons-trash"></span>
				</button>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render total section
	 */
	private function render_total(): void {
		$total = $this->calculate_total();
		?>
		<div class="line-items-total">
			<span class="total-label"><?php esc_html_e( 'Total:', 'wp-flyout' ); ?></span>
			<span class="total-amount" data-value="<?php echo esc_attr( (string) $total ); ?>">
                <?php echo esc_html( $this->format_currency( $total, $this->config['currency'] ) ); ?>
            </span>
		</div>
		<?php
	}

	/**
	 * Render JavaScript template for dynamic items
	 */
	private function render_item_template(): void {
		?>
		<script type="text/template" class="line-item-template">
			<tr class="line-item" data-item-id="{{item_id}}">
				<td class="column-item">
					<div>
						{{thumbnail_html}}
						<span>{{name}}</span>
					</div>
					<input type="hidden" name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][id]"
					       value="{{item_id}}">
					<input type="hidden" name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][name]"
					       value="{{name}}">
				</td>
				<td class="column-quantity">
					<input type="number"
					       name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][quantity]"
					       value="1"
					       min="1"
					       class="quantity-input small-text"
					       data-action="update-quantity">
				</td>
				<td class="column-price">
					<?php if ( $this->config['editable_price'] ) : ?>
						<input type="text"
						       name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][price]"
						       value="{{price_formatted}}"
						       class="price-input small-text"
						       data-cents="{{price}}"
						       data-action="update-price">
					<?php else : ?>
						<span data-price="{{price}}">{{price_formatted}}</span>
						<input type="hidden"
						       name="<?php echo esc_attr( $this->config['name'] ); ?>[{{index}}][price]"
						       value="{{price}}">
					<?php endif; ?>
				</td>
				<td class="column-total">
					<span class="item-total">{{total_formatted}}</span>
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