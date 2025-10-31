<?php
/**
 * Accordion Component
 *
 * Displays collapsible content sections.
 *
 * @package     ArrayPress\WPFlyout\Components\Layout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Layout;

use ArrayPress\WPFlyout\Traits\Renderable;

class Accordion {
    use Renderable;

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
            'items'        => [],
            'multiple'     => false,
            'collapsible'  => true,
            'default_open' => null,
            'icons'        => true,
            'class'        => '',
            'item_class'   => '',
            'header_tag'   => 'h3'
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
            $this->config['id'] = 'accordion-' . wp_generate_uuid4();
        }
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        if ( empty( $this->config['items'] ) ) {
            return '';
        }

        $classes = [ 'accordion' ];
        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        $data_attrs = [];
        if ( $this->config['multiple'] ) {
            $data_attrs[] = 'data-multiple="true"';
        }
        if ( $this->config['collapsible'] ) {
            $data_attrs[] = 'data-collapsible="true"';
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
                <?php echo implode( ' ', $data_attrs ); ?>>
            <?php foreach ( $this->config['items'] as $index => $item ) : ?>
                <?php $this->render_item( $item, $index ); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single accordion item
     *
     * @param array $item  Item configuration
     * @param int   $index Item index
     */
    private function render_item( array $item, int $index ): void {
        $title   = $item['title'] ?? '';
        $content = $item['content'] ?? '';
        $icon    = $item['icon'] ?? '';
        $badge   = $item['badge'] ?? '';
        $is_open = $item['open'] ?? false;

        if ( empty( $title ) || empty( $content ) ) {
            return;
        }

        // Check if this item should be open by default
        if ( $this->config['default_open'] === $index ||
             ( is_array( $this->config['default_open'] ) && in_array( $index, $this->config['default_open'] ) ) ) {
            $is_open = true;
        }

        $item_id    = $this->config['id'] . '-item-' . $index;
        $header_id  = $item_id . '-header';
        $content_id = $item_id . '-content';

        $item_classes = [ 'accordion-item' ];
        if ( $is_open ) {
            $item_classes[] = 'is-open';
        }
        if ( ! empty( $this->config['item_class'] ) ) {
            $item_classes[] = $this->config['item_class'];
        }
        ?>
    <div class="<?php echo esc_attr( implode( ' ', $item_classes ) ); ?>"
         data-index="<?php echo esc_attr( $index ); ?>">
        <<?php echo esc_html( $this->config['header_tag'] ); ?>
        id="<?php echo esc_attr( $header_id ); ?>"
        class="accordion-header">
        <button type="button"
                class="accordion-trigger"
                aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
                aria-controls="<?php echo esc_attr( $content_id ); ?>">

            <?php if ( $this->config['icons'] ) : ?>
                <span class="accordion-icon" aria-hidden="true">
							<span class="dashicons dashicons-arrow-down-alt2"></span>
						</span>
            <?php endif; ?>

            <?php if ( $icon ) : ?>
                <span class="accordion-item-icon dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
            <?php endif; ?>

            <span class="accordion-title"><?php echo esc_html( $title ); ?></span>

            <?php if ( $badge ) : ?>
                <span class="accordion-badge"><?php echo esc_html( $badge ); ?></span>
            <?php endif; ?>
        </button>
        </<?php echo esc_html( $this->config['header_tag'] ); ?>>

        <div id="<?php echo esc_attr( $content_id ); ?>"
             class="accordion-content"
             aria-labelledby="<?php echo esc_attr( $header_id ); ?>"
                <?php echo ! $is_open ? 'hidden' : ''; ?>>
            <div class="accordion-content-inner">
                <?php echo wp_kses_post( $content ); ?>
            </div>
        </div>
        </div>
        <?php
    }

}