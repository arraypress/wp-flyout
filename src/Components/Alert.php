<?php
/**
 * Alert Component
 *
 * Displays alert messages with various styles and optional dismiss functionality.
 *
 * @package     ArrayPress\WPFlyout\Components\Display
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Interfaces\Renderable;

class Alert implements Renderable {

    /**
     * Component configuration
     *
     * @var array
     */
    private array $config;

    /**
     * Icon mappings for alert types
     *
     * @var array
     */
    private const ICONS = [
            'success' => 'yes-alt',
            'error'   => 'dismiss',
            'warning' => 'warning',
            'info'    => 'info-outline'
    ];

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, self::get_defaults() );

        // Auto-generate ID if not provided
        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'alert-' . wp_generate_uuid4();
        }

        // Auto-set icon based on type
        if ( $this->config['icon'] === 'auto' ) {
            $this->config['icon'] = self::ICONS[ $this->config['type'] ] ?? 'info-outline';
        }
    }

    /**
     * Get default configuration
     *
     * @return array
     */
    private static function get_defaults(): array {
        return [
                'id'          => '',
                'type'        => 'info',
                'message'     => '',
                'title'       => '',
                'dismissible' => true,
                'icon'        => 'auto',
                'actions'     => [],
                'class'       => '',
                'inline'      => false,
                'persist'     => false
        ];
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        if ( empty( $this->config['message'] ) ) {
            return '';
        }

        $classes    = $this->get_classes();
        $data_attrs = $this->get_data_attributes();

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( $classes ); ?>"
                <?php echo $data_attrs; ?>
             role="alert">

            <?php if ( $this->config['dismissible'] ) : ?>
                <button type="button" class="notice-dismiss"
                        aria-label="<?php esc_attr_e( 'Dismiss', 'arraypress' ); ?>">
                    <span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'arraypress' ); ?></span>
                </button>
            <?php endif; ?>

            <div class="alert-content">
                <?php if ( $this->config['icon'] !== false ) : ?>
                    <span class="alert-icon dashicons dashicons-<?php echo esc_attr( $this->config['icon'] ); ?>"></span>
                <?php endif; ?>

                <div class="alert-text">
                    <?php if ( ! empty( $this->config['title'] ) ) : ?>
                        <h4 class="alert-title"><?php echo esc_html( $this->config['title'] ); ?></h4>
                    <?php endif; ?>

                    <div class="alert-message">
                        <?php echo wp_kses_post( $this->config['message'] ); ?>
                    </div>

                    <?php if ( ! empty( $this->config['actions'] ) ) : ?>
                        <div class="alert-actions">
                            <?php foreach ( $this->config['actions'] as $action ) : ?>
                                <?php $this->render_action( $action ); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get alert classes
     *
     * @return string
     */
    private function get_classes(): string {
        $classes = [
                'notice',
                'notice-' . $this->config['type'],
                'alert-component'
        ];

        if ( $this->config['dismissible'] ) {
            $classes[] = 'is-dismissible';
        }

        if ( $this->config['inline'] ) {
            $classes[] = 'inline';
        }

        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        return implode( ' ', $classes );
    }

    /**
     * Get data attributes
     *
     * @return string
     */
    private function get_data_attributes(): string {
        $attrs = [];

        if ( $this->config['persist'] ) {
            $attrs[] = 'data-persist="true"';
        }

        if ( $this->config['type'] ) {
            $attrs[] = 'data-type="' . esc_attr( $this->config['type'] ) . '"';
        }

        return implode( ' ', $attrs );
    }

    /**
     * Render an action button/link
     *
     * @param array $action Action configuration
     */
    private function render_action( array $action ): void {
        $label    = $action['label'] ?? '';
        $url      = $action['url'] ?? '#';
        $type     = $action['type'] ?? 'link';
        $class    = $action['class'] ?? '';
        $target   = $action['target'] ?? '';
        $callback = $action['callback'] ?? '';

        if ( empty( $label ) ) {
            return;
        }

        if ( $type === 'button' ) {
            $button_class = 'button ' . ( $class ?: 'button-secondary' );
            ?>
            <button type="button"
                    class="<?php echo esc_attr( $button_class ); ?>"
                    <?php echo $callback ? 'data-action="' . esc_attr( $callback ) . '"' : ''; ?>>
                <?php echo esc_html( $label ); ?>
            </button>
            <?php
        } else {
            ?>
            <a href="<?php echo esc_url( $url ); ?>"
               class="<?php echo esc_attr( $class ); ?>"
                    <?php echo $target ? 'target="' . esc_attr( $target ) . '"' : ''; ?>>
                <?php echo esc_html( $label ); ?>
            </a>
            <?php
        }
    }

}