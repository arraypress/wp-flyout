<?php
/**
 * Feature List Component - Simplified
 *
 * Creates lists with icons for features, benefits, or requirements.
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
 * Class FeatureList
 *
 * Renders lists with icons and consistent styling.
 *
 * @since 1.0.0
 */
class FeatureList {
	use Renderable;

	/**
	 * List items
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $items = [];

	/**
	 * List configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [
		'icon'       => 'yes',
		'icon_color' => 'default', // success, warning, error, info, default
		'type'       => 'ul',       // ul, ol
		'class'      => 'wp-flyout-feature-list',
		'columns'    => 1,
	];

	/**
	 * Constructor
	 *
	 * @param array $items  List items.
	 * @param array $config Optional configuration.
	 *
	 * @since 1.0.0
	 *
	 */
	public function __construct( array $items = [], array $config = [] ) {
		$this->items  = $items;
		$this->config = array_merge( $this->config, $config );
	}

	/**
	 * Create a feature list with checkmarks
	 *
	 * @param array $items Features array.
	 *
	 * @return self
	 * @since 1.0.0
	 *
	 */
	public static function features( array $items ): self {
		return new self( $items, [
			'icon'       => 'yes-alt',
			'icon_color' => 'success',
		] );
	}

	/**
	 * Add an item to the list
	 *
	 * @param string $text Item text.
	 * @param string $icon Optional custom icon.
	 *
	 * @return self
	 * @since 1.0.0
	 *
	 */
	public function add_item( string $text, string $icon = '' ): self {
		$this->items[] = [
			'text' => $text,
			'icon' => $icon ?: $this->config['icon'],
		];

		return $this;
	}

	/**
	 * Render the list
	 *
	 * @return string Generated HTML.
	 * @since 1.0.0
	 *
	 */
	public function render(): string {
		if ( empty( $this->items ) ) {
			return '';
		}

		$classes = [
			$this->config['class'],
			'icon-color-' . $this->config['icon_color'],
		];

		if ( $this->config['columns'] > 1 ) {
			$classes[] = 'columns-' . $this->config['columns'];
		}

		$tag = $this->config['type'];

		ob_start();
		?>
        <<?php echo $tag; ?> class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
		<?php foreach ( $this->items as $item ) : ?>
			<?php
			$text = is_string( $item ) ? $item : ( $item['text'] ?? '' );
			$icon = is_array( $item ) ? ( $item['icon'] ?? $this->config['icon'] ) : $this->config['icon'];
			?>
            <li>
				<?php if ( $icon ) : ?>
                    <span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
				<?php endif; ?>
                <span class="list-text"><?php echo esc_html( $text ); ?></span>
            </li>
		<?php endforeach; ?>
        </<?php echo $tag; ?>>
		<?php
		return ob_get_clean();
	}

}