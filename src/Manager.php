<?php
/**
 * WP Flyout Manager
 *
 * Orchestrates flyout operations with zero JavaScript required from developers.
 * Handles AJAX loading, saving, and component management.
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout;

/**
 * Class Manager
 *
 * Manages flyout registration and AJAX handling.
 *
 * @since 1.0.0
 */
class Manager {

	/**
	 * Unique prefix for this manager instance
	 *
	 * @var string
	 */
	private string $prefix;

	/**
	 * Registered flyout handlers
	 *
	 * @var array
	 */
	private array $handlers = [];

	/**
	 * Whether assets have been enqueued
	 *
	 * @var bool
	 */
	private bool $assets_enqueued = false;

	/**
	 * Admin pages where assets should load
	 *
	 * @var array
	 */
	private array $admin_pages = [];

	/**
	 * Required components across all handlers
	 *
	 * @var array
	 */
	private array $required_components = [];

	/**
	 * Constructor
	 *
	 * @param string $prefix Unique prefix for this manager
	 */
	public function __construct( string $prefix ) {
		$this->prefix = sanitize_key( $prefix );

		// Setup AJAX handlers
		add_action( 'wp_ajax_wp_flyout_' . $this->prefix, [ $this, 'handle_ajax' ] );
		add_action( 'wp_ajax_nopriv_wp_flyout_' . $this->prefix, [ $this, 'handle_ajax' ] );

		// Auto-enqueue assets when needed
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_assets' ] );
	}

	/**
	 * Register a flyout handler
	 *
	 * @param string $id      Unique handler ID
	 * @param array  $options Handler configuration
	 *
	 * @return self
	 */
	public function register( string $id, array $options ): self {
		$defaults = [
			// Display
			'title'           => '',
			'width'           => 'medium',
			'show_footer'     => true,

			// Callbacks
			'load_callback'   => null,
			'save_callback'   => null,
			'delete_callback' => null,
			'custom_actions'  => [],

			// Legacy callback names (backward compatibility)
			'ajax_load'       => null,
			'ajax_save'       => null,
			'ajax_delete'     => null,
			'ajax_custom'     => [],

			// Behavior
			'capability'      => 'manage_options',
			'nonce_action'    => 'wp_flyout_' . $id,
			'auto_close'      => false,
			'refresh'         => false,
			'confirm_close'   => false,
			'track_dirty'     => true,

			// Messages
			'success_message' => __( 'Changes saved successfully!', 'wp-flyout' ),
			'error_message'   => __( 'An error occurred. Please try again.', 'wp-flyout' ),
			'confirm_message' => __( 'Are you sure? Changes will be lost.', 'wp-flyout' ),

			// Asset management
			'admin_pages'     => [], // Which admin pages to load assets on
			'components'      => [], // Which components this handler uses

			// Data
			'defaults'        => [],
		];

		$options = wp_parse_args( $options, $defaults );

		// Support legacy callback names
		if ( $options['ajax_load'] && ! $options['load_callback'] ) {
			$options['load_callback'] = $options['ajax_load'];
		}
		if ( $options['ajax_save'] && ! $options['save_callback'] ) {
			$options['save_callback'] = $options['ajax_save'];
		}
		if ( $options['ajax_delete'] && ! $options['delete_callback'] ) {
			$options['delete_callback'] = $options['ajax_delete'];
		}
		if ( $options['ajax_custom'] && empty( $options['custom_actions'] ) ) {
			$options['custom_actions'] = $options['ajax_custom'];
		}

		// Track admin pages where this handler needs assets
		if ( ! empty( $options['admin_pages'] ) ) {
			$this->admin_pages = array_unique( array_merge( $this->admin_pages, $options['admin_pages'] ) );
		}

		// Track required components
		if ( ! empty( $options['components'] ) ) {
			$this->required_components = array_unique( array_merge( $this->required_components, $options['components'] ) );
		}

		$this->handlers[ $id ] = $options;

		return $this;
	}

	/**
	 * Handle AJAX requests
	 *
	 * @return void
	 */
	public function handle_ajax(): void {
		// Get handler ID and action
		$handler_id = sanitize_key( $_POST['handler'] ?? '' );
		$action     = sanitize_key( $_POST['handler_action'] ?? 'load' );

		// Validate handler exists
		if ( ! isset( $this->handlers[ $handler_id ] ) ) {
			wp_send_json_error( __( 'Invalid handler', 'wp-flyout' ), 400 );
		}

		$handler = $this->handlers[ $handler_id ];

		// Check capabilities
		if ( ! current_user_can( $handler['capability'] ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'wp-flyout' ), 403 );
		}

		// Verify nonce
		if ( ! check_ajax_referer( $handler['nonce_action'], 'nonce', false ) ) {
			wp_send_json_error( __( 'Security check failed', 'wp-flyout' ), 403 );
		}

		// Route to appropriate handler
		switch ( $action ) {
			case 'load':
				$this->handle_load( $handler_id, $handler );
				break;

			case 'save':
				$this->handle_save( $handler_id, $handler );
				break;

			case 'delete':
				$this->handle_delete( $handler_id, $handler );
				break;

			default:
				// Check for custom action
				if ( isset( $handler['custom_actions'][ $action ] ) ) {
					$this->handle_custom( $handler_id, $handler, $action );
				} else {
					wp_send_json_error( __( 'Invalid action', 'wp-flyout' ), 400 );
				}
		}
	}

	/**
	 * Handle load action
	 *
	 * @param string $handler_id Handler ID
	 * @param array  $handler    Handler configuration
	 *
	 * @return void
	 */
	private function handle_load( string $handler_id, array $handler ): void {
		if ( ! is_callable( $handler['load_callback'] ) ) {
			wp_send_json_error( __( 'Load handler not available', 'wp-flyout' ), 500 );
		}

		// Get all data attributes
		$data = $this->get_request_data();

		// Merge with defaults
		$options = array_merge( $handler['defaults'], $data );

		// Call the load callback
		$result = call_user_func( $handler['load_callback'], $options );

		// Handle different return types
		if ( is_string( $result ) ) {
			// Simple HTML string
			wp_send_json_success( [
				'html'       => $result,
				'title'      => $handler['title'],
				'width'      => $handler['width'],
				'showFooter' => $handler['show_footer'],
			] );
		} elseif ( is_array( $result ) ) {
			// Check for error response
			if ( isset( $result['success'] ) && $result['success'] === false ) {
				wp_send_json_error( $result['message'] ?? $handler['error_message'], $result['code'] ?? 400 );

				return;
			}

			// Structured response
			$response = wp_parse_args( $result, [
				'html'       => '',
				'title'      => $handler['title'],
				'width'      => $handler['width'],
				'showFooter' => $handler['show_footer'],
			] );
			wp_send_json_success( $response );
		} elseif ( $result instanceof Flyout ) {
			// Flyout object - just render it
			// Note: Flyout class doesn't have getter methods, so we use handler config
			wp_send_json_success( [
				'html'       => $result->render(),
				'title'      => $handler['title'],
				'width'      => $handler['width'],
				'showFooter' => $handler['show_footer'],
			] );
		} else {
			wp_send_json_error( __( 'Invalid response from load handler', 'wp-flyout' ), 500 );
		}
	}

	/**
	 * Handle save action
	 *
	 * @param string $handler_id Handler ID
	 * @param array  $handler    Handler configuration
	 *
	 * @return void
	 */
	private function handle_save( string $handler_id, array $handler ): void {
		if ( ! is_callable( $handler['save_callback'] ) ) {
			wp_send_json_error( __( 'Save handler not available', 'wp-flyout' ), 500 );
		}

		// Get form data
		$form_data = $_POST['form_data'] ?? [];

		// Parse if needed (from serialized form)
		if ( is_string( $form_data ) ) {
			parse_str( $form_data, $form_data );
		}

		// Get additional data
		$data = $this->get_request_data();

		// Merge all data
		$options = array_merge( $handler['defaults'], $data, $form_data );

		// Call the save callback
		$result = call_user_func( $handler['save_callback'], $options );

		// Handle response
		if ( is_bool( $result ) ) {
			if ( $result ) {
				wp_send_json_success( [
					'message' => $handler['success_message'],
				] );
			} else {
				wp_send_json_error( $handler['error_message'], 400 );
			}
		} elseif ( is_array( $result ) ) {
			if ( ! empty( $result['success'] ) ) {
				wp_send_json_success( [
					'message' => $result['message'] ?? $handler['success_message'],
					'data'    => $result['data'] ?? null,
					'reload'  => $result['reload'] ?? false,
				] );
			} else {
				wp_send_json_error(
					$result['message'] ?? $handler['error_message'],
					$result['code'] ?? 400
				);
			}
		} else {
			wp_send_json_success( [
				'message' => $handler['success_message'],
			] );
		}
	}

	/**
	 * Handle delete action
	 *
	 * @param string $handler_id Handler ID
	 * @param array  $handler    Handler configuration
	 *
	 * @return void
	 */
	private function handle_delete( string $handler_id, array $handler ): void {
		if ( ! is_callable( $handler['delete_callback'] ) ) {
			wp_send_json_error( __( 'Delete handler not available', 'wp-flyout' ), 500 );
		}

		// Get data
		$data    = $this->get_request_data();
		$options = array_merge( $handler['defaults'], $data );

		// Call the delete callback
		$result = call_user_func( $handler['delete_callback'], $options );

		// Handle response
		if ( is_bool( $result ) ) {
			if ( $result ) {
				wp_send_json_success( [
					'message' => __( 'Item deleted successfully!', 'wp-flyout' ),
				] );
			} else {
				wp_send_json_error( __( 'Failed to delete item', 'wp-flyout' ), 400 );
			}
		} elseif ( is_array( $result ) ) {
			if ( ! empty( $result['success'] ) ) {
				wp_send_json_success( $result );
			} else {
				wp_send_json_error(
					$result['message'] ?? __( 'Failed to delete item', 'wp-flyout' ),
					$result['code'] ?? 400
				);
			}
		}
	}

	/**
	 * Handle custom action
	 *
	 * @param string $handler_id Handler ID
	 * @param array  $handler    Handler configuration
	 * @param string $action     Action name
	 *
	 * @return void
	 */
	private function handle_custom( string $handler_id, array $handler, string $action ): void {
		$callback = $handler['custom_actions'][ $action ] ?? null;

		if ( ! is_callable( $callback ) ) {
			wp_send_json_error( __( 'Custom handler not available', 'wp-flyout' ), 500 );
		}

		// Get all data
		$data      = $this->get_request_data();
		$form_data = $_POST['form_data'] ?? [];

		if ( is_string( $form_data ) ) {
			parse_str( $form_data, $form_data );
		}

		$options = array_merge( $handler['defaults'], $data, $form_data );

		// Call the custom callback
		$result = call_user_func( $callback, $options, $action );

		// Send result
		if ( is_array( $result ) ) {
			if ( isset( $result['success'] ) && ! $result['success'] ) {
				wp_send_json_error( $result['message'] ?? '', $result['code'] ?? 400 );
			} else {
				wp_send_json_success( $result );
			}
		} else {
			wp_send_json_success( [ 'result' => $result ] );
		}
	}

	/**
	 * Get request data attributes
	 *
	 * @return array
	 */
	private function get_request_data(): array {
		$data = [];

		// Extract all data-* attributes
		foreach ( $_POST as $key => $value ) {
			if ( strpos( $key, 'data_' ) === 0 ) {
				$clean_key          = substr( $key, 5 ); // Remove 'data_' prefix
				$data[ $clean_key ] = sanitize_text_field( $value );
			}
		}

		return $data;
	}

	/**
	 * Render a trigger button
	 *
	 * @param string $handler_id Handler ID to trigger
	 * @param array  $data       Data attributes to pass
	 * @param array  $args       Button arguments
	 *
	 * @return void
	 */
	public function button( string $handler_id, array $data = [], array $args = [] ): void {
		echo $this->get_button( $handler_id, $data, $args );
	}

	/**
	 * Get a trigger button HTML
	 *
	 * @param string $handler_id Handler ID to trigger
	 * @param array  $data       Data attributes to pass
	 * @param array  $args       Button arguments
	 *
	 * @return string
	 */
	public function get_button( string $handler_id, array $data = [], array $args = [] ): string {
		if ( ! isset( $this->handlers[ $handler_id ] ) ) {
			return '';
		}

		$handler = $this->handlers[ $handler_id ];

		// Check capability
		if ( ! current_user_can( $handler['capability'] ) ) {
			return '';
		}

		// Parse arguments
		$defaults = [
			'text'  => __( 'Open', 'wp-flyout' ),
			'class' => 'button',
			'icon'  => '',
		];
		$args     = wp_parse_args( $args, $defaults );

		// Build attributes
		$attributes = [
			'type'                => 'button',
			'class'               => 'wp-flyout-trigger ' . $args['class'],
			'data-flyout-manager' => $this->prefix,
			'data-flyout-handler' => $handler_id,
			'data-flyout-nonce'   => wp_create_nonce( $handler['nonce_action'] ),
		];

		// Add data attributes
		foreach ( $data as $key => $value ) {
			$attributes[ 'data-' . $key ] = esc_attr( $value );
		}

		// Build HTML
		$html = '<button';
		foreach ( $attributes as $attr => $value ) {
			$html .= sprintf( ' %s="%s"', $attr, $value );
		}
		$html .= '>';

		// Add icon if specified
		if ( $args['icon'] ) {
			$html .= sprintf( '<span class="dashicons dashicons-%s"></span> ', esc_attr( $args['icon'] ) );
		}

		$html .= esc_html( $args['text'] );
		$html .= '</button>';

		return $html;
	}

	/**
	 * Create a trigger link
	 *
	 * @param string $handler_id Handler ID
	 * @param string $text       Link text
	 * @param array  $data       Data attributes
	 * @param array  $args       Additional arguments
	 *
	 * @return string
	 */
	public function link( string $handler_id, string $text, array $data = [], array $args = [] ): string {
		if ( ! isset( $this->handlers[ $handler_id ] ) ) {
			return '';
		}

		$handler = $this->handlers[ $handler_id ];

		// Check capability
		if ( ! current_user_can( $handler['capability'] ) ) {
			return '';
		}

		$defaults = [
			'class' => '',
		];
		$args     = wp_parse_args( $args, $defaults );

		// Build attributes
		$attributes = [
			'href'                => '#',
			'class'               => 'wp-flyout-trigger ' . $args['class'],
			'data-flyout-manager' => $this->prefix,
			'data-flyout-handler' => $handler_id,
			'data-flyout-nonce'   => wp_create_nonce( $handler['nonce_action'] ),
		];

		// Add data attributes
		foreach ( $data as $key => $value ) {
			$attributes[ 'data-' . $key ] = esc_attr( $value );
		}

		// Build HTML
		return sprintf(
			'<a %s>%s</a>',
			$this->build_attributes( $attributes ),
			esc_html( $text )
		);
	}

	/**
	 * Build HTML attributes string
	 *
	 * @param array $attributes Key-value pairs
	 *
	 * @return string
	 */
	private function build_attributes( array $attributes ): string {
		$html = [];
		foreach ( $attributes as $key => $value ) {
			$html[] = sprintf( '%s="%s"', $key, esc_attr( $value ) );
		}

		return implode( ' ', $html );
	}

	/**
	 * Maybe enqueue assets based on current admin page
	 *
	 * @param string $hook Current admin page hook
	 *
	 * @return void
	 */
	public function maybe_enqueue_assets( string $hook ): void {
		// Check if we should load on this page
		$should_load = false;

		// If no specific pages configured, check if we have handlers
		if ( empty( $this->admin_pages ) && ! empty( $this->handlers ) ) {
			// Load on common admin pages by default
			$default_pages = [
				'edit.php',
				'post.php',
				'post-new.php',
				'users.php',
				'user-edit.php',
				'options-general.php',
			];

			if ( in_array( $hook, $default_pages, true ) ) {
				$should_load = true;
			}
		} else {
			// Check if current page is in our admin_pages list
			if ( in_array( $hook, $this->admin_pages, true ) ) {
				$should_load = true;
			}
		}

		// Also check if any handler specifically requests this page
		foreach ( $this->handlers as $handler ) {
			if ( ! empty( $handler['admin_pages'] ) && in_array( $hook, $handler['admin_pages'], true ) ) {
				$should_load = true;
				break;
			}
		}

		if ( $should_load ) {
			$this->enqueue_assets();
		}
	}

	/**
	 * Enqueue assets
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		// Only enqueue once
		if ( $this->assets_enqueued ) {
			return;
		}

		// Only enqueue if we have handlers
		if ( empty( $this->handlers ) ) {
			return;
		}

		// Initialize and enqueue core WP Flyout assets
		if ( class_exists( 'ArrayPress\WPFlyout\Assets' ) ) {
			Assets::init();
			Assets::enqueue();

			// Enqueue specific components if needed
			if ( ! empty( $this->required_components ) ) {
				foreach ( $this->required_components as $component ) {
					Assets::enqueue_component( strtolower( $component ) );
				}
			}
		}

		// Localize script with this manager's configuration
		wp_localize_script(
			'wp-flyout-manager', // Core manager script handle
			'wpFlyoutManager_' . str_replace( '-', '_', $this->prefix ),
			[
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'prefix'   => $this->prefix,
				'handlers' => $this->get_handler_configs(),
				'strings'  => $this->get_strings(),
			]
		);

		$this->assets_enqueued = true;
	}

	/**
	 * Get handler configurations for JavaScript
	 *
	 * @return array
	 */
	private function get_handler_configs(): array {
		$configs = [];

		foreach ( $this->handlers as $id => $handler ) {
			$configs[ $id ] = [
				'autoClose'    => $handler['auto_close'],
				'refresh'      => $handler['refresh'],
				'confirmClose' => $handler['confirm_close'],
				'trackDirty'   => $handler['track_dirty'],
				'showFooter'   => $handler['show_footer'],
			];
		}

		return $configs;
	}

	/**
	 * Get localized strings
	 *
	 * @return array
	 */
	private function get_strings(): array {
		return [
			'loading'      => __( 'Loading...', 'wp-flyout' ),
			'saving'       => __( 'Saving...', 'wp-flyout' ),
			'deleting'     => __( 'Deleting...', 'wp-flyout' ),
			'error'        => __( 'An error occurred', 'wp-flyout' ),
			'confirmClose' => __( 'Are you sure you want to close? Unsaved changes will be lost.', 'wp-flyout' ),
		];
	}
}