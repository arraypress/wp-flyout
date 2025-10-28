<?php
/**
 * WP Flyout Manager
 *
 * Manages flyout registration, AJAX handling, and asset management.
 * Provides a clean API for creating modal flyouts with minimal configuration.
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     5.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout;

use Exception;

/**
 * Class Manager
 *
 * Orchestrates flyout operations with automatic asset management.
 *
 * @since 1.0.0
 */
class Manager {

	/**
	 * Unique prefix for this manager instance
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $prefix;

	/**
	 * Registered flyout handlers
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $handlers = [];

	/**
	 * Admin pages where assets should load
	 *
	 * @since 2.0.0
	 * @var array
	 */
	private array $admin_pages = [];

	/**
	 * Components required across all handlers
	 *
	 * @since 2.0.0
	 * @var array
	 */
	private array $components = [];

	/**
	 * Whether assets have been enqueued
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	private bool $assets_enqueued = false;

	/**
	 * Constructor
	 *
	 * @param string $prefix Unique prefix for this manager instance
	 *
	 * @since 1.0.0
	 */
	public function __construct( string $prefix ) {
		$this->prefix = sanitize_key( $prefix );

		// Setup AJAX handlers
		add_action( 'wp_ajax_wp_flyout_' . $this->prefix, [ $this, 'handle_ajax' ] );
		add_action( 'wp_ajax_nopriv_wp_flyout_' . $this->prefix, [ $this, 'handle_ajax' ] );

		// Auto-enqueue assets on admin pages
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_assets' ] );
	}

	/**
	 * Register a flyout handler
	 *
	 * @param string $id      Unique handler ID
	 * @param array  $options Handler configuration
	 *
	 * @return self Returns instance for method chaining
	 * @since 1.0.0
	 */
	public function register( string $id, array $options ): self {
		$defaults = [
			// Display
			'title'           => '',
			'width'           => 'medium',

			// Callbacks
			'load_callback'   => null,
			'save_callback'   => null,
			'delete_callback' => null,

			// Security
			'capability'      => 'manage_options',
			'nonce_action'    => 'wp_flyout_' . $id,

			// Asset management
			'admin_pages'     => [],
			'components'      => [],
		];

		$handler = wp_parse_args( $options, $defaults );

		// Track admin pages
		if ( ! empty( $handler['admin_pages'] ) ) {
			$this->admin_pages = array_unique(
				array_merge( $this->admin_pages, $handler['admin_pages'] )
			);
		}

		// Track required components
		if ( ! empty( $handler['components'] ) ) {
			$this->components = array_unique(
				array_merge( $this->components, $handler['components'] )
			);
		}

		$this->handlers[ $id ] = $handler;

		return $this;
	}

	/**
	 * Handle AJAX requests
	 *
	 * Routes AJAX requests to appropriate handler callbacks.
	 *
	 * @return void Sends JSON response and exits
	 * @since 1.0.0
	 */
	public function handle_ajax(): void {
		// Get request parameters
		$handler_id = sanitize_key( $_POST['handler'] ?? '' );
		$action     = sanitize_key( $_POST['handler_action'] ?? 'load' );

		// Validate handler exists
		if ( ! isset( $this->handlers[ $handler_id ] ) ) {
			wp_send_json_error( 'Invalid handler', 400 );
		}

		$handler = $this->handlers[ $handler_id ];

		// Check capabilities
		if ( ! current_user_can( $handler['capability'] ) ) {
			wp_send_json_error( 'Insufficient permissions', 403 );
		}

		// Verify nonce
		if ( ! check_ajax_referer( $handler['nonce_action'], 'nonce', false ) ) {
			wp_send_json_error( 'Security check failed', 403 );
		}

		// Route to appropriate callback
		$callback_key = $action . '_callback';

		if ( ! isset( $handler[ $callback_key ] ) || ! is_callable( $handler[ $callback_key ] ) ) {
			wp_send_json_error( 'Invalid action', 400 );
		}

		// Get request data
		$data = $this->get_request_data();

		// Execute callback
		try {
			$result = call_user_func( $handler[ $callback_key ], $data );
			$this->send_response( $result );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage(), 500 );
		}
	}

	/**
	 * Send AJAX response based on result type
	 *
	 * @param mixed $result Callback result
	 *
	 * @return void Sends JSON response and exits
	 * @since 1.0.0
	 */
	private function send_response( $result ): void {
		// Handle Flyout object
		if ( $result instanceof Flyout ) {
			wp_send_json_success( [
				'html' => $result->render()
			] );
		}

		// Handle array response
		if ( is_array( $result ) ) {
			if ( isset( $result['success'] ) && ! $result['success'] ) {
				wp_send_json_error(
					$result['message'] ?? 'An error occurred',
					$result['code'] ?? 400
				);
			}
			wp_send_json_success( $result );
		}

		// Handle string (raw HTML)
		if ( is_string( $result ) ) {
			wp_send_json_success( [
				'html' => $result
			] );
		}

		// Handle boolean
		if ( is_bool( $result ) ) {
			if ( $result ) {
				wp_send_json_success( [
					'message' => 'Operation completed successfully'
				] );
			} else {
				wp_send_json_error( 'Operation failed', 400 );
			}
		}

		// Invalid response type
		wp_send_json_error( 'Invalid response from handler', 500 );
	}

	/**
	 * Extract request data from POST
	 *
	 * Merges form data and custom data attributes.
	 *
	 * @return array Sanitized request data
	 * @since 1.0.0
	 */
	private function get_request_data(): array {
		$data = [];

		// Parse form data if present
		if ( isset( $_POST['form_data'] ) ) {
			if ( is_string( $_POST['form_data'] ) ) {
				parse_str( $_POST['form_data'], $form_data );
				$data = array_merge( $data, $form_data );
			} else {
				$data = array_merge( $data, $_POST['form_data'] );
			}
		}

		// Add other POST data (excluding system keys)
		$exclude = [ 'action', 'handler', 'handler_action', 'nonce', 'form_data' ];

		foreach ( $_POST as $key => $value ) {
			if ( ! in_array( $key, $exclude, true ) ) {
				$data[ $key ] = is_string( $value )
					? sanitize_text_field( $value )
					: $value;
			}
		}

		return $data;
	}

	/**
	 * Render a trigger button
	 *
	 * @param string $handler_id Handler ID to trigger
	 * @param array  $data       Data attributes to pass
	 * @param array  $args       Button configuration
	 *
	 * @return void Outputs HTML
	 * @since 1.0.0
	 */
	public function button( string $handler_id, array $data = [], array $args = [] ): void {
		echo $this->get_button( $handler_id, $data, $args );
	}

	/**
	 * Get trigger button HTML
	 *
	 * @param string $handler_id Handler ID to trigger
	 * @param array  $data       Data attributes to pass
	 * @param array  $args       Button configuration
	 *
	 * @return string Button HTML or empty string if unauthorized
	 * @since 1.0.0
	 */
	public function get_button( string $handler_id, array $data = [], array $args = [] ): string {
		if ( ! $this->can_access( $handler_id ) ) {
			return '';
		}

		$handler = $this->handlers[ $handler_id ];

		// Parse arguments
		$text  = $args['text'] ?? __( 'Open', 'wp-flyout' );
		$class = $args['class'] ?? 'button';
		$icon  = $args['icon'] ?? '';

		// Build attributes
		$attrs = [
			'type'                => 'button',
			'class'               => 'wp-flyout-trigger ' . $class,
			'data-flyout-manager' => $this->prefix,
			'data-flyout-handler' => $handler_id,
			'data-flyout-nonce'   => wp_create_nonce( $handler['nonce_action'] ),
		];

		// Add custom data attributes
		foreach ( $data as $key => $value ) {
			$attrs[ 'data-' . $key ] = esc_attr( (string) $value );
		}

		// Build HTML
		$html = '<button';
		foreach ( $attrs as $key => $value ) {
			$html .= sprintf( ' %s="%s"', $key, $value );
		}
		$html .= '>';

		// Add icon if specified
		if ( $icon ) {
			$html .= sprintf(
				'<span class="dashicons dashicons-%s"></span> ',
				esc_attr( $icon )
			);
		}

		$html .= esc_html( $text );
		$html .= '</button>';

		return $html;
	}

	/**
	 * Create a trigger link
	 *
	 * @param string $handler_id Handler ID to trigger
	 * @param string $text       Link text
	 * @param array  $data       Data attributes to pass
	 * @param array  $args       Additional link arguments
	 *
	 * @return string Link HTML or empty string if unauthorized
	 * @since 1.0.0
	 */
	public function link( string $handler_id, string $text, array $data = [], array $args = [] ): string {
		if ( ! $this->can_access( $handler_id ) ) {
			return '';
		}

		$handler = $this->handlers[ $handler_id ];

		// Parse arguments
		$class = $args['class'] ?? '';

		// Build attributes
		$attrs = [
			'href'                => '#',
			'class'               => trim( 'wp-flyout-trigger ' . $class ),
			'data-flyout-manager' => $this->prefix,
			'data-flyout-handler' => $handler_id,
			'data-flyout-nonce'   => wp_create_nonce( $handler['nonce_action'] ),
		];

		// Add custom data attributes
		foreach ( $data as $key => $value ) {
			$attrs[ 'data-' . $key ] = esc_attr( (string) $value );
		}

		// Build HTML
		$html = '<a';
		foreach ( $attrs as $key => $value ) {
			$html .= sprintf( ' %s="%s"', $key, $value );
		}
		$html .= '>' . esc_html( $text ) . '</a>';

		return $html;
	}

	/**
	 * Check if current user can access handler
	 *
	 * @param string $handler_id Handler ID
	 *
	 * @return bool True if user has capability
	 * @since 1.0.0
	 */
	private function can_access( string $handler_id ): bool {
		if ( ! isset( $this->handlers[ $handler_id ] ) ) {
			return false;
		}

		$handler = $this->handlers[ $handler_id ];

		return current_user_can( $handler['capability'] );
	}

	/**
	 * Maybe enqueue assets based on current admin page
	 *
	 * @param string $hook_suffix Current admin page hook
	 *
	 * @return void
	 * @since 2.0.0
	 */
	public function maybe_enqueue_assets( string $hook_suffix ): void {
		// Skip if already enqueued
		if ( $this->assets_enqueued ) {
			return;
		}

		// Skip if no handlers registered
		if ( empty( $this->handlers ) ) {
			return;
		}

		// Check if we should load on this page
		if ( $this->should_enqueue( $hook_suffix ) ) {
			$this->enqueue_assets();
		}
	}

	/**
	 * Determine if assets should be enqueued
	 *
	 * @param string $hook_suffix Current admin page hook
	 *
	 * @return bool True if assets should load
	 * @since 2.0.0
	 */
	private function should_enqueue( string $hook_suffix ): bool {
		// If specific pages configured, check against them
		if ( ! empty( $this->admin_pages ) ) {
			return in_array( $hook_suffix, $this->admin_pages, true );
		}

		// Otherwise load on common admin pages
		$default_pages = [
			'index.php',
			'edit.php',
			'post.php',
			'post-new.php',
			'users.php',
			'user-edit.php',
			'profile.php',
			'options-general.php',
			'tools.php',
		];

		// Also check for custom post type pages
		if ( str_starts_with( $hook_suffix, 'page_' ) ||
		     str_starts_with( $hook_suffix, 'toplevel_page_' ) ) {
			return true;
		}

		return in_array( $hook_suffix, $default_pages, true );
	}

	/**
	 * Enqueue required assets
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function enqueue_assets(): void {
		// Enqueue core WP Flyout assets
		Assets::enqueue();

		// Enqueue required components
		foreach ( $this->components as $component ) {
			Assets::enqueue_component( strtolower( $component ) );
		}

		$this->assets_enqueued = true;
	}

	/**
	 * Get all registered handlers
	 *
	 * @return array Handler configurations
	 * @since 2.0.0
	 */
	public function get_handlers(): array {
		return $this->handlers;
	}

	/**
	 * Check if handler is registered
	 *
	 * @param string $handler_id Handler ID
	 *
	 * @return bool True if handler exists
	 * @since 2.0.0
	 */
	public function has_handler( string $handler_id ): bool {
		return isset( $this->handlers[ $handler_id ] );
	}

	/**
	 * Get manager prefix
	 *
	 * @return string Manager prefix
	 * @since 2.0.0
	 */
	public function get_prefix(): string {
		return $this->prefix;
	}
}