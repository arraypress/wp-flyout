<?php
/**
 * Code Block Component
 *
 * Displays syntax-highlighted code with copy functionality.
 * Supports line numbers, custom max heights, and various programming languages.
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
 * Class CodeBlock
 *
 * Creates code display blocks with optional line numbers and copy functionality.
 *
 * @since 1.0.0
 */
class CodeBlock {
	use Renderable;

	/**
	 * Code content to display
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $code = '';

	/**
	 * Component configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [
		'language'          => 'php',
		'show_line_numbers' => true,
		'show_copy_button'  => true,
		'max_height'        => '400px',
		'title'             => '',
		'class'             => 'wp-flyout-code-block'
	];

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @param string $code   Code to display
	 * @param array  $config Configuration options
	 */
	public function __construct( string $code, array $config = [] ) {
		$this->code = $code;
		$this->config = array_merge( $this->config, $config );
	}

	/**
	 * Create a PHP code block
	 *
	 * @since 1.0.0
	 *
	 * @param string $code   PHP code to display
	 * @param string $title  Optional title for the block
	 * @return self
	 */
	public static function php( string $code, string $title = '' ): self {
		return new self( $code, [
			'language' => 'php',
			'title'    => $title
		] );
	}

	/**
	 * Create a JavaScript code block
	 *
	 * @since 1.0.0
	 *
	 * @param string $code   JavaScript code to display
	 * @param string $title  Optional title for the block
	 * @return self
	 */
	public static function javascript( string $code, string $title = '' ): self {
		return new self( $code, [
			'language' => 'javascript',
			'title'    => $title
		] );
	}

	/**
	 * Create a CSS code block
	 *
	 * @since 1.0.0
	 *
	 * @param string $code   CSS code to display
	 * @param string $title  Optional title for the block
	 * @return self
	 */
	public static function css( string $code, string $title = '' ): self {
		return new self( $code, [
			'language' => 'css',
			'title'    => $title
		] );
	}

	/**
	 * Render the code block
	 *
	 * @since 1.0.0
	 *
	 * @return string Generated HTML
	 */
	public function render(): string {
		$class = $this->config['class'] . ' language-' . $this->config['language'];

		ob_start();
		?>
		<div class="<?php echo esc_attr( $class ); ?>">
			<?php if ( $this->config['title'] ) : ?>
				<div class="code-block-header">
					<span class="code-block-title"><?php echo esc_html( $this->config['title'] ); ?></span>
					<span class="code-block-language"><?php echo esc_html( strtoupper( $this->config['language'] ) ); ?></span>
				</div>
			<?php endif; ?>

			<div class="code-block-wrapper"
				<?php if ( $this->config['max_height'] ) : ?>
					style="max-height: <?php echo esc_attr( $this->config['max_height'] ); ?>"
				<?php endif; ?>>

				<?php if ( $this->config['show_copy_button'] ) : ?>
					<button type="button" class="code-block-copy" data-action="copy-code" title="Copy to clipboard">
						<span class="dashicons dashicons-clipboard"></span>
						<span class="copy-text">Copy</span>
					</button>
				<?php endif; ?>

				<pre class="code-block-pre"><code class="code-block-code <?php echo $this->config['show_line_numbers'] ? 'line-numbers' : ''; ?>"><?php echo esc_html( $this->code ); ?></code></pre>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}