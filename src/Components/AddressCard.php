<?php
/**
 * Address Card Component
 *
 * Displays formatted addresses with support for international formats.
 * Handles billing and shipping addresses with copy and map functionality.
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Assets;
use ArrayPress\WPFlyout\Traits\Renderable;
use ArrayPress\WPFlyout\Traits\IconRenderer;

/**
 * Class AddressCard
 *
 * Creates formatted address displays with optional actions.
 *
 * @since 1.0.0
 */
class AddressCard {
    use Renderable;
    use IconRenderer;

    /**
     * Address data
     *
     * @since 1.0.0
     * @var array
     */
    private array $address = [];

    /**
     * Component configuration
     *
     * @since 1.0.0
     * @var array
     */
    private array $config = [
            'class'      => 'wp-flyout-address-card',
            'type'       => 'billing', // 'billing', 'shipping', 'general'
            'show_label' => true,
            'show_copy'  => true,
            'show_map'   => true,
            'format'     => 'default' // 'default', 'inline', 'compact'
    ];

    /**
     * Constructor
     *
     * @param array $address Address data
     * @param array $config  Configuration options
     *
     * @since 1.0.0
     *
     */
    public function __construct( array $address, array $config = [] ) {
        $this->address = array_merge( [
                'name'      => '',
                'company'   => '',
                'address_1' => '',
                'address_2' => '',
                'city'      => '',
                'state'     => '',
                'postcode'  => '',
                'country'   => '',
                'phone'     => '',
                'email'     => ''
        ], $address );

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
        Assets::enqueue_component('address-card');

        $class = $this->config['class'] . ' format-' . $this->config['format'];

        ob_start();
        ?>
        <div class="<?php echo esc_attr( $class ); ?>" data-type="<?php echo esc_attr( $this->config['type'] ); ?>">
            <?php if ( $this->config['show_label'] ) : ?>
                <div class="address-header">
                    <h4 class="address-label">
                        <?php if ( $this->config['type'] === 'billing' ) : ?>
                            <?php echo $this->render_icon( 'money-alt' ); ?>
                            <?php esc_html_e( 'Billing Address', 'wp-flyout' ); ?>
                        <?php elseif ( $this->config['type'] === 'shipping' ) : ?>
                            <?php echo $this->render_icon( 'location' ); ?>
                            <?php esc_html_e( 'Shipping Address', 'wp-flyout' ); ?>
                        <?php else : ?>
                            <?php echo $this->render_icon( 'admin-home' ); ?>
                            <?php esc_html_e( 'Address', 'wp-flyout' ); ?>
                        <?php endif; ?>
                    </h4>

                    <?php if ( $this->config['show_copy'] || $this->config['show_map'] ) : ?>
                        <div class="address-actions">
                            <?php if ( $this->config['show_copy'] ) : ?>
                                <button type="button" class="button-link" data-action="copy-address">
                                    <?php echo $this->render_icon( 'clipboard' ); ?>
                                    <?php esc_html_e( 'Copy', 'wp-flyout' ); ?>
                                </button>
                            <?php endif; ?>

                            <?php if ( $this->config['show_map'] && $this->has_physical_address() ) : ?>
                                <a href="<?php echo esc_url( $this->get_map_url() ); ?>"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="button-link">
                                    <?php echo $this->render_icon( 'location-alt' ); ?>
                                    <?php esc_html_e( 'Map', 'wp-flyout' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="address-content">
                <?php echo $this->format_address(); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Format address for display
     *
     * @return string Formatted address HTML
     * @since 1.0.0
     *
     */
    private function format_address(): string {
        $lines = [];

        // Name and company
        if ( ! empty( $this->address['name'] ) ) {
            $lines[] = '<strong>' . esc_html( $this->address['name'] ) . '</strong>';
        }

        if ( ! empty( $this->address['company'] ) ) {
            $lines[] = esc_html( $this->address['company'] );
        }

        // Street address
        if ( ! empty( $this->address['address_1'] ) ) {
            $lines[] = esc_html( $this->address['address_1'] );
        }

        if ( ! empty( $this->address['address_2'] ) ) {
            $lines[] = esc_html( $this->address['address_2'] );
        }

        // City, state, postal
        $locality = [];
        if ( ! empty( $this->address['city'] ) ) {
            $locality[] = esc_html( $this->address['city'] );
        }

        if ( ! empty( $this->address['state'] ) ) {
            $locality[] = esc_html( $this->address['state'] );
        }

        if ( ! empty( $this->address['postcode'] ) ) {
            $locality[] = esc_html( $this->address['postcode'] );
        }

        if ( ! empty( $locality ) ) {
            $lines[] = implode( ', ', $locality );
        }

        // Country - use the Countries library helper function
        if ( ! empty( $this->address['country'] ) ) {
            $lines[] = esc_html( $this->get_country_name( $this->address['country'] ) );
        }

        // Contact info
        if ( ! empty( $this->address['phone'] ) ) {
            $lines[] = '<span class="address-phone">' . $this->render_icon( 'phone' ) . ' ' .
                       esc_html( $this->address['phone'] ) . '</span>';
        }

        if ( ! empty( $this->address['email'] ) ) {
            $lines[] = '<span class="address-email">' . $this->render_icon( 'email-alt' ) . ' ' .
                       esc_html( $this->address['email'] ) . '</span>';
        }

        if ( $this->config['format'] === 'inline' ) {
            return implode( ', ', $lines );
        } elseif ( $this->config['format'] === 'compact' ) {
            return implode( ' • ', $lines );
        } else {
            return implode( '<br>', $lines );
        }
    }

    /**
     * Check if address has physical location
     *
     * @return bool
     * @since 1.0.0
     *
     */
    private function has_physical_address(): bool {
        return ! empty( $this->address['address_1'] ) &&
               ! empty( $this->address['city'] );
    }

    /**
     * Get Google Maps URL
     *
     * @return string Maps URL
     * @since 1.0.0
     *
     */
    private function get_map_url(): string {
        $query = [];

        if ( ! empty( $this->address['address_1'] ) ) {
            $query[] = $this->address['address_1'];
        }

        if ( ! empty( $this->address['city'] ) ) {
            $query[] = $this->address['city'];
        }

        if ( ! empty( $this->address['state'] ) ) {
            $query[] = $this->address['state'];
        }

        if ( ! empty( $this->address['postcode'] ) ) {
            $query[] = $this->address['postcode'];
        }

        if ( ! empty( $this->address['country'] ) ) {
            $query[] = $this->address['country'];
        }

        return 'https://www.google.com/maps/search/' . urlencode( implode( ', ', $query ) );
    }

    /**
     * Get country name from code using ArrayPress Countries library
     *
     * @param string $code Country code
     *
     * @return string Country name (or code if not found)
     * @since 1.0.0
     *
     */
    private function get_country_name( string $code ): string {
        if ( function_exists( 'get_country_name' ) ) {
            return get_country_name( $code );
        }

        // Fallback if Countries library is not loaded
        return strtoupper( $code );
    }

}