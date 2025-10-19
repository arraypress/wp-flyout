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
use ArrayPress\WPFlyout\Traits\CurrencyFormatter;

/**
 * Class PriceBreakdown
 *
 * Creates detailed price breakdowns with calculations.
 *
 * @since 1.0.0
 */
class PriceBreakdown {
    use Renderable;
    use CurrencyFormatter;

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
            'class'           => 'wp-flyout-price-breakdown',
            'currency'        => 'USD', // Currency code for formatting
            'show_quantities' => true,
            'collapsible'     => false
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
     * @param int    $price    Unit price in cents
     * @param int    $quantity Quantity
     * @param array  $meta     Additional metadata
     *
     * @return self
     * @since 1.0.0
     *
     */
    public function add_item( string $name, int $price, int $quantity = 1, array $meta = [] ): self {
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
     * @param int    $amount Amount in cents (positive or negative)
     * @param array  $meta   Additional metadata
     *
     * @return self
     * @since 1.0.0
     *
     */
    public function add_adjustment( string $type, string $label, int $amount, array $meta = [] ): self {
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
                            <th class="item-name"><?php esc_html_e( 'Item', 'arraypress' ); ?></th>
                            <?php if ( $this->config['show_quantities'] ) : ?>
                                <th class="item-quantity"><?php esc_html_e( 'Qty', 'arraypress' ); ?></th>
                                <th class="item-price"><?php esc_html_e( 'Price', 'arraypress' ); ?></th>
                            <?php endif; ?>
                            <th class="item-total"><?php esc_html_e( 'Total', 'arraypress' ); ?></th>
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
                    <span class="summary-label"><?php esc_html_e( 'Subtotal', 'arraypress' ); ?></span>
                    <span class="summary-value"><?php echo $this->format_currency( $subtotal ); ?></span>
                </div>

                <?php foreach ( $this->adjustments as $adjustment ) : ?>
                    <?php echo $this->render_adjustment( $adjustment, $subtotal ); ?>
                <?php endforeach; ?>

                <div class="summary-row total">
                    <span class="summary-label"><?php esc_html_e( 'Total', 'arraypress' ); ?></span>
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
     * @param int   $subtotal   Subtotal for percentage calculations (in cents)
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    private function render_adjustment( array $adjustment, int $subtotal ): string {
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
     * @return int Subtotal in cents
     * @since 1.0.0
     *
     */
    private function calculate_subtotal(): int {
        $subtotal = 0;
        foreach ( $this->items as $item ) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        return $subtotal;
    }

    /**
     * Calculate total
     *
     * @param int $subtotal Subtotal in cents
     *
     * @return int Total in cents
     * @since 1.0.0
     *
     */
    private function calculate_total( int $subtotal ): int {
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

}