<?php
/**
 * Info Grid Component
 *
 * Displays key-value pairs in a clean grid layout, commonly used for
 * overview and detail sections.
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
use DateTimeInterface;

/**
 * Class InfoGrid
 *
 * Creates a grid of label/value pairs for displaying structured information.
 */
class InfoGrid {
    use Renderable;

    /**
     * Grid items array
     *
     * @var array
     */
    private array $items = [];

    /**
     * Grid configuration
     *
     * @var array
     */
    private array $config = [
            'columns'    => 2,
            'class'      => 'wp-flyout-info-grid',
            'show_empty' => true,
            'empty_text' => '—',
            'escape'     => true  // Add this as a default
    ];

    /**
     * Constructor
     *
     * @param array $items  Initial items to add
     * @param array $config Optional configuration
     */
    public function __construct( array $items = [], array $config = [] ) {
        $this->config = array_merge( $this->config, $config );

        foreach ( $items as $label => $value ) {
            $this->add_item( $label, $value );
        }
    }

    /**
     * Add an item to the grid
     *
     * @param string $label Item label
     * @param mixed  $value Item value
     * @param array  $args  Optional item arguments
     *
     * @return self
     */
    public function add_item( string $label, mixed $value, array $args = [] ): self {
        // Use the global escape setting if not specified for item
        $escape = $args['escape'] ?? $this->config['escape'];

        $this->items[] = array_merge( [
                'label'  => $label,
                'value'  => $value,
                'type'   => 'text',
                'class'  => '',
                'escape' => $escape
        ], $args );

        return $this;
    }

    /**
     * Add a separator between items
     *
     * @param string $title Optional separator title
     *
     * @return self
     */
    public function add_separator( string $title = '' ): self {
        $this->items[] = [
                'type'  => 'separator',
                'title' => $title
        ];

        return $this;
    }

    /**
     * Render the info grid
     *
     * @return string Generated HTML
     */
    public function render(): string {
        if ( empty( $this->items ) ) {
            return '';
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr( $this->config['class'] ); ?>"
             data-columns="<?php echo esc_attr( (string) $this->config['columns'] ); ?>">
            <?php foreach ( $this->items as $item ): ?>
                <?php echo $this->render_item( $item ); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single grid item
     *
     * @param array $item Item configuration
     *
     * @return string Generated HTML
     */
    private function render_item( array $item ): string {
        if ( $item['type'] === 'separator' ) {
            return $this->render_separator( $item );
        }

        // Don't format if we're not escaping (HTML content)
        if ( ! $item['escape'] && is_string( $item['value'] ) ) {
            $value = $item['value'];
        } else {
            $value = $this->format_value( $item['value'], $item );
        }

        if ( ! $this->config['show_empty'] && empty( $value ) ) {
            return '';
        }

        ob_start();
        ?>
        <div class="wp-flyout-info-item <?php echo esc_attr( $item['class'] ); ?>">
            <span class="wp-flyout-info-label"><?php echo esc_html( $item['label'] ); ?></span>
            <span class="wp-flyout-info-value">
                <?php
                // THE FIX: Actually check the escape flag
                if ( $item['escape'] ) {
                    echo esc_html( $value );
                } else {
                    echo $value; // Output raw HTML when escape is false
                }
                ?>
            </span>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a separator
     *
     * @param array $item Separator configuration
     *
     * @return string Generated HTML
     */
    private function render_separator( array $item ): string {
        ob_start();
        ?>
        <div class="wp-flyout-info-separator">
            <?php if ( ! empty( $item['title'] ) ): ?>
                <span><?php echo esc_html( $item['title'] ); ?></span>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Format a value for display
     *
     * @param mixed $value Value to format
     * @param array $item  Item configuration
     *
     * @return string Formatted value
     */
    private function format_value( mixed $value, array $item ): string {
        if ( empty( $value ) && $value !== 0 && $value !== '0' ) {
            return $this->config['empty_text'];
        }

        // Handle different value types
        if ( is_bool( $value ) ) {
            return $value ? __( 'Yes', 'wp-flyout' ) : __( 'No', 'wp-flyout' );
        }

        if ( is_array( $value ) ) {
            return implode( ', ', array_map( 'strval', $value ) );
        }

        if ( $value instanceof DateTimeInterface ) {
            return $value->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
        }

        return (string) $value;
    }
}