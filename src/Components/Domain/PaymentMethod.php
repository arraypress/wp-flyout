<?php
/**
 * PaymentMethod Component
 *
 * Displays payment method information with brand SVG icons.
 * Optimized for Stripe payment methods and order display.
 *
 * @package     ArrayPress\WPFlyout\Components\Domain
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     3.0.0
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
            'id'             => '',
            'payment_method' => '', // From DB: payment_method field
            'payment_brand'  => '', // From DB: payment_brand field (visa, mastercard, etc)
            'payment_last4'  => '', // From DB: payment_last4 field
            'is_default'     => false,
            'status'         => 'active',
            'show_icon'      => true,
            'format'         => 'default', // default, compact, detailed
            'class'          => ''
    ];

    /**
     * SVG file mappings for card brands
     *
     * @var array
     */
    private const CARD_BRANDS = [
            'visa'       => 'visa.svg',
            'mastercard' => 'mastercard.svg',
            'amex'       => 'amex.svg',
            'discover'   => 'discover.svg',
            'diners'     => 'diners.svg',
            'jcb'        => 'jcb.svg',
            'unionpay'   => 'unionpay.svg',
            'maestro'    => 'maestro.svg',
            'elo'        => 'elo.svg',
            'hiper'      => 'hiper.svg',
            'hipercard'  => 'hipercard.svg',
            'mir'        => 'mir.svg',
            'alipay'     => 'alipay.svg',
    ];

    /**
     * Icon mappings for non-card payment methods
     *
     * @var array
     */
    private const METHOD_ICONS = [
            'bank'   => 'bank',
            'crypto' => 'tickets-alt',
            'wallet' => 'smartphone',
            'paypal' => 'paypal.svg', // Use SVG if available
            'card'   => 'credit-card'
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
        // Skip rendering if no payment method
        if ( empty( $this->config['payment_method'] ) ) {
            return '';
        }

        $classes = [
                'wp-flyout-payment-method',
                'format-' . $this->config['format'],
                'method-' . $this->config['payment_method']
        ];

        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        if ( $this->config['payment_brand'] ) {
            $classes[] = 'brand-' . $this->config['payment_brand'];
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
                        <span class="payment-badge default"><?php esc_html_e( 'Default', 'wp-flyout' ); ?></span>
                    <?php endif; ?>

                    <?php if ( $this->config['status'] === 'expired' ) : ?>
                        <span class="payment-badge expired"><?php esc_html_e( 'Expired', 'wp-flyout' ); ?></span>
                    <?php endif; ?>
                </div>
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
        // For card payments, use brand SVG if available
        if ( $this->config['payment_method'] === 'card' && $this->config['payment_brand'] ) {
            return $this->get_card_brand_svg( $this->config['payment_brand'] );
        }

        // For PayPal, use SVG
        if ( $this->config['payment_method'] === 'paypal' ) {
            return $this->get_payment_svg( 'paypal' );
        }

        // For other payment methods, use dashicons
        $icon = self::METHOD_ICONS[ $this->config['payment_method'] ] ?? 'money-alt';

        // Check if it's an SVG file
        if ( str_ends_with( $icon, '.svg' ) ) {
            return $this->get_payment_svg( str_replace( '.svg', '', $icon ) );
        }

        return '<span class="dashicons dashicons-' . esc_attr( $icon ) . '"></span>';
    }

    /**
     * Get card brand SVG icon
     *
     * @param string $brand Card brand
     *
     * @return string
     */
    private function get_card_brand_svg( string $brand ): string {
        $brand = strtolower( $brand );

        // Check if we have an SVG for this brand
        if ( isset( self::CARD_BRANDS[ $brand ] ) ) {
            $svg_content = $this->load_svg( self::CARD_BRANDS[ $brand ] );
            if ( $svg_content ) {
                return $svg_content;
            }
        }

        // Fallback to generic card icon
        $svg_content = $this->load_svg( 'generic.svg' );
        if ( $svg_content ) {
            return $svg_content;
        }

        // Last resort - dashicon
        return '<span class="dashicons dashicons-credit-card"></span>';
    }

    /**
     * Get payment method SVG
     *
     * @param string $method Payment method
     *
     * @return string
     */
    private function get_payment_svg( string $method ): string {
        $svg_content = $this->load_svg( $method . '.svg' );

        if ( $svg_content ) {
            return $svg_content;
        }

        // Fallback to dashicon
        return '<span class="dashicons dashicons-money-alt"></span>';
    }

    /**
     * Load SVG file content
     *
     * @param string $filename SVG filename
     *
     * @return string|false SVG content or false on failure
     */
    private function load_svg( string $filename ) {
        return wp_get_composer_file(
                __FILE__,
                'images/payment-methods/' . $filename,
                true // Sanitize SVG
        );
    }

    /**
     * Get payment display text
     *
     * @return string
     */
    private function get_payment_display(): string {
        // Card payment display
        if ( $this->config['payment_method'] === 'card' ) {
            $display = '';

            // Brand name (capitalize properly)
            if ( ! empty( $this->config['payment_brand'] ) ) {
                $brand_display = $this->format_brand_name( $this->config['payment_brand'] );
                $display       .= '<span class="payment-brand">' . esc_html( $brand_display ) . '</span>';
            }

            // Last 4 digits
            if ( ! empty( $this->config['payment_last4'] ) ) {
                $display .= ' <span class="payment-last4">•••• ' . esc_html( $this->config['payment_last4'] ) . '</span>';
            }

            return $display ?: '<span class="payment-type">' . esc_html__( 'Card', 'wp-flyout' ) . '</span>';
        }

        // Bank payment display
        if ( $this->config['payment_method'] === 'bank' && ! empty( $this->config['payment_last4'] ) ) {
            return sprintf(
                    '<span class="payment-bank">%s</span>',
                    sprintf( esc_html__( 'Bank •••• %s', 'wp-flyout' ), esc_html( $this->config['payment_last4'] ) )
            );
        }

        // PayPal display
        if ( $this->config['payment_method'] === 'paypal' ) {
            return '<span class="payment-paypal">PayPal</span>';
        }

        // Generic payment method
        return '<span class="payment-type">' . esc_html( ucfirst( $this->config['payment_method'] ) ) . '</span>';
    }

    /**
     * Format brand name for display
     *
     * @param string $brand Brand identifier
     *
     * @return string Formatted brand name
     */
    private function format_brand_name( string $brand ): string {
        $brand_names = [
                'visa'       => 'Visa',
                'mastercard' => 'Mastercard',
                'amex'       => 'American Express',
                'discover'   => 'Discover',
                'diners'     => 'Diners Club',
                'jcb'        => 'JCB',
                'unionpay'   => 'UnionPay',
                'maestro'    => 'Maestro',
                'elo'        => 'Elo',
                'hiper'      => 'Hiper',
                'hipercard'  => 'Hipercard',
                'mir'        => 'MIR',
                'alipay'     => 'Alipay',
        ];

        return $brand_names[ strtolower( $brand ) ] ?? ucfirst( $brand );
    }

}