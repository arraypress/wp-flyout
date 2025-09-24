<?php
/**
 * Link Component
 *
 * Creates consistent link elements with optional icons and styling.
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
 * Class Link
 *
 * Renders link elements with various configurations.
 */
class Link {
    use Renderable;

    /**
     * Link text
     *
     * @var string
     */
    private string $text;

    /**
     * Link URL
     *
     * @var string
     */
    private string $href;

    /**
     * Link configuration
     *
     * @var array
     */
    private array $config = [
            'target'        => '',
            'class'         => '',
            'icon'          => null,
            'icon_position' => 'before', // before or after
            'external'      => false,
            'button_style'  => false,
            'button_type'   => '', // primary, secondary
            'attributes'    => []
    ];

    /**
     * Constructor
     *
     * @param string $text   Link text
     * @param string $href   Link URL
     * @param array  $config Optional configuration
     */
    public function __construct( string $text, string $href, array $config = [] ) {
        $this->text   = $text;
        $this->href   = $href;
        $this->config = array_merge( $this->config, $config );

        // Auto-detect external links
        if ( str_starts_with( $href, 'http' ) && ! str_contains( $href, home_url() ) ) {
            $this->config['external'] = true;
            if ( empty( $this->config['target'] ) ) {
                $this->config['target'] = '_blank';
            }
        }
    }

    /**
     * Create an action link
     *
     * @param string $text Link text
     * @param string $href Link URL
     * @param string $icon Optional dashicon
     *
     * @return self
     */
    public static function action( string $text, string $href, string $icon = '' ): self {
        return new self( $text, $href, [
                'icon'         => $icon,
                'button_style' => true,
                'button_type'  => 'secondary'
        ] );
    }

    /**
     * Create an email link
     *
     * @param string $email Email address
     * @param string $text  Optional text (defaults to email)
     *
     * @return self
     */
    public static function email( string $email, string $text = '' ): self {
        $text = $text ?: $email;
        return new self( $text, 'mailto:' . $email, [
                'icon' => 'email-alt'
        ] );
    }

    /**
     * Create a standard link
     *
     * @param string $text   Link text
     * @param string $href   Link URL
     * @param array  $config Optional configuration
     *
     * @return self
     */
    public static function create( string $text, string $href, array $config = [] ): self {
        // Handle new_tab shorthand
        if ( isset( $config['new_tab'] ) && $config['new_tab'] ) {
            $config['target'] = '_blank';
            unset( $config['new_tab'] );
        }
        return new self( $text, $href, $config );
    }

    /**
     * Create an external link
     *
     * @param string $text Link text
     * @param string $href Link URL
     *
     * @return self
     */
    public static function external( string $text, string $href ): self {
        return new self( $text, $href, [
                'external' => true,
                'target'   => '_blank'
        ] );
    }

    /**
     * Create a button-styled link
     *
     * @param string $text Link text
     * @param string $href Link URL
     * @param string $type Button type (primary, secondary)
     *
     * @return self
     */
    public static function button( string $text, string $href, string $type = 'secondary' ): self {
        return new self( $text, $href, [
                'button_style' => true,
                'button_type'  => $type
        ] );
    }


    /**
     * Render the link
     *
     * @return string Generated HTML
     */
    public function render(): string {
        $classes = [];

        if ( $this->config['button_style'] ) {
            $classes[] = 'button';
            if ( $this->config['button_type'] === 'primary' ) {
                $classes[] = 'button-primary';
            } elseif ( $this->config['button_type'] === 'link' ) {
                $classes = [ 'button-link' ];
            }
        }

        if ( $this->config['class'] ) {
            $classes[] = $this->config['class'];
        }

        $attributes = [
                'href' => esc_url( $this->href )
        ];

        if ( ! empty( $classes ) ) {
            $attributes['class'] = implode( ' ', $classes );
        }

        if ( $this->config['target'] ) {
            $attributes['target'] = $this->config['target'];
            if ( $this->config['target'] === '_blank' ) {
                $attributes['rel'] = 'noopener noreferrer';
            }
        }

        // Merge custom attributes
        $attributes = array_merge( $attributes, $this->config['attributes'] );

        ob_start();
        ?>
        <a <?php echo $this->render_attributes( $attributes ); ?>>
            <?php if ( $this->config['icon'] && $this->config['icon_position'] === 'before' ): ?>
                <span class="dashicons dashicons-<?php echo esc_attr( $this->config['icon'] ); ?>"></span>
            <?php endif; ?>

            <?php echo esc_html( $this->text ); ?>

            <?php if ( $this->config['icon'] && $this->config['icon_position'] === 'after' ): ?>
                <span class="dashicons dashicons-<?php echo esc_attr( $this->config['icon'] ); ?>"></span>
            <?php endif; ?>

            <?php if ( $this->config['external'] ): ?>
                <span class="dashicons dashicons-external" style="font-size: 14px; vertical-align: text-top;"></span>
            <?php endif; ?>
        </a>
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
                $output[] = sprintf( '%s="%s"', $key, esc_attr( $value ) );
            }
        }

        return implode( ' ', $output );
    }

}