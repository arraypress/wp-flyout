<?php
/**
 * EmptyState Component
 *
 * Displays helpful empty state messages with optional actions.
 *
 * @package     ArrayPress\WPFlyout\Components\Display
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Display;

use ArrayPress\WPFlyout\Traits\Renderable;
use ArrayPress\WPFlyout\Traits\HtmlAttributes;

class EmptyState {
    use Renderable;
    use HtmlAttributes;

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
            'id'           => '',
            'icon'         => 'admin-page',
            'title'        => '',
            'description'  => '',
            'action_text'  => '',
            'action_url'   => '',
            'action_class' => 'button',
            'action_attrs' => [],
            'class'        => ''
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
            $this->config['id'] = 'empty-state-' . wp_generate_uuid4();
        }
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        $classes = [ 'wp-flyout-empty-state' ];
        if ( $this->config['class'] ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">

            <?php if ( $this->config['icon'] ) : ?>
                <span class="empty-state-icon dashicons dashicons-<?php echo esc_attr( $this->config['icon'] ); ?>"></span>
            <?php endif; ?>

            <?php if ( $this->config['title'] ) : ?>
                <h3 class="empty-state-title"><?php echo esc_html( $this->config['title'] ); ?></h3>
            <?php endif; ?>

            <?php if ( $this->config['description'] ) : ?>
                <p class="empty-state-description"><?php echo esc_html( $this->config['description'] ); ?></p>
            <?php endif; ?>

            <?php if ( $this->config['action_text'] ) : ?>
                <?php $this->render_action(); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the action button/link
     */
    private function render_action(): void {
        $attrs = $this->config['action_attrs'];

        if ( $this->config['action_url'] ) {
            ?>
            <a href="<?php echo esc_url( $this->config['action_url'] ); ?>"
               class="<?php echo esc_attr( $this->config['action_class'] ); ?>"
                    <?php echo $this->build_attributes( $attrs ); ?>>
                <?php echo esc_html( $this->config['action_text'] ); ?>
            </a>
            <?php
        } else {
            ?>
            <button type="button"
                    class="<?php echo esc_attr( $this->config['action_class'] ); ?>"
                    <?php echo $this->build_attributes( $attrs ); ?>>
                <?php echo esc_html( $this->config['action_text'] ); ?>
            </button>
            <?php
        }
    }

}