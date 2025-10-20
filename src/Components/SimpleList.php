<?php
/**
 * Simple List Component
 *
 * Basic list items with optional icons and actions.
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
use ArrayPress\WPFlyout\Traits\IconRenderer;

/**
 * Class SimpleList
 *
 * Creates simple lists with optional icons and actions.
 *
 * @since 1.0.0
 */
class SimpleList {
	use Renderable;
	use IconRenderer;

	/**
	 * List items
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $items = [];

	/**
	 * Component configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [
		'class'       => 'wp-flyout-simple-list',
		'show_icons'  => true,
		'show_actions' => true,
		'bordered'    => false
	];

	/**
	 * Constructor
	 *
	 * @param array $items  List items
	 * @param array $config Configuration options
	 *
	 * @since 1.0.0
	 */
	public function __construct( array $items = [], array $config = [] ) {
		$this->items  = $items;
		$this->config = array_merge( $this->config, $config );
	}

	/**
	 * Add an item to the list
	 *
	 * @param string $text    Item text
	 * @param array  $options Item options (icon, url, action, etc.)
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public function add_item( string $text, array $options = [] ): self {
		$this->items[] = array_merge( [
			'text'   => $text,
			'icon'   => null,
			'url'    => null,
			'action' => null,
			'class'  => ''
		], $options );

		return $this;
	}

	/**
	 * Render the list
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 */
	public function render(): string {
		if ( empty( $this->items ) ) {
			return '';
		}

		$classes = [ $this->config['class'] ];
		if ( $this->config['bordered'] ) {
			$classes[] = 'bordered';
		}

		ob_start();
		?>
		<ul class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<?php foreach ( $this->items as $item ) : ?>
				<?php echo $this->render_item( $item ); ?>
			<?php endforeach; ?>
		</ul>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a single list item
	 *
	 * @param array $item Item data
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 */
	private function render_item( array $item ): string {
		$classes = [ 'list-item' ];
		if ( ! empty( $item['class'] ) ) {
			$classes[] = $item['class'];
		}

		ob_start();
		?>
		<li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<div class="list-item-content">
				<?php if ( $this->config['show_icons'] && ! empty( $item['icon'] ) ) : ?>
					<span class="list-item-icon">
                        <?php echo $this->render_icon( $item['icon'] ); ?>
                    </span>
				<?php endif; ?>

				<span class="list-item-text"><?php echo esc_html( $item['text'] ); ?></span>
			</div>

			<?php if ( $this->config['show_actions'] && ( ! empty( $item['url'] ) || ! empty( $item['action'] ) ) ) : ?>
				<div class="list-item-actions">
					<?php if ( ! empty( $item['url'] ) ) : ?>
						<a href="<?php echo esc_url( $item['url'] ); ?>" class="button-link">
							<?php echo $this->render_icon( 'arrow-right-alt2' ); ?>
						</a>
					<?php elseif ( ! empty( $item['action'] ) ) : ?>
						<button type="button" class="button-link" data-action="<?php echo esc_attr( $item['action'] ); ?>">
							<?php echo $this->render_icon( 'arrow-right-alt2' ); ?>
						</button>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</li>
		<?php
		return ob_get_clean();
	}
}