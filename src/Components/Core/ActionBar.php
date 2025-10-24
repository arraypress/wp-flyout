<?php
/**
 * Action Bar Component
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Core;

use ArrayPress\WPFlyout\Traits\Renderable;

/**
 * Class ActionBar
 */
class ActionBar {
    use Renderable;

    /**
     * Actions array
     * @var array
     */
    private array $actions = [];

    /**
     * Configuration
     * @var array
     */
    private array $config = [
            'class' => 'wp-flyout-actions',
            'align' => 'stretch' // stretch (default), left, right, center
    ];

    /**
     * Constructor
     */
    public function __construct( array $config = [] ) {
        $this->config = array_merge( $this->config, $config );
    }

    /**
     * Add an action button
     */
    public function add_action( string $text, array $attrs = [] ): self {
        $this->actions[] = array_merge( [
                'type'  => 'button',
                'text'  => $text,
                'style' => 'secondary',
                'icon'  => '',
                'class' => '',
                'attrs' => []
        ], $attrs );

        return $this;
    }

    /**
     * Add submit button
     */
    public function add_submit( string $text = 'Save Changes', array $attrs = [] ): self {
        return $this->add_action( $text, array_merge( [
                'type'  => 'submit',
                'style' => 'primary'
        ], $attrs ) );
    }

    /**
     * Add cancel button
     */
    public function add_cancel( string $text = 'Cancel', array $attrs = [] ): self {
        return $this->add_action( $text, array_merge( [
                'style'   => 'secondary',
                'onclick' => 'WPFlyout.closeAll(); return false;'
        ], $attrs ) );
    }

    /**
     * Render the action bar
     */
    public function render(): string {
        if ( empty( $this->actions ) ) {
            return '';
        }

        $class = $this->config['class'];
        if ( $this->config['align'] !== 'stretch' ) {
            $class .= ' align-' . $this->config['align'];
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr( $class ); ?>">
            <?php foreach ( $this->actions as $action ) : ?>
                <?php echo $this->render_button( $action ); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single button
     */
    private function render_button( array $action ): string {
        $type  = $action['type'];
        $class = 'button button-' . $action['style'];

        if ( ! empty( $action['class'] ) ) {
            $class .= ' ' . $action['class'];
        }

        $attrs          = [];
        $attrs['type']  = $type === 'submit' ? 'submit' : 'button';
        $attrs['class'] = $class;

        // Add custom attributes
        foreach ( $action['attrs'] as $key => $value ) {
            $attrs[ $key ] = $value;
        }

        // Add onclick if specified
        if ( ! empty( $action['onclick'] ) ) {
            $attrs['onclick'] = $action['onclick'];
        }

        // Build attributes string
        $attr_string = '';
        foreach ( $attrs as $key => $value ) {
            $attr_string .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
        }

        ob_start();
        ?>
        <button<?php echo $attr_string; ?>>
            <?php if ( ! empty( $action['icon'] ) ) : ?>
                <span class="dashicons dashicons-<?php echo esc_attr( $action['icon'] ); ?>"></span>
            <?php endif; ?>
            <?php echo esc_html( $action['text'] ); ?>
        </button>
        <?php
        return ob_get_clean();
    }

    /**
     * Static factory for common patterns
     */
    public static function standard( string $submit_text = 'Save Changes', string $cancel_text = 'Cancel' ): self {
        $bar = new self();
        $bar->add_submit( $submit_text )
            ->add_cancel( $cancel_text );

        return $bar;
    }

    /**
     * Static factory for single action
     */
    public static function single( string $text, string $style = 'primary' ): self {
        $bar = new self();
        $bar->add_action( $text, [ 'style' => $style ] );

        return $bar;
    }

}