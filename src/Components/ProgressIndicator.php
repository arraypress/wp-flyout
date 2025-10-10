<?php
/**
 * Progress Indicator Component
 *
 * Shows multi-step progress for wizards, workflows, and multi-page forms.
 * Supports steps view, progress bar, and circular progress types.
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
 * Class ProgressIndicator
 *
 * Creates visual progress indicators for multi-step processes.
 *
 * @since 1.0.0
 */
class ProgressIndicator {
	use Renderable;

	/**
	 * Progress steps
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $steps = [];

	/**
	 * Current step (1-based)
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private int $current_step = 1;

	/**
	 * Component configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [
		'type'         => 'steps', // steps, bar, circular
		'show_labels'  => true,
		'show_numbers' => true,
		'clickable'    => false,
		'class'        => 'wp-flyout-progress-indicator'
	];

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @param array $steps        Array of step definitions
	 * @param int   $current_step Current step number (1-based)
	 * @param array $config       Configuration options
	 */
	public function __construct( array $steps = [], int $current_step = 1, array $config = [] ) {
		$this->steps = $steps;
		$this->current_step = $current_step;
		$this->config = array_merge( $this->config, $config );
	}

	/**
	 * Create a wizard progress indicator
	 *
	 * @since 1.0.0
	 *
	 * @param array $steps        Step labels array
	 * @param int   $current_step Current step number
	 * @return self
	 */
	public static function wizard( array $steps, int $current_step = 1 ): self {
		$formatted_steps = array_map( function( $step ) {
			return is_array( $step ) ? $step : [ 'label' => $step, 'description' => '' ];
		}, $steps );

		return new self( $formatted_steps, $current_step, [
			'type'         => 'steps',
			'show_numbers' => true
		] );
	}

	/**
	 * Create a simple progress bar
	 *
	 * @since 1.0.0
	 *
	 * @param int    $current Current value
	 * @param int    $total   Total value
	 * @param string $label   Optional label
	 * @return self
	 */
	public static function bar( int $current, int $total, string $label = '' ): self {
		$steps = [];
		for ( $i = 1; $i <= $total; $i++ ) {
			$steps[] = [ 'label' => $label ?: "Step $i", 'description' => '' ];
		}

		return new self( $steps, $current, [
			'type'        => 'bar',
			'show_labels' => ! empty( $label )
		] );
	}

	/**
	 * Add a step to the progress
	 *
	 * @since 1.0.0
	 *
	 * @param string $label       Step label
	 * @param string $description Optional step description
	 * @return self
	 */
	public function add_step( string $label, string $description = '' ): self {
		$this->steps[] = [
			'label'       => $label,
			'description' => $description
		];
		return $this;
	}

	/**
	 * Set the current step
	 *
	 * @since 1.0.0
	 *
	 * @param int $step Step number (1-based)
	 * @return self
	 */
	public function set_current_step( int $step ): self {
		$this->current_step = max( 1, min( $step, count( $this->steps ) ) );
		return $this;
	}

	/**
	 * Get progress percentage
	 *
	 * @since 1.0.0
	 *
	 * @return float Progress percentage (0-100)
	 */
	public function get_percentage(): float {
		if ( empty( $this->steps ) ) {
			return 0;
		}
		return ( $this->current_step / count( $this->steps ) ) * 100;
	}

	/**
	 * Render the progress indicator
	 *
	 * @since 1.0.0
	 *
	 * @return string Generated HTML
	 */
	public function render(): string {
		if ( empty( $this->steps ) ) {
			return '';
		}

		$method = 'render_' . $this->config['type'];
		if ( method_exists( $this, $method ) ) {
			return $this->$method();
		}

		return $this->render_steps();
	}

	/**
	 * Render steps view
	 *
	 * @since 1.0.0
	 *
	 * @return string Generated HTML
	 */
	private function render_steps(): string {
		$total_steps = count( $this->steps );
		$class = $this->config['class'] . ' type-steps';

		ob_start();
		?>
		<div class="<?php echo esc_attr( $class ); ?>">
			<div class="progress-steps">
				<?php foreach ( $this->steps as $index => $step ) : ?>
					<?php
					$step_number = $index + 1;
					$is_completed = $step_number < $this->current_step;
					$is_current = $step_number === $this->current_step;
					$step_class = $is_completed ? 'completed' : ($is_current ? 'current' : '');
					?>

					<div class="progress-step <?php echo esc_attr( $step_class ); ?>">
						<div class="step-indicator">
							<?php if ( $is_completed ) : ?>
								<span class="dashicons dashicons-yes"></span>
							<?php elseif ( $this->config['show_numbers'] ) : ?>
								<span class="step-number"><?php echo $step_number; ?></span>
							<?php endif; ?>
						</div>

						<?php if ( $this->config['show_labels'] ) : ?>
							<div class="step-content">
								<div class="step-label"><?php echo esc_html( $step['label'] ); ?></div>
								<?php if ( $step['description'] ) : ?>
									<div class="step-description"><?php echo esc_html( $step['description'] ); ?></div>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<?php if ( $index < $total_steps - 1 ) : ?>
							<div class="step-connector"></div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render bar view
	 *
	 * @since 1.0.0
	 *
	 * @return string Generated HTML
	 */
	private function render_bar(): string {
		$percentage = $this->get_percentage();
		$class = $this->config['class'] . ' type-bar';

		ob_start();
		?>
		<div class="<?php echo esc_attr( $class ); ?>">
			<div class="progress-bar-wrapper">
				<div class="progress-bar">
					<div class="progress-bar-fill" style="width: <?php echo $percentage; ?>%">
						<span class="progress-percentage"><?php echo round( $percentage ); ?>%</span>
					</div>
				</div>
			</div>

			<?php if ( $this->config['show_labels'] ) : ?>
				<div class="progress-info">
                    <span class="progress-step-text">
                        Step <?php echo $this->current_step; ?> of <?php echo count( $this->steps ); ?>:
                        <?php echo esc_html( $this->steps[ $this->current_step - 1 ]['label'] ?? '' ); ?>
                    </span>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}