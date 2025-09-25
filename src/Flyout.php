<?php
/**
 * WP Flyout Library - Complete Version with Extensibility
 *
 * Modern, accessible flyout panels for WordPress admin interfaces.
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.1.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout;

use ArrayPress\WPFlyout\Traits\AssetManager;

/**
 * Class Flyout
 *
 * Main flyout container with simplified configuration, full AJAX support, and extensibility hooks.
 */
class Flyout {
    use AssetManager;

    /**
     * Unique identifier for this flyout instance
     *
     * @var string
     */
    private string $id;

    /**
     * Flyout title displayed in header
     *
     * @var string
     */
    private string $title = '';

    /**
     * Tab configuration array
     *
     * @var array
     */
    private array $tabs = [];

    /**
     * Active tab identifier
     *
     * @var string
     */
    private string $active_tab = '';

    /**
     * Content for each tab
     *
     * @var array
     */
    private array $tab_content = [];

    /**
     * Footer content
     *
     * @var string
     */
    private string $footer_content = '';

    /**
     * Flyout configuration options
     *
     * @var array
     */
    private array $config = [
            'width'            => 'large',
            'classes'          => [],
            'show_tabs'        => true,
            'close_on_save'    => true,
            'close_on_escape'  => true,
            'close_on_overlay' => true
    ];

    /**
     * AJAX configuration
     *
     * @var array
     */
    private array $ajax = [
            'prefix'     => null,
            'capability' => 'manage_options',
            'on_load'    => null,
            'on_save'    => null,
            'on_delete'  => null,
            'update_row' => null
    ];

    /**
     * Registered flyout instances
     *
     * @var array
     */
    private static array $registered = [];

    /**
     * Constructor
     *
     * @param string $id     Unique identifier for this flyout
     * @param array  $config Optional configuration array
     */
    public function __construct( string $id, array $config = [] ) {
        $this->id     = $id;
        $this->config = array_merge( $this->config, $config );

        // Register this instance
        self::$registered[ $id ] = $this;

        // Hook to enqueue scripts (only once)
        if ( ! has_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] ) ) {
            add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        }
    }

    /**
     * Configure global settings for all flyouts
     *
     * @param array $config Global configuration options
     *
     * @return void
     */
    public static function configure( array $config ): void {
        self::set_global_config( $config );
    }

    /**
     * Enqueue assets and localize script with flyout configurations
     *
     * @return void
     */
    public static function enqueue_assets(): void {
        // Only enqueue on admin pages
        if ( ! is_admin() ) {
            return;
        }

        // Enqueue core assets (this also handles localization)
        self::enqueue_core_assets();
    }

    /**
     * Get all registered flyout instances
     *
     * @return array
     */
    public static function get_registered(): array {
        return self::$registered;
    }

    /**
     * Get AJAX configuration for this flyout
     *
     * @return array
     */
    public function get_ajax_config(): array {
        return $this->ajax;
    }

    /**
     * Set flyout title
     *
     * @param string $title Title to display in header
     *
     * @return self
     */
    public function set_title( string $title ): self {
        $this->title = $title;

        return $this;
    }

    /**
     * Get sanitized ID for use in hook names
     * Replaces hyphens with underscores for valid WordPress hook names
     *
     * @return string
     */
    private function get_hook_id(): string {
        return str_replace( '-', '_', $this->id );
    }

    /**
     * Verify AJAX request security
     * Checks nonce and user capabilities
     *
     * @param string $nonce_action Nonce action to verify
     *
     * @return bool|void Returns true if valid, sends JSON error and exits if not
     */
    private function verify_ajax_request( string $nonce_action ): bool {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', $nonce_action ) ) {
            wp_send_json_error( 'Invalid security token' );
            exit;
        }

        // Check capabilities
        if ( ! current_user_can( $this->ajax['capability'] ?? 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
            exit;
        }

        return true;
    }

    /**
     * Setup AJAX handlers with simplified configuration
     *
     * @param string $prefix    Action prefix (e.g., 'product' generates 'product_load', 'product_save', etc.)
     * @param array  $callbacks Array with 'load', 'save', 'delete', and optional 'update_row' callbacks
     *
     * @return self
     */
    public function setup_ajax( string $prefix, array $callbacks ): self {
        $this->ajax['prefix'] = $prefix;

        // Set callbacks
        $this->ajax['on_load']    = $callbacks['load'] ?? null;
        $this->ajax['on_save']    = $callbacks['save'] ?? null;
        $this->ajax['on_delete']  = $callbacks['delete'] ?? null;
        $this->ajax['update_row'] = $callbacks['update_row'] ?? null;

        // Use closures to preserve context
        $flyout       = $this;
        $nonce_action = $prefix . '_nonce';
        $hook_id      = $this->get_hook_id();

        // Register WordPress AJAX actions with closures
        if ( $this->ajax['on_load'] ) {
            add_action( 'wp_ajax_' . $prefix . '_load', function () use ( $flyout, $nonce_action, $hook_id ) {
                // Verify request security
                $flyout->verify_ajax_request( $nonce_action );

                // Clear any previous content
                $flyout->clear_content();

                // Call the load callback
                if ( $flyout->ajax['on_load'] ) {
                    call_user_func( $flyout->ajax['on_load'], $flyout, $_POST );
                }

                /**
                 * After flyout content has been loaded
                 * Allows extensions to modify tabs and content
                 *
                 * @param Flyout $flyout  The flyout instance
                 * @param array  $request Request data
                 */
                do_action( 'wp_flyout_after_load', $flyout, $_POST );
                do_action( "wp_flyout_after_load_{$hook_id}", $flyout, $_POST );

                // Send response
                wp_send_json_success( [
                        'html'   => $flyout->render(),
                        'config' => $flyout->get_client_config()
                ] );
            } );
        }

        if ( $this->ajax['on_save'] ) {
            add_action( 'wp_ajax_' . $prefix . '_save', function () use ( $flyout, $nonce_action, $hook_id ) {
                // Verify request security
                $flyout->verify_ajax_request( $nonce_action );

                /**
                 * Filter save data before processing
                 *
                 * @param array  $data   Data to save
                 * @param Flyout $flyout The flyout instance
                 */
                $data = apply_filters( 'wp_flyout_before_save', $_POST, $flyout );
                $data = apply_filters( "wp_flyout_before_save_{$hook_id}", $data, $flyout );

                // Call save callback
                if ( $flyout->ajax['on_save'] ) {
                    $result = call_user_func( $flyout->ajax['on_save'], $data );

                    if ( is_wp_error( $result ) ) {
                        wp_send_json_error( $result->get_error_message() );
                    }

                    /**
                     * After successful save
                     *
                     * @param mixed  $result The save result (usually ID)
                     * @param array  $data   Data that was saved
                     * @param Flyout $flyout The flyout instance
                     */
                    do_action( 'wp_flyout_after_save', $result, $data, $flyout );
                    do_action( "wp_flyout_after_save_{$hook_id}", $result, $data, $flyout );

                    $response = [
                            'id'      => $result,
                            'message' => 'Saved successfully'
                    ];

                    // Generate updated row HTML if callback provided
                    if ( $flyout->ajax['update_row'] ) {
                        $response['row_html'] = call_user_func( $flyout->ajax['update_row'], $result );
                    }

                    wp_send_json_success( $response );
                }

                wp_send_json_error( 'Save handler not configured' );
            } );
        }

        if ( $this->ajax['on_delete'] ) {
            add_action( 'wp_ajax_' . $prefix . '_delete', function () use ( $flyout, $nonce_action, $hook_id ) {
                // Verify request security
                $flyout->verify_ajax_request( $nonce_action );

                // Call delete handler if configured
                if ( $flyout->ajax['on_delete'] ) {
                    $id = intval( $_POST['id'] ?? 0 );
                    if ( ! $id ) {
                        wp_send_json_error( 'Invalid ID' );
                    }

                    /**
                     * Before delete processing
                     *
                     * @param int    $id     Item ID to delete
                     * @param Flyout $flyout The flyout instance
                     */
                    do_action( 'wp_flyout_before_delete', $id, $flyout );
                    do_action( "wp_flyout_before_delete_{$hook_id}", $id, $flyout );

                    $result = call_user_func( $flyout->ajax['on_delete'], $id );

                    if ( is_wp_error( $result ) ) {
                        wp_send_json_error( $result->get_error_message() );
                    }

                    /**
                     * After successful delete
                     *
                     * @param int    $id     Deleted item ID
                     * @param Flyout $flyout The flyout instance
                     */
                    do_action( 'wp_flyout_after_delete', $id, $flyout );
                    do_action( "wp_flyout_after_delete_{$hook_id}", $id, $flyout );

                    wp_send_json_success( [
                            'id'      => $id,
                            'message' => 'Deleted successfully'
                    ] );
                }

                wp_send_json_error( 'Delete handler not configured' );
            } );
        }

        return $this;
    }

    /**
     * Get client-side configuration
     *
     * @return array Configuration for JavaScript
     */
    private function get_client_config(): array {
        return [
                'id'   => $this->id,
                'ajax' => [
                        'load_action'   => $this->ajax['prefix'] . '_load',
                        'save_action'   => $this->ajax['prefix'] . '_save',
                        'delete_action' => $this->ajax['prefix'] . '_delete',
                        'nonce'         => wp_create_nonce( $this->ajax['prefix'] . '_nonce' )
                ],
                'ui'   => [
                        'close_on_save'    => $this->config['close_on_save'],
                        'close_on_escape'  => $this->config['close_on_escape'],
                        'close_on_overlay' => $this->config['close_on_overlay']
                ]
        ];
    }

    /**
     * Add a tab to the flyout
     *
     * @param string $id     Tab identifier
     * @param string $label  Tab label
     * @param bool   $active Whether this tab is active
     * @param array  $config Optional tab configuration
     *
     * @return self
     */
    public function add_tab( string $id, string $label, bool $active = false, array $config = [] ): self {
        $this->tabs[ $id ] = array_merge( [
                'id'       => $id,
                'label'    => $label,
                'icon'     => null,
                'badge'    => null,
                'disabled' => false
        ], $config );

        if ( $active || empty( $this->active_tab ) ) {
            $this->active_tab = $id;
        }

        // Initialize content array for this tab
        if ( ! isset( $this->tab_content[ $id ] ) ) {
            $this->tab_content[ $id ] = [];
        }

        $hook_id = $this->get_hook_id();

        /**
         * Tab has been added to flyout
         *
         * @param string $tab_id Tab ID
         * @param array  $tab    Tab configuration
         * @param Flyout $flyout The flyout instance
         */
        do_action( 'wp_flyout_tab_added', $id, $this->tabs[ $id ], $this );
        do_action( "wp_flyout_tab_added_{$hook_id}", $id, $this->tabs[ $id ], $this );

        return $this;
    }

    /**
     * Add content to a tab or main body
     *
     * @param string $tab_id  Tab identifier (empty for no tabs)
     * @param mixed  $content Content to add
     *
     * @return self
     */
    public function add_content( string $tab_id, $content ): self {
        // If no tabs, use default key
        if ( empty( $tab_id ) && empty( $this->tabs ) ) {
            $tab_id = '_default';
        }

        if ( ! isset( $this->tab_content[ $tab_id ] ) ) {
            $this->tab_content[ $tab_id ] = [];
        }

        $this->tab_content[ $tab_id ][] = $content;

        return $this;
    }

    /**
     * Clear all content
     *
     * @return self
     */
    public function clear_content(): self {
        $this->tabs           = [];
        $this->tab_content    = [];
        $this->active_tab     = '';
        $this->footer_content = '';

        return $this;
    }

    /**
     * Set footer content
     *
     * @param string $content Footer HTML content
     *
     * @return self
     */
    public function set_footer( string $content ): self {
        $this->footer_content = $content;

        return $this;
    }

    /**
     * Check if flyout has tabs
     *
     * @return bool
     */
    private function has_tabs(): bool {
        return ! empty( $this->tabs ) && $this->config['show_tabs'];
    }

    /**
     * Check if content appears to be a form
     *
     * @return bool
     */
    private function is_form(): bool {
        foreach ( $this->tab_content as $content_array ) {
            foreach ( $content_array as $item ) {
                if ( is_string( $item ) && preg_match( '/<(input|select|textarea|form)/i', $item ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Render the complete flyout
     *
     * @return string Generated HTML
     */
    public function render(): string {
        $hook_id = $this->get_hook_id();

        /**
         * Before flyout renders
         *
         * @param Flyout $flyout The flyout instance
         */
        do_action( 'wp_flyout_before_render', $this );
        do_action( "wp_flyout_before_render_{$hook_id}", $this );

        // Generate HTML
        $html = $this->generate_html();

        /**
         * Filter the generated HTML
         *
         * @param string $html   Generated HTML
         * @param Flyout $flyout The flyout instance
         */
        $html = apply_filters( 'wp_flyout_html', $html, $this );
        $html = apply_filters( "wp_flyout_html_{$hook_id}", $html, $this );

        return $html;
    }

    /**
     * Generate the flyout HTML
     *
     * @return string Generated HTML
     */
    private function generate_html(): string {
        $classes = array_merge( [
                'wp-flyout',
                'wp-flyout-' . $this->config['width']
        ], $this->config['classes'] );

        // Add contextual classes
        if ( $this->is_form() ) {
            $classes[] = 'wp-flyout-editable';
        } else {
            $classes[] = 'wp-flyout-readonly';
        }

        // Encode configuration for JavaScript
        $js_config   = $this->get_client_config();
        $config_json = htmlspecialchars( json_encode( $js_config ), ENT_QUOTES, 'UTF-8' );

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->id ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             data-flyout-id="<?php echo esc_attr( $this->id ); ?>"
             data-flyout-config="<?php echo $config_json; ?>">

            <?php echo $this->render_header(); ?>

            <?php if ( $this->has_tabs() ): ?>
                <?php echo $this->render_tabs(); ?>
            <?php endif; ?>

            <?php if ( $this->is_form() ): ?>
                <form class="wp-flyout-form">
                    <div class="wp-flyout-body">
                        <?php echo $this->render_body(); ?>
                    </div>

                    <?php if ( ! empty( $this->footer_content ) ): ?>
                        <div class="wp-flyout-footer">
                            <?php echo $this->footer_content; ?>
                        </div>
                    <?php endif; ?>
                </form>
            <?php else: ?>
                <div class="wp-flyout-body">
                    <?php echo $this->render_body(); ?>
                </div>

                <?php if ( ! empty( $this->footer_content ) ): ?>
                    <div class="wp-flyout-footer">
                        <?php echo $this->footer_content; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render flyout header
     *
     * @return string Generated HTML
     */
    private function render_header(): string {
        ob_start();
        ?>
        <div class="wp-flyout-header">
            <h2><?php echo esc_html( $this->title ); ?></h2>
            <button type="button" class="wp-flyout-close" aria-label="Close">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render tab navigation
     *
     * @return string Generated HTML
     */
    private function render_tabs(): string {
        $hook_id = $this->get_hook_id();

        /**
         * Filter tabs before rendering
         *
         * @param array  $tabs   Tabs array
         * @param Flyout $flyout The flyout instance
         */
        $this->tabs = apply_filters( 'wp_flyout_tabs', $this->tabs, $this );
        $this->tabs = apply_filters( "wp_flyout_tabs_{$hook_id}", $this->tabs, $this );

        ob_start();
        ?>
        <div class="wp-flyout-tabs">
            <nav class="wp-flyout-tab-nav" role="tablist">
                <?php foreach ( $this->tabs as $tab ): ?>
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

                        <?php if ( $tab['icon'] ): ?>
                            <span class="dashicons dashicons-<?php echo esc_attr( $tab['icon'] ); ?>"></span>
                        <?php endif; ?>

                        <?php echo esc_html( $tab['label'] ); ?>

                        <?php if ( $tab['badge'] ): ?>
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
     * @return string Generated HTML
     */
    private function render_body(): string {
        ob_start();

        if ( $this->has_tabs() ) {
            // Render tabbed content
            foreach ( $this->tabs as $tab ) {
                $classes = [ 'wp-flyout-tab-content' ];
                if ( $tab['id'] === $this->active_tab ) {
                    $classes[] = 'active';
                }
                ?>
                <div id="tab-<?php echo esc_attr( $tab['id'] ); ?>"
                     class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
                     role="tabpanel">
                    <?php echo $this->render_tab_content( $tab['id'] ); ?>
                </div>
                <?php
            }
        } else {
            // Render single panel content
            echo $this->render_all_content();
        }

        return ob_get_clean();
    }

    /**
     * Render content for a specific tab
     *
     * @param string $tab_id Tab identifier
     *
     * @return string Generated HTML
     */
    private function render_tab_content( string $tab_id ): string {
        $hook_id     = $this->get_hook_id();
        $tab_hook_id = str_replace( '-', '_', $tab_id );

        /**
         * Before rendering tab content
         *
         * @param string $tab_id Tab ID
         * @param Flyout $flyout The flyout instance
         */
        do_action( 'wp_flyout_before_tab_content', $tab_id, $this );
        do_action( "wp_flyout_before_tab_content_{$hook_id}", $tab_id, $this );
        do_action( "wp_flyout_before_tab_content_{$hook_id}_{$tab_hook_id}", $this );

        $content = $this->tab_content[ $tab_id ] ?? [];

        if ( empty( $content ) ) {
            // Return empty state
            $empty = new Components\EmptyState( 'No Content', [
                    'description' => 'This section is currently empty.',
                    'icon'        => 'admin-page'
            ] );

            return $empty->render();
        }

        ob_start();
        foreach ( $content as $item ) {
            echo $this->render_content_item( $item );
        }
        $html = ob_get_clean();

        /**
         * Filter tab content HTML
         *
         * @param string $html   Tab content HTML
         * @param string $tab_id Tab ID
         * @param Flyout $flyout The flyout instance
         */
        $html = apply_filters( 'wp_flyout_tab_content', $html, $tab_id, $this );
        $html = apply_filters( "wp_flyout_tab_content_{$hook_id}", $html, $tab_id, $this );
        $html = apply_filters( "wp_flyout_tab_content_{$hook_id}_{$tab_hook_id}", $html, $this );

        return $html;
    }

    /**
     * Render all content (when no tabs)
     *
     * @return string Generated HTML
     */
    private function render_all_content(): string {
        ob_start();

        // Check for default content first
        if ( isset( $this->tab_content['_default'] ) ) {
            foreach ( $this->tab_content['_default'] as $item ) {
                echo $this->render_content_item( $item );
            }
        } else {
            // Render all tab content
            foreach ( $this->tab_content as $content_array ) {
                foreach ( $content_array as $item ) {
                    echo $this->render_content_item( $item );
                }
            }
        }

        return ob_get_clean();
    }

    /**
     * Render a single content item
     *
     * @param mixed $item Content item to render
     *
     * @return string Generated HTML
     */
    private function render_content_item( $item ): string {
        if ( is_string( $item ) ) {
            return $item;
        }

        if ( is_object( $item ) && method_exists( $item, 'render' ) ) {
            return $item->render();
        }

        if ( is_callable( $item ) ) {
            ob_start();
            call_user_func( $item );

            return ob_get_clean();
        }

        return '';
    }

    /**
     * Get registered flyout instance
     *
     * @param string $id Flyout ID
     *
     * @return self|null
     */
    public static function get_instance( string $id ): ?self {
        return self::$registered[ $id ] ?? null;
    }

    /**
     * Get the flyout ID
     *
     * @return string
     */
    public function get_id(): string {
        return $this->id;
    }

    /**
     * Get tabs array
     *
     * @return array
     */
    public function get_tabs(): array {
        return $this->tabs;
    }

}