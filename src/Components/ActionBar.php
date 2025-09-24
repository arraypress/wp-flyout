<?php
/**
 * Action Bar Component
 *
 * Creates consistent action button groups for form submission and navigation.
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
 * Class ActionBar
 *
 * Renders action button groups with primary and secondary actions.
 */
class ActionBar {
    use Renderable;

    /**
     * Actions array
     *
     * @var array
     */
    private array $actions = [];

    /**
     * Configuration
     *
     * @var array
     */
    private array $config = [
            'class'  => 'wp-flyout-actions',
            'align'  => 'left', // left, right, center, space-between
            'sticky' => false
    ];

    /**
     * Constructor
     *
     * @param array $config Optional configuration
     */
    public function __construct( array $config = [] ) {
        $this->config = array_merge( $this->config, $config );
    }

    /**
     * Add an action button
     *
     * @param string $text   Button text
     * @param array  $config Button configuration
     *
     * @return self
     */
    public function add_action( string $text, array $config = [] ): self {
        $this->actions[] = array_merge( [
                'text'         => $text,
                'type'         => 'button', // button, submit, link
                'style'        => 'secondary', // primary, secondary, link
                'name'         => '',
                'value'        => '',
                'href'         => '#',
                'target'       => '',
                'icon'         => null,
                'class'        => '',
                'id'           => '',
                'disabled'     => false,
                'loading_text' => null,
                'confirm'      => null,
                'attributes'   => []
        ], $config );

        return $this;
    }

    /**
     * Add primary submit button
     *
     * @param string $text Button text
     * @param array  $args Additional arguments
     *
     * @return self
     */
    public function add_submit( string $text = 'Save Changes', array $args = [] ): self {
        return $this->add_action( $text, array_merge( [
                'type'  => 'submit',
                'style' => 'primary'
        ], $args ) );
    }

    /**
     * Add cancel button
     *
     * @param string $text Button text
     * @param array  $args Additional arguments
     *
     * @return self
     */
    public function add_cancel( string $text = 'Cancel', array $args = [] ): self {
        return $this->add_action( $text, array_merge( [
                'type'  => 'button',
                'style' => 'link',
                'class' => 'wp-flyout-cancel'
        ], $args ) );
    }

    /**
     * Render the action bar
     *
     * @return string Generated HTML
     */
    public function render(): string {
        if ( empty( $this->actions ) ) {
            return '';
        }

        $classes = [
                $this->config['class'],
                'align-' . $this->config['align']
        ];

        if ( $this->config['sticky'] ) {
            $classes[] = 'sticky';
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr( implode( ' ', array_filter( $classes ) ) ); ?>">
            <?php foreach ( $this->actions as $action ): ?>
                <?php echo $this->render_action( $action ); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single action
     *
     * @param array $action Action configuration
     *
     * @return string Generated HTML
     */
    private function render_action( array $action ): string {
        if ( $action['type'] === 'link' ) {
            return $this->render_link( $action );
        }

        return $this->render_button( $action );
    }

    /**
     * Render a button
     *
     * @param array $action Action configuration
     *
     * @return string Generated HTML
     */
    private function render_button( array $action ): string {
        $classes = [ 'button' ];

        if ( $action['style'] === 'primary' ) {
            $classes[] = 'button-primary';
        } elseif ( $action['style'] === 'link' ) {
            $classes = [ 'button-link' ];
        }

        if ( $action['class'] ) {
            $classes[] = $action['class'];
        }

        $attributes = [
                'type'  => $action['type'],
                'class' => implode( ' ', $classes )
        ];

        if ( $action['id'] ) {
            $attributes['id'] = $action['id'];
        }

        if ( $action['name'] ) {
            $attributes['name'] = $action['name'];
        }

        if ( $action['value'] ) {
            $attributes['value'] = $action['value'];
        }

        if ( $action['disabled'] ) {
            $attributes['disabled'] = 'disabled';
        }

        if ( $action['loading_text'] ) {
            $attributes['data-loading-text'] = $action['loading_text'];
        }

        if ( $action['confirm'] ) {
            $attributes['data-confirm'] = $action['confirm'];
        }

        // Merge custom attributes
        $attributes = array_merge( $attributes, $action['attributes'] );

        ob_start();
        ?>
        <button <?php echo $this->render_attributes( $attributes ); ?>>
            <?php if ( $action['icon'] ): ?>
                <span class="dashicons dashicons-<?php echo esc_attr( $action['icon'] ); ?>"></span>
            <?php endif; ?>
            <span class="button-text"><?php echo esc_html( $action['text'] ); ?></span>
        </button>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a link
     *
     * @param array $action Action configuration
     *
     * @return string Generated HTML
     */
    private function render_link( array $action ): string {
        $classes = [ 'button' ];

        if ( $action['style'] === 'primary' ) {
            $classes[] = 'button-primary';
        } elseif ( $action['style'] === 'link' ) {
            $classes = [ 'button-link' ];
        }

        if ( $action['class'] ) {
            $classes[] = $action['class'];
        }

        $attributes = [
                'href'  => $action['href'],
                'class' => implode( ' ', $classes )
        ];

        if ( $action['id'] ) {
            $attributes['id'] = $action['id'];
        }

        if ( $action['target'] ) {
            $attributes['target'] = $action['target'];
        }

        ob_start();
        ?>
        <a <?php echo $this->render_attributes( $attributes ); ?>>
            <?php if ( $action['icon'] ): ?>
                <span class="dashicons dashicons-<?php echo esc_attr( $action['icon'] ); ?>"></span>
            <?php endif; ?>
            <span class="button-text"><?php echo esc_html( $action['text'] ); ?></span>
        </a>
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