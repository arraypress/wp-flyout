<?php
/**
 * Image Gallery Component
 *
 * Grid-based image gallery with media library integration, previews, and drag-drop reordering.
 * Optimized for visual content management with support for captions and alt text.
 *
 * @package     ArrayPress\WPFlyout\Components\Interactive
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Interfaces\Renderable;
use ArrayPress\WPFlyout\Traits\FileUtilities;

/**
 * Class ImageGallery
 *
 * Manages image collections with visual preview grid and drag-drop sorting.
 *
 * @since 1.0.0
 */
class ImageGallery implements Renderable {
	use FileUtilities;

	/**
	 * Component configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @param array $config Configuration options
	 */
	public function __construct( array $config = [] ) {
		$this->config = wp_parse_args( $config, self::get_defaults() );

		// Auto-generate ID if not provided
		if ( empty( $this->config['id'] ) ) {
			$this->config['id'] = 'image-gallery-' . wp_generate_uuid4();
		}

		// Ensure items is array
		if ( ! is_array( $this->config['items'] ) ) {
			$this->config['items'] = [];
		}
	}

	/**
	 * Get default configuration
	 *
	 * @since 1.0.0
	 *
	 * @return array Default configuration values
	 */
	private static function get_defaults(): array {
		return [
			'id'            => '',
			'name'          => 'gallery',
			'items'         => [],
			'max_images'    => 0,  // 0 = unlimited
			'sortable'      => true,
			'show_caption'  => true,
			'show_alt'      => true,
			'columns'       => 4,  // Grid columns (2-6)
			'size'          => 'thumbnail', // WordPress image size
			'add_text'      => __( 'Add Images', 'wp-flyout' ),
			'empty_text'    => __( 'No images added yet', 'wp-flyout' ),
			'empty_icon'    => 'format-gallery',
			'multiple'      => true, // Allow multiple selection in media library
			'class'         => ''
		];
	}

	/**
	 * Render the component
	 *
	 * @since 1.0.0
	 *
	 * @return string Generated HTML
	 */
	public function render(): string {
		$classes = [ 'wp-flyout-image-gallery' ];

		if ( $this->config['sortable'] ) {
			$classes[] = 'is-sortable';
		}

		$classes[] = 'columns-' . $this->config['columns'];

		if ( ! empty( $this->config['class'] ) ) {
			$classes[] = $this->config['class'];
		}

		ob_start();
		?>
		<div id="<?php echo esc_attr( $this->config['id'] ); ?>"
		     class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
		     data-name="<?php echo esc_attr( $this->config['name'] ); ?>"
		     data-max-images="<?php echo esc_attr( (string) $this->config['max_images'] ); ?>"
		     data-show-caption="<?php echo esc_attr( $this->config['show_caption'] ? 'true' : 'false' ); ?>"
		     data-show-alt="<?php echo esc_attr( $this->config['show_alt'] ? 'true' : 'false' ); ?>"
		     data-size="<?php echo esc_attr( $this->config['size'] ); ?>"
		     data-multiple="<?php echo esc_attr( $this->config['multiple'] ? 'true' : 'false' ); ?>">

			<div class="gallery-header">
				<div class="gallery-title">
					<span class="dashicons dashicons-format-gallery"></span>
					<?php esc_html_e( 'Image Gallery', 'wp-flyout' ); ?>
					<?php if ( $this->config['max_images'] > 0 ) : ?>
						<span class="image-count">
							(<span class="current-count"><?php echo count( $this->config['items'] ); ?></span>/<?php echo $this->config['max_images']; ?>)
						</span>
					<?php endif; ?>
				</div>

				<button type="button"
				        class="button button-secondary gallery-add-btn"
				        data-action="add"
					<?php if ( $this->config['max_images'] > 0 && count( $this->config['items'] ) >= $this->config['max_images'] ) : ?>
						disabled
					<?php endif; ?>>
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php echo esc_html( $this->config['add_text'] ); ?>
				</button>
			</div>

			<div class="gallery-container <?php echo empty( $this->config['items'] ) ? 'is-empty' : ''; ?>">
				<div class="gallery-empty">
					<span class="dashicons dashicons-<?php echo esc_attr( $this->config['empty_icon'] ); ?>"></span>
					<p><?php echo esc_html( $this->config['empty_text'] ); ?></p>
				</div>

				<div class="gallery-grid">
					<?php foreach ( $this->config['items'] as $index => $image ) : ?>
						<?php $this->render_image_item( $image, $index ); ?>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render single image item
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param array $image Image data
	 * @param int   $index Item index
	 *
	 * @return void
	 */
	private function render_image_item( array $image, int $index ): void {
		$attachment_id = $image['attachment_id'] ?? $image['id'] ?? 0;
		$url           = $image['url'] ?? '';
		$thumbnail     = $image['thumbnail'] ?? $url;
		$caption       = $image['caption'] ?? '';
		$alt           = $image['alt'] ?? '';
		$title         = $image['title'] ?? '';

		// Try to get thumbnail if we have attachment ID
		if ( $attachment_id && ! $thumbnail ) {
			$thumbnail = wp_get_attachment_image_url( $attachment_id, $this->config['size'] );
		}

		// Use placeholder if no thumbnail
		if ( ! $thumbnail ) {
			$thumbnail = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2RkZCIvPjx0ZXh0IHRleHQtYW5jaG9yPSJtaWRkbGUiIHg9IjUwIiB5PSI1MCIgc3R5bGU9ImZpbGw6I2FhYTtmb250LXdlaWdodDpib2xkO2ZvbnQtc2l6ZToxM3B4O2ZvbnQtZmFtaWx5OkFyaWFsLEhlbHZldGljYSxzYW5zLXNlcmlmO2RvbWluYW50LWJhc2VsaW5lOmNlbnRyYWwiPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';
		}
		?>
		<div class="gallery-item" data-index="<?php echo esc_attr( (string) $index ); ?>">
			<?php if ( $this->config['sortable'] ) : ?>
				<div class="gallery-item-handle">
					<span class="dashicons dashicons-move"></span>
				</div>
			<?php endif; ?>

			<div class="gallery-item-preview">
				<img src="<?php echo esc_url( $thumbnail ); ?>"
				     alt="<?php echo esc_attr( $alt ); ?>"
				     class="gallery-thumbnail">

				<div class="gallery-item-overlay">
					<button type="button"
					        class="gallery-item-edit"
					        data-action="edit"
					        title="<?php esc_attr_e( 'Edit image details', 'wp-flyout' ); ?>">
						<span class="dashicons dashicons-edit"></span>
					</button>

					<button type="button"
					        class="gallery-item-remove"
					        data-action="remove"
					        title="<?php esc_attr_e( 'Remove image', 'wp-flyout' ); ?>">
						<span class="dashicons dashicons-trash"></span>
					</button>
				</div>
			</div>

			<div class="gallery-item-fields">
				<?php if ( $this->config['show_caption'] ) : ?>
					<input type="text"
					       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][caption]"
					       value="<?php echo esc_attr( $caption ); ?>"
					       placeholder="<?php esc_attr_e( 'Caption', 'wp-flyout' ); ?>"
					       class="gallery-caption-input">
				<?php endif; ?>

				<?php if ( $this->config['show_alt'] ) : ?>
					<input type="text"
					       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][alt]"
					       value="<?php echo esc_attr( $alt ); ?>"
					       placeholder="<?php esc_attr_e( 'Alt text', 'wp-flyout' ); ?>"
					       class="gallery-alt-input">
				<?php endif; ?>

				<!-- Hidden fields -->
				<input type="hidden"
				       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][attachment_id]"
				       value="<?php echo esc_attr( (string) $attachment_id ); ?>"
				       data-field="attachment_id">

				<input type="hidden"
				       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][url]"
				       value="<?php echo esc_attr( $url ); ?>"
				       data-field="url">

				<input type="hidden"
				       name="<?php echo esc_attr( $this->config['name'] ); ?>[<?php echo $index; ?>][thumbnail]"
				       value="<?php echo esc_attr( $thumbnail ); ?>"
				       data-field="thumbnail">
			</div>
		</div>
		<?php
	}

}