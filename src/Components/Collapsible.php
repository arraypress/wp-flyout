<?php
/**
 * Collapsible Component
 *
 * Single expandable/collapsible section
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Traits\IconRenderer;
use ArrayPress\WPFlyout\Traits\Renderable;

/**
 * Class Collapsible
 *
 * Creates a single collapsible section.
 *
 * @since 1.0.0
 */
class Collapsible {
	use Renderable;
	use IconRenderer;

	/**
	 * Component configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [
		'id'      => '',
		'title'   => '',
		'content' => '',
		'open'    => false,
		'icon'    => null,
		'class'   => 'wp-flyout-collapsible',
	];

	/**
	 * Constructor
	 *
	 * @param string $title   Section title
	 * @param string $content Section content
	 * @param array  $config  Configuration options
	 *
	 * @since 1.0.0
	 */
	public function __construct( string $title, string $content, array $config = [] ) {
		$this->config            = array_merge( $this->config, $config );
		$this->config['title']   = $title;
		$this->config['content'] = $content;

		// Auto-generate ID if not provided
		if ( empty( $this->config['id'] ) ) {
			$this->config['id'] = 'collapsible-' . uniqid();
		}
	}

	/**
	 * Create a collapsible section
	 *
	 * @param string $title   Section title
	 * @param string $content Section content
	 * @param array  $config  Configuration options
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function create( string $title, string $content, array $config = [] ): self {
		return new self( $title, $content, $config );
	}

	/**
	 * Set collapsible as initially collapsed
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public function collapsed(): self {
		$this->config['open'] = false;

		return $this;
	}

	/**
	 * Set collapsible as initially open
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public function open(): self {
		$this->config['open'] = true;

		return $this;
	}

	/**
	 * Set icon
	 *
	 * @param string $icon Dashicon name
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public function icon( string $icon ): self {
		$this->config['icon'] = $icon;

		return $this;
	}

	/**
	 * Render the collapsible
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 */
	public function render(): string {
		$is_open    = $this->config['open'];
		$content_id = $this->config['id'] . '-content';

		ob_start();
		?>
		<div id="<?php echo esc_attr( $this->config['id'] ); ?>"
		     class="<?php echo esc_attr( $this->config['class'] ); ?> <?php echo $is_open ? 'is-open' : ''; ?>">

			<button type="button"
			        class="collapsible-header"
			        aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
			        aria-controls="<?php echo esc_attr( $content_id ); ?>">

				<?php if ( $this->config['icon'] ) : ?>
					<?php echo $this->render_icon( $this->config['icon'] ); ?>
				<?php endif; ?>

				<span class="collapsible-title"><?php echo esc_html( $this->config['title'] ); ?></span>

				<span class="collapsible-indicator">
					<span class="dashicons dashicons-arrow-down-alt2"></span>
				</span>
			</button>

			<div id="<?php echo esc_attr( $content_id ); ?>"
			     class="collapsible-content"
			     role="region"
				<?php echo ! $is_open ? 'style="display: none;"' : ''; ?>>
				<div class="collapsible-content-inner">
					<?php echo $this->config['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

}