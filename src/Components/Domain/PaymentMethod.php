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
     * Get card brand icon SVG
     *
     * Returns official brand SVG icons for all major payment methods.
     * Falls back to generic card icon for unknown brands.
     *
     * @param string $brand Card brand
     *
     * @return string SVG icon HTML
     * @since 1.0.0
     *
     */
    private function get_card_icon( string $brand ): string {
        $brand = strtolower( $brand );

        $svgs = [
                'visa'       => '<svg width="32" height="20" viewBox="0 0 32 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="32" height="20" rx="2" fill="#1434CB"/><path d="M13.8 14L15.2 6H17L15.6 14H13.8ZM22.5 6.2C22.1 6.1 21.5 6 20.7 6C18.9 6 17.6 6.9 17.6 8.2C17.6 9.2 18.5 9.7 19.2 10C19.9 10.3 20.2 10.5 20.2 10.8C20.2 11.3 19.6 11.5 19 11.5C18.2 11.5 17.7 11.4 17.1 11.1L16.9 11L16.7 12.4C17.1 12.6 17.9 12.8 18.7 12.8C20.6 12.8 21.9 11.9 21.9 10.5C21.9 9.7 21.4 9.1 20.3 8.6C19.7 8.3 19.3 8.1 19.3 7.7C19.3 7.4 19.7 7.1 20.4 7.1C21 7.1 21.5 7.2 21.9 7.4L22.1 7.5L22.3 6.2H22.5ZM25.5 6H24C23.5 6 23.1 6.1 22.9 6.6L20 14H21.9L22.3 12.9H24.6L24.8 14H26.5L25 6H25.5ZM23.5 8L24.2 11.4H22.7L23.5 8ZM12.5 6L10.7 11.7L10.5 10.7L9.8 7.1C9.7 6.6 9.3 6 8.7 6H5.5L5.5 6.3C6.3 6.5 7.1 6.8 7.8 7.2L9.5 14H11.4L14.4 6H12.5Z" fill="white"/></svg>',
                'mastercard' => '<svg width="32" height="20" viewBox="0 0 32 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="32" height="20" rx="2" fill="#252525"/><circle cx="12" cy="10" r="5.5" fill="#EB001B"/><circle cx="20" cy="10" r="5.5" fill="#F79E1B"/><path d="M16 14.5C17.1 13.6 17.8 12.2 17.8 10.6C17.8 9 17.1 7.6 16 6.7C14.9 7.6 14.2 9 14.2 10.6C14.2 12.2 14.9 13.6 16 14.5Z" fill="#FF5F00"/></svg>',
                'amex'       => '<svg width="32" height="20" viewBox="0 0 32 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="32" height="20" rx="2" fill="#006FCF"/><path d="M8.5 7H10.2L11 9.5L11.8 7H13.5L12 11H10.8L11.5 9L10.7 11H9.3L8.5 9L9.2 11H8L6.5 7H8.2L9 9.5L9.8 7H8.5ZM14 7H18V8.2H15.5V8.6H17.8V9.7H15.5V10.2H18V11.4H14V7ZM19 7H20.8L21.5 8.5L22.2 7H24L22.5 10L24 11.4H22.2L21.5 9.9L20.8 11.4H19L20.5 10L19 7Z" fill="white"/></svg>',
                'discover'   => '<svg width="32" height="20" viewBox="0 0 32 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="32" height="20" rx="2" fill="#FF6000"/><circle cx="24" cy="10" r="6" fill="#F68121"/><path d="M7 8H8.5V12H10V8H11.5V7H7V8ZM12 7H13.5V12H12V7ZM14.5 9.5C14.5 8.1 15.4 7 17 7C18.6 7 19.5 8.1 19.5 9.5C19.5 10.9 18.6 12 17 12C15.4 12 14.5 10.9 14.5 9.5ZM16 9.5C16 10.3 16.4 10.8 17 10.8C17.6 10.8 18 10.3 18 9.5C18 8.7 17.6 8.2 17 8.2C16.4 8.2 16 8.7 16 9.5Z" fill="white"/></svg>',
                'diners'     => '<svg width="32" height="20" viewBox="0 0 32 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="32" height="20" rx="2" fill="#0079BE"/><circle cx="12" cy="10" r="5" fill="none" stroke="white" stroke-width="1.5"/><circle cx="20" cy="10" r="5" fill="none" stroke="white" stroke-width="1.5"/><path d="M12 6C14.2 6 16 7.8 16 10C16 12.2 14.2 14 12 14" fill="white"/><path d="M20 6C17.8 6 16 7.8 16 10C16 12.2 17.8 14 20 14" fill="white"/></svg>',
                'jcb'        => '<svg width="32" height="20" viewBox="0 0 32 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="32" height="20" rx="2" fill="#0E4C96"/><path d="M8 6H11C12.7 6 14 7.3 14 9V14H11C9.3 14 8 12.7 8 11V6Z" fill="#fff"/><path d="M15 6H18C19.7 6 21 7.3 21 9V11C21 12.7 19.7 14 18 14H15V6Z" fill="#DB2128"/><path d="M22 6H25V11C25 12.7 23.7 14 22 14V6Z" fill="#339B36"/></svg>',
                'unionpay'   => '<svg width="32" height="20" viewBox="0 0 32 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="32" height="20" rx="2" fill="#002B7A"/><path d="M6 8H8L9 10L10 8H12L10.5 11H9L10 9L9 11H7L6 9L7 11H5.5L4 8H6ZM13 8H17V9H14.5V9.5H16.5V10.5H14.5V11H17V12H13V8ZM18 8H20L21 10.5L22 8H24L22.5 12H21L22 10L21 12H19L18 10L19 12H17.5L16 8H18Z" fill="white"/><path d="M8 13.5C8 13.2 8.2 13 8.5 13H11.5C11.8 13 12 13.2 12 13.5C12 13.8 11.8 14 11.5 14H8.5C8.2 14 8 13.8 8 13.5Z" fill="#E21836"/><path d="M20 13.5C20 13.2 20.2 13 20.5 13H23.5C23.8 13 24 13.2 24 13.5C24 13.8 23.8 14 23.5 14H20.5C20.2 14 20 13.8 20 13.5Z" fill="#00A651"/></svg>',
                'maestro'    => '<svg width="32" height="20" viewBox="0 0 32 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="32" height="20" rx="2" fill="#0099DF"/><circle cx="12" cy="10" r="5.5" fill="#ED0006"/><circle cx="20" cy="10" r="5.5" fill="#0099DF"/><path d="M16 14.5C17.1 13.6 17.8 12.2 17.8 10.6C17.8 9 17.1 7.6 16 6.7C14.9 7.6 14.2 9 14.2 10.6C14.2 12.2 14.9 13.6 16 14.5Z" fill="#6C6BBD"/></svg>',
                'elo'        => '<svg width="32" height="20" viewBox="0 0 32 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="32" height="20" rx="2" fill="#000"/><path d="M10 8L12 6L14 8L12 10L10 8Z" fill="#FFCB05"/><path d="M10 12L12 10L14 12L12 14L10 12Z" fill="#00A4E0"/><path d="M18 8L20 6L22 8L20 10L18 8Z" fill="#EF4123"/></svg>',
                'default'    => '<svg width="32" height="20" viewBox="0 0 32 20" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="32" height="20" rx="2" fill="#6B7280"/><rect x="4" y="5" width="24" height="3" rx="1" fill="white" opacity="0.6"/><rect x="4" y="10" width="16" height="2" rx="1" fill="white" opacity="0.8"/><rect x="4" y="14" width="12" height="2" rx="1" fill="white" opacity="0.8"/></svg>'
        ];

        $svg = $svgs[ $brand ] ?? $svgs['default'];

        return '<span class="payment-brand-icon brand-' . esc_attr( $brand ) . '">' . $svg . '</span>';
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