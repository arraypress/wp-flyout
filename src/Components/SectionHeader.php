<?php
/**
 * Section Header Component
 *
 * Creates consistent section headers with titles, descriptions, and optional icons.
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
 * Class SectionHeader
 *
 * Renders section headers with consistent styling.
 */
class SectionHeader {
    use Renderable;

    /**
     * Section title
     *
     * @var string
     */
    private string $title;

    /**
     * Section configuration
     *
     * @var array
     */
    private array $config = [
            'description' => '',
            'icon'        => null,
            'tag'         => 'h3',
            'class'       => 'wp-flyout-section-header',
            'badge'       => null,
            'actions'     => [] // Array of action links/buttons
    ];

    /**
     * Constructor
     *
     * @param string $title  Section title
     * @param array  $config Optional configuration
     */
    public function __construct( string $title, array $config = [] ) {
        $this->title  = $title;
        $this->config = array_merge( $this->config, $config );
    }

    /**
     * Create a standard section header
     *
     * @param string $title       Section title
     * @param string $description Optional description
     * @param string $icon        Optional dashicon
     *
     * @return self
     */
    public static function create( string $title, string $description = '', string $icon = null ): self {
        return new self( $title, [
                'description' => $description,
                'icon'        => $icon
        ] );
    }

    /**
     * Add an action button/link to the header
     *
     * @param string $text   Button text
     * @param string $url    URL or JavaScript action
     * @param array  $config Button configuration
     *
     * @return self
     */
    public function add_action( string $text, string $url = '#', array $config = [] ): self {
        $this->config['actions'][] = array_merge( [
                'text'  => $text,
                'url'   => $url,
                'class' => 'button button-small',
                'icon'  => null
        ], $config );

        return $this;
    }

    /**
     * Render the section header
     *
     * @return string Generated HTML
     */
    public function render(): string {
        $tag = $this->config['tag'];

        ob_start();
        ?>
    <div class="<?php echo esc_attr( $this->config['class'] ); ?>">
        <div class="section-header-main">
        <<?php echo $tag; ?> class="section-title">
        <?php if ( $this->config['icon'] ): ?>
            <span class="dashicons dashicons-<?php echo esc_attr( $this->config['icon'] ); ?>"></span>
        <?php endif; ?>
        <?php echo esc_html( $this->title ); ?>
        <?php if ( $this->config['badge'] ): ?>
            <?php echo $this->config['badge']; ?>
        <?php endif; ?>
        </<?php echo $tag; ?>>

        <?php if ( ! empty( $this->config['actions'] ) ): ?>
            <div class="section-actions">
                <?php foreach ( $this->config['actions'] as $action ): ?>
                    <a href="<?php echo esc_url( $action['url'] ); ?>"
                       class="<?php echo esc_attr( $action['class'] ); ?>">
                        <?php if ( $action['icon'] ): ?>
                            <span class="dashicons dashicons-<?php echo esc_attr( $action['icon'] ); ?>"></span>
                        <?php endif; ?>
                        <?php echo esc_html( $action['text'] ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        </div><?php // Closing section-header-main ?>

        <?php if ( $this->config['description'] ): ?>
            <p class="description"><?php echo esc_html( $this->config['description'] ); ?></p>
        <?php endif; ?>
        </div><?php // Closing main wrapper ?>
        <?php
        return ob_get_clean();
    }

}