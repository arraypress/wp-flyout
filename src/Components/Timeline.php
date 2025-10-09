<?php
/**
 * Timeline Component
 *
 * Displays chronological events in a vertical timeline format.
 * Useful for showing order history, activity logs, or process steps.
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
 * Class Timeline
 *
 * Creates vertical timelines with optional icons and status indicators.
 *
 * @since 1.0.0
 */
class Timeline {
	use Renderable;

	/**
	 * Timeline events
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $events = [];

	/**
	 * Component configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [
		'class'          => 'wp-flyout-timeline',
		'show_connector' => true,
		'show_icons'     => true,
		'date_format'    => 'M j, Y',
		'time_format'    => 'g:i A'
	];

	/**
	 * Constructor
	 *
	 * @param array $events Timeline events
	 * @param array $config Configuration options
	 *
	 * @since 1.0.0
	 *
	 */
	public function __construct( array $events = [], array $config = [] ) {
		$this->events = $events;
		$this->config = array_merge( $this->config, $config );
	}

	/**
	 * Add an event to the timeline
	 *
	 * @param string      $title       Event title
	 * @param string|null $description Event description
	 * @param array       $meta        Additional metadata
	 *
	 * @return self
	 * @since 1.0.0
	 *
	 */
	public function add_event( string $title, ?string $description = null, array $meta = [] ): self {
		$this->events[] = array_merge( [
			'title'       => $title,
			'description' => $description,
			'date'        => current_time( 'mysql' ),
			'icon'        => 'marker',
			'type'        => 'default', // default, success, info, warning, error
			'user'        => null
		], $meta );

		return $this;
	}

	/**
	 * Render the timeline
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 *
	 */
	public function render(): string {
		if ( empty( $this->events ) ) {
			return '';
		}

		ob_start();
		?>
        <div class="<?php echo esc_attr( $this->config['class'] ); ?>">
			<?php if ( $this->config['show_connector'] ) : ?>
                <div class="timeline-connector"></div>
			<?php endif; ?>

			<?php foreach ( $this->events as $event ) : ?>
				<?php echo $this->render_event( $event ); ?>
			<?php endforeach; ?>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a single event
	 *
	 * @param array $event Event data
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 *
	 */
	private function render_event( array $event ): string {
		$type_class = ! empty( $event['type'] ) ? 'timeline-item-' . $event['type'] : '';

		ob_start();
		?>
        <div class="timeline-item <?php echo esc_attr( $type_class ); ?>">
            <div class="timeline-badge">
				<?php if ( $this->config['show_icons'] && ! empty( $event['icon'] ) ) : ?>
                    <span class="dashicons dashicons-<?php echo esc_attr( $event['icon'] ); ?>"></span>
				<?php endif; ?>
            </div>

            <div class="timeline-content">
				<?php if ( ! empty( $event['date'] ) ) : ?>
                    <div class="timeline-date">
						<?php echo esc_html( $this->format_date( $event['date'] ) ); ?>
                    </div>
				<?php endif; ?>

                <h4 class="timeline-title"><?php echo esc_html( $event['title'] ); ?></h4>

				<?php if ( ! empty( $event['description'] ) ) : ?>
                    <p class="timeline-description"><?php echo esc_html( $event['description'] ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $event['user'] ) ) : ?>
                    <div class="timeline-meta">
                        <span class="timeline-user">By <?php echo esc_html( $event['user'] ); ?></span>
                    </div>
				<?php endif; ?>
            </div>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Format date for display
	 *
	 * @param string $date Date string
	 *
	 * @return string Formatted date
	 * @since 1.0.0
	 *
	 */
	private function format_date( string $date ): string {
		$timestamp = strtotime( $date );
		if ( ! $timestamp ) {
			return $date;
		}

		// If today, show time only
		if ( date( 'Y-m-d', $timestamp ) === date( 'Y-m-d' ) ) {
			return 'Today at ' . date( $this->config['time_format'], $timestamp );
		}

		// If yesterday
		if ( date( 'Y-m-d', $timestamp ) === date( 'Y-m-d', strtotime( '-1 day' ) ) ) {
			return 'Yesterday at ' . date( $this->config['time_format'], $timestamp );
		}

		// Otherwise show date
		return date( $this->config['date_format'], $timestamp );
	}

	/**
	 * Create a customer journey timeline
	 *
	 * @param array $journey Journey data
	 *
	 * @return self
	 * @since 1.0.0
	 *
	 */
	public static function customer_journey( array $journey ): self {
		$timeline = new self();

		foreach ( $journey as $step ) {
			$timeline->add_event(
				$step['title'],
				$step['description'] ?? null,
				[
					'date' => $step['date'] ?? current_time( 'mysql' ),
					'icon' => $step['icon'] ?? 'marker',
					'type' => $step['type'] ?? 'info'
				]
			);
		}

		return $timeline;
	}

	/**
	 * Create an order status timeline
	 *
	 * @param array $statuses Status updates
	 *
	 * @return self
	 * @since 1.0.0
	 *
	 */
	public static function order_status( array $statuses ): self {
		$timeline = new self();

		$icon_map = [
			'pending'    => 'clock',
			'processing' => 'update',
			'shipped'    => 'airplane',
			'delivered'  => 'yes-alt',
			'completed'  => 'yes',
			'refunded'   => 'undo',
			'cancelled'  => 'no-alt'
		];

		foreach ( $statuses as $status ) {
			$status_key = strtolower( $status['status'] ?? 'pending' );

			$timeline->add_event(
				$status['title'] ?? $status['status'],
				$status['note'] ?? null,
				[
					'date' => $status['date'],
					'icon' => $icon_map[ $status_key ] ?? 'marker',
					'type' => $status['type'] ?? 'info',
					'user' => $status['user'] ?? null
				]
			);
		}

		return $timeline;
	}
	
}