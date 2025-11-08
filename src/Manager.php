<?php
/**
 * WP Flyout Manager - Standardized Implementation
 *
 * Manages flyout registration, AJAX handling, and automatic data mapping.
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     12.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout;

use ArrayPress\WPFlyout\Components\FormField;
use ArrayPress\WPFlyout\Parts\ActionBar;
use Exception;

/**
 * Class Manager
 *
 * Orchestrates flyout operations with automatic data resolution.
 * Uses standardized nonce handling where all components use action names as nonce keys.
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
	 * Registered flyout configurations
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $flyouts = [];

	/**
	 * Admin pages where assets should load
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $admin_pages = [];

	/**
	 * Components required across all flyouts
	 *
	 * @since 1.0.0
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

		// Single AJAX handler for all actions
		add_action( 'wp_ajax_wp_flyout_' . $this->prefix, [ $this, 'handle_ajax' ] );
		add_action( 'wp_ajax_nopriv_wp_flyout_' . $this->prefix, [ $this, 'handle_ajax' ] );

		// Auto-enqueue assets on admin pages
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_assets' ] );
	}

	/**
	 * Register a flyout with declarative configuration
	 *
	 * @param string $id     Unique flyout identifier
	 * @param array  $config Flyout configuration array
	 *
	 * @return self Returns instance for method chaining
	 * @since 1.0.0
	 */
	public function register_flyout( string $id, array $config ): self {
		$defaults = [
			'title'       => '',
			'size'        => 'medium',
			'panels'      => [],
			'fields'      => [],
			'actions'     => [],
			'capability'  => 'manage_options',
			'admin_pages' => [],
			'load'        => null,
			'save'        => null,
			'delete'      => null,
		];

		$config = wp_parse_args( $config, $defaults );

		// Apply filters for extensibility
		$config = apply_filters( 'wp_flyout_register_config', $config, $id, $this->prefix );
		$config = apply_filters( "wp_flyout_{$this->prefix}_{$id}_config", $config );

		// Auto-detect required components
		$this->detect_components( $config );

		// Track admin pages for asset loading
		if ( ! empty( $config['admin_pages'] ) ) {
			$this->admin_pages = array_unique(
				array_merge( $this->admin_pages, $config['admin_pages'] )
			);
		}

		// Store flyout configuration
		$this->flyouts[ $id ] = $config;

		// Register AJAX endpoints for components with callbacks
		$this->register_component_endpoints( $id, $this->flyouts[ $id ] );

		// Register action button endpoints
		$this->register_action_button_endpoints( $id, $config );

		return $this;
	}

	/**
	 * Register AJAX endpoints for components
	 *
	 * Standardized approach: all components use action name as nonce key
	 * and send _wpnonce parameter.
	 *
	 * @param string $flyout_id Flyout identifier
	 * @param array  $config    Flyout configuration (passed by reference to update)
	 *
	 * @return void
	 * @since 12.0.0
	 */
	private function register_component_endpoints( string $flyout_id, array &$config ): void {
		// Define callback to AJAX field mappings
		$callback_mappings = [
			'search_callback'  => 'ajax_search',
			'details_callback' => 'ajax_details',
			'add_callback'     => 'ajax_add',
			'delete_callback'  => 'ajax_delete',
			'options_callback' => 'ajax_options'
		];

		foreach ( $config['fields'] as $field_key => &$field ) {
			$field_name = $field['name'] ?? $field_key;

			foreach ( $callback_mappings as $callback_key => $ajax_key ) {
				if ( ! isset( $field[ $callback_key ] ) || ! is_callable( $field[ $callback_key ] ) ) {
					continue;
				}

				// Generate unique action name
				$action_name = 'wp_flyout_' . $this->prefix . '_' . $flyout_id . '_' . sanitize_key( $field_name ) . '_' . str_replace( 'ajax_', '', $ajax_key );

				// Store action name in field config for frontend use
				$field[ $ajax_key ] = $action_name;

				// STANDARDIZED: Always use action name as nonce key
				$field[ $ajax_key . '_nonce_key' ] = $action_name;

				// Register the AJAX handler
				add_action( 'wp_ajax_' . $action_name, function () use ( $field, $callback_key, $action_name, $config ) {
					// STANDARDIZED: Always check _wpnonce with action name
					if ( ! check_ajax_referer( $action_name, '_wpnonce', false ) ) {
						wp_send_json_error( 'Security check failed', 403 );
					}

					// Check capability
					if ( ! current_user_can( $config['capability'] ) ) {
						wp_send_json_error( 'Insufficient permissions', 403 );
					}

					// Call the callback
					$result = call_user_func( $field[ $callback_key ], $_POST );

					if ( is_wp_error( $result ) ) {
						wp_send_json_error( $result->get_error_message() );
					}

					wp_send_json_success( $result );
				} );
			}
		}
	}

	/**
	 * Register AJAX endpoints for action components (buttons and menus)
	 *
	 * @param string $flyout_id Flyout identifier
	 * @param array  $config    Flyout configuration
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function register_action_button_endpoints( string $flyout_id, array $config ): void {
		foreach ( $config['fields'] as $field ) {
			$type = $field['type'] ?? '';

			// Get items array based on component type
			$items = [];
			if ( $type === 'action_buttons' ) {
				$items = $field['buttons'] ?? [];
			} elseif ( $type === 'action_menu' ) {
				$items = $field['items'] ?? [];
			} else {
				continue;
			}

			foreach ( $items as $item ) {
				// Skip separators (action_menu only)
				if ( isset( $item['type'] ) && $item['type'] === 'separator' ) {
					continue;
				}

				// Check for callback instead of action string
				if ( empty( $item['callback'] ) || ! is_callable( $item['callback'] ) ) {
					continue;
				}

				// Generate action name if not provided
				$action      = $item['action'] ?? uniqid( 'action_' );
				$action_name = 'wp_flyout_action_' . $action;

				// Check if already registered to avoid duplicates
				if ( has_action( 'wp_ajax_' . $action_name ) ) {
					continue;
				}

				// Register the AJAX handler
				add_action( 'wp_ajax_' . $action_name, function () use ( $item, $action_name, $config ) {
					// STANDARDIZED: Always use action name as nonce key
					if ( ! check_ajax_referer( $action_name, '_wpnonce', false ) ) {
						wp_send_json_error( 'Security check failed', 403 );
					}

					// Check capability
					if ( ! current_user_can( $config['capability'] ) ) {
						wp_send_json_error( 'Insufficient permissions', 403 );
					}

					// Call the callback
					$result = call_user_func( $item['callback'], $_POST );

					if ( is_wp_error( $result ) ) {
						wp_send_json_error( $result->get_error_message() );
					}

					// If result is array with message, send as success
					if ( is_array( $result ) && isset( $result['message'] ) ) {
						wp_send_json_success( $result );
					}

					// Default success
					wp_send_json_success( [ 'message' => 'Action completed successfully' ] );
				} );
			}
		}
	}

	/**
	 * Normalize field configurations
	 * Ensures all fields have proper 'name' attributes
	 *
	 * @param array $fields Field configurations
	 *
	 * @return array Normalized fields
	 * @since 1.0.0
	 */
	private function normalize_fields( array $fields ): array {
		// Apply pre-normalization filter
		$fields = apply_filters( 'wp_flyout_before_normalize_fields', $fields, $this->prefix );

		$normalized = [];

		foreach ( $fields as $field_key => $field ) {
			if ( is_numeric( $field_key ) ) {
				$field_key = $field['name'] ?? 'field_' . $field_key;
			}

			if ( ! isset( $field['name'] ) ) {
				$field['name'] = $field_key;
			}

			$normalized[ $field_key ] = $field;
		}

		// Apply post-normalization filter
		return apply_filters( 'wp_flyout_after_normalize_fields', $normalized, $this->prefix );
	}

	/**
	 * Central AJAX handler with security checks
	 *
	 * @return void Sends JSON response and exits
	 * @since 1.0.0
	 */
	public function handle_ajax(): void {
		try {
			// Extract and validate request
			$request = $this->validate_request();

			// Get flyout config
			$config = $this->flyouts[ $request['flyout_id'] ];

			// Route to appropriate handler
			switch ( $request['action'] ) {
				case 'load':
					$this->handle_load( $config, $request );
					break;

				case 'save':
					$this->handle_save( $config, $request );
					break;

				case 'delete':
					$this->handle_delete( $config, $request );
					break;

				default:
					wp_send_json_error( __( 'Invalid action', 'wp-flyout' ), 400 );
			}

		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage(), $e->getCode() ?: 500 );
		}
	}

	/**
	 * Validate AJAX request and check permissions
	 *
	 * @return array Validated request data
	 * @throws Exception If validation fails
	 * @since 1.0.0
	 */
	private function validate_request(): array {
		$flyout_id = sanitize_key( $_POST['flyout'] ?? '' );
		$action    = sanitize_key( $_POST['flyout_action'] ?? 'load' );

		if ( ! isset( $this->flyouts[ $flyout_id ] ) ) {
			throw new Exception( 'Invalid flyout', 400 );
		}

		$config = $this->flyouts[ $flyout_id ];

		if ( ! check_ajax_referer( 'wp_flyout_' . $this->prefix . '_' . $flyout_id, 'nonce', false ) ) {
			throw new Exception( 'Security check failed', 403 );
		}

		if ( ! current_user_can( $config['capability'] ) ) {
			throw new Exception( 'Insufficient permissions', 403 );
		}

		return [
			'flyout_id' => $flyout_id,
			'action'    => $action,
			'id'        => $_POST['id'] ?? null,
			'form_data' => $_POST['form_data'] ?? '',
		];
	}

	/**
	 * Handle load action
	 *
	 * @param array $config  Flyout configuration
	 * @param array $request Validated request data
	 *
	 * @return void Sends JSON response and exits
	 * @since 1.0.0
	 */
	private function handle_load( array $config, array $request ): void {
		$data = [];

		if ( $config['load'] && is_callable( $config['load'] ) ) {
			$data = call_user_func( $config['load'], $request['id'] );

			if ( is_wp_error( $data ) ) {
				wp_send_json_error( $data->get_error_message(), 400 );
			}

			if ( $data === false ) {
				wp_send_json_error( __( 'Record not found', 'wp-flyout' ), 404 );
			}
		}

		$flyout = $this->build_flyout( $config, $data, $request['id'] );
		wp_send_json_success( [ 'html' => $flyout->render() ] );
	}

	/**
	 * Handle save action
	 *
	 * @param array $config  Flyout configuration
	 * @param array $request Validated request data
	 *
	 * @return void Sends JSON response and exits
	 * @since 1.0.0
	 */
	private function handle_save( array $config, array $request ): void {
		if ( ! $config['save'] || ! is_callable( $config['save'] ) ) {
			wp_send_json_error( __( 'Save not configured', 'wp-flyout' ), 501 );
		}

		parse_str( $request['form_data'], $raw_data );

		$normalized_fields = $this->normalize_fields( $config['fields'] );
		$form_data         = Sanitizer::sanitize_form_data( $raw_data, $normalized_fields );

		// Apply filter after sanitization
		$form_data = apply_filters( 'wp_flyout_before_save', $form_data, $config, $this->prefix );

		if ( ! empty( $config['validate'] ) && is_callable( $config['validate'] ) ) {
			$validation = call_user_func( $config['validate'], $form_data );

			if ( is_wp_error( $validation ) ) {
				wp_send_json_error(
					$validation->get_error_message(),
					400
				);
			}

			if ( $validation === false ) {
				wp_send_json_error( __( 'Validation failed', 'wp-flyout' ), 400 );
			}
		}

		$id     = $form_data['id'] ?? $request['id'] ?? null;
		$result = call_user_func( $config['save'], $id, $form_data );

		// Apply filter after save
		do_action( 'wp_flyout_after_save', $result, $id, $form_data, $config, $this->prefix );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message(), 400 );
		}

		if ( $result === false ) {
			wp_send_json_error( __( 'Save failed', 'wp-flyout' ), 500 );
		}

		wp_send_json_success( [
			'message' => __( 'Saved successfully', 'wp-flyout' )
		] );
	}

	/**
	 * Handle delete action
	 *
	 * @param array $config  Flyout configuration
	 * @param array $request Validated request data
	 *
	 * @return void Sends JSON response and exits
	 * @since 1.0.0
	 */
	private function handle_delete( array $config, array $request ): void {
		if ( ! $config['delete'] || ! is_callable( $config['delete'] ) ) {
			wp_send_json_error( __( 'Delete not configured', 'wp-flyout' ), 501 );
		}

		// Apply filter before delete
		$id = apply_filters( 'wp_flyout_before_delete', $request['id'], $config, $this->prefix );

		$result = call_user_func( $config['delete'], $id );

		// Apply action after delete
		do_action( 'wp_flyout_after_delete', $result, $id, $config, $this->prefix );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message(), 400 );
		}

		if ( $result === false ) {
			wp_send_json_error( __( 'Delete failed', 'wp-flyout' ), 500 );
		}

		wp_send_json_success( [
			'message' => __( 'Deleted successfully', 'wp-flyout' )
		] );
	}

	/**
	 * Build flyout interface
	 *
	 * @param array  $config Flyout configuration
	 * @param mixed  $data   Data for field population
	 * @param string $id     Record ID if editing
	 *
	 * @return Flyout Configured flyout instance
	 * @since 1.0.0
	 */
	private function build_flyout( array $config, $data, $id = null ): Flyout {
		$flyout_instance_id = $config['id'] ?? uniqid() . '_' . ( $id ?: 'new' );
		$flyout             = new Flyout( $flyout_instance_id );

		$flyout->set_title( $config['title'] );
		$flyout->set_size( $config['size'] );

		// Apply filter to modify flyout instance
		$flyout = apply_filters( 'wp_flyout_build_flyout', $flyout, $config, $data, $this->prefix );

		if ( ! empty( $config['panels'] ) ) {
			$this->build_panel_interface( $flyout, $config['panels'], $config['fields'], $data );
		} else {
			$content = $this->render_fields( $config['fields'], $data );
			$flyout->add_content( '', $content );
		}

		if ( $id ) {
			$flyout->add_content( '', sprintf(
				'<input type="hidden" name="id" value="%s">',
				esc_attr( $id )
			) );
		}

		$actions = ! empty( $config['actions'] )
			? $config['actions']
			: $this->get_default_actions( $config );

		// Only set footer if there are actions
		if ( ! empty( $actions ) ) {
			$flyout->set_footer( $this->render_actions( $actions ) );
		}

		return $flyout;
	}

	/**
	 * Build panel interface for flyout
	 *
	 * @param Flyout $flyout Flyout instance
	 * @param array  $panels Panel configurations
	 * @param array  $fields All field configurations
	 * @param mixed  $data   Data for field population
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function build_panel_interface( Flyout $flyout, array $panels, array $fields, $data ): void {
		// Group fields by panel
		$fields_by_panel = [];
		foreach ( $fields as $key => $field ) {
			$panel = $field['panel'] ?? $field['tab'] ?? 'default';
			if ( ! isset( $fields_by_panel[ $panel ] ) ) {
				$fields_by_panel[ $panel ] = [];
			}
			$fields_by_panel[ $panel ][ $key ] = $field;
		}

		// Add panels with their fields
		foreach ( $panels as $panel_id => $panel_config ) {
			$label    = is_array( $panel_config ) ? $panel_config['label'] : $panel_config;
			$is_first = array_key_first( $panels ) === $panel_id;

			$flyout->add_tab( $panel_id, $label, $is_first );

			$panel_fields = $fields_by_panel[ $panel_id ] ?? [];
			$content      = $this->render_fields( $panel_fields, $data );
			$flyout->set_tab_content( $panel_id, $content );
		}
	}

	/**
	 * Render fields from configuration
	 *
	 * @param array $fields Field configurations
	 * @param mixed $data   Data object or array
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 */
	private function render_fields( array $fields, $data ): string {
		$output = '';

		// Apply filter before rendering
		$fields = apply_filters( 'wp_flyout_before_render_fields', $fields, $data, $this->prefix );

		$normalized_fields = $this->normalize_fields( $fields );

		foreach ( $normalized_fields as $field_key => $field ) {
			// Apply field-specific filter
			$field = apply_filters( 'wp_flyout_render_field', $field, $field_key, $data, $this->prefix );
			$field = apply_filters( "wp_flyout_render_field_{$field_key}", $field, $data, $this->prefix );

			$type = $field['type'] ?? 'text';

			// Generate all nonce keys that exist
			$nonce_mappings = [
				'ajax_search_nonce_key'  => 'nonce',
				'ajax_add_nonce_key'     => 'add_nonce',
				'ajax_delete_nonce_key'  => 'delete_nonce',
				'ajax_details_nonce_key' => 'details_nonce'
			];

			foreach ( $nonce_mappings as $key_field => $nonce_field ) {
				if ( ! empty( $field[ $key_field ] ) ) {
					$field[ $nonce_field ] = wp_create_nonce( $field[ $key_field ] );
				}
			}

			// Map ajax_search to ajax for ajax_select compatibility
			if ( $type === 'ajax_select' && ! empty( $field['ajax_search'] ) ) {
				$field['ajax'] = $field['ajax_search'];
			}

			// Handle ajax_select options callback
			if ( $type === 'ajax_select' ) {
				if ( ! isset( $field['value'] ) && $data ) {
					$field['value'] = Components::resolve_value( $field_key, $data );
				}

				if ( ! empty( $field['value'] ) && empty( $field['options'] ) ) {
					if ( ! empty( $field['options_callback'] ) && is_callable( $field['options_callback'] ) ) {
						$field['options'] = call_user_func( $field['options_callback'], $field['value'], $data );
					}
				}
			}

			if ( Components::is_component( $type ) ) {
				$resolved_data = Components::resolve_data( $type, $field_key, $data );

				foreach ( $resolved_data as $key => $value ) {
					if ( ! isset( $field[ $key ] ) && $value !== null ) {
						$field[ $key ] = $value;
					}
				}

				$component = Components::create( $type, $field );
				$output    .= $component ? $component->render() : '';
			} else {
				if ( ! isset( $field['value'] ) && $data ) {
					$field['value'] = Components::resolve_value( $field_key, $data );
				}

				$form_field = new FormField( $field );
				$output     .= $form_field->render();
			}
		}

		// Apply filter after rendering
		return apply_filters( 'wp_flyout_after_render_fields', $output, $fields, $data, $this->prefix );
	}

	/**
	 * Detect and register required components from configuration
	 *
	 * @param array $config Flyout configuration
	 *
	 * @return void
	 * @since 1.0.0
	 */
	private function detect_components( array $config ): void {
		foreach ( $config['fields'] as $field ) {
			$type = $field['type'] ?? 'text';

			// Get asset from Components registry
			if ( $asset = Components::get_asset( $type ) ) {
				$this->components[] = $asset;
			}
		}

		$this->components = array_unique( $this->components );
	}

	/**
	 * Render action buttons for footer
	 *
	 * @param array $actions Action button configurations
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 */
	private function render_actions( array $actions ): string {
		$action_bar = new ActionBar( [ 'actions' => $actions ] );

		return $action_bar->render();
	}

	/**
	 * Get default action buttons based on configuration
	 *
	 * @param array $config Flyout configuration
	 *
	 * @return array Default action buttons
	 * @since 1.0.0
	 */
	private function get_default_actions( array $config ): array {
		$actions = [];

		if ( ! empty( $config['save'] ) ) {
			$actions[] = [
				'text'  => __( 'Save', 'wp-flyout' ),
				'style' => 'primary',
				'class' => 'wp-flyout-save'
			];
		}

		if ( ! empty( $config['delete'] ) ) {
			$actions[] = [
				'text'  => __( 'Delete', 'wp-flyout' ),
				'style' => 'link-delete',
				'class' => 'wp-flyout-delete'
			];
		}

		return $actions;
	}

	/**
	 * Maybe enqueue assets based on current admin page
	 *
	 * @param string $hook_suffix Current admin page hook
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function maybe_enqueue_assets( string $hook_suffix ): void {
		if ( $this->assets_enqueued || empty( $this->flyouts ) ) {
			return;
		}

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
	 * @since 1.0.0
	 */
	private function should_enqueue( string $hook_suffix ): bool {
		if ( ! empty( $this->admin_pages ) ) {
			return in_array( $hook_suffix, $this->admin_pages, true );
		}

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

		if ( str_starts_with( $hook_suffix, 'toplevel_page_' ) ||
		     str_starts_with( $hook_suffix, 'page_' ) ) {
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
		Assets::enqueue();

		foreach ( $this->components as $component ) {
			Assets::enqueue_component( $component );
		}

		$this->assets_enqueued = true;
	}

	/**
	 * Render a trigger button
	 *
	 * @param string $flyout_id Flyout identifier
	 * @param array  $data      Data attributes to pass
	 * @param array  $args      Button configuration
	 *
	 * @return void Outputs HTML
	 * @since 1.0.0
	 */
	public function button( string $flyout_id, array $data = [], array $args = [] ): void {
		echo $this->get_button( $flyout_id, $data, $args );
	}

	/**
	 * Get trigger button HTML
	 *
	 * @param string $flyout_id Flyout identifier
	 * @param array  $data      Data attributes to pass
	 * @param array  $args      Button configuration
	 *
	 * @return string Button HTML or empty string if unauthorized
	 * @since 1.0.0
	 */
	public function get_button( string $flyout_id, array $data = [], array $args = [] ): string {
		if ( ! $this->can_access( $flyout_id ) ) {
			return '';
		}

		$text  = $args['text'] ?? __( 'Open', 'wp-flyout' );
		$class = $args['class'] ?? 'button';
		$icon  = $args['icon'] ?? '';

		$attrs = $this->build_trigger_attributes( $flyout_id, $data, 'button ' . $class );

		$html = '<button type="button"';
		foreach ( $attrs as $key => $value ) {
			$html .= sprintf( ' %s="%s"', $key, $value );
		}
		$html .= '>';

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
	 * @param string $flyout_id Flyout identifier
	 * @param string $text      Link text
	 * @param array  $data      Data attributes to pass
	 * @param array  $args      Additional link arguments
	 *
	 * @return string Link HTML or empty string if unauthorized
	 * @since 1.0.0
	 */
	public function link( string $flyout_id, string $text, array $data = [], array $args = [] ): string {
		if ( ! $this->can_access( $flyout_id ) ) {
			return '';
		}

		$class         = $args['class'] ?? '';
		$attrs         = $this->build_trigger_attributes( $flyout_id, $data, $class );
		$attrs['href'] = '#';

		$html = '<a';
		foreach ( $attrs as $key => $value ) {
			$html .= sprintf( ' %s="%s"', $key, $value );
		}
		$html .= '>' . esc_html( $text ) . '</a>';

		return $html;
	}

	/**
	 * Build trigger element attributes
	 *
	 * @param string $flyout_id Flyout identifier
	 * @param array  $data      Data attributes
	 * @param string $class     Additional CSS classes
	 *
	 * @return array Attributes array
	 * @since 1.0.0
	 */
	private function build_trigger_attributes( string $flyout_id, array $data, string $class = '' ): array {
		$attrs = [
			'class'               => trim( 'wp-flyout-trigger ' . $class ),
			'data-flyout-manager' => $this->prefix,
			'data-flyout'         => $flyout_id,
			'data-flyout-nonce'   => wp_create_nonce( 'wp_flyout_' . $this->prefix . '_' . $flyout_id ),
		];

		foreach ( $data as $key => $value ) {
			$attrs[ 'data-' . $key ] = esc_attr( (string) $value );
		}

		return $attrs;
	}

	/**
	 * Check if current user can access flyout
	 *
	 * @param string $flyout_id Flyout identifier
	 *
	 * @return bool True if user has required capability
	 * @since 1.0.0
	 */
	private function can_access( string $flyout_id ): bool {
		if ( ! isset( $this->flyouts[ $flyout_id ] ) ) {
			return false;
		}

		$config = $this->flyouts[ $flyout_id ];

		return current_user_can( $config['capability'] );
	}

	/**
	 * Get all registered flyouts
	 *
	 * @return array Flyout configurations
	 * @since 1.0.0
	 */
	public function get_flyouts(): array {
		return $this->flyouts;
	}

	/**
	 * Check if flyout is registered
	 *
	 * @param string $flyout_id Flyout identifier
	 *
	 * @return bool True if flyout exists
	 * @since 1.0.0
	 */
	public function has_flyout( string $flyout_id ): bool {
		return isset( $this->flyouts[ $flyout_id ] );
	}

	/**
	 * Get manager prefix
	 *
	 * @return string Manager prefix
	 * @since 1.0.0
	 */
	public function get_prefix(): string {
		return $this->prefix;
	}

}