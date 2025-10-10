<?php
/**
 * Status Indicator Component
 *
 * Shows system/service status with optional details and real-time pulse animation.
 * Supports operational, degraded, down, and maintenance states.
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
 * Class StatusIndicator
 *
 * Displays system or service status with visual indicators and optional details.
 *
 * @since 1.0.0
 */
class StatusIndicator {
	use Renderable;

	/**
	 * Component configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [
		'status'       => 'operational', // operational, degraded, down, maintenance
		'title'        => 'System Status',
		'message'      => '',
		'show_details' => false,
		'details'      => [],
		'show_icon'    => true,
		'show_pulse'   => true,
		'class'        => 'wp-flyout-status-indicator'
	];

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @param array $config Configuration options
	 */
	public function __construct( array $config = [] ) {
		$this->config = array_merge( $this->config, $config );
	}

	/**
	 * Create an operational status indicator
	 *
	 * @since 1.0.0
	 *
	 * @param string $title   Status title
	 * @param string $message Optional status message
	 * @return self
	 */
	public static function operational( string $title = 'System Status', string $message = '' ): self {
		return new self( [
			'status'  => 'operational',
			'title'   => $title,
			'message' => $message
		] );
	}

	/**
	 * Create a degraded performance indicator
	 *
	 * @since 1.0.0
	 *
	 * @param string $title   Status title
	 * @param string $message Degradation message
	 * @param array  $details Optional detail items
	 * @return self
	 */
	public static function degraded( string $title, string $message, array $details = [] ): self {
		return new self( [
			'status'       => 'degraded',
			'title'        => $title,
			'message'      => $message,
			'show_details' => ! empty( $details ),
			'details'      => $details
		] );
	}

	/**
	 * Create a system down indicator
	 *
	 * @since 1.0.0
	 *
	 * @param string $title   Status title
	 * @param string $message Down message
	 * @param array  $details Optional detail items
	 * @return self
	 */
	public static function down( string $title, string $message, array $details = [] ): self {
		return new self( [
			'status'       => 'down',
			'title'        => $title,
			'message'      => $message,
			'show_details' => ! empty( $details ),
			'details'      => $details,
			'show_pulse'   => false
		] );
	}

	/**
	 * Create a maintenance mode indicator
	 *
	 * @since 1.0.0
	 *
	 * @param string $title        Status title
	 * @param string $message      Maintenance message
	 * @param string $estimated    Estimated completion time
	 * @return self
	 */
	public static function maintenance( string $title, string $message, string $estimated = '' ): self {
		$details = [];
		if ( $estimated ) {
			$details['Estimated Completion'] = $estimated;
		}

		return new self( [
			'status'       => 'maintenance',
			'title'        => $title,
			'message'      => $message,
			'show_details' => ! empty( $details ),
			'details'      => $details,
			'show_pulse'   => false
		] );
	}

	/**
	 * Get status configuration
	 *
	 * @since 1.0.0
	 *
	 * @return array Status icon, label, and color
	 */
	private function get_status_config(): array {
		$statuses = [
			'operational' => [
				'icon'  => 'yes-alt',
				'label' => 'Operational',
				'color' => 'success'
			],
			'degraded' => [
				'icon'  => 'warning',
				'label' => 'Degraded Performance',
				'color' => 'warning'
			],
			'down' => [
				'icon'  => 'no-alt',
				'label' => 'System Down',
				'color' => 'error'
			],
			'maintenance' => [
				'icon'  => 'admin-tools',
				'label' => 'Under Maintenance',
				'color' => 'info'
			]
		];

		return $statuses[ $this->config['status'] ] ?? $statuses['operational'];
	}

	/**
	 * Render the status indicator
	 *
	 * @since 1.0.0
	 *
	 * @return string Generated HTML
	 */
	public function render(): string {
		$status = $this->get_status_config();
		$class = $this->config['class'] . ' status-' . $status['color'];

		ob_start();
		?>
		<div class="<?php echo esc_attr( $class ); ?>">
			<div class="status-header">
				<?php if ( $this->config['show_icon'] ) : ?>
					<div class="status-icon">
						<?php if ( $this->config['show_pulse'] && $this->config['status'] === 'operational' ) : ?>
							<span class="status-pulse"></span>
						<?php endif; ?>
						<span class="dashicons dashicons-<?php echo esc_attr( $status['icon'] ); ?>"></span>
					</div>
				<?php endif; ?>

				<div class="status-info">
					<h4 class="status-title"><?php echo esc_html( $this->config['title'] ); ?></h4>
					<p class="status-label"><?php echo esc_html( $status['label'] ); ?></p>

					<?php if ( $this->config['message'] ) : ?>
						<p class="status-message"><?php echo esc_html( $this->config['message'] ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( $this->config['show_details'] && ! empty( $this->config['details'] ) ) : ?>
				<div class="status-details">
					<?php foreach ( $this->config['details'] as $key => $value ) : ?>
						<div class="status-detail-item">
							<span class="detail-label"><?php echo esc_html( $key ); ?>:</span>
							<span class="detail-value"><?php echo esc_html( $value ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}