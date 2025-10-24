<?php
/**
 * Card Choice Component
 *
 * Visual card-style checkboxes and radio buttons for better UX on choices.
 * Perfect for selecting plans, features, or options where visual context helps.
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
use ArrayPress\WPFlyout\Traits\IconRenderer;

/**
 * Class CardChoice
 *
 * Creates visual card-style checkbox/radio selections.
 *
 * @since 1.0.0
 */
class CardChoice {
	use Renderable;
	use IconRenderer;

	/**
	 * Choice options
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $options = [];

	/**
	 * Component configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [
		'name'        => '',
		'type'        => 'radio', // 'radio' or 'checkbox'
		'value'       => null,
		'columns'     => 2,
		'class'       => 'wp-flyout-card-choice-group',
		'required'    => false,
		'compact'     => false,
		'show_checks' => true,
	];

	/**
	 * Constructor
	 *
	 * @param string $name   Field name
	 * @param array  $config Configuration options
	 *
	 * @since 1.0.0
	 */
	public function __construct( string $name, array $config = [] ) {
		$this->config         = array_merge( $this->config, $config );
		$this->config['name'] = $name;

		// Ensure value is array for checkboxes
		if ( $this->config['type'] === 'checkbox' && ! is_array( $this->config['value'] ) ) {
			$this->config['value'] = $this->config['value'] ? [ $this->config['value'] ] : [];
		}
	}

	/**
	 * Create a radio card group
	 *
	 * @param string $name    Field name
	 * @param array  $options Options array
	 * @param mixed  $value   Selected value
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function radio( string $name, array $options = [], $value = null ): self {
		$instance = new self( $name, [
			'type'  => 'radio',
			'value' => $value
		] );

		foreach ( $options as $key => $option ) {
			$instance->add_option( $key, $option );
		}

		return $instance;
	}

	/**
	 * Create a checkbox card group
	 *
	 * @param string $name    Field name
	 * @param array  $options Options array
	 * @param array  $value   Selected values
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function checkbox( string $name, array $options = [], array $value = [] ): self {
		$instance = new self( $name, [
			'type'  => 'checkbox',
			'value' => $value
		] );

		foreach ( $options as $key => $option ) {
			$instance->add_option( $key, $option );
		}

		return $instance;
	}

	/**
	 * Create a plan selection card group
	 *
	 * @param string $name  Field name
	 * @param array  $plans Plans array
	 * @param mixed  $value Selected value
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function plans( string $name, array $plans, $value = null ): self {
		$instance = new self( $name, [
			'type'  => 'radio',
			'value' => $value
		] );

		foreach ( $plans as $key => $plan ) {
			$instance->add_option( $key, [
				'title'       => $plan['name'] ?? $key,
				'description' => $plan['description'] ?? '',
				'icon'        => $plan['icon'] ?? 'yes',
				'badge'       => $plan['badge'] ?? null
			] );
		}

		return $instance;
	}

	/**
	 * Add an option to the choice group
	 *
	 * @param string $value  Option value
	 * @param mixed  $option Option configuration (string or array)
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public function add_option( string $value, $option ): self {
		// Handle string option (convert to array)
		if ( is_string( $option ) ) {
			$option = [
				'title'       => $option,
				'description' => ''
			];
		}

		$this->options[ $value ] = array_merge( [
			'title'       => '',
			'description' => '',
			'icon'        => null,
			'badge'       => null,
			'badge_type'  => null, // recommended, popular, new
			'disabled'    => false
		], $option );

		return $this;
	}

	/**
	 * Set number of columns
	 *
	 * @param int $columns Number of columns (1-3)
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public function columns( int $columns ): self {
		$this->config['columns'] = max( 1, min( 3, $columns ) );

		return $this;
	}

	/**
	 * Set as compact layout
	 *
	 * @param bool $compact Whether to use compact layout
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public function compact( bool $compact = true ): self {
		$this->config['compact'] = $compact;

		return $this;
	}

	/**
	 * Render the card choice group
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 */
	public function render(): string {
		if ( empty( $this->options ) ) {
			return '';
		}

		$classes = [
			$this->config['class'],
			'columns-' . $this->config['columns']
		];

		if ( $this->config['compact'] ) {
			$classes[] = 'compact';
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
		     role="<?php echo $this->config['type'] === 'radio' ? 'radiogroup' : 'group'; ?>">
			<?php foreach ( $this->options as $value => $option ) : ?>
				<?php echo $this->render_option( $value, $option ); ?>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a single option card
	 *
	 * @param string $value  Option value
	 * @param array  $option Option configuration
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 */
	private function render_option( string $value, array $option ): string {
		$is_checked = $this->is_checked( $value );
		$input_id   = sanitize_key( $this->config['name'] . '_' . $value );
		$input_name = $this->config['name'];

		// For checkboxes, use array notation
		if ( $this->config['type'] === 'checkbox' ) {
			$input_name .= '[]';
		}

		ob_start();
		?>
		<div class="card-choice">
			<input type="<?php echo esc_attr( $this->config['type'] ); ?>"
			       id="<?php echo esc_attr( $input_id ); ?>"
			       name="<?php echo esc_attr( $input_name ); ?>"
			       value="<?php echo esc_attr( $value ); ?>"
				<?php checked( $is_checked ); ?>
				<?php disabled( $option['disabled'] ); ?>
				<?php echo $this->config['required'] ? 'required' : ''; ?>>

			<label class="card-choice-label" for="<?php echo esc_attr( $input_id ); ?>">
				<div class="card-choice-header">
					<?php if ( ! empty( $option['icon'] ) ) : ?>
						<div class="card-choice-icon">
							<?php echo $this->render_icon( $option['icon'] ); ?>
						</div>
					<?php endif; ?>

					<div class="card-choice-content">
						<div class="card-choice-title">
							<?php echo esc_html( $option['title'] ); ?>

							<?php if ( ! empty( $option['badge'] ) ) : ?>
								<span class="card-choice-badge <?php echo $option['badge_type'] ? 'badge-' . esc_attr( $option['badge_type'] ) : ''; ?>">
									<?php echo esc_html( $option['badge'] ); ?>
								</span>
							<?php endif; ?>
						</div>

						<?php if ( ! empty( $option['description'] ) ) : ?>
							<p class="card-choice-description">
								<?php echo esc_html( $option['description'] ); ?>
							</p>
						<?php endif; ?>
					</div>

					<?php if ( $this->config['show_checks'] ) : ?>
						<div class="card-choice-check"></div>
					<?php endif; ?>
				</div>
			</label>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Check if option is selected
	 *
	 * @param string $value Option value
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	private function is_checked( string $value ): bool {
		if ( $this->config['type'] === 'checkbox' ) {
			return is_array( $this->config['value'] ) && in_array( $value, $this->config['value'], true );
		}

		return $this->config['value'] === $value;
	}

}