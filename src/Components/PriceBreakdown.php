<?php
/**
 * Price Breakdown Component
 *
 * Displays detailed price calculations with line items, taxes, and discounts.
 * Useful for invoices, quotes, and order summaries.
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Traits\Renderable;

/**
 * Class PriceBreakdown
 *
 * Creates detailed price breakdowns with calculations.
 *
 * @since 1.0.0
 */
class PriceBreakdown {
	use Renderable;

	/**
	 * Line items
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $items = [];

	/**
	 * Adjustments (discounts, fees, taxes)
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $adjustments = [];

	/**
	 * Component configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [
		'class'             => 'wp-flyout-price-breakdown',
		'currency_symbol'   => '$',
		'currency_position' => 'before',
		'decimal_places'    => 2,
		'show_quantities'   => true,
		'collapsible'       => false
	];

	/**
	 * Constructor
	 *
	 * @param array $config Configuration options
	 *
	 * @since 1.0.0
	 *
	 */
	public function __construct( array $config = [] ) {
		$this->config = array_merge( $this->config, $config );
	}

	/**
	 * Add a line item
	 *
	 * @param string $name     Item name
	 * @param float  $price    Unit price
	 * @param int    $quantity Quantity
	 * @param array  $meta     Additional metadata
	 *
	 * @return self
	 * @since 1.0.0
	 *
	 */
	public function add_item( string $name, float $price, int $quantity = 1, array $meta = [] ): self {
		$this->items[] = array_merge( [
			'name'        => $name,
			'price'       => $price,
			'quantity'    => $quantity,
			'description' => ''
		], $meta );

		return $this;
	}

	/**
	 * Add an adjustment
	 *
	 * @param string $type   Type: 'discount', 'tax', 'fee', 'shipping'
	 * @param string $label  Label
	 * @param float  $amount Amount (positive or negative)
	 * @param array  $meta   Additional metadata
	 *
	 * @return self
	 * @since 1.0.0
	 *
	 */
	public function add_adjustment( string $type, string $label, float $amount, array $meta = [] ): self {
		$this->adjustments[] = array_merge( [
			'type'       => $type,
			'label'      => $label,
			'amount'     => $amount,
			'percentage' => null
		], $meta );

		return $this;
	}

	/**
	 * Render the component
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 *
	 */
	public function render(): string {
		$subtotal = $this->calculate_subtotal();
		$total    = $this->calculate_total( $subtotal );

		ob_start();
		?>
        <div class="<?php echo esc_attr( $this->config['class'] ); ?>">
			<?php if ( ! empty( $this->items ) ) : ?>
                <div class="breakdown-items">
                    <table class="breakdown-table">
                        <thead>
                        <tr>
                            <th class="item-name">Item</th>
							<?php if ( $this->config['show_quantities'] ) : ?>
                                <th class="item-quantity">Qty</th>
                                <th class="item-price">Price</th>
							<?php endif; ?>
                            <th class="item-total">Total</th>
                        </tr>
                        </thead>
                        <tbody>
						<?php foreach ( $this->items as $item ) : ?>
							<?php echo $this->render_item( $item ); ?>
						<?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
			<?php endif; ?>

            <div class="breakdown-summary">
                <div class="summary-row subtotal">
                    <span class="summary-label">Subtotal</span>
                    <span class="summary-value"><?php echo $this->format_currency( $subtotal ); ?></span>
                </div>

				<?php foreach ( $this->adjustments as $adjustment ) : ?>
					<?php echo $this->render_adjustment( $adjustment, $subtotal ); ?>
				<?php endforeach; ?>

                <div class="summary-row total">
                    <span class="summary-label">Total</span>
                    <span class="summary-value"><?php echo $this->format_currency( $total ); ?></span>
                </div>
            </div>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a line item
	 *
	 * @param array $item Item data
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 *
	 */
	private function render_item( array $item ): string {
		$line_total = $item['price'] * $item['quantity'];

		ob_start();
		?>
        <tr class="breakdown-item">
            <td class="item-name">
				<?php echo esc_html( $item['name'] ); ?>
				<?php if ( ! empty( $item['description'] ) ) : ?>
                    <small class="item-description"><?php echo esc_html( $item['description'] ); ?></small>
				<?php endif; ?>
            </td>
			<?php if ( $this->config['show_quantities'] ) : ?>
                <td class="item-quantity"><?php echo esc_html( $item['quantity'] ); ?></td>
                <td class="item-price"><?php echo $this->format_currency( $item['price'] ); ?></td>
			<?php endif; ?>
            <td class="item-total"><?php echo $this->format_currency( $line_total ); ?></td>
        </tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render an adjustment
	 *
	 * @param array $adjustment Adjustment data
	 * @param float $subtotal   Subtotal for percentage calculations
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 *
	 */
	private function render_adjustment( array $adjustment, float $subtotal ): string {
		$class = 'summary-row adjustment-' . $adjustment['type'];

		ob_start();
		?>
        <div class="<?php echo esc_attr( $class ); ?>">
			<span class="summary-label">
				<?php echo esc_html( $adjustment['label'] ); ?>
				<?php if ( ! empty( $adjustment['percentage'] ) ) : ?>
                    <small>(<?php echo esc_html( $adjustment['percentage'] ); ?>%)</small>
				<?php endif; ?>
			</span>
            <span class="summary-value">
				<?php if ( $adjustment['type'] === 'discount' && $adjustment['amount'] > 0 ) : ?>
                    -<?php echo $this->format_currency( abs( $adjustment['amount'] ) ); ?>
				<?php else : ?>
					<?php echo $this->format_currency( $adjustment['amount'] ); ?>
				<?php endif; ?>
			</span>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Calculate subtotal
	 *
	 * @return float Subtotal
	 * @since 1.0.0
	 *
	 */
	private function calculate_subtotal(): float {
		$subtotal = 0;
		foreach ( $this->items as $item ) {
			$subtotal += $item['price'] * $item['quantity'];
		}

		return $subtotal;
	}

	/**
	 * Calculate total
	 *
	 * @param float $subtotal Subtotal
	 *
	 * @return float Total
	 * @since 1.0.0
	 *
	 */
	private function calculate_total( float $subtotal ): float {
		$total = $subtotal;

		foreach ( $this->adjustments as $adjustment ) {
			if ( $adjustment['type'] === 'discount' ) {
				$total -= abs( $adjustment['amount'] );
			} else {
				$total += $adjustment['amount'];
			}
		}

		return max( 0, $total );
	}

	/**
	 * Format currency
	 *
	 * @param float $amount Amount
	 *
	 * @return string Formatted currency
	 * @since 1.0.0
	 *
	 */
	private function format_currency( float $amount ): string {
		$formatted = number_format( $amount, $this->config['decimal_places'] );

		if ( $this->config['currency_position'] === 'after' ) {
			return $formatted . $this->config['currency_symbol'];
		}

		return $this->config['currency_symbol'] . $formatted;
	}

}