<?php
/**
 * Separator Component
 *
 * Visual dividers with optional text labels.
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
 * Class Separator
 *
 * Creates visual separators between content sections.
 *
 * @since 1.0.0
 */
class Separator {
	use Renderable;

	/**
	 * Component configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [
		'text'   => '',
		'class'  => 'wp-flyout-separator',
		'margin' => '20px' // Vertical margin
	];

	/**
	 * Constructor
	 *
	 * @param string $text   Optional text label
	 * @param array  $config Configuration options
	 *
	 * @since 1.0.0
	 */
	public function __construct( string $text = '', array $config = [] ) {
		$this->config         = array_merge( $this->config, $config );
		$this->config['text'] = $text;
	}

	/**
	 * Create a simple line separator
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function line(): self {
		return new self();
	}

	/**
	 * Create a separator with text
	 *
	 * @param string $text Text to display
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function with_text( string $text ): self {
		return new self( $text );
	}

	/**
	 * Create an "or" separator (common pattern)
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function or(): self {
		return new self( __( 'or', 'arraypress' ) );
	}

	/**
	 * Render the separator
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 */
	public function render(): string {
		$style = sprintf( 'margin: %s 0;', esc_attr( $this->config['margin'] ) );

		if ( empty( $this->config['text'] ) ) {
			return sprintf(
				'<hr class="%s" style="%s">',
				esc_attr( $this->config['class'] ),
				$style
			);
		}

		ob_start();
		?>
        <div class="<?php echo esc_attr( $this->config['class'] ); ?> has-text"
             style="<?php echo esc_attr( $style ); ?>">
            <span class="separator-text"><?php echo esc_html( $this->config['text'] ); ?></span>
        </div>
		<?php
		return ob_get_clean();
	}

}