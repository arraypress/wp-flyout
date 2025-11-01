<?php
/**
 * WP Flyout Manager with Declarative Registration
 *
 * Manages flyout registration, AJAX handling, and asset management.
 * Provides a declarative API for creating modal flyouts with minimal configuration.
 *
 * @package     ArrayPress\WPFlyout
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     6.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout;

use ArrayPress\WPFlyout\Components\Form\FormField;
use ArrayPress\WPFlyout\Components\Core\ActionBar;
use ArrayPress\WPFlyout\Components\Interactive\OrderItems;
use ArrayPress\WPFlyout\Components\Interactive\Notes;
use ArrayPress\WPFlyout\Components\Interactive\FileManager;
use Exception;

/**
 * Class Manager
 *
 * Orchestrates flyout operations with automatic asset management and declarative configuration.
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
	 * @since 6.0.0
	 * @var array
	 */
	private array $flyouts = [];

	/**
	 * Admin pages where assets should load
	 *
	 * @since 2.0.0
	 * @var array
	 */
	private array $admin_pages = [];

	/**
	 * Components required across all flyouts
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
	 * Field types that require special component handling
	 *
	 * @since 6.0.0
	 * @var array
	 */
	private const COMPONENT_FIELD_TYPES = [
		'order_items',
		'notes',
		'files'
	];

	/**
	 * Map of field types to required asset components
	 *
	 * @since 6.0.0
	 * @var array
	 */
	private const FIELD_COMPONENT_MAP = [
		'order_items' => 'order-items',
		'notes'       => 'notes',
		'files'       => 'file-manager',
		'ajax_select' => 'ajax-select',
		'tags'        => 'tags'
	];

	/**
	 * Constructor
	 *
	 * @param string $prefix Unique prefix for this manager instance
	 *
	 * @since 1.0.0
	 *
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
	 * Register a flyout with declarative configuration
	 *
	 * @param string  $id          Unique flyout identifier
	 * @param array   $config      {
	 *                             Flyout configuration array
	 *
	 * @type string   $title       Flyout title
	 * @type string   $width       Width size: 'small', 'medium', 'large', 'full'
	 * @type array    $tabs        Tab configurations (optional)
	 * @type array    $fields      Field configurations for single view
	 * @type array    $actions     Footer action buttons
	 * @type string   $capability  Required capability (default: 'manage_options')
	 * @type array    $admin_pages Admin page hooks to load on
	 * @type callable $load_data   Function to load data: function($id)
	 * @type callable $save_data   Function to save data: function($id, $data)
	 * @type callable $delete_data Function to delete data: function($id)
	 *                             }
	 * @return self Returns instance for method chaining
	 * @since 6.0.0
	 *
	 */
	public function register_flyout( string $id, array $config ): self {
		$defaults = [
			'title'       => '',
			'width'       => 'medium',
			'tabs'        => [],
			'fields'      => [],
			'actions'     => [],
			'capability'  => 'manage_options',
			'admin_pages' => [],
			'load_data'   => null,
			'save_data'   => null,
			'delete_data' => null,
		];

		$config = wp_parse_args( $config, $defaults );

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

		// Register AJAX endpoints for components if needed
		$this->register_component_endpoints( $id, $config );

		return $this;
	}

	/**
	 * Handle AJAX requests
	 *
	 * Routes AJAX requests to appropriate flyout handlers.
	 *
	 * @return void Sends JSON response and exits
	 * @since 1.0.0
	 *
	 */
	public function handle_ajax(): void {
		// Get request parameters
		$flyout_id = sanitize_key( $_POST['flyout'] ?? '' );
		$action    = sanitize_key( $_POST['flyout_action'] ?? 'load' );

		// Validate flyout exists
		if ( ! isset( $this->flyouts[ $flyout_id ] ) ) {
			wp_send_json_error( 'Invalid flyout', 400 );
		}

		$config = $this->flyouts[ $flyout_id ];

		// Check capabilities
		if ( ! current_user_can( $config['capability'] ) ) {
			wp_send_json_error( 'Insufficient permissions', 403 );
		}

		// Verify nonce
		if ( ! check_ajax_referer( 'wp_flyout_' . $this->prefix . '_' . $flyout_id, 'nonce', false ) ) {
			wp_send_json_error( 'Security check failed', 403 );
		}

		try {
			switch ( $action ) {
				case 'load':
					$result = $this->load_flyout( $flyout_id, $_POST );
					break;
				case 'save':
					$result = $this->save_flyout( $flyout_id, $_POST );
					break;
				case 'delete':
					$result = $this->delete_flyout( $flyout_id, $_POST );
					break;
				default:
					wp_send_json_error( 'Invalid action', 400 );
			}

			$this->send_response( $result );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage(), 500 );
		}
	}

	/**
	 * Load flyout and build interface
	 *
	 * @param string $id      Flyout identifier
	 * @param array  $request Request data from AJAX
	 *
	 * @return Flyout Configured flyout instance
	 * @since 6.0.0
	 *
	 */
	private function load_flyout( string $id, array $request ): Flyout {
		$config = $this->flyouts[ $id ];
		$data   = [];

		// Load data if callback provided
		if ( $config['load_data'] ) {
			$data_id = $request['id'] ?? null;

			if ( is_callable( $config['load_data'] ) ) {
				$data = call_user_func( $config['load_data'], $data_id );
			} elseif ( function_exists( $config['load_data'] ) ) {
				$data = call_user_func( $config['load_data'], $data_id );
			}
		}

		// Build flyout instance
		$flyout_instance_id = $id . '_' . ( $request['id'] ?? 'new' );
		$flyout             = new Flyout( $flyout_instance_id );
		$flyout->set_title( $config['title'] );

		// FIX: Actually set the width!
		if ( ! empty( $config['width'] ) ) {
			$flyout->set_width( $config['width'] );
		}

		// Build interface with tabs or single view
		if ( ! empty( $config['tabs'] ) ) {
			$this->build_tabbed_interface( $flyout, $config['tabs'], $data );
		} else {
			$content = $this->render_fields( $config['fields'], $data );
			$flyout->add_content( '', $content );
		}

		// Add hidden ID field if editing existing record
		if ( isset( $request['id'] ) ) {
			$flyout->add_content( '', sprintf(
				'<input type="hidden" name="id" value="%s">',
				esc_attr( $request['id'] )
			) );
		}

		// Add footer actions
		$actions = ! empty( $config['actions'] )
			? $config['actions']
			: $this->get_default_actions( $config );

		$flyout->set_footer( $this->render_actions( $actions ) );

		return $flyout;
	}

	/**
	 * Build tabbed interface for flyout
	 *
	 * @param Flyout $flyout Flyout instance
	 * @param array  $tabs   Tab configurations
	 * @param mixed  $data   Data for field population
	 *
	 * @return void
	 * @since 6.0.0
	 *
	 */
	private function build_tabbed_interface( Flyout $flyout, array $tabs, $data ): void {
		foreach ( $tabs as $tab_id => $tab ) {
			$is_first = array_key_first( $tabs ) === $tab_id;
			$flyout->add_tab( $tab_id, $tab['label'], $is_first );

			$content = $this->render_fields( $tab['fields'] ?? [], $data );
			$flyout->set_tab_content( $tab_id, $content );
		}
	}

	/**
	 * Save flyout data
	 *
	 * @param string $id      Flyout identifier
	 * @param array  $request Request data from AJAX
	 *
	 * @return array Response array
	 * @since 6.0.0
	 *
	 */
	private function save_flyout( string $id, array $request ): array {
		$config = $this->flyouts[ $id ];

		if ( ! $config['save_data'] ) {
			return [
				'success' => false,
				'message' => 'No save handler configured'
			];
		}

		// Parse form data
		parse_str( $request['form_data'] ?? '', $form_data );
		$data_id = $form_data['id'] ?? $request['id'] ?? null;

		// Call save handler
		$result = $this->call_data_handler( $config['save_data'], $data_id, $form_data );

		// Normalize response
		return $this->normalize_response( $result, 'Saved successfully' );
	}

	/**
	 * Delete flyout data
	 *
	 * @param string $id      Flyout identifier
	 * @param array  $request Request data from AJAX
	 *
	 * @return array Response array
	 * @since 6.0.0
	 *
	 */
	private function delete_flyout( string $id, array $request ): array {
		$config = $this->flyouts[ $id ];

		if ( ! $config['delete_data'] ) {
			return [
				'success' => false,
				'message' => 'No delete handler configured'
			];
		}

		$data_id = $request['id'] ?? null;

		// Call delete handler
		$result = $this->call_data_handler( $config['delete_data'], $data_id );

		// Normalize response
		return $this->normalize_response( $result, 'Deleted successfully' );
	}

	/**
	 * Call a data handler function
	 *
	 * @param callable|string $handler Handler function
	 * @param mixed           ...$args Arguments to pass
	 *
	 * @return mixed Handler result
	 * @since 6.0.0
	 *
	 */
	private function call_data_handler( $handler, ...$args ) {
		if ( is_callable( $handler ) ) {
			return call_user_func( $handler, ...$args );
		} elseif ( is_string( $handler ) && function_exists( $handler ) ) {
			return call_user_func( $handler, ...$args );
		}

		return false;
	}

	/**
	 * Normalize handler response
	 *
	 * @param mixed  $result          Handler result
	 * @param string $success_message Default success message
	 *
	 * @return array Normalized response array
	 * @since 6.0.0
	 *
	 */
	private function normalize_response( $result, string $success_message = 'Operation successful' ): array {
		if ( $result === true ) {
			return [
				'success' => true,
				'message' => $success_message,
				'reload'  => true
			];
		} elseif ( $result === false ) {
			return [
				'success' => false,
				'message' => 'Operation failed'
			];
		} elseif ( is_array( $result ) ) {
			return $result;
		}

		return [
			'success' => false,
			'message' => 'Invalid response from handler'
		];
	}
	
	/**
	 * Render fields from configuration
	 *
	 * @param array $fields Field configurations
	 * @param mixed $data   Data for field values
	 *
	 * @return string Generated HTML
	 * @since 6.0.0
	 */
	private function render_fields( array $fields, $data ): string {
		$output = '';

		foreach ( $fields as $field ) {
			// Extract field value from data if not already set
			if ( isset( $field['name'] ) && ! isset( $field['value'] ) ) {
				$field['value'] = $this->extract_value( $data, $field['name'] );
			}

			// Check for options (for selects, ajax_select, etc.)
			$options_key = $field['name'] . '_options';
			if ( is_array( $data ) && isset( $data[ $options_key ] ) ) {
				$field['options'] = $data[ $options_key ];
			}

			// Check if field type requires special component handling
			if ( in_array( $field['type'], self::COMPONENT_FIELD_TYPES, true ) ) {
				$output .= $this->render_component_field( $field, $data );
			} else {
				// Standard form field
				$form_field = new FormField( $field );
				$output     .= $form_field->render();
			}
		}

		return $output;
	}

	/**
	 * Render component-based field
	 *
	 * @param array $field Field configuration
	 * @param mixed $data  Data for field values
	 *
	 * @return string Generated HTML
	 * @since 6.0.0
	 */
	private function render_component_field( array $field, $data ): string {
		$type = $field['type'];

		// Pass the entire field array to the component (minus the 'type')
		$component_config = $field;
		unset( $component_config['type'] ); // Component doesn't need to know its type

		// Ensure we have the value from data if not already set
		if ( ! isset( $component_config['value'] ) && isset( $component_config['name'] ) ) {
			// For components, the value is often stored under the field name
			$component_config['items'] = $this->extract_value( $data, $component_config['name'] );
		}

		if ( $type === 'order_items' ) {
			$component = new OrderItems( $component_config );

			return $component->render();
		} elseif ( $type === 'notes' ) {
			$component = new Notes( $component_config );

			return $component->render();
		} elseif ( $type === 'files' ) {
			$component = new FileManager( $component_config );

			return $component->render();
		}

		return '';
	}

	/**
	 * Extract value from data object/array
	 *
	 * Supports direct properties, array keys, and getter methods.
	 *
	 * @param mixed  $data       Data object or array
	 * @param string $field_name Field name to extract
	 *
	 * @return mixed Extracted value or null
	 * @since 6.0.0
	 *
	 */
	private function extract_value( $data, string $field_name ) {
		if ( ! $data ) {
			return null;
		}

		// Direct object property
		if ( is_object( $data ) && property_exists( $data, $field_name ) ) {
			return $data->$field_name;
		}

		// Array key
		if ( is_array( $data ) && isset( $data[ $field_name ] ) ) {
			return $data[ $field_name ];
		}

		// Getter methods for objects
		if ( is_object( $data ) ) {
			// Try get_field_name() pattern
			$getter = 'get_' . $field_name;
			if ( method_exists( $data, $getter ) ) {
				return $data->$getter();
			}

			// Try getFieldName() pattern (camelCase)
			$camelGetter = 'get' . str_replace( '_', '', ucwords( $field_name, '_' ) );
			if ( method_exists( $data, $camelGetter ) ) {
				return $data->$camelGetter();
			}
		}

		return null;
	}

	/**
	 * Render action buttons for footer
	 *
	 * @param array $actions Action button configurations
	 *
	 * @return string Generated HTML
	 * @since 6.0.0
	 *
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
	 * @since 6.0.0
	 *
	 */
	private function get_default_actions( array $config = [] ): array {
		$actions = [];

		// Add save button if save handler exists
		if ( ! empty( $config['save_data'] ) ) {
			$actions[] = [
				'text'  => __( 'Save', 'wp-flyout' ),
				'style' => 'primary',
				'class' => 'wp-flyout-save'
			];
		}

		// Add delete button if delete handler exists
		if ( ! empty( $config['delete_data'] ) ) {
			$actions[] = [
				'text'  => __( 'Delete', 'wp-flyout' ),
				'style' => 'link-delete',
				'class' => 'wp-flyout-delete'
			];
		}

		// Always add cancel button
		$actions[] = [
			'text'  => __( 'Cancel', 'wp-flyout' ),
			'class' => 'wp-flyout-close'
		];

		return $actions;
	}

	/**
	 * Detect and register required components from configuration
	 *
	 * @param array $config Flyout configuration
	 *
	 * @return void
	 * @since 6.0.0
	 *
	 */
	private function detect_components( array $config ): void {
		$all_fields = $config['fields'];

		// Collect fields from all tabs
		foreach ( $config['tabs'] as $tab ) {
			$all_fields = array_merge( $all_fields, $tab['fields'] ?? [] );
		}

		// Detect required components from field types
		foreach ( $all_fields as $field ) {
			if ( isset( self::FIELD_COMPONENT_MAP[ $field['type'] ] ) ) {
				$this->components[] = self::FIELD_COMPONENT_MAP[ $field['type'] ];
			}
		}

		$this->components = array_unique( $this->components );
	}

	/**
	 * Register AJAX endpoints for components
	 *
	 * @param string $flyout_id Flyout identifier
	 * @param array  $config    Flyout configuration
	 *
	 * @return void
	 * @since 6.0.0
	 *
	 */
	private function register_component_endpoints( string $flyout_id, array $config ): void {
		$all_fields = $config['fields'];

		// Collect fields from all tabs
		foreach ( $config['tabs'] as $tab ) {
			$all_fields = array_merge( $all_fields, $tab['fields'] ?? [] );
		}

		// Register endpoints for components that need them
		foreach ( $all_fields as $field ) {
			if ( $field['type'] === 'ajax_select' && isset( $field['endpoint'] ) ) {
				$this->register_ajax_endpoint( $field['endpoint'], $field['search_callback'] ?? null );
			} elseif ( $field['type'] === 'order_items' && isset( $field['ajax_endpoint'] ) ) {
				$this->register_ajax_endpoint( $field['ajax_endpoint'], $field['search_callback'] ?? null );
			}
		}
	}

	/**
	 * Register a single AJAX endpoint
	 *
	 * @param string        $endpoint Endpoint name
	 * @param callable|null $callback Callback function
	 *
	 * @return void
	 * @since 6.0.0
	 *
	 */
	private function register_ajax_endpoint( string $endpoint, $callback = null ): void {
		if ( ! $callback ) {
			return;
		}

		$action = 'wp_ajax_' . $this->prefix . '_' . $endpoint;

		if ( ! has_action( $action ) ) {
			add_action( $action, $callback );
		}
	}

	/**
	 * Send AJAX response based on result type
	 *
	 * @param mixed $result Callback result
	 *
	 * @return void Sends JSON response and exits
	 * @since 6.0.0
	 *
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

		// Invalid response type
		wp_send_json_error( 'Invalid response from handler', 500 );
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
	 *
	 */
	public function button( string $flyout_id, array $data = [], array $args = [] ): void {
		echo $this->get_button( $flyout_id, $data, $args );
	}

	/**
	 * Get trigger button HTML
	 *
	 * @param string $flyout_id Flyout identifier
	 * @param array  $data      Data attributes to pass
	 * @param array  $args      {
	 *                          Button configuration
	 *
	 * @type string  $text      Button text (default: 'Open')
	 * @type string  $class     CSS class (default: 'button')
	 * @type string  $icon      Dashicon name (optional)
	 *                          }
	 * @return string Button HTML or empty string if unauthorized
	 * @since 1.0.0
	 *
	 */
	public function get_button( string $flyout_id, array $data = [], array $args = [] ): string {
		if ( ! $this->can_access( $flyout_id ) ) {
			return '';
		}

		$text  = $args['text'] ?? __( 'Open', 'wp-flyout' );
		$class = $args['class'] ?? 'button';
		$icon  = $args['icon'] ?? '';

		$attrs = $this->build_trigger_attributes( $flyout_id, $data, 'button ' . $class );

		// Build HTML
		$html = '<button type="button"';
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
	 * @param string $flyout_id Flyout identifier
	 * @param string $text      Link text
	 * @param array  $data      Data attributes to pass
	 * @param array  $args      Additional link arguments
	 *
	 * @return string Link HTML or empty string if unauthorized
	 * @since 1.0.0
	 *
	 */
	public function link( string $flyout_id, string $text, array $data = [], array $args = [] ): string {
		if ( ! $this->can_access( $flyout_id ) ) {
			return '';
		}

		$class         = $args['class'] ?? '';
		$attrs         = $this->build_trigger_attributes( $flyout_id, $data, $class );
		$attrs['href'] = '#';

		// Build HTML
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
	 * @since 6.0.0
	 *
	 */
	private function build_trigger_attributes( string $flyout_id, array $data, string $class = '' ): array {
		$attrs = [
			'class'               => trim( 'wp-flyout-trigger ' . $class ),
			'data-flyout-manager' => $this->prefix,
			'data-flyout'         => $flyout_id,
			'data-flyout-nonce'   => wp_create_nonce( 'wp_flyout_' . $this->prefix . '_' . $flyout_id ),
		];

		// Add custom data attributes
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
	 * @since 6.0.0
	 *
	 */
	private function can_access( string $flyout_id ): bool {
		if ( ! isset( $this->flyouts[ $flyout_id ] ) ) {
			return false;
		}

		$config = $this->flyouts[ $flyout_id ];

		return current_user_can( $config['capability'] );
	}

	/**
	 * Maybe enqueue assets based on current admin page
	 *
	 * @param string $hook_suffix Current admin page hook
	 *
	 * @return void
	 * @since 2.0.0
	 *
	 */
	public function maybe_enqueue_assets( string $hook_suffix ): void {
		// Skip if already enqueued
		if ( $this->assets_enqueued ) {
			return;
		}

		// Skip if no flyouts registered
		if ( empty( $this->flyouts ) ) {
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
	 *
	 */
	private function should_enqueue( string $hook_suffix ): bool {
		// Check specific configured pages first
		if ( ! empty( $this->admin_pages ) ) {
			return in_array( $hook_suffix, $this->admin_pages, true );
		}

		// Default pages where flyouts commonly appear
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

		// Check for custom admin pages
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
	 *
	 */
	public function enqueue_assets(): void {
		// Enqueue core WP Flyout assets
		Assets::enqueue();

		// Enqueue required components
		foreach ( $this->components as $component ) {
			Assets::enqueue_component( $component );
		}

		$this->assets_enqueued = true;
	}

	/**
	 * Get all registered flyouts
	 *
	 * @return array Flyout configurations
	 * @since 6.0.0
	 *
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
	 * @since 6.0.0
	 *
	 */
	public function has_flyout( string $flyout_id ): bool {
		return isset( $this->flyouts[ $flyout_id ] );
	}

	/**
	 * Get manager prefix
	 *
	 * @return string Manager prefix
	 * @since 2.0.0
	 *
	 */
	public function get_prefix(): string {
		return $this->prefix;
	}

}