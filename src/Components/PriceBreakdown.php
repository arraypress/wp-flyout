<?php
/**
 * PriceBreakdown Component
 *
 * Displays a detailed price breakdown with line items and totals.
 * Supports interactive refund actions on individual items.
 *
 * @package     ArrayPress\WPFlyout\Components\Domain
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     3.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Interfaces\Renderable;
use ArrayPress\WPFlyout\Traits\CurrencyFormatter;

class PriceBreakdown implements Renderable {
    use CurrencyFormatter;

    /**
     * Component configuration
     *
     * @var array
     */
    private array $config;

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, self::get_defaults() );

        // Auto-generate ID if not provided
        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'price-breakdown-' . wp_generate_uuid4();
        }

        // Generate nonce for refunds if needed
        if ( $this->config['refundable'] && empty( $this->config['refund_nonce'] ) ) {
            $this->config['refund_nonce'] = wp_create_nonce( 'price_breakdown_refund' );
        }
    }

    /**
     * Get default configuration
     *
     * @return array
     */
    private static function get_defaults(): array {
        return [
                'id'              => '',
                'items'           => [],
                'subtotal'        => null,
                'tax'             => null,
                'discount'        => null,
                'total'           => 0,
                'currency'        => 'USD',
                'show_zero'       => false,
                'class'           => '',
                'highlight_total' => true,

            // Refund functionality
                'refundable'      => false,
                'refund_ajax'     => '',
                'refund_nonce'    => '',
                'order_id'        => '',
        ];
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        $classes = [ 'price-breakdown' ];
        if ( $this->config['refundable'] ) {
            $classes[] = 'refundable';
        }
        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
                <?php if ( $this->config['refundable'] ) : ?>
                    data-refund-ajax="<?php echo esc_attr( $this->config['refund_ajax'] ); ?>"
                    data-refund-nonce="<?php echo esc_attr( $this->config['refund_nonce'] ); ?>"
                    data-order-id="<?php echo esc_attr( $this->config['order_id'] ); ?>"
                <?php endif; ?>>

            <?php if ( ! empty( $this->config['items'] ) ) : ?>
                <div class="price-breakdown-items">
                    <?php foreach ( $this->config['items'] as $item ) : ?>
                        <?php $this->render_line_item( $item ); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="price-breakdown-summary">
                <?php $this->render_summary_lines(); ?>
            </div>

            <?php if ( $this->config['total'] !== null ) : ?>
                <div class="price-breakdown-total <?php echo $this->config['highlight_total'] ? 'highlighted' : ''; ?>">
                    <span class="label"><?php esc_html_e( 'Total', 'wp-flyout' ); ?></span>
                    <span class="amount" data-original-total="<?php echo esc_attr( $this->config['total'] ); ?>">
                        <?php echo $this->format_currency( $this->config['total'], $this->config['currency'] ); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a line item with optional refund capability
     *
     * @param array $item Item configuration
     */
    private function render_line_item( array $item ): void {
        $label       = $item['label'] ?? '';
        $amount      = $item['amount'] ?? 0;
        $quantity    = $item['quantity'] ?? null;
        $description = $item['description'] ?? '';
        $item_id     = $item['id'] ?? '';
        $product_id  = $item['product_id'] ?? '';
        $price_id    = $item['price_id'] ?? '';
        $refunded    = $item['refunded'] ?? false;
        $refundable  = $item['refundable'] ?? $this->config['refundable'];

        if ( empty( $label ) || ( ! $this->config['show_zero'] && $amount == 0 ) ) {
            return;
        }

        $item_classes = [ 'price-breakdown-item' ];
        if ( $refunded ) {
            $item_classes[] = 'refunded';
        }
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>"
             <?php if ( $item_id ) : ?>data-item-id="<?php echo esc_attr( $item_id ); ?>"<?php endif; ?>
             <?php if ( $product_id ) : ?>data-product-id="<?php echo esc_attr( $product_id ); ?>"<?php endif; ?>
             <?php if ( $price_id ) : ?>data-price-id="<?php echo esc_attr( $price_id ); ?>"<?php endif; ?>
             data-amount="<?php echo esc_attr( $amount ); ?>">

            <div class="item-details">
                <span class="item-label">
                    <?php echo esc_html( $label ); ?>
                    <?php if ( $refunded ) : ?>
                        <span class="refund-badge"><?php esc_html_e( 'Refunded', 'wp-flyout' ); ?></span>
                    <?php endif; ?>
                </span>
                <?php if ( $quantity !== null ) : ?>
                    <span class="item-quantity">× <?php echo esc_html( $quantity ); ?></span>
                <?php endif; ?>
                <?php if ( $description ) : ?>
                    <span class="item-description"><?php echo esc_html( $description ); ?></span>
                <?php endif; ?>
            </div>

            <div class="item-actions">
                <span class="item-amount <?php echo $refunded ? 'strikethrough' : ''; ?>">
                    <?php echo $this->format_currency( $amount, $this->config['currency'] ); ?>
                </span>

                <?php if ( $refundable && ! $refunded && $this->config['refundable'] ) : ?>
                    <button type="button"
                            class="price-breakdown-refund-btn"
                            title="<?php esc_attr_e( 'Refund this item', 'wp-flyout' ); ?>"
                            aria-label="<?php esc_attr_e( 'Refund', 'wp-flyout' ); ?>">
                        <span class="dashicons dashicons-undo"></span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render summary lines (subtotal, tax, discount, shipping)
     */
    private function render_summary_lines(): void {
        $lines = [
                'subtotal' => __( 'Subtotal', 'wp-flyout' ),
                'discount' => __( 'Discount', 'wp-flyout' ),
                'tax'      => __( 'Tax', 'wp-flyout' ),
        ];

        foreach ( $lines as $key => $label ) {
            if ( $this->config[ $key ] === null ) {
                continue;
            }

            $amount = $this->config[ $key ];
            if ( ! $this->config['show_zero'] && $amount == 0 ) {
                continue;
            }

            $class = 'price-breakdown-' . $key;
            if ( $key === 'discount' && $amount > 0 ) {
                $amount = - $amount; // Show discounts as negative
            }
            ?>
            <div class="<?php echo esc_attr( $class ); ?>" data-type="<?php echo esc_attr( $key ); ?>">
                <span class="label"><?php echo esc_html( $label ); ?></span>
                <span class="amount <?php echo $amount < 0 ? 'negative' : ''; ?>"
                      data-amount="<?php echo esc_attr( abs( $amount ) ); ?>">
                    <?php echo $this->format_currency( $amount, $this->config['currency'] ); ?>
                </span>
            </div>
            <?php
        }
    }

}