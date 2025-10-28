<?php
/**
 * WP Flyout Manager
 *
 * Provides zero-JavaScript flyout management through handler registration
 * and automatic AJAX orchestration.
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
 * Class Manager
 *
 * Manages flyout operations with automatic AJAX handling.
 * Each plugin/theme creates its own Manager instance with a unique prefix.
 *
 * @since 1.0.0
 */
class Manager {

	/**
	 * Unique prefix for this instance
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
	 * @param string $prefix Unique prefix for this instance (e.g., 'my-plugin')
	 *
	 * @since 1.0.0
	 */
	public function __construct( string $prefix ) {
		$this->prefix = sanitize_key( $prefix );
		$this->hooks();
	}

	/**
	 * Setup WordPress hooks
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function hooks(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( "wp_ajax_wp_flyout_{$this->prefix}", [ $this, 'handle_ajax' ] );
	}

	/**
	 * Register a flyout handler
	 *
	 * @param string  $id              Unique identifier for this flyout
	 * @param array   $args            {
	 *                                 Flyout handler configuration
	 *
	 * @type callable $ajax_load       Function to load flyout content (required)
	 * @type callable $ajax_save       Function to save flyout data (optional)
	 * @type callable $ajax_delete     Function to delete items (optional)
	 * @type array    $ajax_custom     Custom action handlers (optional)
	 * @type string   $title           Flyout title (default: 'Edit Item')
	 * @type string   $width           Width: small, medium, large, full (default: 'medium')
	 * @type string   $position        Position: right, left (default: 'right')
	 * @type string   $capability      Required capability (default: 'manage_options')
	 * @type string   $icon            Dashicon name (default: 'edit')
	 * @type bool     $auto_close      Close after save (default: true)
	 * @type bool     $refresh         Refresh page after save (default: false)
	 * @type string   $refresh_target  CSS selector to refresh (optional)
	 * @type string   $success_message Default success message
	 * @type string   $error_message   Default error message
	 * @type array    $defaults        Default options passed to callbacks
	 * @type bool     $show_footer     Show footer buttons (default: true)
	 *                                 }
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function register( string $id, array $args ): void {
		$this->handlers[ $id ] = wp_parse_args( $args, [
			'ajax_load'       => null,
			'ajax_save'       => null,
			'ajax_delete'     => null,
			'ajax_custom'     => [],
			'title'           => __( 'Edit Item', 'wp-flyout' ),
			'width'           => 'medium',
			'position'        => 'right',
			'capability'      => 'manage_options',
			'icon'            => 'edit',
			'auto_close'      => true,
			'refresh'         => false,
			'refresh_target'  => '',
			'success_message' => __( 'Changes saved successfully!', 'wp-flyout' ),
			'error_message'   => __( 'An error occurred. Please try again.', 'wp-flyout' ),
			'defaults'        => [],
			'show_footer'     => true,
		] );

		// Validate required callback
		if ( ! is_callable( $this->handlers[ $id ]['ajax_load'] ) ) {
			unset( $this->handlers[ $id ] );
			trigger_error( "WP Flyout: ajax_load callback is required for handler '{$id}'", E_USER_WARNING );
		}
	}

	/**
	 * Render a flyout trigger button
	 *
	 * @param string $id   Handler ID
	 * @param array  $data Data attributes for the button
	 * @param array  $args Button arguments
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function button( string $id, array $data = [], array $args = [] ): void {
		echo $this->get_button( $id, $data, $args );
	}

	/**
	 * Get button HTML
	 *
	 * @param string $id   Handler ID
	 * @param array  $data Data attributes (e.g., ['object_id' => 123])
	 * @param array  $args Button arguments
	 *
	 * @return string Button HTML
	 * @since 1.0.0
	 */
	public function get_button( string $id, array $data = [], array $args = [] ): string {
		if ( ! isset( $this->handlers[ $id ] ) ) {
			return '';
		}

		$handler = $this->handlers[ $id ];

		// Check capability
		if ( ! current_user_can( $handler['capability'] ) ) {
			return '';
		}

		// Parse button args
		$args = wp_parse_args( $args, [
			'text'  => __( 'Edit', 'wp-flyout' ),
			'class' => 'button',
			'icon'  => $handler['icon'],
		] );

		// Generate nonce
		$nonce = wp_create_nonce( "wp_flyout_{$this->prefix}_{$id}" );

		// Build data attributes
		$data_attrs = [
			'data-flyout-prefix="' . esc_attr( $this->prefix ) . '"',
			'data-flyout-id="' . esc_attr( $id ) . '"',
			'data-flyout-nonce="' . esc_attr( $nonce ) . '"',
		];

		foreach ( $data as $key => $value ) {
			$key          = str_replace( '_', '-', $key );
			$data_attrs[] = 'data-' . esc_attr( $key ) . '="' . esc_attr( (string) $value ) . '"';
		}

		// Build button HTML
		$html = sprintf(
			'<button type="button" class="%s wp-flyout-trigger" %s>',
			esc_attr( $args['class'] ),
			implode( ' ', $data_attrs )
		);

		if ( $args['icon'] ) {
			$html .= '<span class="dashicons dashicons-' . esc_attr( $args['icon'] ) . '"></span> ';
		}

		$html .= '<span class="wp-flyout-button-text">' . esc_html( $args['text'] ) . '</span>';
		$html .= '</button>';

		return $html;
	}

	/**
	 * Create a trigger link
	 *
	 * @param string $id   Handler ID
	 * @param string $text Link text
	 * @param array  $data Data attributes
	 * @param array  $args Link arguments
	 *
	 * @return string Link HTML
	 * @since 1.0.0
	 */
	public function link( string $id, string $text, array $data = [], array $args = [] ): string {
		if ( ! isset( $this->handlers[ $id ] ) ) {
			return '';
		}

		$handler = $this->handlers[ $id ];

		// Check capability
		if ( ! current_user_can( $handler['capability'] ) ) {
			return '';
		}

		// Parse link args
		$args = wp_parse_args( $args, [
			'class' => '',
		] );

		// Generate nonce
		$nonce = wp_create_nonce( "wp_flyout_{$this->prefix}_{$id}" );

		// Build data attributes
		$data_attrs = [
			'data-flyout-prefix="' . esc_attr( $this->prefix ) . '"',
			'data-flyout-id="' . esc_attr( $id ) . '"',
			'data-flyout-nonce="' . esc_attr( $nonce ) . '"',
		];

		foreach ( $data as $key => $value ) {
			$key          = str_replace( '_', '-', $key );
			$data_attrs[] = 'data-' . esc_attr( $key ) . '="' . esc_attr( (string) $value ) . '"';
		}

		return sprintf(
			'<a href="#" class="%s wp-flyout-trigger" %s>%s</a>',
			esc_attr( $args['class'] ),
			implode( ' ', $data_attrs ),
			esc_html( $text )
		);
	}

	/**
	 * Enqueue assets
	 *
	 * @return void
	 * @since 1.0.0
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

		// Enqueue core WP Flyout assets (includes manager.js)
		Assets::enqueue();

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
	 * @return array Handler configurations
	 * @since 1.0.0
	 */
	private function get_handler_configs(): array {
		$configs = [];

		foreach ( $this->handlers as $id => $handler ) {
			$configs[ $id ] = [
				'title'          => $handler['title'],
				'width'          => $handler['width'],
				'position'       => $handler['position'],
				'autoClose'      => $handler['auto_close'],
				'refresh'        => $handler['refresh'],
				'refreshTarget'  => $handler['refresh_target'],
				'successMessage' => $handler['success_message'],
				'errorMessage'   => $handler['error_message'],
				'hasDelete'      => is_callable( $handler['ajax_delete'] ),
				'hasSave'        => is_callable( $handler['ajax_save'] ),
				'customActions'  => array_keys( $handler['ajax_custom'] ),
				'showFooter'     => $handler['show_footer'],
			];
		}

		return $configs;
	}

	/**
	 * Get localized strings
	 *
	 * @return array Translatable strings
	 * @since 1.0.0
	 */
	private function get_strings(): array {
		return [
			'loading'       => __( 'Loading...', 'wp-flyout' ),
			'saving'        => __( 'Saving...', 'wp-flyout' ),
			'saved'         => __( 'Saved!', 'wp-flyout' ),
			'error'         => __( 'Error', 'wp-flyout' ),
			'close'         => __( 'Close', 'wp-flyout' ),
			'save'          => __( 'Save Changes', 'wp-flyout' ),
			'delete'        => __( 'Delete', 'wp-flyout' ),
			'confirmDelete' => __( 'Are you sure you want to delete this item?', 'wp-flyout' ),
			'deleting'      => __( 'Deleting...', 'wp-flyout' ),
			'deleted'       => __( 'Deleted!', 'wp-flyout' ),
			'cancel'        => __( 'Cancel', 'wp-flyout' ),
			'confirmClose'  => __( 'You have unsaved changes. Are you sure you want to close?', 'wp-flyout' ),
		];
	}

	/**
	 * Handle AJAX requests
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function handle_ajax(): void {
		// Get parameters
		$handler_id = sanitize_text_field( $_POST['handler_id'] ?? '' );
		$action     = sanitize_text_field( $_POST['flyout_action'] ?? 'load' );

		// Verify handler exists
		if ( ! isset( $this->handlers[ $handler_id ] ) ) {
			wp_send_json_error( __( 'Invalid flyout handler', 'wp-flyout' ), 400 );
		}

		$handler = $this->handlers[ $handler_id ];

		// Verify nonce
		if ( ! check_ajax_referer( "wp_flyout_{$this->prefix}_{$handler_id}", '_wpnonce', false ) ) {
			wp_send_json_error( __( 'Invalid security token', 'wp-flyout' ), 403 );
		}

		// Check capability
		if ( ! current_user_can( $handler['capability'] ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'wp-flyout' ), 403 );
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
				if ( isset( $handler['ajax_custom'][ $action ] ) ) {
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
	 * @since 1.0.0
	 */
	private function handle_load( string $handler_id, array $handler ): void {
		if ( ! is_callable( $handler['ajax_load'] ) ) {
			wp_send_json_error( __( 'Load handler not available', 'wp-flyout' ), 500 );
		}

		try {
			// Get all data attributes
			$data = $this->get_request_data();

			// Merge with defaults
			$options = array_merge( $handler['defaults'], $data );

			// Call the load callback
			$result = call_user_func( $handler['ajax_load'], $options );

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
				// Flyout object
				wp_send_json_success( [
					'html'       => $result->render(),
					'title'      => $result->get_title() ?: $handler['title'],
					'width'      => $result->get_width() ?: $handler['width'],
					'showFooter' => $handler['show_footer'],
				] );
			} else {
				wp_send_json_error( __( 'Invalid response from load handler', 'wp-flyout' ), 500 );
			}

		} catch ( \Exception $e ) {
			wp_send_json_error( $e->getMessage(), 500 );
		}
	}

	/**
	 * Handle save action
	 *
	 * @param string $handler_id Handler ID
	 * @param array  $handler    Handler configuration
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function handle_save( string $handler_id, array $handler ): void {
		if ( ! is_callable( $handler['ajax_save'] ) ) {
			wp_send_json_error( __( 'Save handler not available', 'wp-flyout' ), 500 );
		}

		try {
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
			$result = call_user_func( $handler['ajax_save'], $options );

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

		} catch ( \Exception $e ) {
			wp_send_json_error( $e->getMessage(), 500 );
		}
	}

	/**
	 * Handle delete action
	 *
	 * @param string $handler_id Handler ID
	 * @param array  $handler    Handler configuration
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function handle_delete( string $handler_id, array $handler ): void {
		if ( ! is_callable( $handler['ajax_delete'] ) ) {
			wp_send_json_error( __( 'Delete handler not available', 'wp-flyout' ), 500 );
		}

		try {
			// Get data
			$data    = $this->get_request_data();
			$options = array_merge( $handler['defaults'], $data );

			// Call the delete callback
			$result = call_user_func( $handler['ajax_delete'], $options );

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

		} catch ( \Exception $e ) {
			wp_send_json_error( $e->getMessage(), 500 );
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
	 * @since 1.0.0
	 */
	private function handle_custom( string $handler_id, array $handler, string $action ): void {
		$callback = $handler['ajax_custom'][ $action ] ?? null;

		if ( ! is_callable( $callback ) ) {
			wp_send_json_error( __( 'Custom handler not available', 'wp-flyout' ), 500 );
		}

		try {
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

		} catch ( \Exception $e ) {
			wp_send_json_error( $e->getMessage(), 500 );
		}
	}

	/**
	 * Get request data
	 *
	 * @return array Sanitized request data
	 * @since 1.0.0
	 */
	private function get_request_data(): array {
		$data = [];

		// Get all POST data that starts with 'data_'
		foreach ( $_POST as $key => $value ) {
			if ( strpos( $key, 'data_' ) === 0 ) {
				$clean_key          = str_replace( 'data_', '', $key );
				$data[ $clean_key ] = is_array( $value )
					? array_map( 'sanitize_text_field', $value )
					: sanitize_text_field( $value );
			}
		}

		return $data;
	}

	/**
	 * Get registered handlers
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get_handlers(): array {
		return $this->handlers;
	}

	/**
	 * Check if handler exists
	 *
	 * @param string $id Handler ID
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function has_handler( string $id ): bool {
		return isset( $this->handlers[ $id ] );
	}

	/**
	 * Get prefix
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function get_prefix(): string {
		return $this->prefix;
	}

	/**
	 * Get the width of the flyout
	 *
	 * @return string
	 */
	public function get_width(): string {
		return $this->config['width'] ?? 'medium';
	}

	/**
	 * Get the title
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->title ?? '';
	}
}