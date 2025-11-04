<?php
/**
 * SectionHeader Component
 *
 * Creates section headers with optional descriptions and actions.
 *
 * @package     ArrayPress\WPFlyout\Components\Core
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Interfaces\Renderable;

class SectionHeader implements Renderable {

    /**
     * Component configuration
     *
     * @var array
     */
    private array $config;

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, self::get_defaults() );

        // Auto-generate ID if not provided
        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'section-header-' . wp_generate_uuid4();
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
                'title'       => '',
                'description' => '',
                'icon'        => '',
                'tag'         => 'h2',
                'border'      => true,
                'actions'     => [],
                'class'       => ''
        ];
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        if ( empty( $this->config['title'] ) ) {
            return '';
        }

        $classes = [ 'wp-flyout-section-header' ];

        if ( $this->config['border'] ) {
            $classes[] = 'has-border';
        }

        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <div class="section-header-main">
                <<?php echo esc_html( $this->config['tag'] ); ?> class="section-header-title">
                <?php if ( $this->config['icon'] ) : ?>
                    <span class="dashicons dashicons-<?php echo esc_attr( $this->config['icon'] ); ?>"></span>
                <?php endif; ?>
                <?php echo esc_html( $this->config['title'] ); ?>
            </<?php echo esc_html( $this->config['tag'] ); ?>>

            <?php if ( ! empty( $this->config['actions'] ) ) : ?>
                <div class="section-header-actions">
                    <?php foreach ( $this->config['actions'] as $action ) : ?>
                        <?php $this->render_action( $action ); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ( $this->config['description'] ) : ?>
            <p class="section-header-description">
                <?php echo esc_html( $this->config['description'] ); ?>
            </p>
        <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render an action button/link
     *
     * @param array $action Action configuration
     */
    private function render_action( array $action ): void {
        $label = $action['label'] ?? '';
        $url   = $action['url'] ?? '#';
        $class = $action['class'] ?? 'button-link';
        $icon  = $action['icon'] ?? '';

        if ( empty( $label ) ) {
            return;
        }
        ?>
        <a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $class ); ?>">
            <?php if ( $icon ) : ?>
                <span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
            <?php endif; ?>
            <?php echo esc_html( $label ); ?>
        </a>
        <?php
    }

}