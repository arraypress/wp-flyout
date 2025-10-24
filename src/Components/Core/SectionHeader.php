<?php
/**
 * Section Header Component
 *
 * Creates consistent section headers with titles and descriptions.
 *
 * @package     ArrayPress\WPFlyout\Components\Core
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Core;

use ArrayPress\WPFlyout\Traits\Renderable;

/**
 * Class SectionHeader
 *
 * Renders section headers with consistent styling.
 *
 * @since 1.0.0
 */
class SectionHeader {
    use Renderable;

    /**
     * Section title
     *
     * @since 1.0.0
     * @var string
     */
    private string $title;

    /**
     * Section configuration
     *
     * @since 1.0.0
     * @var array
     */
    private array $config = [
            'description' => '',
            'icon'        => null,
            'tag'         => 'h3',
            'class'       => 'wp-flyout-section-header',
    ];

    /**
     * Constructor
     *
     * @param string $title  Section title.
     * @param array  $config Optional configuration.
     *
     * @since 1.0.0
     */
    public function __construct( string $title, array $config = [] ) {
        $this->title  = $title;
        $this->config = array_merge( $this->config, $config );
    }

    /**
     * Create a standard section header
     *
     * @param string      $title       Section title.
     * @param string      $description Optional description.
     * @param string|null $icon        Optional dashicon.
     *
     * @return self
     * @since 1.0.0
     */
    public static function create( string $title, string $description = '', ?string $icon = null ): self {
        return new self( $title, [
                'description' => $description,
                'icon'        => $icon,
        ] );
    }

    /**
     * Create a heading with emphasized value
     *
     * @param string $label Label text.
     * @param string $value Value to emphasize.
     * @param string $tag   HTML tag.
     *
     * @return string Generated HTML.
     * @since 1.0.0
     */
    public static function with_value( string $label, string $value, string $tag = 'p' ): string {
        return sprintf(
                '<%1$s class="wp-flyout-label-value">%2$s: <strong>%3$s</strong></%1$s>',
                esc_attr( $tag ),
                esc_html( $label ),
                esc_html( $value )
        );
    }

    /**
     * Quick render of section header
     *
     * @param string      $title       Section title.
     * @param string      $description Optional description.
     * @param string|null $icon        Optional icon.
     *
     * @return string Rendered HTML.
     * @since 1.0.0
     */
    public static function quick( string $title, string $description = '', ?string $icon = null ): string {
        return self::create( $title, $description, $icon )->render();
    }

    /**
     * Render the section header
     *
     * @return string Generated HTML.
     * @since 1.0.0
     */
    public function render(): string {
        $tag = $this->config['tag'];

        ob_start();
        ?>
    <div class="<?php echo esc_attr( $this->config['class'] ); ?>">
        <<?php echo $tag; ?> class="section-title">
        <?php if ( $this->config['icon'] ) : ?>
            <span class="dashicons dashicons-<?php echo esc_attr( $this->config['icon'] ); ?>"></span>
        <?php endif; ?>
        <?php echo esc_html( $this->title ); ?>
        </<?php echo $tag; ?>>

        <?php if ( $this->config['description'] ) : ?>
            <p class="description"><?php echo esc_html( $this->config['description'] ); ?></p>
        <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

}