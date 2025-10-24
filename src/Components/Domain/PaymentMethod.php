<?php
/**
 * Payment Method Component
 *
 * Displays payment method information including cards, bank accounts, and digital wallets.
 * Supports various payment providers and card brands.
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Domain;

use ArrayPress\WPFlyout\Traits\Renderable;

/**
 * Class PaymentMethod
 *
 * Creates payment method displays with brand icons and masked numbers.
 *
 * @since 1.0.0
 */
class PaymentMethod {
    use Renderable;

    /**
     * Payment method data
     *
     * @since 1.0.0
     * @var array
     */
    private array $payment = [];

    /**
     * Component configuration
     *
     * @since 1.0.0
     * @var array
     */
    private array $config = [
            'class'       => 'wp-flyout-payment-method',
            'show_icon'   => true,
            'show_expiry' => true,
            'format'      => 'default' // 'default', 'compact', 'detailed'
    ];

    /**
     * Constructor
     *
     * @param array $payment Payment data
     * @param array $config  Configuration options
     *
     * @since 1.0.0
     *
     */
    public function __construct( array $payment, array $config = [] ) {
        $this->payment = array_merge( [
                'type'        => 'card', // 'card', 'bank', 'paypal', 'crypto'
                'brand'       => '',
                'last4'       => '',
                'exp_month'   => '',
                'exp_year'    => '',
                'holder_name' => '',
                'is_default'  => false,
                'provider'    => '', // 'stripe', 'paypal', etc.
                'status'      => 'active' // 'active', 'expired', 'pending'
        ], $payment );

        $this->config = array_merge( $this->config, $config );
    }

    /**
     * Render the component
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    public function render(): string {
        $class = $this->config['class'] . ' format-' . $this->config['format'];
        $class .= ' payment-' . $this->payment['type'];

        ob_start();
        ?>
        <div class="<?php echo esc_attr( $class ); ?>">
            <?php if ( $this->config['show_icon'] ) : ?>
                <div class="payment-icon">
                    <?php echo $this->get_payment_icon(); ?>
                </div>
            <?php endif; ?>

            <div class="payment-details">
                <div class="payment-primary">
                    <?php echo $this->get_payment_display(); ?>

                    <?php if ( $this->payment['is_default'] ) : ?>
                        <span class="payment-badge default"><?php esc_html_e( 'Default', 'arraypress' ); ?></span>
                    <?php endif; ?>

                    <?php if ( $this->payment['status'] === 'expired' ) : ?>
                        <span class="payment-badge expired"><?php esc_html_e( 'Expired', 'arraypress' ); ?></span>
                    <?php endif; ?>
                </div>

                <?php if ( $this->config['format'] === 'detailed' ) : ?>
                    <?php if ( ! empty( $this->payment['holder_name'] ) ) : ?>
                        <div class="payment-holder"><?php echo esc_html( $this->payment['holder_name'] ); ?></div>
                    <?php endif; ?>

                    <?php if ( ! empty( $this->payment['provider'] ) ) : ?>
                        <div class="payment-provider">
                            <?php
                            /* translators: %s: payment provider name (e.g., Stripe, PayPal) */
                            printf( esc_html__( 'via %s', 'arraypress' ), esc_html( ucfirst( $this->payment['provider'] ) ) );
                            ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get payment icon HTML
     *
     * @return string Icon HTML
     * @since 1.0.0
     *
     */
    private function get_payment_icon(): string {
        if ( $this->payment['type'] === 'card' ) {
            return $this->get_card_icon( $this->payment['brand'] );
        }

        $icons = [
                'bank'   => 'bank',
                'paypal' => 'money',
                'crypto' => 'bitcoin',
                'wallet' => 'smartphone'
        ];

        $icon = $icons[ $this->payment['type'] ] ?? 'money-alt';

        return '<span class="dashicons dashicons-' . $icon . '"></span>';
    }

    /**
     * Get card brand icon
     *
     * @param string $brand Card brand
     *
     * @return string Icon HTML
     * @since 1.0.0
     *
     */
    private function get_card_icon( string $brand ): string {
        $brand = strtolower( $brand );

        // Map brands to dashicons (limited options)
        $icons = [
                'visa'       => '💳',
                'mastercard' => '💳',
                'amex'       => '💳',
                'discover'   => '💳',
                'default'    => '💳'
        ];

        $icon = $icons[ $brand ] ?? $icons['default'];

        return '<span class="payment-brand-icon brand-' . esc_attr( $brand ) . '">' . $icon . '</span>';
    }

    /**
     * Get payment display text
     *
     * @return string Payment display
     * @since 1.0.0
     *
     */
    private function get_payment_display(): string {
        if ( $this->payment['type'] === 'card' ) {
            $display = '';

            if ( ! empty( $this->payment['brand'] ) ) {
                $display .= '<span class="payment-brand">' . ucfirst( $this->payment['brand'] ) . '</span> ';
            }

            if ( ! empty( $this->payment['last4'] ) ) {
                $display .= '<span class="payment-last4">•••• ' . $this->payment['last4'] . '</span>';
            }

            if ( $this->config['show_expiry'] && ! empty( $this->payment['exp_month'] ) && ! empty( $this->payment['exp_year'] ) ) {
                $display .= ' <span class="payment-expiry">(' .
                            sprintf( '%02d/%02d', $this->payment['exp_month'], $this->payment['exp_year'] % 100 ) .
                            ')</span>';
            }

            return $display;
        }

        if ( $this->payment['type'] === 'bank' && ! empty( $this->payment['last4'] ) ) {
            return '<span class="payment-bank">' .
                   sprintf(
                   /* translators: %s: last 4 digits of bank account */
                           esc_html__( 'Bank Account •••• %s', 'arraypress' ),
                           esc_html( $this->payment['last4'] )
                   ) .
                   '</span>';
        }

        if ( $this->payment['type'] === 'paypal' && ! empty( $this->payment['email'] ) ) {
            return '<span class="payment-paypal">' .
                   sprintf(
                   /* translators: %s: PayPal email address */
                           esc_html__( 'PayPal (%s)', 'arraypress' ),
                           esc_html( $this->payment['email'] )
                   ) .
                   '</span>';
        }

        return '<span class="payment-type">' . ucfirst( $this->payment['type'] ) . '</span>';
    }

}