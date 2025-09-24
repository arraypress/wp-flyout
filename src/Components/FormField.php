<?php
/**
 * Form Field Component
 *
 * Consistent form field rendering with labels, descriptions, and validation states.
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Traits\Renderable;

/**
 * Class FormField
 *
 * Renders consistent form fields with various input types and configurations.
 */
class FormField {
    use Renderable;

    /**
     * Field configuration
     *
     * @var array
     */
    private array $field = [];

    /**
     * Default field configuration
     *
     * @var array
     */
    private static array $defaults = [
            'type'          => 'text',
            'name'          => '',
            'id'            => '',
            'label'         => '',
            'value'         => '',
            'description'   => '',
            'placeholder'   => '',
            'required'      => false,
            'disabled'      => false,
            'readonly'      => false,
            'class'         => '',
            'wrapper_class' => '',
            'options'       => [],
            'multiple'      => false,
            'rows'          => 5,
            'min'           => null,
            'max'           => null,
            'step'          => null,
            'pattern'       => null,
            'ajax'          => null, // For wp-ajax-select integration
            'nonce'         => ''
    ];

    /**
     * Constructor
     *
     * @param array $field Field configuration
     */
    public function __construct( array $field ) {
        $this->field = array_merge( self::$defaults, $field );

        // Auto-generate ID if not provided
        if ( empty( $this->field['id'] ) && ! empty( $this->field['name'] ) ) {
            $this->field['id'] = sanitize_key( $this->field['name'] );
        }
    }

    /**
     * Create a text field
     *
     * @param string $name  Field name
     * @param string $label Field label
     * @param array  $args  Additional arguments
     *
     * @return self
     */
    public static function text( string $name, string $label, array $args = [] ): self {
        return new self( array_merge( [ 'type' => 'text', 'name' => $name, 'label' => $label ], $args ) );
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
     * Create an AJAX select field
     *
     * @param string $name  Field name
     * @param string $label Field label
     * @param string $ajax  AJAX action
     * @param array  $args  Additional arguments
     *
     * @return self
     */
    public static function ajax_select( string $name, string $label, string $ajax, array $args = [] ): self {
        return new self( array_merge( [
                'type'  => 'ajax_select',
                'name'  => $name,
                'label' => $label,
                'ajax'  => $ajax
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
     */
    public static function email( string $name, string $label, array $args = [] ): self {
        return new self( array_merge( [ 'type' => 'email', 'name' => $name, 'label' => $label ], $args ) );
    }

    /**
     * Create a textarea field
     *
     * @param string $name  Field name
     * @param string $label Field label
     * @param array  $args  Additional arguments
     *
     * @return self
     */
    public static function textarea( string $name, string $label, array $args = [] ): self {
        return new self( array_merge( [ 'type' => 'textarea', 'name' => $name, 'label' => $label ], $args ) );
    }

    /**
     * Render the form field
     *
     * @return string Generated HTML
     */
    public function render(): string {
        $wrapper_classes = [ 'wp-flyout-form-field', $this->field['wrapper_class'] ];
        if ( $this->field['required'] ) {
            $wrapper_classes[] = 'required';
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr( implode( ' ', array_filter( $wrapper_classes ) ) ); ?>">
            <?php if ( $this->field['label'] ): ?>
                <label for="<?php echo esc_attr( $this->field['id'] ); ?>">
                    <?php echo esc_html( $this->field['label'] ); ?>
                    <?php if ( $this->field['required'] ): ?>
                        <span class="required">*</span>
                    <?php endif; ?>
                </label>
            <?php endif; ?>

            <?php echo $this->render_input(); ?>

            <?php if ( $this->field['description'] ): ?>
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
     */
    private function render_input(): string {
        switch ( $this->field['type'] ) {
            case 'select':
                return $this->render_select();
            case 'ajax_select':
                return $this->render_ajax_select();
            case 'textarea':
                return $this->render_textarea();
            case 'checkbox':
                return $this->render_checkbox();
            case 'radio':
                return $this->render_radio();
            case 'number':
                return $this->render_number();
            default:
                return $this->render_text();
        }
    }

    /**
     * Render text input
     *
     * @return string Generated HTML
     */
    private function render_text(): string {
        $type       = in_array( $this->field['type'], [ 'email', 'url', 'tel' ] ) ? $this->field['type'] : 'text';
        $attributes = $this->get_input_attributes();

        ob_start();
        ?>
        <input type="<?php echo esc_attr( $type ); ?>" <?php echo $this->render_attributes( $attributes ); ?> />
        <?php
        return ob_get_clean();
    }

    /**
     * Render number input
     *
     * @return string Generated HTML
     */
    private function render_number(): string {
        $attributes = $this->get_input_attributes();

        if ( $this->field['min'] !== null ) {
            $attributes['min'] = $this->field['min'];
        }
        if ( $this->field['max'] !== null ) {
            $attributes['max'] = $this->field['max'];
        }
        if ( $this->field['step'] !== null ) {
            $attributes['step'] = $this->field['step'];
        }

        ob_start();
        ?>
        <input type="number" <?php echo $this->render_attributes( $attributes ); ?> />
        <?php
        return ob_get_clean();
    }

    /**
     * Render textarea
     *
     * @return string Generated HTML
     */
    private function render_textarea(): string {
        $attributes         = $this->get_input_attributes();
        $attributes['rows'] = $this->field['rows'];

        ob_start();
        ?>
        <textarea <?php echo $this->render_attributes( $attributes ); ?>><?php echo esc_textarea( $this->field['value'] ); ?></textarea>
        <?php
        return ob_get_clean();
    }

    /**
     * Render select
     *
     * @return string Generated HTML
     */
    private function render_select(): string {
        $attributes = $this->get_input_attributes();

        if ( $this->field['multiple'] ) {
            $attributes['multiple'] = 'multiple';
            $attributes['name']     .= '[]';
        }

        ob_start();
        ?>
        <select <?php echo $this->render_attributes( $attributes ); ?>>
            <?php if ( ! $this->field['multiple'] && $this->field['placeholder'] ): ?>
                <option value=""><?php echo esc_html( $this->field['placeholder'] ); ?></option>
            <?php endif; ?>

            <?php foreach ( $this->field['options'] as $value => $label ): ?>
                <?php
                $selected = $this->field['multiple']
                        ? in_array( $value, (array) $this->field['value'] )
                        : $value == $this->field['value'];
                ?>
                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
        return ob_get_clean();
    }

    /**
     * Render AJAX select
     *
     * @return string Generated HTML
     */
    private function render_ajax_select(): string {
        // Use wp-ajax-select if available
        if ( function_exists( 'wp_ajax_select' ) ) {
            return wp_ajax_select( [
                    'name'        => $this->field['name'],
                    'id'          => $this->field['id'],
                    'ajax'        => $this->field['ajax'],
                    'placeholder' => $this->field['placeholder'],
                    'value'       => $this->field['value'],
                    'nonce'       => $this->field['nonce'] ?: wp_create_nonce( $this->field['ajax'] ),
                    'required'    => $this->field['required'],
                    'disabled'    => $this->field['disabled'],
                    'class'       => $this->field['class']
            ] );
        }

        // Fallback to regular select
        return $this->render_select();
    }

    /**
     * Render checkbox
     *
     * @return string Generated HTML
     */
    private function render_checkbox(): string {
        ob_start();
        ?>
        <label class="checkbox-label">
            <input type="checkbox"
                   name="<?php echo esc_attr( $this->field['name'] ); ?>"
                   id="<?php echo esc_attr( $this->field['id'] ); ?>"
                   value="1"
                    <?php checked( $this->field['value'] ); ?>
                    <?php disabled( $this->field['disabled'] ); ?> />
            <?php if ( $this->field['description'] ): ?>
                <?php echo esc_html( $this->field['description'] ); ?>
            <?php endif; ?>
        </label>
        <?php
        return ob_get_clean();
    }

    /**
     * Render radio buttons
     *
     * @return string Generated HTML
     */
    private function render_radio(): string {
        ob_start();
        ?>
        <div class="radio-group">
            <?php foreach ( $this->field['options'] as $value => $label ): ?>
                <label class="radio-label">
                    <input type="radio"
                           name="<?php echo esc_attr( $this->field['name'] ); ?>"
                           value="<?php echo esc_attr( $value ); ?>"
                            <?php checked( $this->field['value'], $value ); ?>
                            <?php disabled( $this->field['disabled'] ); ?> />
                    <?php echo esc_html( $label ); ?>
                </label>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get input attributes
     *
     * @return array Attributes array
     */
    private function get_input_attributes(): array {
        $attributes = [
                'name'  => $this->field['name'],
                'id'    => $this->field['id'],
                'value' => $this->field['value'],
                'class' => $this->field['class'] ?: 'regular-text'
        ];

        if ( $this->field['placeholder'] ) {
            $attributes['placeholder'] = $this->field['placeholder'];
        }

        if ( $this->field['required'] ) {
            $attributes['required'] = 'required';
        }

        if ( $this->field['disabled'] ) {
            $attributes['disabled'] = 'disabled';
        }

        if ( $this->field['readonly'] ) {
            $attributes['readonly'] = 'readonly';
        }

        if ( $this->field['pattern'] ) {
            $attributes['pattern'] = $this->field['pattern'];
        }

        return $attributes;
    }

    /**
     * Render HTML attributes
     *
     * @param array $attributes Attributes array
     *
     * @return string HTML attributes string
     */
    private function render_attributes( array $attributes ): string {
        $output = [];
        foreach ( $attributes as $key => $value ) {
            if ( is_bool( $value ) ) {
                if ( $value ) {
                    $output[] = $key;
                }
            } else {
                $output[] = sprintf( '%s="%s"', $key, esc_attr( $value ) );
            }
        }

        return implode( ' ', $output );
    }

}