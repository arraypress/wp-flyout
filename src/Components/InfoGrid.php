<?php
/**
 * InfoGrid Component
 *
 * Displays information in a structured grid layout.
 *
 * @package     ArrayPress\WPFlyout\Components\Data
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Traits\HtmlAttributes;
use ArrayPress\WPFlyout\Interfaces\Renderable;
use ArrayPress\WPFlyout\Traits\Formatter;

class InfoGrid implements Renderable {
    use Formatter;
    use HtmlAttributes;

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
            $this->config['id'] = 'info-grid-' . wp_generate_uuid4();
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
                'class'       => '',
                'items'       => [],
                'columns'     => 2,
                'gap'         => 'medium',
                'label_width' => 'auto',
                'separator'   => true,
                'empty_value' => '—',
                'responsive'  => true
        ];
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

        $classes = $this->build_classes( [
                'info-grid'                                  => true,
                'info-grid-cols-' . $this->config['columns'] => true,
                'info-grid-gap-' . $this->config['gap']      => true,
                'info-grid-responsive'                       => $this->config['responsive'],
                'info-grid-separated'                        => $this->config['separator'],
                $this->config['class']                       => ! empty( $this->config['class'] )
        ] );

        $style = '';
        if ( $this->config['label_width'] !== 'auto' ) {
            $style = 'style="--label-width: ' . esc_attr( $this->config['label_width'] ) . '"';
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( $classes ); ?>"
                <?php echo $style; ?>>
            <?php foreach ( $this->config['items'] as $item ) : ?>
                <?php $this->render_item( $item ); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single grid item
     *
     * @param array $item Item configuration
     */
    private function render_item( array $item ): void {
        if ( empty( $item['label'] ) ) {
            return;
        }

        $value       = $item['value'] ?? '';
        $description = $item['description'] ?? '';
        $callback    = $item['callback'] ?? null;
        $class       = $item['class'] ?? '';
        $icon        = $item['icon'] ?? '';
        $badge       = $item['badge'] ?? '';
        $link        = $item['link'] ?? '';
        $colspan     = $item['colspan'] ?? 1;

        // Process value through callback if provided
        if ( is_callable( $callback ) ) {
            $value = call_user_func( $callback, $value, $item );
        } elseif ( empty( $value ) ) {
            $value = $this->format_value( $this->config['empty_value'] );
        }

        $item_classes = $this->build_classes( [
                'info-grid-item'                  => true,
                'info-grid-item-span-' . $colspan => $colspan > 1,
                $class                            => ! empty( $class )
        ] );
        ?>
        <div class="<?php echo esc_attr( $item_classes ); ?>">
            <dt class="info-grid-label">
                <?php if ( $icon ) : ?>
                    <span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
                <?php endif; ?>
                <?php echo esc_html( $item['label'] ); ?>
            </dt>
            <dd class="info-grid-value">
                <?php if ( $link ) : ?>
                    <a href="<?php echo esc_url( $link ); ?>"
                            <?php echo isset( $item['target'] ) ? 'target="' . esc_attr( $item['target'] ) . '"' : ''; ?>>
                        <?php echo wp_kses_post( $value ); ?>
                    </a>
                <?php else : ?>
                    <?php echo wp_kses_post( $value ); ?>
                <?php endif; ?>

                <?php if ( $badge ) : ?>
                    <span class="badge"><?php echo esc_html( $badge ); ?></span>
                <?php endif; ?>

                <?php if ( $description ) : ?>
                    <p class="info-grid-description"><?php echo esc_html( $description ); ?></p>
                <?php endif; ?>
            </dd>
        </div>
        <?php
    }

}