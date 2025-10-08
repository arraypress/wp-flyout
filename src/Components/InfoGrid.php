<?php
/**
 * Info Grid Component - Simplified
 *
 * Displays key-value pairs in a clean grid layout.
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     3.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Traits\Renderable;

/**
 * Class InfoGrid
 *
 * Creates a grid of label/value pairs for displaying structured information.
 *
 * @since 1.0.0
 */
class InfoGrid {
	use Renderable;

	/**
	 * Grid items array
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $items = [];

	/**
	 * Grid configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [
		'columns'    => 2,
		'class'      => 'wp-flyout-info-grid',
		'empty_text' => '—',
	];

	/**
	 * Constructor
	 *
	 * @param array $items  Initial items to add.
	 * @param array $config Optional configuration.
	 *
	 * @since 1.0.0
	 *
	 */
	public function __construct( array $items = [], array $config = [] ) {
		$this->config = array_merge( $this->config, $config );

		foreach ( $items as $label => $value ) {
			$this->add_item( $label, $value );
		}
	}

	/**
	 * Add an item to the grid
	 *
	 * @param string $label  Item label.
	 * @param mixed  $value  Item value.
	 * @param bool   $escape Whether to escape HTML (default true).
	 *
	 * @return self
	 * @since 1.0.0
	 *
	 */
	public function add_item( string $label, $value, bool $escape = true ): self {
		$this->items[] = [
			'label'  => $label,
			'value'  => $value,
			'escape' => $escape,
		];

		return $this;
	}

	/**
	 * Add a separator between items
	 *
	 * @param string $title Optional separator title.
	 *
	 * @return self
	 * @since 1.0.0
	 *
	 */
	public function add_separator( string $title = '' ): self {
		$this->items[] = [
			'type'  => 'separator',
			'title' => $title,
		];

		return $this;
	}

	/**
	 * Render the info grid
	 *
	 * @return string Generated HTML.
	 * @since 1.0.0
	 *
	 */
	public function render(): string {
		if ( empty( $this->items ) ) {
			return '';
		}

		ob_start();
		?>
        <div class="<?php echo esc_attr( $this->config['class'] ); ?>"
             data-columns="<?php echo esc_attr( (string) $this->config['columns'] ); ?>">
			<?php foreach ( $this->items as $item ) : ?>
				<?php echo $this->render_item( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endforeach; ?>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a single grid item
	 *
	 * @param array $item Item configuration.
	 *
	 * @return string Generated HTML.
	 * @since 1.0.0
	 *
	 */
	private function render_item( array $item ): string {
		if ( isset( $item['type'] ) && 'separator' === $item['type'] ) {
			return $this->render_separator( $item );
		}

		$value = $this->format_value( $item['value'] );

		ob_start();
		?>
        <div class="wp-flyout-info-item">
            <span class="wp-flyout-info-label"><?php echo esc_html( $item['label'] ); ?></span>
            <span class="wp-flyout-info-value">
				<?php
				if ( $item['escape'] ) {
					echo esc_html( $value );
				} else {
					echo $value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</span>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a separator
	 *
	 * @param array $item Separator configuration.
	 *
	 * @return string Generated HTML.
	 * @since 1.0.0
	 *
	 */
	private function render_separator( array $item ): string {
		ob_start();
		?>
        <div class="wp-flyout-info-separator">
			<?php if ( ! empty( $item['title'] ) ) : ?>
                <span><?php echo esc_html( $item['title'] ); ?></span>
			<?php endif; ?>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Format a value for display
	 *
	 * @param mixed $value Value to format.
	 *
	 * @return string Formatted value.
	 * @since 1.0.0
	 *
	 */
	private function format_value( $value ): string {
		if ( empty( $value ) && $value !== 0 && $value !== '0' ) {
			return $this->config['empty_text'];
		}

		if ( is_bool( $value ) ) {
			return $value ? __( 'Yes', 'wp-flyout' ) : __( 'No', 'wp-flyout' );
		}

		if ( is_array( $value ) ) {
			return implode( ', ', array_map( 'strval', $value ) );
		}

		return (string) $value;
	}

}