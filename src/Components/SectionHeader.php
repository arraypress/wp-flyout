<?php
/**
 * Section Header Component - Simplified
 *
 * Creates consistent section headers with titles and descriptions.
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
 * Class SectionHeader
 *
 * Renders section headers with consistent styling.
 *
 * @since 3.0.0
 */
class SectionHeader {
    use Renderable;

    /**
     * Section title
     *
     * @since 3.0.0
     * @var string
     */
    private string $title;

    /**
     * Section configuration
     *
     * @since 3.0.0
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
     * @since 3.0.0
     *
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
     * @since 3.0.0
     *
     */
    public static function create( string $title, string $description = '', ?string $icon = null ): self {
        return new self( $title, [
                'description' => $description,
                'icon'        => $icon,
        ] );
    }

    /**
     * Render the section header
     *
     * @return string Generated HTML.
     * @since 3.0.0
     *
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