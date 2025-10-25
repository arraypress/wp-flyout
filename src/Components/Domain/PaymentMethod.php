<?php
/**
 * Payment Method Component
 *
 * Displays payment method information including cards, bank accounts, and digital wallets.
 * Supports various payment providers and card brands with official brand SVG icons.
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
                'status'      => 'active', // 'active', 'expired', 'pending'
                'email'       => '' // For PayPal and similar
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

        // For non-card payment types
        if ( $this->payment['type'] === 'paypal' ) {
            return $this->get_paypal_icon();
        }

        $icons = [
                'bank'   => 'bank',
                'crypto' => 'tickets-alt',
                'wallet' => 'smartphone'
        ];

        $icon = $icons[ $this->payment['type'] ] ?? 'money-alt';

        return '<span class="dashicons dashicons-' . esc_attr( $icon ) . '"></span>';
    }

    /**
     * Get card brand icon SVG
     *
     * Loads SVG from file for proper rendering of brand assets.
     *
     * @param string $brand Card brand
     *
     * @return string SVG icon HTML
     * @since 1.0.0
     */
    private function get_card_icon( string $brand ): string {
        $brand = strtolower( $brand );

        // Map brand names to SVG filenames
        $brand_files = [
            'visa'       => 'images/payment-methods/visa.svg',
            'mastercard' => 'images/payment-methods/mastercard.svg',
            'amex'       => 'images/payment-methods/amex.svg',
            'discover'   => 'images/payment-methods/discover.svg',
            'diners'     => 'images/payment-methods/diners.svg',
            'jcb'        => 'images/payment-methods/jcb.svg',
            'unionpay'   => 'images/payment-methods/unionpay.svg',
            'maestro'    => 'images/payment-methods/maestro.svg',
            'elo'        => 'images/payment-methods/elo.svg',
            'alipay'     => 'images/payment-methods/alipay.svg',
            'hiper'      => 'images/payment-methods/hiper.svg',
            'hipercard'  => 'images/payment-methods/hipercard.svg',
            'mir'        => 'images/payment-methods/mir.svg'
        ];

        $file = $brand_files[ $brand ] ?? 'images/payment-methods/generic.svg';

        $svg = wp_get_composer_file( __FILE__, $file );

        if ( $svg ) {
            return '<span class="payment-brand-icon brand-' . esc_attr( $brand ) . '">' . $svg . '</span>';
        }

        // Fallback to dashicon
        return '<span class="dashicons dashicons-money-alt"></span>';
    }

    /**
     * Get PayPal icon SVG
     *
     * @return string SVG icon HTML
     * @since 1.0.0
     */
    private function get_paypal_icon(): string {
        $svg = wp_get_composer_file( __FILE__, 'images/payment-methods/paypal.svg' );

        if ( $svg ) {
            return '<span class="payment-brand-icon brand-paypal">' . $svg . '</span>';
        }

        return '<span class="dashicons dashicons-money-alt"></span>';
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