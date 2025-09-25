<?php
/**
 * Feature List Component
 *
 * Creates consistent feature/item lists with icons and styling.
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
 * Class FeatureList
 *
 * Renders feature or item lists with consistent styling.
 */
class FeatureList {
    use Renderable;

    /**
     * List items
     *
     * @var array
     */
    private array $items = [];

    /**
     * List configuration
     *
     * @var array
     */
    private array $config = [
            'icon'        => 'yes',
            'icon_color'  => 'success', // success, warning, error, info, default
            'type'        => 'ul', // ul, ol
            'class'       => 'wp-flyout-feature-list',
            'columns'     => 1,
            'show_empty'  => true,
            'empty_state' => null // EmptyState component
    ];

    /**
     * Constructor
     *
     * @param array $items  List items
     * @param array $config Optional configuration
     */
    public function __construct( array $items = [], array $config = [] ) {
        $this->items  = $items;
        $this->config = array_merge( $this->config, $config );
    }

    /**
     * Create a feature list with checkmarks
     *
     * @param array $items Features array
     *
     * @return self
     */
    public static function features( array $items ): self {
        return new self( $items, [
                'icon'       => 'yes-alt',
                'icon_color' => 'success',
                'class'      => 'wp-flyout-feature-list features'
        ] );
    }

    /**
     * Create a benefits list
     *
     * @param array $items Benefits array
     *
     * @return self
     */
    public static function benefits( array $items ): self {
        return new self( $items, [
                'icon'       => 'star-filled',
                'icon_color' => 'info',
                'class'      => 'wp-flyout-feature-list benefits'
        ] );
    }

    /**
     * Create a requirements list
     *
     * @param array $items Requirements array
     *
     * @return self
     */
    public static function requirements( array $items ): self {
        return new self( $items, [
                'icon'       => 'info',
                'icon_color' => 'warning',
                'class'      => 'wp-flyout-feature-list requirements'
        ] );
    }

    /**
     * Add an item to the list
     *
     * @param string $text   Item text
     * @param array  $config Optional item configuration
     *
     * @return self
     */
    public function add_item( string $text, array $config = [] ): self {
        $this->items[] = array_merge( [
                'text'      => $text,
                'icon'      => $this->config['icon'],
                'highlight' => false
        ], $config );

        return $this;
    }

    /**
     * Set empty state
     *
     * @param EmptyState $empty_state Empty state component
     *
     * @return self
     */
    public function set_empty_state( EmptyState $empty_state ): self {
        $this->config['empty_state'] = $empty_state;

        return $this;
    }

    /**
     * Render the list
     *
     * @return string Generated HTML
     */
    public function render(): string {
        if ( empty( $this->items ) ) {
            if ( $this->config['empty_state'] ) {
                return $this->config['empty_state']->render();
            }
            if ( ! $this->config['show_empty'] ) {
                return '';
            }
        }

        $classes = [
                $this->config['class'],
                'icon-color-' . $this->config['icon_color']
        ];

        if ( $this->config['columns'] > 1 ) {
            $classes[] = 'columns-' . $this->config['columns'];
        }

        $tag = $this->config['type'];

        ob_start();
        ?>
        <<?php echo $tag; ?> class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
        <?php foreach ( $this->items as $item ): ?>
            <?php
            // Handle both string items and array items
            if ( is_string( $item ) ) {
                $text      = $item;
                $icon      = $this->config['icon'];
                $highlight = false;
            } else {
                $text      = $item['text'] ?? '';
                $icon      = $item['icon'] ?? $this->config['icon'];
                $highlight = $item['highlight'] ?? false;
            }
            ?>
            <li <?php echo $highlight ? 'class="highlight"' : ''; ?>>
                <?php if ( $icon ): ?>
                    <span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
                <?php endif; ?>
                <span class="list-text"><?php echo esc_html( $text ); ?></span>
            </li>
        <?php endforeach; ?>
        </<?php echo $tag; ?>>
        <?php
        return ob_get_clean();
    }
}