<?php
/**
 * Customer Header Component
 *
 * Displays customer profile information with avatar, contact details, and stats.
 * Integrates with WordPress Gravatar system for avatar display.
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
use ArrayPress\WPFlyout\Traits\IconRenderer;

/**
 * Class CustomerHeader
 *
 * Creates customer profile headers with avatar and key information.
 *
 * @since 1.0.0
 */
class CustomerHeader {
    use Renderable;
    use IconRenderer;

    /**
     * Customer data
     *
     * @since 1.0.0
     * @var array
     */
    private array $customer = [];

    /**
     * Component configuration
     *
     * @since 1.0.0
     * @var array
     */
    private array $config = [
            'class'       => 'wp-flyout-customer-header',
            'avatar_size' => 60,
            'show_avatar' => true,
            'show_stats'  => true,
            'show_badges' => true,
            'date_format' => 'M j, Y'
    ];

    /**
     * Constructor
     *
     * @param array $customer Customer data
     * @param array $config   Configuration options
     *
     * @since 1.0.0
     *
     */
    public function __construct( array $customer, array $config = [] ) {
        $this->customer = array_merge( [
                'id'         => 0,
                'name'       => '',
                'email'      => '',
                'phone'      => '',
                'company'    => '',
                'registered' => '',
                'stats'      => [],
                'badges'     => [],
                'avatar_url' => ''
        ], $customer );

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
        ob_start();
        ?>
        <div class="<?php echo esc_attr( $this->config['class'] ); ?>">
            <?php if ( $this->config['show_avatar'] ) : ?>
                <div class="customer-avatar">
                    <?php echo $this->get_avatar(); ?>
                </div>
            <?php endif; ?>

            <div class="customer-info">
                <div class="customer-primary">
                    <h3 class="customer-name">
                        <?php echo esc_html( $this->customer['name'] ?: 'Guest Customer' ); ?>
                        <?php if ( $this->config['show_badges'] && ! empty( $this->customer['badges'] ) ) : ?>
                            <?php foreach ( $this->customer['badges'] as $badge ) : ?>
                                <span class="customer-badge badge-<?php echo esc_attr( $badge['type'] ?? 'default' ); ?>">
									<?php echo esc_html( $badge['text'] ); ?>
								</span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </h3>

                    <div class="customer-contact">
                        <?php if ( ! empty( $this->customer['email'] ) ) : ?>
                            <span class="customer-email">
								<?php echo $this->render_icon( 'email-alt' ); ?>
                                <?php echo esc_html( $this->customer['email'] ); ?>
							</span>
                        <?php endif; ?>

                        <?php if ( ! empty( $this->customer['phone'] ) ) : ?>
                            <span class="customer-phone">
								<?php echo $this->render_icon( 'phone' ); ?>
                                <?php echo esc_html( $this->customer['phone'] ); ?>
							</span>
                        <?php endif; ?>

                        <?php if ( ! empty( $this->customer['company'] ) ) : ?>
                            <span class="customer-company">
								<?php echo $this->render_icon( 'building' ); ?>
                                <?php echo esc_html( $this->customer['company'] ); ?>
							</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ( $this->config['show_stats'] && ! empty( $this->customer['stats'] ) ) : ?>
                    <div class="customer-stats">
                        <?php foreach ( $this->customer['stats'] as $stat ) : ?>
                            <div class="customer-stat">
                                <span class="stat-value"><?php echo esc_html( $stat['value'] ); ?></span>
                                <span class="stat-label"><?php echo esc_html( $stat['label'] ); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get avatar HTML
     *
     * @return string Avatar HTML
     * @since 1.0.0
     *
     */
    private function get_avatar(): string {
        // Use provided avatar URL
        if ( ! empty( $this->customer['avatar_url'] ) ) {
            return sprintf(
                    '<img src="%s" alt="%s" class="avatar" width="%d" height="%d">',
                    esc_url( $this->customer['avatar_url'] ),
                    esc_attr( $this->customer['name'] ),
                    $this->config['avatar_size'],
                    $this->config['avatar_size']
            );
        }

        // Use Gravatar if email provided
        if ( ! empty( $this->customer['email'] ) ) {
            return get_avatar(
                    $this->customer['email'],
                    $this->config['avatar_size'],
                    '',
                    $this->customer['name']
            );
        }

        // Fallback to initials
        return $this->get_initials_avatar();
    }

    /**
     * Generate initials avatar
     *
     * @return string Avatar HTML with initials
     * @since 1.0.0
     *
     */
    private function get_initials_avatar(): string {
        $initials = $this->get_initials( $this->customer['name'] );

        return sprintf(
                '<div class="avatar-initials" style="width: %1$dpx; height: %1$dpx; line-height: %1$dpx;">%2$s</div>',
                $this->config['avatar_size'],
                esc_html( $initials )
        );
    }

    /**
     * Get initials from name
     *
     * @param string $name Full name
     *
     * @return string Initials (max 2 characters)
     * @since 1.0.0
     *
     */
    private function get_initials( string $name ): string {
        if ( empty( $name ) ) {
            return 'G';
        }

        $parts    = explode( ' ', trim( $name ) );
        $initials = '';

        foreach ( $parts as $part ) {
            if ( ! empty( $part ) ) {
                $initials .= strtoupper( substr( $part, 0, 1 ) );
            }
            if ( strlen( $initials ) >= 2 ) {
                break;
            }
        }

        return $initials ?: 'U';
    }

}