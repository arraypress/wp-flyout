<?php
/**
 * WP Flyout Core Class - Simplified
 *
 * Renders flyout panels with optional tabs.
 * This is a pure UI component - all business logic should be handled
 * by the implementing plugin via the Manager class.
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     3.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout;

/**
 * Class Flyout
 *
 * Main flyout container for rendering slide-out panels.
 *
 * @since 1.0.0
 */
class Flyout {

    /**
     * Unique identifier for this flyout
     *
     * @since 1.0.0
     * @var string
     */
    private string $id;

    /**
     * Flyout configuration
     *
     * @since 1.0.0
     * @var array{
     *     title: string,
     *     width: string,
     *     position: string,
     *     classes: array<string>
     * }
     */
    private array $config = [
            'title'    => '',
            'width'    => 'medium', // small, medium, large, full
            'position' => 'right',  // right or left
            'classes'  => [],
    ];

    /**
     * Tab configuration
     *
     * @since 1.0.0
     * @var array<string, array{id: string, label: string}>
     */
    private array $tabs = [];

    /**
     * Currently active tab identifier
     *
     * @since 1.0.0
     * @var string
     */
    private string $active_tab = '';

    /**
     * Content storage for tabs or main body
     *
     * @since 1.0.0
     * @var array<string, string>
     */
    private array $content = [];

    /**
     * Footer content HTML
     *
     * @since 1.0.0
     * @var string
     */
    private string $footer = '';

    /**
     * Constructor
     *
     * @param string $id     Unique identifier for this flyout
     * @param array  $config Optional configuration array
     *
     * @since 1.0.0
     *
     */
    public function __construct( string $id, array $config = [] ) {
        $this->id     = $id;
        $this->config = array_merge( $this->config, $config );
    }

    /**
     * Set flyout title
     *
     * @param string $title Title to display in header
     *
     * @return self Returns instance for method chaining
     * @since 1.0.0
     *
     */
    public function set_title( string $title ): self {
        $this->config['title'] = $title;

        return $this;
    }

    /**
     * Get flyout title
     *
     * @return string Current title
     * @since 3.0.0
     *
     */
    public function get_title(): string {
        return $this->config['title'];
    }

    /**
     * Set flyout width
     *
     * @param string $width Width size: 'small', 'medium', 'large', or 'full'
     *
     * @return self Returns instance for method chaining
     * @since 1.0.0
     *
     */
    public function set_width( string $width ): self {
        $valid_widths = [ 'small', 'medium', 'large', 'full' ];

        if ( in_array( $width, $valid_widths, true ) ) {
            $this->config['width'] = $width;
        }

        return $this;
    }

    /**
     * Get flyout width
     *
     * @return string Current width setting
     * @since 3.0.0
     *
     */
    public function get_width(): string {
        return $this->config['width'];
    }

    /**
     * Set flyout position
     *
     * @param string $position Position: 'left' or 'right'
     *
     * @return self Returns instance for method chaining
     * @since 3.0.0
     *
     */
    public function set_position( string $position ): self {
        if ( in_array( $position, [ 'left', 'right' ], true ) ) {
            $this->config['position'] = $position;
        }

        return $this;
    }

    /**
     * Get flyout ID
     *
     * @return string Flyout identifier
     * @since 1.0.0
     *
     */
    public function get_id(): string {
        return $this->id;
    }

    /**
     * Add a tab to the flyout
     *
     * @param string $id     Tab identifier (used for content association)
     * @param string $label  Tab label to display
     * @param bool   $active Whether this tab should be active by default
     *
     * @return self Returns instance for method chaining
     * @since 1.0.0
     *
     */
    public function add_tab( string $id, string $label, bool $active = false ): self {
        $this->tabs[ $id ] = [
                'id'    => $id,
                'label' => $label,
        ];

        // Set as active if requested or if it's the first tab
        if ( $active || empty( $this->active_tab ) ) {
            $this->active_tab = $id;
        }

        // Initialize content storage for this tab
        if ( ! isset( $this->content[ $id ] ) ) {
            $this->content[ $id ] = '';
        }

        return $this;
    }

    /**
     * Add content to a tab or main body
     *
     * Appends content to existing content.
     *
     * @param string $tab_id  Tab identifier (empty string for non-tabbed content)
     * @param string $content HTML content to add
     *
     * @return self Returns instance for method chaining
     * @since 1.0.0
     *
     */
    public function add_content( string $tab_id, string $content ): self {
        // If no tabs exist, use main content area
        if ( empty( $tab_id ) && empty( $this->tabs ) ) {
            $tab_id = 'main';
        }

        if ( ! isset( $this->content[ $tab_id ] ) ) {
            $this->content[ $tab_id ] = '';
        }

        $this->content[ $tab_id ] .= $content;

        return $this;
    }

    /**
     * Set content for a tab (replaces existing)
     *
     * Replaces any existing content for the specified tab.
     *
     * @param string $tab_id  Tab identifier
     * @param string $content HTML content to set
     *
     * @return self Returns instance for method chaining
     * @since 2.0.0
     *
     */
    public function set_tab_content( string $tab_id, string $content ): self {
        if ( empty( $tab_id ) && empty( $this->tabs ) ) {
            $tab_id = 'main';
        }

        $this->content[ $tab_id ] = $content;

        return $this;
    }

    /**
     * Clear all content
     *
     * @return self Returns instance for method chaining
     * @since 3.0.0
     *
     */
    public function clear_content(): self {
        $this->content = [];

        // Re-initialize content for existing tabs
        foreach ( $this->tabs as $tab ) {
            $this->content[ $tab['id'] ] = '';
        }

        // Initialize main content if no tabs
        if ( empty( $this->tabs ) ) {
            $this->content['main'] = '';
        }

        return $this;
    }

    /**
     * Set footer content
     *
     * @param string $content Footer HTML content (typically action buttons)
     *
     * @return self Returns instance for method chaining
     * @since 1.0.0
     *
     */
    public function set_footer( string $content ): self {
        $this->footer = $content;

        return $this;
    }

    /**
     * Get footer content
     *
     * @return string Current footer HTML
     * @since 3.0.0
     *
     */
    public function get_footer(): string {
        return $this->footer;
    }

    /**
     * Add CSS class to flyout
     *
     * @param string $class CSS class name to add
     *
     * @return self Returns instance for method chaining
     * @since 3.0.0
     *
     */
    public function add_class( string $class ): self {
        if ( ! in_array( $class, $this->config['classes'], true ) ) {
            $this->config['classes'][] = $class;
        }

        return $this;
    }

    /**
     * Check if flyout has tabs
     *
     * @return bool True if tabs are configured
     * @since 3.0.0
     *
     */
    public function has_tabs(): bool {
        return ! empty( $this->tabs );
    }

    /**
     * Check if flyout has footer
     *
     * @return bool True if footer content exists
     * @since 3.0.0
     *
     */
    public function has_footer(): bool {
        return ! empty( $this->footer );
    }

    /**
     * Render the complete flyout
     *
     * Generates the complete HTML for the flyout panel.
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    public function render(): string {
        $classes = [
                'wp-flyout',
                'wp-flyout-' . $this->config['width'],
                'wp-flyout-' . $this->config['position'],
                ...$this->config['classes']
        ];

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->id ); ?>"
             class="<?php echo esc_attr( implode( ' ', array_filter( $classes ) ) ); ?>"
             data-flyout-id="<?php echo esc_attr( $this->id ); ?>">

            <?php $this->render_header(); ?>

            <?php if ( $this->has_tabs() ) : ?>
                <?php $this->render_tabs(); ?>
            <?php endif; ?>

            <?php $this->render_body(); ?>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render flyout header
     *
     * @return void
     * @since  3.0.0
     * @access private
     *
     */
    private function render_header(): void {
        ?>
        <div class="wp-flyout-header">
            <h2><?php echo esc_html( $this->config['title'] ); ?></h2>
            <button type="button" class="wp-flyout-close" aria-label="<?php esc_attr_e( 'Close', 'wp-flyout' ); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <?php
    }

    /**
     * Render tab navigation
     *
     * @return void
     * @since  3.0.0
     * @access private
     *
     */
    private function render_tabs(): void {
        ?>
        <div class="wp-flyout-tabs">
            <nav class="wp-flyout-tab-nav" role="tablist">
                <?php foreach ( $this->tabs as $tab ) : ?>
                    <?php
                    $is_active = ( $tab['id'] === $this->active_tab );
                    $classes   = [ 'wp-flyout-tab' ];
                    if ( $is_active ) {
                        $classes[] = 'active';
                    }
                    ?>
                    <a href="#tab-<?php echo esc_attr( $tab['id'] ); ?>"
                       class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
                       role="tab"
                       data-tab="<?php echo esc_attr( $tab['id'] ); ?>"
                       aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>">
                        <?php echo esc_html( $tab['label'] ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
        <?php
    }

    /**
     * Render flyout body and footer
     *
     * @return void
     * @since  3.0.0
     * @access private
     *
     */
    private function render_body(): void {
        ?>
        <div class="wp-flyout-body">
            <?php if ( $this->has_tabs() ) : ?>
                <?php $this->render_tabbed_content(); ?>
            <?php else : ?>
                <?php $this->render_single_content(); ?>
            <?php endif; ?>
        </div>

        <?php if ( $this->has_footer() ) : ?>
            <div class="wp-flyout-footer">
                <?php echo $this->footer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Render tabbed content panels
     *
     * @return void
     * @since  3.0.0
     * @access private
     *
     */
    private function render_tabbed_content(): void {
        foreach ( $this->tabs as $tab ) {
            $is_active = ( $tab['id'] === $this->active_tab );
            $classes   = [ 'wp-flyout-tab-content' ];
            if ( $is_active ) {
                $classes[] = 'active';
            }
            ?>
            <div id="tab-<?php echo esc_attr( $tab['id'] ); ?>"
                 class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
                 role="tabpanel">
                <?php echo $this->content[ $tab['id'] ] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
            <?php
        }
    }

    /**
     * Render single content panel (no tabs)
     *
     * @return void
     * @since  3.0.0
     * @access private
     *
     */
    private function render_single_content(): void {
        echo $this->content['main'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

}