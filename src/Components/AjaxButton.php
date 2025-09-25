<?php
/**
 * Ajax Button Component
 *
 * Creates buttons that trigger AJAX actions with loading states and notifications.
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
 * Class AjaxButton
 *
 * Renders buttons that perform AJAX operations with feedback.
 */
class AjaxButton {
    use Renderable;

    /**
     * Button text
     *
     * @var string
     */
    private string $text;

    /**
     * Button configuration
     *
     * @var array
     */
    private array $config = [
            'action'          => '',           // AJAX action to call
            'nonce_action'    => '',           // Nonce action name
            'nonce'           => '',           // Pre-generated nonce (optional)
            'data'            => [],           // Additional data to send
            'style'           => 'secondary',  // primary, secondary, link, danger
            'size'            => 'normal',     // small, normal, large
            'icon'            => null,         // Dashicon name
            'class'           => '',           // Additional CSS classes
            'id'              => '',           // Button ID
            'confirm'         => '',           // Confirmation message
            'loading_text'    => 'Processing...',
            'success_text'    => 'Success!',
            'error_text'      => 'Error occurred',
            'disabled'        => false,
            'show_spinner'    => true,
            'show_notice'     => true,
            'notice_location' => 'flyout',     // flyout, table, inline
            'callback'        => null,         // JS callback function name
    ];

    /**
     * Constructor
     *
     * @param string $text   Button text
     * @param string $action AJAX action
     * @param array  $config Optional configuration
     */
    public function __construct( string $text, string $action, array $config = [] ) {
        $this->text             = $text;
        $this->config           = array_merge( $this->config, $config );
        $this->config['action'] = $action;

        // Auto-generate ID if not provided
        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'ajax-btn-' . uniqid();
        }

        // Generate nonce if not provided
        if ( empty( $this->config['nonce'] ) && ! empty( $this->config['nonce_action'] ) ) {
            $this->config['nonce'] = wp_create_nonce( $this->config['nonce_action'] );
        }
    }

    /**
     * Create a refund button
     *
     * @param string $order_id Order ID to refund
     * @param array  $config   Optional configuration
     *
     * @return self
     */
    public static function refund( string $order_id, array $config = [] ): self {
        return new self( __( 'Refund Order', 'wp-flyout' ), 'process_refund', array_merge( [
                'data'         => [ 'order_id' => $order_id ],
                'style'        => 'danger',
                'icon'         => 'undo',
                'confirm'      => __( 'Are you sure you want to refund this order?', 'wp-flyout' ),
                'success_text' => __( 'Order refunded successfully', 'wp-flyout' )
        ], $config ) );
    }

    /**
     * Create a sync button
     *
     * @param string $item_id Item to sync
     * @param array  $config  Optional configuration
     *
     * @return self
     */
    public static function sync( string $item_id, array $config = [] ): self {
        return new self( __( 'Sync', 'wp-flyout' ), 'sync_item', array_merge( [
                'data'         => [ 'item_id' => $item_id ],
                'style'        => 'secondary',
                'icon'         => 'update',
                'loading_text' => __( 'Syncing...', 'wp-flyout' ),
                'success_text' => __( 'Synced successfully', 'wp-flyout' )
        ], $config ) );
    }

    /**
     * Create a delete button
     *
     * @param string $item_id Item to delete
     * @param array  $config  Optional configuration
     *
     * @return self
     */
    public static function delete( string $item_id, array $config = [] ): self {
        return new self( __( 'Delete', 'wp-flyout' ), 'delete_item', array_merge( [
                'data'         => [ 'item_id' => $item_id ],
                'style'        => 'danger',
                'icon'         => 'trash',
                'confirm'      => __( 'Are you sure you want to delete this item?', 'wp-flyout' ),
                'success_text' => __( 'Deleted successfully', 'wp-flyout' )
        ], $config ) );
    }

    /**
     * Render the button
     *
     * @return string Generated HTML
     */
    public function render(): string {
        $classes = [ 'wp-flyout-ajax-button', 'button' ];

        // Style classes
        if ( $this->config['style'] === 'primary' ) {
            $classes[] = 'button-primary';
        } elseif ( $this->config['style'] === 'link' ) {
            $classes = [ 'button-link', 'wp-flyout-ajax-button' ];
        } elseif ( $this->config['style'] === 'danger' ) {
            $classes[] = 'button-danger';
        }

        // Size classes
        if ( $this->config['size'] === 'small' ) {
            $classes[] = 'button-small';
        } elseif ( $this->config['size'] === 'large' ) {
            $classes[] = 'button-large';
        }

        if ( $this->config['class'] ) {
            $classes[] = $this->config['class'];
        }

        $attributes = [
                'type'  => 'button',
                'id'    => $this->config['id'],
                'class' => implode( ' ', $classes )
        ];

        // Data attributes for AJAX
        $data_attrs = [
                'action'          => $this->config['action'],
                'nonce'           => $this->config['nonce'],
                'loading-text'    => $this->config['loading_text'],
                'success-text'    => $this->config['success_text'],
                'error-text'      => $this->config['error_text'],
                'show-spinner'    => $this->config['show_spinner'] ? 'true' : 'false',
                'show-notice'     => $this->config['show_notice'] ? 'true' : 'false',
                'notice-location' => $this->config['notice_location']
        ];

        if ( $this->config['confirm'] ) {
            $data_attrs['confirm'] = $this->config['confirm'];
        }

        if ( $this->config['callback'] ) {
            $data_attrs['callback'] = $this->config['callback'];
        }

        // Add custom data
        if ( ! empty( $this->config['data'] ) ) {
            $data_attrs['params'] = json_encode( $this->config['data'] );
        }

        // Add data attributes to main attributes
        foreach ( $data_attrs as $key => $value ) {
            $attributes[ 'data-' . $key ] = $value;
        }

        if ( $this->config['disabled'] ) {
            $attributes['disabled'] = 'disabled';
        }

        ob_start();
        ?>
        <button <?php echo $this->render_attributes( $attributes ); ?>>
            <span class="button-content">
                <?php if ( $this->config['icon'] ): ?>
                    <span class="dashicons dashicons-<?php echo esc_attr( $this->config['icon'] ); ?>"></span>
                <?php endif; ?>
                <span class="button-text"><?php echo esc_html( $this->text ); ?></span>
            </span>
            <?php if ( $this->config['show_spinner'] ): ?>
                <span class="button-spinner" style="display: none;">
                    <span class="spinner"></span>
                </span>
            <?php endif; ?>
        </button>
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
            if ( is_bool( $value ) ) {
                if ( $value ) {
                    $output[] = $key;
                }
            } else {
                $output[] = sprintf( '%s="%s"', $key, esc_attr( $value ) );
            }
        }

        return implode( ' ', $output );
    }

}