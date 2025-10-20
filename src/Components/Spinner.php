<?php
/**
 * Spinner Component
 *
 * Loading indicators for async operations and processing states.
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
 * Class Spinner
 *
 * Creates loading spinners and overlays.
 *
 * @since 1.0.0
 */
class Spinner {
	use Renderable;

	/**
	 * Component configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [
		'type'    => 'inline', // 'inline', 'overlay', 'button'
		'text'    => '',
		'size'    => 'default', // 'small', 'default', 'large'
		'class'   => 'wp-flyout-spinner',
		'visible' => true
	];

	/**
	 * Constructor
	 *
	 * @param array $config Configuration options
	 *
	 * @since 1.0.0
	 */
	public function __construct( array $config = [] ) {
		$this->config = array_merge( $this->config, $config );
	}

	/**
	 * Create an inline spinner
	 *
	 * @param string $text Optional loading text
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function inline( string $text = '' ): self {
		return new self( [
			'type' => 'inline',
			'text' => $text ?: __( 'Loading...', 'arraypress' )
		] );
	}

	/**
	 * Create an overlay spinner
	 *
	 * @param string $text Optional loading text
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function overlay( string $text = '' ): self {
		return new self( [
			'type' => 'overlay',
			'text' => $text ?: __( 'Loading...', 'arraypress' )
		] );
	}

	/**
	 * Create a button spinner
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function button(): self {
		return new self( [
			'type' => 'button',
			'size' => 'small'
		] );
	}

	/**
	 * Render the spinner
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 */
	public function render(): string {
		$classes = [
			$this->config['class'],
			'spinner-' . $this->config['type'],
			'size-' . $this->config['size']
		];

		if ( ! $this->config['visible'] ) {
			$classes[] = 'hidden';
		}

		ob_start();
		?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <div class="spinner-icon">
                <span class="dashicons dashicons-update"></span>
            </div>
			<?php if ( ! empty( $this->config['text'] ) ) : ?>
                <span class="spinner-text"><?php echo esc_html( $this->config['text'] ); ?></span>
			<?php endif; ?>
        </div>
		<?php
		return ob_get_clean();
	}

}