<?php
/**
 * Timeline Component
 *
 * Displays chronological events in a vertical timeline format.
 *
 * @package     ArrayPress\WPFlyout\Components\Data
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Data;

use ArrayPress\WPFlyout\Traits\Renderable;

class Timeline {
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
            'id'          => '',
            'events'      => [],
            'date_format' => 'M j, Y',
            'time_format' => 'g:i A',
            'show_icons'  => true,
            'compact'     => false,
            'class'       => ''
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
            $this->config['id'] = 'timeline-' . wp_generate_uuid4();
        }

        // Normalize events
        $this->config['events'] = $this->normalize_events( $this->config['events'] );
    }

    /**
     * Normalize event data
     *
     * @param array $events Raw events array
     *
     * @return array
     */
    private function normalize_events( array $events ): array {
        $normalized = [];

        foreach ( $events as $event ) {
            if ( is_string( $event ) ) {
                $event = [ 'title' => $event ];
            }

            $normalized[] = wp_parse_args( $event, [
                    'title'       => '',
                    'description' => '',
                    'date'        => current_time( 'mysql' ),
                    'icon'        => 'marker',
                    'type'        => 'default',
                    'user'        => '',
                    'meta'        => []
            ] );
        }

        return $normalized;
    }

    /**
     * Format date for display
     *
     * @param string $date Date string
     *
     * @return string
     */
    private function format_date( string $date ): string {
        $timestamp = strtotime( $date );
        if ( ! $timestamp ) {
            return $date;
        }

        $today      = date( 'Y-m-d' );
        $yesterday  = date( 'Y-m-d', strtotime( '-1 day' ) );
        $event_date = date( 'Y-m-d', $timestamp );

        if ( $event_date === $today ) {
            return sprintf( __( 'Today at %s', 'wp-flyout' ), date( $this->config['time_format'], $timestamp ) );
        }

        if ( $event_date === $yesterday ) {
            return sprintf( __( 'Yesterday at %s', 'wp-flyout' ), date( $this->config['time_format'], $timestamp ) );
        }

        return date( $this->config['date_format'], $timestamp );
    }

    /**
     * Render the component
     *
     * @return string
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
            <?php foreach ( $this->config['events'] as $index => $event ) : ?>
                <?php $this->render_event( $event, $index ); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render single timeline event
     *
     * @param array $event Event data
     * @param int   $index Event index
     */
    private function render_event( array $event, int $index ): void {
        $classes = [
                'timeline-item',
                'timeline-' . $event['type']
        ];

        if ( $index === 0 ) {
            $classes[] = 'first';
        }
        if ( $index === count( $this->config['events'] ) - 1 ) {
            $classes[] = 'last';
        }
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <?php if ( $this->config['show_icons'] ) : ?>
                <div class="timeline-badge">
                    <span class="dashicons dashicons-<?php echo esc_attr( $event['icon'] ); ?>"></span>
                </div>
            <?php endif; ?>

            <div class="timeline-content">
                <div class="timeline-header">
                    <span class="timeline-date"><?php echo esc_html( $this->format_date( $event['date'] ) ); ?></span>
                    <?php if ( $event['user'] ) : ?>
                        <span class="timeline-user"><?php echo esc_html( $event['user'] ); ?></span>
                    <?php endif; ?>
                </div>

                <h4 class="timeline-title"><?php echo esc_html( $event['title'] ); ?></h4>

                <?php if ( $event['description'] ) : ?>
                    <p class="timeline-description"><?php echo esc_html( $event['description'] ); ?></p>
                <?php endif; ?>

                <?php if ( ! empty( $event['meta'] ) ) : ?>
                    <div class="timeline-meta">
                        <?php foreach ( $event['meta'] as $key => $value ) : ?>
                            <span class="meta-item">
								<strong><?php echo esc_html( $key ); ?>:</strong>
								<?php echo esc_html( $value ); ?>
							</span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

}