<?php
/**
 * Range Slider Component
 *
 * Dual-handle range selection for prices, dates, quantities, and other numeric ranges.
 * Supports value display, direct input, and customizable prefixes/suffixes.
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Form;

use ArrayPress\WPFlyout\Traits\Renderable;

/**
 * Class RangeSlider
 *
 * Creates dual-handle range sliders for selecting numeric ranges.
 *
 * @since 1.0.0
 */
class RangeSlider {
	use Renderable;

	/**
	 * Component configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [
		'name'         => 'range',
		'min'          => 0,
		'max'          => 100,
		'step'         => 1,
		'value_min'    => null,
		'value_max'    => null,
		'show_values'  => true,
		'show_inputs'  => false,
		'prefix'       => '',
		'suffix'       => '',
		'class'        => 'wp-flyout-range-slider'
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

		// Set default values if not provided
		if ( $this->config['value_min'] === null ) {
			$this->config['value_min'] = $this->config['min'];
		}
		if ( $this->config['value_max'] === null ) {
			$this->config['value_max'] = $this->config['max'];
		}
	}

	/**
	 * Create a price range slider
	 *
	 * @since 1.0.0
	 *
	 * @param float  $min     Minimum price
	 * @param float  $max     Maximum price
	 * @param string $symbol  Currency symbol
	 * @param array  $config  Additional configuration
	 * @return self
	 */
	public static function price( float $min, float $max, string $symbol = '$', array $config = [] ): self {
		return new self( array_merge( [
			'name'        => 'price_range',
			'min'         => $min,
			'max'         => $max,
			'step'        => 1,
			'prefix'      => $symbol,
			'show_inputs' => true
		], $config ) );
	}

	/**
	 * Create a percentage range slider
	 *
	 * @since 1.0.0
	 *
	 * @param int   $min    Minimum percentage (default 0)
	 * @param int   $max    Maximum percentage (default 100)
	 * @param array $config Additional configuration
	 * @return self
	 */
	public static function percentage( int $min = 0, int $max = 100, array $config = [] ): self {
		return new self( array_merge( [
			'name'   => 'percentage_range',
			'min'    => $min,
			'max'    => $max,
			'step'   => 1,
			'suffix' => '%'
		], $config ) );
	}

	/**
	 * Create a quantity range slider
	 *
	 * @since 1.0.0
	 *
	 * @param int   $min    Minimum quantity
	 * @param int   $max    Maximum quantity
	 * @param array $config Additional configuration
	 * @return self
	 */
	public static function quantity( int $min, int $max, array $config = [] ): self {
		return new self( array_merge( [
			'name' => 'quantity_range',
			'min'  => $min,
			'max'  => $max,
			'step' => 1
		], $config ) );
	}

	/**
	 * Set the range values
	 *
	 * @since 1.0.0
	 *
	 * @param float $min Minimum value
	 * @param float $max Maximum value
	 * @return self
	 */
	public function set_values( float $min, float $max ): self {
		$this->config['value_min'] = max( $this->config['min'], min( $min, $this->config['max'] ) );
		$this->config['value_max'] = max( $this->config['min'], min( $max, $this->config['max'] ) );
		return $this;
	}

	/**
	 * Render the range slider
	 *
	 * @since 1.0.0
	 *
	 * @return string Generated HTML
	 */
	public function render(): string {
		ob_start();
		?>
		<div class="<?php echo esc_attr( $this->config['class'] ); ?>"
		     data-min="<?php echo esc_attr( (string) $this->config['min'] ); ?>"
		     data-max="<?php echo esc_attr( (string) $this->config['max'] ); ?>"
		     data-step="<?php echo esc_attr( (string) $this->config['step'] ); ?>">

			<?php if ( $this->config['show_values'] || $this->config['show_inputs'] ) : ?>
				<div class="range-values">
					<div class="range-value-min">
						<?php if ( $this->config['show_inputs'] ) : ?>
							<input type="number"
							       name="<?php echo esc_attr( $this->config['name'] ); ?>_min"
							       class="range-input-min"
							       value="<?php echo esc_attr( (string) $this->config['value_min'] ); ?>"
							       min="<?php echo esc_attr( (string) $this->config['min'] ); ?>"
							       max="<?php echo esc_attr( (string) $this->config['max'] ); ?>"
							       step="<?php echo esc_attr( (string) $this->config['step'] ); ?>">
						<?php else : ?>
							<span class="range-display-min">
                                <?php echo esc_html( $this->config['prefix'] . $this->config['value_min'] . $this->config['suffix'] ); ?>
                            </span>
						<?php endif; ?>
					</div>

					<span class="range-separator">–</span>

					<div class="range-value-max">
						<?php if ( $this->config['show_inputs'] ) : ?>
							<input type="number"
							       name="<?php echo esc_attr( $this->config['name'] ); ?>_max"
							       class="range-input-max"
							       value="<?php echo esc_attr( (string) $this->config['value_max'] ); ?>"
							       min="<?php echo esc_attr( (string) $this->config['min'] ); ?>"
							       max="<?php echo esc_attr( (string) $this->config['max'] ); ?>"
							       step="<?php echo esc_attr( (string) $this->config['step'] ); ?>">
						<?php else : ?>
							<span class="range-display-max">
                                <?php echo esc_html( $this->config['prefix'] . $this->config['value_max'] . $this->config['suffix'] ); ?>
                            </span>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

			<div class="range-slider-track">
				<div class="range-slider-fill"></div>
				<input type="range" class="range-slider-min"
				       value="<?php echo esc_attr( (string) $this->config['value_min'] ); ?>"
				       min="<?php echo esc_attr( (string) $this->config['min'] ); ?>"
				       max="<?php echo esc_attr( (string) $this->config['max'] ); ?>"
				       step="<?php echo esc_attr( (string) $this->config['step'] ); ?>">
				<input type="range" class="range-slider-max"
				       value="<?php echo esc_attr( (string) $this->config['value_max'] ); ?>"
				       min="<?php echo esc_attr( (string) $this->config['min'] ); ?>"
				       max="<?php echo esc_attr( (string) $this->config['max'] ); ?>"
				       step="<?php echo esc_attr( (string) $this->config['step'] ); ?>">
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}