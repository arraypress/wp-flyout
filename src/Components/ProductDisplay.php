<?php
/**
 * Product Display Component
 *
 * Flexible component for displaying products, items, or any media + content layout
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Traits\Renderable;
use ArrayPress\WPFlyout\Traits\ClassBuilder;

/**
 * Class ProductDisplay
 */
class ProductDisplay {
    use Renderable;
    use ClassBuilder;

    /**
     * Item data
     * @var array
     */
    private array $item = [];

    /**
     * Component configuration
     * @var array
     */
    private array $config = [
            'size'         => 'default', // 'compact', 'default', 'large'
            'layout'       => 'horizontal', // 'horizontal', 'stacked'
            'show_media'   => true,
            'show_actions' => true,
            'borderless'   => false,
            'class'        => 'product-display',
            'media_size'   => 60, // pixels
            'placeholder'  => '<span class="dashicons dashicons-format-image"></span>'
    ];

    /**
     * Constructor
     */
    public function __construct( array $item, array $config = [] ) {
        $this->item = array_merge( [
                'id'          => '',
                'title'       => '',
                'subtitle'    => '',
                'description' => '',
                'media'       => '', // URL or HTML
                'media_type'  => 'image', // 'image', 'icon', 'html'
                'price'       => '',
                'meta'        => [], // Key-value pairs
                'actions'     => [], // Buttons/links
                'badges'      => [], // Status badges
        ], $item );

        $this->config = array_merge( $this->config, $config );
    }

    /**
     * Render the component
     */
    public function render(): string {
        $classes = [
                $this->config['class'],
                $this->config['size'] !== 'default' ? $this->config['size'] : '',
                $this->config['layout'] === 'stacked' ? 'stacked' : '',
                $this->conditional_class( 'borderless', $this->config['borderless'] )
        ];

        $class_string = $this->build_classes( $classes );

        ob_start();
        ?>
        <div class="<?php echo $class_string; ?>"
             data-item-id="<?php echo esc_attr( $this->item['id'] ); ?>">

            <?php if ( $this->config['show_media'] ) : ?>
                <?php echo $this->render_media(); ?>
            <?php endif; ?>

            <?php echo $this->render_content(); ?>

            <?php if ( $this->config['show_actions'] && ! empty( $this->item['actions'] ) ) : ?>
                <?php echo $this->render_actions(); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render media section
     */
    private function render_media(): string {
        ob_start();
        ?>
        <div class="product-media">
            <?php if ( ! empty( $this->item['media'] ) ) : ?>
                <?php if ( $this->item['media_type'] === 'image' ) : ?>
                    <img src="<?php echo esc_url( $this->item['media'] ); ?>"
                         alt="<?php echo esc_attr( $this->item['title'] ); ?>">
                <?php else : ?>
                    <?php echo $this->item['media']; ?>
                <?php endif; ?>
            <?php else : ?>
                <div class="product-media-placeholder">
                    <?php echo $this->config['placeholder']; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render content section
     */
    private function render_content(): string {
        ob_start();
        ?>
        <div class="product-content">
            <?php if ( ! empty( $this->item['badges'] ) ) : ?>
                <div class="product-badges">
                    <?php foreach ( $this->item['badges'] as $badge ) : ?>
                        <span class="product-badge <?php echo esc_attr( $badge['type'] ?? '' ); ?>">
							<?php echo esc_html( $badge['text'] ); ?>
						</span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $this->item['title'] ) ) : ?>
                <h4 class="product-title"><?php echo esc_html( $this->item['title'] ); ?></h4>
            <?php endif; ?>

            <?php if ( ! empty( $this->item['subtitle'] ) ) : ?>
                <p class="product-subtitle"><?php echo esc_html( $this->item['subtitle'] ); ?></p>
            <?php endif; ?>

            <?php if ( ! empty( $this->item['description'] ) && $this->config['size'] !== 'compact' ) : ?>
                <p class="product-description"><?php echo esc_html( $this->item['description'] ); ?></p>
            <?php endif; ?>

            <?php if ( ! empty( $this->item['price'] ) || ! empty( $this->item['meta'] ) ) : ?>
                <div class="product-meta">
                    <?php if ( ! empty( $this->item['price'] ) ) : ?>
                        <div class="product-meta-item product-price">
                            <span class="product-meta-value"><?php echo esc_html( $this->item['price'] ); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php foreach ( $this->item['meta'] as $key => $value ) : ?>
                        <div class="product-meta-item">
                            <span class="product-meta-label"><?php echo esc_html( $key ); ?>:</span>
                            <span class="product-meta-value"><?php echo esc_html( $value ); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render actions section
     */
    private function render_actions(): string {
        ob_start();
        ?>
        <div class="product-actions">
            <?php foreach ( $this->item['actions'] as $action ) : ?>
                <?php if ( $action['type'] === 'link' ) : ?>
                    <a href="<?php echo esc_url( $action['url'] ?? '#' ); ?>"
                       class="<?php echo esc_attr( $action['class'] ?? 'button' ); ?>">
                        <?php if ( ! empty( $action['icon'] ) ) : ?>
                            <span class="dashicons dashicons-<?php echo esc_attr( $action['icon'] ); ?>"></span>
                        <?php endif; ?>
                        <?php echo esc_html( $action['text'] ); ?>
                    </a>
                <?php else : ?>
                    <button type="button"
                            class="<?php echo esc_attr( $action['class'] ?? 'button' ); ?>"
                            <?php if ( ! empty( $action['action'] ) ) : ?>
                                data-action="<?php echo esc_attr( $action['action'] ); ?>"
                            <?php endif; ?>>
                        <?php if ( ! empty( $action['icon'] ) ) : ?>
                            <span class="dashicons dashicons-<?php echo esc_attr( $action['icon'] ); ?>"></span>
                        <?php endif; ?>
                        <?php echo esc_html( $action['text'] ); ?>
                    </button>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Static factory for product item
     */
    public static function product( string $title, string $price, array $options = [] ): self {
        $item = array_merge( [
                'title' => $title,
                'price' => $price
        ], $options );

        return new self( $item );
    }

    /**
     * Static factory for file item
     */
    public static function file( string $name, string $size, array $options = [] ): self {
        $item = array_merge( [
                'title'      => $name,
                'meta'       => [ 'Size' => $size ],
                'media'      => '<span class="dashicons dashicons-media-default"></span>',
                'media_type' => 'html'
        ], $options );

        return new self( $item, [ 'size' => 'compact' ] );
    }

}