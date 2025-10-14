<?php
/**
 * WP Flyout Core Class
 *
 * Handles rendering of flyout panels with tabs and content areas.
 * This is a pure UI component - all business logic should be handled
 * by the implementing plugin.
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
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
     * Flyout title displayed in header
     *
     * @since 1.0.0
     * @var string
     */
    private string $title = '';

    /**
     * Tab configuration array
     *
     * @since 1.0.0
     * @var array
     */
    private array $tabs = [];

    /**
     * Active tab identifier
     *
     * @since 1.0.0
     * @var string
     */
    private string $active_tab = '';

    /**
     * Content for each tab
     *
     * @since 1.0.0
     * @var array
     */
    private array $tab_content = [];

    /**
     * Footer content HTML
     *
     * @since 1.0.0
     * @var string
     */
    private string $footer_content = '';

    /**
     * Flyout configuration
     *
     * @since 1.0.0
     * @var array
     */
    private array $config = [
            'width'   => 'medium', // small, medium, large, full
            'classes' => [],
    ];

    /**
     * Constructor
     *
     * @param string $id     Unique identifier for this flyout.
     * @param array  $config Optional configuration array.
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
     * @param string $title Title to display in header.
     *
     * @return self
     * @since 1.0.0
     *
     */
    public function set_title( string $title ): self {
        $this->title = $title;

        return $this;
    }

    /**
     * Add a tab to the flyout
     *
     * @param string $id     Tab identifier.
     * @param string $label  Tab label.
     * @param bool   $active Whether this tab is active.
     * @param array  $args   Optional tab arguments (icon, badge, disabled).
     *
     * @return self
     * @since 1.0.0
     *
     */
    public function add_tab( string $id, string $label, bool $active = false, array $args = [] ): self {
        $this->tabs[ $id ] = array_merge(
                [
                        'id'       => $id,
                        'label'    => $label,
                        'icon'     => null,
                        'badge'    => null,
                        'disabled' => false,
                ],
                $args
        );

        if ( $active || empty( $this->active_tab ) ) {
            $this->active_tab = $id;
        }

        // Initialize content array for this tab.
        if ( ! isset( $this->tab_content[ $id ] ) ) {
            $this->tab_content[ $id ] = '';
        }

        return $this;
    }

    /**
     * Add content to a tab or main body
     *
     * @param string $tab_id  Tab identifier (empty for no tabs).
     * @param string $content HTML content to add.
     *
     * @return self
     * @since 1.0.0
     *
     */
    public function add_content( string $tab_id, string $content ): self {
        if ( empty( $tab_id ) && empty( $this->tabs ) ) {
            $tab_id = '_main';
        }

        if ( ! isset( $this->tab_content[ $tab_id ] ) ) {
            $this->tab_content[ $tab_id ] = '';
        }

        $this->tab_content[ $tab_id ] .= $content;

        return $this;
    }

    /**
     * Set footer content
     *
     * @param string $content Footer HTML content.
     *
     * @return self
     * @since 1.0.0
     *
     */
    public function set_footer( string $content ): self {
        $this->footer_content = $content;

        return $this;
    }

    /**
     * Check if flyout has tabs
     *
     * @return bool
     * @since 1.0.0
     *
     */
    private function has_tabs(): bool {
        return ! empty( $this->tabs );
    }

    /**
     * Render the complete flyout
     *
     * @return string Generated HTML.
     * @since 1.0.0
     *
     */
    public function render(): string {
        $classes = array_merge(
                [
                        'wp-flyout',
                        'wp-flyout-' . $this->config['width'],
                ],
                $this->config['classes']
        );

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->id ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             data-flyout-id="<?php echo esc_attr( $this->id ); ?>">

            <?php echo $this->render_header(); ?>

            <?php if ( $this->has_tabs() ) : ?>
                <?php echo $this->render_tabs(); ?>
            <?php endif; ?>

            <?php echo $this->render_body(); ?>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render flyout header
     *
     * @return string Generated HTML.
     * @since 1.0.0
     *
     */
    private function render_header(): string {
        ob_start();
        ?>
        <div class="wp-flyout-header">
            <h2><?php echo esc_html( $this->title ); ?></h2>
            <button type="button" class="wp-flyout-close" aria-label="<?php esc_attr_e( 'Close', 'wp-flyout' ); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render tab navigation
     *
     * @return string Generated HTML.
     * @since 1.0.0
     *
     */
    private function render_tabs(): string {
        ob_start();
        ?>
        <div class="wp-flyout-tabs">
            <nav class="wp-flyout-tab-nav" role="tablist">
                <?php foreach ( $this->tabs as $tab ) : ?>
                    <?php
                    $classes = [ 'wp-flyout-tab' ];
                    if ( $tab['id'] === $this->active_tab ) {
                        $classes[] = 'active';
                    }
                    if ( $tab['disabled'] ) {
                        $classes[] = 'disabled';
                    }
                    ?>
                    <a href="#tab-<?php echo esc_attr( $tab['id'] ); ?>"
                       class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
                       role="tab"
                       data-tab="<?php echo esc_attr( $tab['id'] ); ?>"
                       aria-selected="<?php echo $tab['id'] === $this->active_tab ? 'true' : 'false'; ?>"
                            <?php echo $tab['disabled'] ? 'aria-disabled="true"' : ''; ?>>

                        <?php if ( $tab['icon'] ) : ?>
                            <span class="dashicons dashicons-<?php echo esc_attr( $tab['icon'] ); ?>"></span>
                        <?php endif; ?>

                        <?php echo esc_html( $tab['label'] ); ?>

                        <?php if ( $tab['badge'] ) : ?>
                            <span class="wp-flyout-tab-badge"><?php echo esc_html( $tab['badge'] ); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render flyout body content
     *
     * @return string Generated HTML.
     * @since 1.0.0
     *
     */
    private function render_body(): string {
        $is_form = $this->has_form_content();

        ob_start();
        ?>
        <?php if ( $is_form ) : ?>
            <form class="wp-flyout-form">
                <div class="wp-flyout-body">
                    <?php echo $this->render_body_content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
                <?php if ( ! empty( $this->footer_content ) ) : ?>
                    <div class="wp-flyout-footer">
                        <?php echo $this->footer_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                <?php endif; ?>
            </form>
        <?php else : ?>
            <div class="wp-flyout-body">
                <?php echo $this->render_body_content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
            <?php if ( ! empty( $this->footer_content ) ) : ?>
                <div class="wp-flyout-footer">
                    <?php echo $this->footer_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the actual body content
     *
     * @return string Generated HTML.
     * @since 1.0.0
     *
     */
    private function render_body_content(): string {
        ob_start();

        if ( $this->has_tabs() ) {
            // Render tabbed content.
            foreach ( $this->tabs as $tab ) {
                $classes = [ 'wp-flyout-tab-content' ];
                if ( $tab['id'] === $this->active_tab ) {
                    $classes[] = 'active';
                }
                ?>
                <div id="tab-<?php echo esc_attr( $tab['id'] ); ?>"
                     class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
                     role="tabpanel">
                    <?php echo $this->tab_content[ $tab['id'] ] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>
                <?php
            }
        } else {
            // Render single panel content.
            echo $this->tab_content['_main'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        return ob_get_clean();
    }

    /**
     * Check if content appears to contain form elements
     *
     * @return bool
     * @since 1.0.0
     *
     */
    private function has_form_content(): bool {
        $all_content = implode( '', $this->tab_content );

        return (bool) preg_match( '/<(input|select|textarea)/i', $all_content );
    }

}