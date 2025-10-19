<?php
/**
 * Form Field Component with Helper Methods
 *
 * Basic form field rendering and utility methods for form handling.
 * Supports standard HTML5 inputs, textareas, selects, and AJAX-powered selects.
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     3.1.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Traits\Renderable;

/**
 * Class FormField
 *
 * Renders form fields and provides form utility methods.
 *
 * @since 1.0.0
 */
class FormField {
    use Renderable;

    /**
     * Field configuration
     *
     * @since 1.0.0
     * @var array
     */
    private array $field = [];

    /**
     * Constructor
     *
     * @param array $field Field configuration
     *
     * @since 1.0.0
     *
     */
    public function __construct( array $field ) {
        $type     = $field['type'] ?? 'text';
        $defaults = self::get_field_defaults( $type );

        $this->field         = array_merge( $defaults, $field );
        $this->field['type'] = $type; // Ensure type is set

        // Auto-generate ID if not provided
        if ( empty( $this->field['id'] ) && ! empty( $this->field['name'] ) ) {
            $this->field['id'] = sanitize_key( $this->field['name'] );
        }
    }

    /**
     * Get field defaults by type
     *
     * @param string $type Field type
     *
     * @return array Default configuration
     * @since 3.1.0
     *
     */
    private static function get_field_defaults( string $type ): array {
        $base_defaults = [
                'type'        => 'text',
                'name'        => '',
                'id'          => '',
                'label'       => '',
                'value'       => '',
                'description' => '',
                'placeholder' => '',
                'required'    => false,
                'disabled'    => false,
                'readonly'    => false,
                'class'       => 'regular-text',
        ];

        $type_defaults = [
                'select'      => [
                        'options'  => [],
                        'multiple' => false,
                ],
                'textarea'    => [
                        'rows' => 5,
                        'cols' => 50,
                ],
                'number'      => [
                        'min'  => null,
                        'max'  => null,
                        'step' => 1,
                ],
                'ajax_select' => [
                        'ajax_action'     => '',
                        'min_length'      => 3,
                        'delay'           => 300,
                        'initial_results' => 20,
                        'empty_option'    => null,
                        'text'            => '',
                        'nonce'           => '',
                        'ajax_url'        => '',
                ],
        ];

        return array_merge(
                $base_defaults,
                $type_defaults[ $type ] ?? []
        );
    }

    /* =========================================
       FIELD FACTORY METHODS
       ========================================= */

    /**
     * Create a text field
     *
     * @param string $name  Field name
     * @param string $label Field label
     * @param array  $args  Additional arguments
     *
     * @return self
     * @since 1.0.0
     *
     */
    public static function text( string $name, string $label, array $args = [] ): self {
        return new self( array_merge( [
                'type'  => 'text',
                'name'  => $name,
                'label' => $label
        ], $args ) );
    }

    /**
     * Create an email field
     *
     * @param string $name  Field name
     * @param string $label Field label
     * @param array  $args  Additional arguments
     *
     * @return self
     * @since 3.1.0
     *
     */
    public static function email( string $name, string $label, array $args = [] ): self {
        return new self( array_merge( [
                'type'        => 'email',
                'name'        => $name,
                'label'       => $label,
                'placeholder' => 'you@example.com'
        ], $args ) );
    }

    /**
     * Create a URL field
     *
     * @param string $name  Field name
     * @param string $label Field label
     * @param array  $args  Additional arguments
     *
     * @return self
     * @since 3.1.0
     *
     */
    public static function url( string $name, string $label, array $args = [] ): self {
        return new self( array_merge( [
                'type'        => 'url',
                'name'        => $name,
                'label'       => $label,
                'placeholder' => 'https://'
        ], $args ) );
    }

    /**
     * Create a number field
     *
     * @param string $name  Field name
     * @param string $label Field label
     * @param array  $args  Additional arguments
     *
     * @return self
     * @since 3.1.0
     *
     */
    public static function number( string $name, string $label, array $args = [] ): self {
        return new self( array_merge( [
                'type'  => 'number',
                'name'  => $name,
                'label' => $label
        ], $args ) );
    }

    /**
     * Create a telephone field
     *
     * @param string $name  Field name
     * @param string $label Field label
     * @param array  $args  Additional arguments
     *
     * @return self
     * @since 3.1.0
     *
     */
    public static function tel( string $name, string $label, array $args = [] ): self {
        return new self( array_merge( [
                'type'        => 'tel',
                'name'        => $name,
                'label'       => $label,
                'placeholder' => '+1 (555) 123-4567'
        ], $args ) );
    }

    /**
     * Create a password field
     *
     * @param string $name  Field name
     * @param string $label Field label
     * @param array  $args  Additional arguments
     *
     * @return self
     * @since 3.1.0
     *
     */
    public static function password( string $name, string $label, array $args = [] ): self {
        return new self( array_merge( [
                'type'        => 'password',
                'name'        => $name,
                'label'       => $label,
                'placeholder' => '••••••••'
        ], $args ) );
    }

    /**
     * Create a date field
     *
     * @param string $name  Field name
     * @param string $label Field label
     * @param array  $args  Additional arguments
     *
     * @return self
     * @since 3.1.0
     *
     */
    public static function date( string $name, string $label, array $args = [] ): self {
        return new self( array_merge( [
                'type'  => 'date',
                'name'  => $name,
                'label' => $label
        ], $args ) );
    }

    /**
     * Create a select field
     *
     * @param string $name    Field name
     * @param string $label   Field label
     * @param array  $options Options array
     * @param array  $args    Additional arguments
     *
     * @return self
     * @since 1.0.0
     *
     */
    public static function select( string $name, string $label, array $options, array $args = [] ): self {
        return new self( array_merge( [
                'type'    => 'select',
                'name'    => $name,
                'label'   => $label,
                'options' => $options
        ], $args ) );
    }

    /**
     * Create a textarea field
     *
     * @param string $name  Field name
     * @param string $label Field label
     * @param array  $args  Additional arguments
     *
     * @return self
     * @since 1.0.0
     *
     */
    public static function textarea( string $name, string $label, array $args = [] ): self {
        return new self( array_merge( [
                'type'  => 'textarea',
                'name'  => $name,
                'label' => $label
        ], $args ) );
    }

    /**
     * Create an AJAX select field
     *
     * @param string $name        Field name
     * @param string $label       Field label
     * @param string $ajax_action AJAX action name
     * @param array  $args        Additional arguments
     *
     * @return self
     * @since 3.1.0
     *
     */
    public static function ajax_select( string $name, string $label, string $ajax_action, array $args = [] ): self {
        return new self( array_merge( [
                'type'        => 'ajax_select',
                'name'        => $name,
                'label'       => $label,
                'ajax_action' => $ajax_action,
                'placeholder' => __( 'Type to search...', 'arraypress' ),
        ], $args ) );
    }

    /* =========================================
       FORM HELPER METHODS (Static Utilities)
       ========================================= */

    /**
     * Generate a hidden field
     *
     * @param string $name  Field name
     * @param mixed  $value Field value
     *
     * @return string HTML for hidden field
     * @since 1.0.0
     *
     */
    public static function hidden( string $name, $value ): string {
        return sprintf(
                '<input type="hidden" name="%s" value="%s" />',
                esc_attr( $name ),
                esc_attr( (string) $value )
        );
    }

    /**
     * Generate a nonce field
     *
     * @param string $action Nonce action
     * @param string $name   Field name (defaults to '_wpnonce')
     *
     * @return string HTML for nonce field
     * @since 1.0.0
     *
     */
    public static function nonce( string $action, string $name = '_wpnonce' ): string {
        return self::hidden( $name, wp_create_nonce( $action ) );
    }

    /**
     * Generate multiple hidden fields
     *
     * @param array $fields Array of name => value pairs
     *
     * @return string HTML for all hidden fields
     * @since 1.0.0
     *
     */
    public static function hidden_fields( array $fields ): string {
        $output = '';
        foreach ( $fields as $name => $value ) {
            $output .= self::hidden( $name, $value ) . "\n";
        }

        return $output;
    }

    /**
     * Generate form metadata fields (ID and nonce)
     *
     * @param string     $id_field_name Name of the ID field
     * @param int|string $id_value      Value of the ID
     * @param string     $nonce_action  Nonce action
     * @param string     $nonce_name    Nonce field name
     *
     * @return string HTML for metadata fields
     * @since 1.0.0
     *
     */
    public static function metadata(
            string $id_field_name,
            $id_value,
            string $nonce_action,
            string $nonce_name = '_wpnonce'
    ): string {
        return self::hidden( $id_field_name, $id_value ) . "\n" .
               self::nonce( $nonce_action, $nonce_name );
    }

    /**
     * Generate referer field
     *
     * @param string $name Field name (defaults to '_wp_http_referer')
     *
     * @return string HTML for referer field
     * @since 1.0.0
     *
     */
    public static function referer( string $name = '_wp_http_referer' ): string {
        $referer = wp_unslash( $_SERVER['REQUEST_URI'] ?? '' );

        return self::hidden( $name, esc_url( $referer ) );
    }

    /**
     * Generate action field for admin forms
     *
     * @param string $action Action value
     *
     * @return string HTML for action field
     * @since 1.0.0
     *
     */
    public static function action( string $action ): string {
        return self::hidden( 'action', $action );
    }

    /**
     * Generate a complete set of form security fields
     *
     * @param string $nonce_action    Nonce action
     * @param bool   $include_referer Whether to include referer field
     *
     * @return string HTML for security fields
     * @since 1.0.0
     *
     */
    public static function security( string $nonce_action, bool $include_referer = false ): string {
        $output = self::nonce( $nonce_action );

        if ( $include_referer ) {
            $output .= "\n" . self::referer();
        }

        return $output;
    }

    /* =========================================
       INSTANCE METHODS (For rendering fields)
       ========================================= */

    /**
     * Render the form field
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    public function render(): string {
        ob_start();
        ?>
        <div class="wp-flyout-field field-type-<?php echo esc_attr( $this->field['type'] ); ?>">
            <?php if ( $this->field['label'] ) : ?>
                <label for="<?php echo esc_attr( $this->field['id'] ); ?>">
                    <?php echo esc_html( $this->field['label'] ); ?>
                    <?php if ( $this->field['required'] ) : ?>
                        <span class="required">*</span>
                    <?php endif; ?>
                </label>
            <?php endif; ?>

            <?php echo $this->render_input(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

            <?php if ( $this->field['description'] ) : ?>
                <p class="description"><?php echo esc_html( $this->field['description'] ); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the input element
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    private function render_input(): string {
        switch ( $this->field['type'] ) {
            case 'select':
                return $this->render_select();
            case 'textarea':
                return $this->render_textarea();
            case 'ajax_select':
                return $this->render_ajax_select();
            default:
                return $this->render_text_input();
        }
    }

    /**
     * Render text-based input
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    private function render_text_input(): string {
        // Supported HTML5 input types
        $valid_types = [
                'text',
                'email',
                'url',
                'number',
                'tel',
                'password',
                'date',
                'datetime-local',
                'time',
                'search',
                'color',
                'range'
        ];

        $type = in_array( $this->field['type'], $valid_types, true )
                ? $this->field['type']
                : 'text';

        $attrs = [
                'type'        => $type,
                'id'          => $this->field['id'],
                'name'        => $this->field['name'],
                'value'       => $this->field['value'],
                'class'       => $this->field['class'],
                'placeholder' => $this->field['placeholder'],
        ];

        // Add type-specific attributes
        if ( $type === 'number' ) {
            if ( $this->field['min'] !== null ) {
                $attrs['min'] = $this->field['min'];
            }
            if ( $this->field['max'] !== null ) {
                $attrs['max'] = $this->field['max'];
            }
            if ( $this->field['step'] !== null ) {
                $attrs['step'] = $this->field['step'];
            }
        }

        // Build attribute string
        $attr_string = '';
        foreach ( $attrs as $key => $value ) {
            if ( $value !== '' && $value !== null ) {
                $attr_string .= sprintf( ' %s="%s"', $key, esc_attr( (string) $value ) );
            }
        }

        // Add boolean attributes
        if ( $this->field['required'] ) {
            $attr_string .= ' required';
        }
        if ( $this->field['disabled'] ) {
            $attr_string .= ' disabled';
        }
        if ( $this->field['readonly'] ) {
            $attr_string .= ' readonly';
        }

        return sprintf( '<input%s>', $attr_string );
    }

    /**
     * Render select field
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    private function render_select(): string {
        ob_start();
        ?>
        <select id="<?php echo esc_attr( $this->field['id'] ); ?>"
                name="<?php echo esc_attr( $this->field['name'] ); ?><?php echo $this->field['multiple'] ? '[]' : ''; ?>"
                class="<?php echo esc_attr( $this->field['class'] ); ?>"
                <?php echo $this->field['required'] ? 'required' : ''; ?>
                <?php echo $this->field['disabled'] ? 'disabled' : ''; ?>
                <?php echo $this->field['multiple'] ? 'multiple' : ''; ?>>
            <?php if ( $this->field['placeholder'] ) : ?>
                <option value=""><?php echo esc_html( $this->field['placeholder'] ); ?></option>
            <?php endif; ?>
            <?php foreach ( $this->field['options'] as $value => $label ) : ?>
                <option value="<?php echo esc_attr( $value ); ?>"
                        <?php
                        if ( $this->field['multiple'] && is_array( $this->field['value'] ) ) {
                            selected( in_array( $value, $this->field['value'], true ) );
                        } else {
                            selected( $this->field['value'], $value );
                        }
                        ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
        return ob_get_clean();
    }

    /**
     * Render textarea field
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    private function render_textarea(): string {
        return sprintf(
                '<textarea id="%s" name="%s" class="%s" rows="%d" cols="%d" placeholder="%s" %s %s %s>%s</textarea>',
                esc_attr( $this->field['id'] ),
                esc_attr( $this->field['name'] ),
                esc_attr( $this->field['class'] ),
                absint( $this->field['rows'] ),
                absint( $this->field['cols'] ),
                esc_attr( $this->field['placeholder'] ),
                $this->field['required'] ? 'required' : '',
                $this->field['disabled'] ? 'disabled' : '',
                $this->field['readonly'] ? 'readonly' : '',
                esc_textarea( $this->field['value'] )
        );
    }

    /**
     * Render AJAX select field
     *
     * @return string Generated HTML
     * @since 3.1.0
     *
     */
    private function render_ajax_select(): string {
        // Use the internal AjaxSelect component
        return AjaxSelect::field( [
                'name'            => $this->field['name'],
                'id'              => $this->field['id'],
                'ajax'            => $this->field['ajax_action'] ?? '',
                'placeholder'     => $this->field['placeholder'] ?? __( 'Type to search...', 'arraypress' ),
                'value'           => $this->field['value'] ?? '',
                'text'            => $this->field['text'] ?? '',
                'required'        => $this->field['required'] ?? false,
                'disabled'        => $this->field['disabled'] ?? false,
                'class'           => $this->field['class'] ?? '',
                'min_length'      => $this->field['min_length'] ?? 3,
                'delay'           => $this->field['delay'] ?? 300,
                'initial_results' => $this->field['initial_results'] ?? 20,
                'empty_option'    => $this->field['empty_option'] ?? null,
                'nonce'           => $this->field['nonce'] ?? ( ! empty( $this->field['ajax_action'] ) ? wp_create_nonce( $this->field['ajax_action'] ) : '' ),
                'ajax_url'        => $this->field['ajax_url'] ?? ''
        ] );
    }

}