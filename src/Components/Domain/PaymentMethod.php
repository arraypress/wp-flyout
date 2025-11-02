<?php
/**
 * PaymentMethod Component
 *
 * Displays payment method information with icons.
 *
 * @package     ArrayPress\WPFlyout\Components\Domain
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Domain;

use ArrayPress\WPFlyout\Traits\Renderable;

class PaymentMethod {
    use Renderable;

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
            'id'          => '',
            'method'      => 'card', // Changed from 'type' to avoid conflict
            'brand'       => '',
            'last4'       => '',
            'exp_month'   => '',
            'exp_year'    => '',
            'holder_name' => '',
            'email'       => '',
            'is_default'  => false,
            'status'      => 'active',
            'provider'    => '',
            'show_icon'   => true,
            'show_expiry' => true,
            'format'      => 'default',
            'class'       => ''
    ];

    /**
     * Icon mappings
     *
     * @var array
     */
    private const ICONS = [
            'bank'   => 'bank',
            'crypto' => 'tickets-alt',
            'wallet' => 'smartphone',
            'paypal' => 'money-alt'
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
            $this->config['id'] = 'payment-method-' . wp_generate_uuid4();
        }
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        $classes = [
                'wp-flyout-payment-method',
                'format-' . $this->config['format'],
                'payment-' . $this->config['method']
        ];

        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">

            <?php if ( $this->config['show_icon'] ) : ?>
                <div class="payment-icon">
                    <?php echo $this->get_payment_icon(); ?>
                </div>
            <?php endif; ?>

            <div class="payment-details">
                <div class="payment-primary">
                    <?php echo $this->get_payment_display(); ?>

                    <?php if ( $this->config['is_default'] ) : ?>
                        <span class="payment-badge default"><?php esc_html_e( 'Default', 'arraypress' ); ?></span>
                    <?php endif; ?>

                    <?php if ( $this->config['status'] === 'expired' ) : ?>
                        <span class="payment-badge expired"><?php esc_html_e( 'Expired', 'arraypress' ); ?></span>
                    <?php endif; ?>
                </div>

                <?php if ( $this->config['format'] === 'detailed' ) : ?>
                    <?php if ( ! empty( $this->config['holder_name'] ) ) : ?>
                        <div class="payment-holder"><?php echo esc_html( $this->config['holder_name'] ); ?></div>
                    <?php endif; ?>

                    <?php if ( ! empty( $this->config['provider'] ) ) : ?>
                        <div class="payment-provider">
                            <?php printf( esc_html__( 'via %s', 'arraypress' ), esc_html( ucfirst( $this->config['provider'] ) ) ); ?>
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
     * @return string
     */
    private function get_payment_icon(): string {
        if ( $this->config['method'] === 'card' && $this->config['brand'] ) {
            return $this->get_card_icon( $this->config['brand'] );
        }

        $icon = self::ICONS[ $this->config['method'] ] ?? 'money-alt';

        return '<span class="dashicons dashicons-' . esc_attr( $icon ) . '"></span>';
    }

    /**
     * Get card brand icon
     *
     * @param string $brand Card brand
     *
     * @return string
     */
    private function get_card_icon( string $brand ): string {
        $brand = strtolower( $brand );

        return '<span class="payment-brand-icon brand-' . esc_attr( $brand ) . '"></span>';
    }

    /**
     * Get payment display text
     *
     * @return string
     */
    private function get_payment_display(): string {
        if ( $this->config['method'] === 'card' ) {
            $display = '';

            if ( ! empty( $this->config['brand'] ) ) {
                $display .= '<span class="payment-brand">' . ucfirst( $this->config['brand'] ) . '</span> ';
            }

            if ( ! empty( $this->config['last4'] ) ) {
                $display .= '<span class="payment-last4">•••• ' . $this->config['last4'] . '</span>';
            }

            if ( $this->config['show_expiry'] && ! empty( $this->config['exp_month'] ) && ! empty( $this->config['exp_year'] ) ) {
                $display .= sprintf(
                        ' <span class="payment-expiry">(%02d/%02d)</span>',
                        $this->config['exp_month'],
                        $this->config['exp_year'] % 100
                );
            }

            return $display;
        }

        if ( $this->config['method'] === 'bank' && ! empty( $this->config['last4'] ) ) {
            return sprintf(
                    '<span class="payment-bank">%s</span>',
                    sprintf( esc_html__( 'Bank Account •••• %s', 'arraypress' ), esc_html( $this->config['last4'] ) )
            );
        }

        if ( $this->config['method'] === 'paypal' && ! empty( $this->config['email'] ) ) {
            return sprintf(
                    '<span class="payment-paypal">PayPal (%s)</span>',
                    esc_html( $this->config['email'] )
            );
        }

        return '<span class="payment-type">' . ucfirst( $this->config['method'] ) . '</span>';
    }

}