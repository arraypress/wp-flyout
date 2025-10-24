<?php
/**
 * Flex Row Component
 *
 * Utility component for creating flexible horizontal layouts with configurable
 * spacing and alignment. Useful for arranging any content in rows.
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Layout;

use ArrayPress\WPFlyout\Traits\Renderable;
use ArrayPress\WPFlyout\Traits\ClassBuilder;

/**
 * Class FlexRow
 *
 * Provides flexible row layouts for organizing content horizontally.
 *
 * @since 1.0.0
 */
class FlexRow {
    use Renderable;
    use ClassBuilder;

    /**
     * Items to display in the row
     *
     * @since 1.0.0
     * @var array
     */
    private array $items = [];

    /**
     * Component configuration
     *
     * @since 1.0.0
     * @var array
     */
    private array $config = [
            'gap'       => '20px',
            'align'     => 'center', // flex-start, center, flex-end, stretch, baseline
            'justify'   => 'flex-start', // flex-start, center, flex-end, space-between, space-around
            'wrap'      => false,
            'class'     => 'wp-flyout-flex-row',
            'direction' => 'row' // row, column
    ];

    /**
     * Constructor
     *
     * @param array $items  Initial items to add
     * @param array $config Configuration options
     *
     * @since 1.0.0
     *
     */
    public function __construct( array $items = [], array $config = [] ) {
        $this->items  = $items;
        $this->config = array_merge( $this->config, $config );
    }

    /**
     * Add an item to the row
     *
     * @param mixed       $content Content to add (object with render() or HTML string)
     * @param string|null $flex    Optional flex value (e.g., '1', '2', 'auto')
     *
     * @return self
     * @since 1.0.0
     *
     */
    public function add( $content, ?string $flex = null ): self {
        if ( $flex !== null ) {
            $this->items[] = [
                    'content' => $content,
                    'flex'    => $flex
            ];
        } else {
            $this->items[] = $content;
        }

        return $this;
    }

    /**
     * Add multiple items at once
     *
     * @param array $items Items to add
     *
     * @return self
     * @since 1.0.0
     *
     */
    public function add_items( array $items ): self {
        $this->items = array_merge( $this->items, $items );

        return $this;
    }

    /**
     * Add a spacer element
     *
     * @param string $flex Flex value for the spacer
     *
     * @return self
     * @since 1.0.0
     *
     */
    public function add_spacer( string $flex = '1' ): self {
        $this->items[] = [
                'content' => '',
                'flex'    => $flex,
                'spacer'  => true
        ];

        return $this;
    }

    /**
     * Get the number of items
     *
     * @return int
     * @since 1.0.0
     *
     */
    public function count(): int {
        return count( $this->items );
    }

    /**
     * Render the flex row
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    public function render(): string {
        if ( empty( $this->items ) ) {
            return '';
        }

        $style_parts = [
                'display: flex',
                'gap: ' . $this->config['gap'],
                'align-items: ' . $this->config['align'],
                'justify-content: ' . $this->config['justify'],
                'flex-direction: ' . $this->config['direction']
        ];

        if ( $this->config['wrap'] ) {
            $style_parts[] = 'flex-wrap: wrap';
        }

        $style = implode( '; ', $style_parts );

        ob_start();
        ?>
        <div class="<?php echo esc_attr( $this->config['class'] ); ?>"
             style="<?php echo esc_attr( $style ); ?>">
            <?php foreach ( $this->items as $item ) : ?>
                <?php echo $this->render_item( $item ); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single item
     *
     * @param mixed $item Item to render
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    private function render_item( $item ): string {
        // Handle spacer
        if ( is_array( $item ) && ! empty( $item['spacer'] ) ) {
            return sprintf(
                    '<div class="flex-spacer" style="flex: %s;"></div>',
                    esc_attr( $item['flex'] )
            );
        }

        // Handle item with flex value
        if ( is_array( $item ) && isset( $item['flex'] ) ) {
            $content = $this->render_content( $item['content'] );

            return sprintf(
                    '<div class="flex-item" style="flex: %s;">%s</div>',
                    esc_attr( $item['flex'] ),
                    $content
            );
        }

        // Handle regular item
        $content = $this->render_content( $item );

        return sprintf( '<div class="flex-item">%s</div>', $content );
    }

    /**
     * Render content (object or string)
     *
     * @param mixed $content Content to render
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    private function render_content( $content ): string {
        if ( is_object( $content ) && method_exists( $content, 'render' ) ) {
            return $content->render();
        }

        return (string) $content;
    }

    /**
     * Create a row with badges
     *
     * Convenience method for badge layouts.
     *
     * @param array $badges Array of Badge objects
     * @param array $config Configuration options
     *
     * @return self
     * @since 1.0.0
     *
     */
    public static function badges( array $badges, array $config = [] ): self {
        $default_config = [
                'gap'   => '10px',
                'align' => 'center',
                'wrap'  => true
        ];

        $row = new self( [], array_merge( $default_config, $config ) );

        foreach ( $badges as $badge ) {
            $row->add( $badge );
        }

        return $row;
    }

    /**
     * Create a row with buttons
     *
     * Convenience method for button layouts.
     *
     * @param array $buttons Array of button HTML or objects
     * @param array $config  Configuration options
     *
     * @return self
     * @since 1.0.0
     *
     */
    public static function buttons( array $buttons, array $config = [] ): self {
        $default_config = [
                'gap'     => '10px',
                'align'   => 'center',
                'justify' => 'flex-start'
        ];

        $row = new self( [], array_merge( $default_config, $config ) );

        foreach ( $buttons as $button ) {
            $row->add( $button );
        }

        return $row;
    }

    /**
     * Create a row with space between items
     *
     * Convenience method for spaced layouts.
     *
     * @param mixed $left   Left content
     * @param mixed $right  Right content
     * @param array $config Configuration options
     *
     * @return self
     * @since 1.0.0
     *
     */
    public static function space_between( $left, $right, array $config = [] ): self {
        $default_config = [
                'justify' => 'space-between',
                'align'   => 'center'
        ];

        $row = new self( [], array_merge( $default_config, $config ) );
        $row->add( $left )->add( $right );

        return $row;
    }

    /**
     * Create a label/value row
     *
     * @param string $label       Label text
     * @param string $value       Value text
     * @param array  $value_style Optional style attributes for value
     *
     * @return self
     * @since 1.0.0
     *
     */
    public static function label_value( string $label, string $value, array $value_style = [] ): self {
        $label_html = '<span class="flex-label"><strong>' . esc_html( $label ) . ':</strong></span>';

        $style_string = '';
        if ( ! empty( $value_style ) ) {
            $styles = [];
            foreach ( $value_style as $prop => $val ) {
                $styles[] = esc_attr( $prop ) . ': ' . esc_attr( $val );
            }
            $style_string = ' style="' . implode( '; ', $styles ) . '"';
        }

        $value_html = '<span class="flex-value"' . $style_string . '>' . esc_html( $value ) . '</span>';

        return self::space_between( $label_html, $value_html );
    }

    /**
     * Quick render of flex row
     *
     * @param array $items Items to display.
     * @param array $config Optional configuration.
     *
     * @return string Rendered HTML.
     * @since 1.0.0
     */
    public static function quick( array $items, array $config = [] ): string {
        return ( new self( $items, $config ) )->render();
    }

}