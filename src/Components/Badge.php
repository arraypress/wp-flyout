<?php
/**
 * Badge Component - Fixed Version
 *
 * Creates consistent badge elements for status indicators, labels, and tags.
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
 * Class Badge
 *
 * Renders badge elements with various styles and configurations.
 */
class Badge {
    use Renderable;

    /**
     * Badge text
     *
     * @var string
     */
    private string $text;

    /**
     * Badge configuration
     *
     * @var array
     */
    private array $config = [
            'type'        => 'default', // default, success, warning, error, info, test, live, subscription, one-time
            'icon'        => null,
            'class'       => '',
            'tooltip'     => '',
            'dismissible' => false
    ];

    /**
     * Badge type to class mapping
     *
     * @var array
     */
    private static array $type_classes = [
            'default'      => 'wp-flyout-badge-default',
            'success'      => 'wp-flyout-badge-success',
            'warning'      => 'wp-flyout-badge-warning',
            'error'        => 'wp-flyout-badge-error',
            'info'         => 'wp-flyout-badge-info',
            'test'         => 'wp-flyout-badge-test',
            'live'         => 'wp-flyout-badge-live',
            'recurring'    => 'wp-flyout-badge-recurring',
            'subscription' => 'wp-flyout-badge-subscription',
            'one-time'     => 'wp-flyout-badge-one-time',
            'mixed'        => 'wp-flyout-badge-mixed'
    ];

    /**
     * Constructor
     *
     * @param string $text   Badge text
     * @param array  $config Optional configuration
     */
    public function __construct( string $text, array $config = [] ) {
        $this->text   = $text;
        $this->config = array_merge( $this->config, $config );
    }

    /**
     * Create a status badge
     *
     * @param string $status Status text
     * @param string $type   Status type for styling
     *
     * @return self
     */
    public static function status( string $status, string $type = 'default' ): self {
        return new self( $status, [ 'type' => $type ] );
    }

    /**
     * Create a count badge
     *
     * @param int    $count Count to display
     * @param string $type  Badge type
     *
     * @return self
     */
    public static function count( int $count, string $type = 'info' ): self {
        return new self( (string) $count, [ 'type' => $type ] );
    }

    /**
     * Create environment badge (test/live)
     *
     * @param bool $is_test Whether in test mode
     *
     * @return self
     */
    public static function environment( bool $is_test ): self {
        if ( $is_test ) {
            return new self( __( 'Test', 'wp-flyout' ), [
                    'type' => 'test'
            ] );
        }

        return new self( __( 'Live', 'wp-flyout' ), [
                'type' => 'live'
        ] );
    }

    /**
     * Create payment type badge
     *
     * @param string $type Payment type (subscription, one-time, mixed)
     *
     * @return self
     */
    public static function payment_type( string $type ): self {
        $labels = [
                'subscription' => __( 'Subscription', 'wp-flyout' ),
                'recurring'    => __( 'Recurring', 'wp-flyout' ),
                'one-time'     => __( 'One-time', 'wp-flyout' ),
                'mixed'        => __( 'Mixed', 'wp-flyout' )
        ];

        $label = $labels[ $type ] ?? ucfirst( $type );

        return new self( $label, [ 'type' => $type ] );
    }

    /**
     * Render the badge
     *
     * @return string Generated HTML
     */
    public function render(): string {
        $classes = [
                'wp-flyout-badge',
                self::$type_classes[ $this->config['type'] ] ?? 'wp-flyout-badge-default'
        ];

        if ( $this->config['class'] ) {
            $classes[] = $this->config['class'];
        }

        if ( $this->config['dismissible'] ) {
            $classes[] = 'dismissible';
        }

        $attributes = [
                'class' => implode( ' ', $classes )
        ];

        if ( $this->config['tooltip'] ) {
            $attributes['title'] = $this->config['tooltip'];
        }

        ob_start();
        ?>
        <span <?php echo $this->render_attributes( $attributes ); ?>>
            <?php if ( $this->config['icon'] && ! in_array( $this->config['type'], [ 'test', 'live' ] ) ): ?>
                <span class="dashicons dashicons-<?php echo esc_attr( $this->config['icon'] ); ?>"></span>
            <?php endif; ?>

            <span class="badge-text"><?php echo esc_html( $this->text ); ?></span>

            <?php if ( $this->config['dismissible'] ): ?>
                <button type="button" class="badge-dismiss" aria-label="<?php esc_attr_e( 'Dismiss', 'wp-flyout' ); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            <?php endif; ?>
        </span>
        <?php
        return ob_get_clean();
    }

    /**
     * Render HTML attributes
     *
     * @param array $attributes Attributes array
     *
     * @return string HTML attributes string
     */
    private function render_attributes( array $attributes ): string {
        $output = [];
        foreach ( $attributes as $key => $value ) {
            $output[] = sprintf( '%s="%s"', $key, esc_attr( $value ) );
        }

        return implode( ' ', $output );
    }

}