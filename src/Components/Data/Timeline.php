<?php
/**
 * Timeline Component - Simplified
 *
 * Displays chronological events in a vertical timeline format.
 *
 * @package     ArrayPress\WPFlyout\Components\Data
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     3.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Data;

use ArrayPress\WPFlyout\Traits\Renderable;

/**
 * Class Timeline
 *
 * Renders a vertical timeline of events.
 *
 * @since 3.0.0
 */
class Timeline {
    use Renderable;

    /**
     * Component configuration
     *
     * @since 3.0.0
     * @var array
     */
    private array $config;

    /**
     * Default configuration
     *
     * @since 3.0.0
     * @var array
     */
    private const DEFAULTS = [
            'id'      => '',
            'events'  => [],
            'compact' => false,
            'class'   => ''
    ];

    /**
     * Constructor
     *
     * @param array $config  {
     *                       Configuration options
     *
     * @type string $id      Component ID (auto-generated if empty)
     * @type array  $events  Array of timeline events
     * @type bool   $compact Use compact display mode
     * @type string $class   Additional CSS classes
     *                       }
     * @since 3.0.0
     *
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, self::DEFAULTS );

        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'timeline-' . wp_generate_uuid4();
        }
    }

    /**
     * Render the component
     *
     * @return string Generated HTML
     * @since 3.0.0
     *
     */
    public function render(): string {
        if ( empty( $this->config['events'] ) ) {
            return '';
        }

        $classes = [ 'wp-flyout-timeline' ];
        if ( $this->config['compact'] ) {
            $classes[] = 'compact';
        }
        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <?php
            $total = count( $this->config['events'] );
            foreach ( $this->config['events'] as $index => $event ) :
                $this->render_event( $event, $index, $total );
            endforeach;
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render single timeline event
     *
     * @param array $event Event data
     * @param int   $index Event index
     * @param int   $total Total events count
     *
     * @return void
     * @since  3.0.0
     * @access private
     *
     */
    private function render_event( array $event, int $index, int $total ): void {
        // Normalize event data
        if ( is_string( $event ) ) {
            $event = [ 'title' => $event ];
        }

        $title       = $event['title'] ?? '';
        $description = $event['description'] ?? '';
        $date        = $event['date'] ?? '';
        $type        = $event['type'] ?? 'default';
        $icon        = $event['icon'] ?? 'marker';

        if ( empty( $title ) ) {
            return;
        }

        $classes = [
                'timeline-item',
                'timeline-item-' . sanitize_html_class( $type )
        ];

        // Mark last item for CSS
        if ( $index === $total - 1 ) {
            $classes[] = 'last-item';
        }
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <div class="timeline-badge">
                <span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
            </div>
            <div class="timeline-content">
                <?php if ( $date ) : ?>
                    <div class="timeline-date"><?php echo esc_html( $date ); ?></div>
                <?php endif; ?>

                <h4 class="timeline-title"><?php echo esc_html( $title ); ?></h4>

                <?php if ( $description ) : ?>
                    <p class="timeline-description"><?php echo wp_kses_post( $description ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

}