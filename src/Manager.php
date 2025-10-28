<?php
/**
 * WP Flyout Manager - Clean Version
 *
 * Handles flyout AJAX operations with minimal complexity.
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     3.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout;

/**
 * Class Manager
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
	 * Constructor
	 *
	 * @param string $prefix Unique prefix for this manager
	 */
	public function __construct( string $prefix ) {
		$this->prefix = sanitize_key( $prefix );

		// Setup AJAX handlers
		add_action( 'wp_ajax_wp_flyout_' . $this->prefix, [ $this, 'handle_ajax' ] );
		add_action( 'wp_ajax_nopriv_wp_flyout_' . $this->prefix, [ $this, 'handle_ajax' ] );

		// Auto-enqueue assets
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
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

			// Callbacks (required)
			'load_callback'   => null,
			'save_callback'   => null,
			'delete_callback' => null,
			'custom_actions'  => [],

			// Security
			'capability'      => 'manage_options',
			'nonce_action'    => 'wp_flyout_' . $id,

			// Behavior
			'auto_close'      => false,
			'refresh'         => false,

			// Messages
			'success_message' => 'Saved successfully!',
			'error_message'   => 'An error occurred.',
		];

		$this->handlers[ $id ] = wp_parse_args( $options, $defaults );

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
					wp_send_json_error( 'Invalid action', 400 );
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
			wp_send_json_error( 'Load handler not configured', 500 );
		}

		// Get data from request
		$data = $this->get_request_data();

		// Call the load callback
		$result = call_user_func( $handler['load_callback'], $data );

		// Handle response
		if ( is_string( $result ) ) {
			// Simple HTML string
			wp_send_json_success( [
				'html' => $result,
			] );
		} elseif ( $result instanceof Flyout ) {
			// Flyout object - just render it
			wp_send_json_success( [
				'html' => $result->render(),
			] );
		} elseif ( is_array( $result ) ) {
			// Check for error
			if ( isset( $result['success'] ) && $result['success'] === false ) {
				wp_send_json_error( $result['message'] ?? $handler['error_message'] );
				return;
			}
			// Pass through array response
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( 'Invalid response from load handler', 500 );
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
			wp_send_json_error( 'Save handler not configured', 500 );
		}

		// Get form data
		$form_data = $_POST['form_data'] ?? [];

		// Parse if serialized
		if ( is_string( $form_data ) ) {
			parse_str( $form_data, $form_data );
		}

		// Get other data
		$data = array_merge( $this->get_request_data(), $form_data );

		// Call save callback
		$result = call_user_func( $handler['save_callback'], $data );

		// Handle response
		if ( is_bool( $result ) ) {
			if ( $result ) {
				wp_send_json_success( [ 'message' => $handler['success_message'] ] );
			} else {
				wp_send_json_error( $handler['error_message'] );
			}
		} elseif ( is_array( $result ) ) {
			if ( ! empty( $result['success'] ) ) {
				wp_send_json_success( $result );
			} else {
				wp_send_json_error( $result['message'] ?? $handler['error_message'] );
			}
		} else {
			wp_send_json_success( [ 'message' => $handler['success_message'] ] );
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
			wp_send_json_error( 'Delete handler not configured', 500 );
		}

		$data = $this->get_request_data();
		$result = call_user_func( $handler['delete_callback'], $data );

		// Handle response
		if ( is_bool( $result ) ) {
			if ( $result ) {
				wp_send_json_success( [ 'message' => 'Deleted successfully' ] );
			} else {
				wp_send_json_error( 'Delete failed' );
			}
		} elseif ( is_array( $result ) ) {
			if ( ! empty( $result['success'] ) ) {
				wp_send_json_success( $result );
			} else {
				wp_send_json_error( $result['message'] ?? 'Delete failed' );
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
			wp_send_json_error( 'Custom action not configured', 500 );
		}

		$data = $this->get_request_data();
		$result = call_user_func( $callback, $data, $action );

		// Pass through result
		if ( is_array( $result ) ) {
			if ( isset( $result['success'] ) && ! $result['success'] ) {
				wp_send_json_error( $result['message'] ?? 'Action failed' );
			} else {
				wp_send_json_success( $result );
			}
		} else {
			wp_send_json_success( [ 'result' => $result ] );
		}
	}

	/**
	 * Get request data
	 *
	 * @return array
	 */
	private function get_request_data(): array {
		$data = [];

		foreach ( $_POST as $key => $value ) {
			// Skip WordPress/AJAX specific keys
			if ( in_array( $key, [ 'action', 'handler', 'handler_action', 'nonce', 'form_data' ] ) ) {
				continue;
			}
			// Clean up data_ prefix if present
			if ( strpos( $key, 'data_' ) === 0 ) {
				$key = substr( $key, 5 );
			}
			$data[ $key ] = is_string( $value ) ? sanitize_text_field( $value ) : $value;
		}

		return $data;
	}

	/**
	 * Render a trigger button
	 *
	 * @param string $handler_id Handler ID
	 * @param array  $data       Data to pass
	 * @param array  $args       Button arguments
	 *
	 * @return void
	 */
	public function button( string $handler_id, array $data = [], array $args = [] ): void {
		echo $this->get_button( $handler_id, $data, $args );
	}

	/**
	 * Get trigger button HTML
	 *
	 * @param string $handler_id Handler ID
	 * @param array  $data       Data to pass
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

		// Button settings
		$text = $args['text'] ?? 'Open';
		$class = $args['class'] ?? 'button';
		$icon = $args['icon'] ?? '';

		// Build button
		$attrs = [
			'type' => 'button',
			'class' => 'wp-flyout-trigger ' . $class,
			'data-flyout-manager' => $this->prefix,
			'data-flyout-handler' => $handler_id,
			'data-flyout-nonce' => wp_create_nonce( $handler['nonce_action'] ),
		];

		// Add data attributes
		foreach ( $data as $key => $value ) {
			$attrs[ 'data-' . $key ] = esc_attr( $value );
		}

		// Build HTML
		$html = '<button';
		foreach ( $attrs as $key => $value ) {
			$html .= sprintf( ' %s="%s"', $key, $value );
		}
		$html .= '>';

		if ( $icon ) {
			$html .= sprintf( '<span class="dashicons dashicons-%s"></span> ', $icon );
		}

		$html .= esc_html( $text ) . '</button>';

		return $html;
	}

	/**
	 * Create a trigger link
	 *
	 * @param string $handler_id Handler ID
	 * @param string $text       Link text
	 * @param array  $data       Data to pass
	 *
	 * @return string
	 */
	public function link( string $handler_id, string $text, array $data = [] ): string {
		if ( ! isset( $this->handlers[ $handler_id ] ) ) {
			return '';
		}

		$handler = $this->handlers[ $handler_id ];

		// Check capability
		if ( ! current_user_can( $handler['capability'] ) ) {
			return '';
		}

		$attrs = [
			'href' => '#',
			'class' => 'wp-flyout-trigger',
			'data-flyout-manager' => $this->prefix,
			'data-flyout-handler' => $handler_id,
			'data-flyout-nonce' => wp_create_nonce( $handler['nonce_action'] ),
		];

		// Add data attributes
		foreach ( $data as $key => $value ) {
			$attrs[ 'data-' . $key ] = esc_attr( $value );
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
	 * Enqueue assets
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		// Only enqueue once
		if ( $this->assets_enqueued ) {
			return;
		}

		// Only if we have handlers
		if ( empty( $this->handlers ) ) {
			return;
		}

		// Load WP Flyout assets
		Assets::init();
		Assets::enqueue();

		// Localize script
		wp_localize_script(
			'wp-flyout-manager',
			'wpFlyoutManager_' . str_replace( '-', '_', $this->prefix ),
			[
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'prefix'   => $this->prefix,
				'handlers' => $this->get_handler_configs(),
			]
		);

		$this->assets_enqueued = true;
	}

	/**
	 * Get handler configs for JS
	 *
	 * @return array
	 */
	private function get_handler_configs(): array {
		$configs = [];

		foreach ( $this->handlers as $id => $handler ) {
			$configs[ $id ] = [
				'autoClose' => $handler['auto_close'],
				'refresh'   => $handler['refresh'],
			];
		}

		return $configs;
	}
}