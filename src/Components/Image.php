<?php
/**
 * Image Component
 *
 * Displays images with various sizes and fallback support for thumbnails,
 * product images, and other visual elements.
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
 * Class Image
 *
 * Renders images with proper sizing and fallback handling.
 */
class Image {
    use Renderable;

    /**
     * Image source URL
     *
     * @var string
     */
    private string $src;

    /**
     * Alternative text for accessibility
     *
     * @var string
     */
    private string $alt = '';

    /**
     * Image configuration
     *
     * @var array
     */
    private array $config = [
        'size'        => 'thumbnail',
        'shape'       => 'square', // square, rounded, circle
        'class'       => '',
        'width'       => null,
        'height'      => null,
        'fallback'    => '',
        'lazy'        => true,
        'link'        => null,
        'link_class'  => '',
        'link_target' => '',
        'rounded'     => false // deprecated, use shape instead
    ];

    /**
     * Constructor
     *
     * @param string $src    Image source URL
     * @param string $alt    Alternative text
     * @param array  $config Optional configuration
     */
    public function __construct( string $src, string $alt = '', array $config = [] ) {
        $this->src    = $src;
        $this->alt    = $alt;
        $this->config = array_merge( $this->config, $config );
    }

    /**
     * Create a thumbnail image
     *
     * @param string $src Image source
     * @param string $alt Alternative text
     *
     * @return self
     */
    public static function thumbnail( string $src, string $alt = '' ): self {
        return new self( $src, $alt, [
                'size'    => 'thumbnail',
                'class'   => 'wp-flyout-thumbnail',
                'rounded' => true
        ] );
    }

    /**
     * Create a product image
     *
     * @param string $src Image source
     * @param string $alt Alternative text
     *
     * @return self
     */
    public static function product( string $src, string $alt = '' ): self {
        return new self( $src, $alt, [
                'size'    => 'medium',
                'class'   => 'wp-flyout-product-image',
                'rounded' => true
        ] );
    }

    /**
     * Render the image
     *
     * @return string Generated HTML
     */
    public function render(): string {
        $src = $this->src ?: $this->config['fallback'];

        if ( empty( $src ) ) {
            return $this->render_placeholder();
        }

        $classes = [ 'wp-flyout-image' ];
        if ( $this->config['class'] ) {
            $classes[] = $this->config['class'];
        }

        // Handle shape classes
        if ( $this->config['shape'] ) {
            $classes[] = 'shape-' . $this->config['shape'];
        } elseif ( $this->config['rounded'] ) {
            $classes[] = 'rounded';
        }

        $classes[] = 'size-' . $this->config['size'];

        $attributes = [
                'src'   => esc_url( $src ),
                'alt'   => esc_attr( $this->alt ),
                'class' => implode( ' ', $classes )
        ];

        // Add dimensions if specified
        if ( $this->config['width'] ) {
            $attributes['width'] = $this->config['width'];
        }
        if ( $this->config['height'] ) {
            $attributes['height'] = $this->config['height'];
        }

        // Add lazy loading
        if ( $this->config['lazy'] ) {
            $attributes['loading'] = 'lazy';
        }

        ob_start();

        if ( $this->config['link'] ) {
            $link_attrs = [
                    'href'  => esc_url( $this->config['link'] ),
                    'class' => esc_attr( $this->config['link_class'] )
            ];
            if ( $this->config['link_target'] ) {
                $link_attrs['target'] = esc_attr( $this->config['link_target'] );
            }
            ?>
            <a <?php echo $this->render_attributes( $link_attrs ); ?>>
                <img <?php echo $this->render_attributes( $attributes ); ?> />
            </a>
            <?php
        } else {
            ?>
            <img <?php echo $this->render_attributes( $attributes ); ?> />
            <?php
        }

        return ob_get_clean();
    }

    /**
     * Render a placeholder when no image is available
     *
     * @return string Generated HTML
     */
    private function render_placeholder(): string {
        $classes = [ 'wp-flyout-image-placeholder' ];
        if ( $this->config['class'] ) {
            $classes[] = $this->config['class'];
        }
        if ( $this->config['rounded'] ) {
            $classes[] = 'rounded';
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
            <span class="dashicons dashicons-format-image"></span>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render HTML attributes
     *
     * @param array $attributes Attributes array
     *
     * @return string HTML attributes string
     */
    private function render_attributes( array $attributes ): string {
        $output = [];
        foreach ( $attributes as $key => $value ) {
            if ( $value !== null && $value !== '' ) {
                $output[] = sprintf( '%s="%s"', $key, $value );
            }
        }

        return implode( ' ', $output );
    }

}