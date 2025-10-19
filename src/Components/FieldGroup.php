<?php
/**
 * Field Group Component
 *
 * Groups form fields horizontally for common patterns like name fields,
 * address components, or any related input fields that should appear side-by-side.
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
 * Class FieldGroup
 *
 * Provides flexible field grouping for form layouts with responsive behavior.
 *
 * @since 1.0.0
 */
class FieldGroup {
    use Renderable;

    /**
     * Fields to group together
     *
     * @since 1.0.0
     * @var array
     */
    private array $fields = [];

    /**
     * Component configuration
     *
     * @since 1.0.0
     * @var array
     */
    private array $config = [
            'class'      => 'wp-flyout-field-group',
            'columns'    => 2,
            'gap'        => '15px',
            'responsive' => true, // Stack on mobile
            'breakpoint' => '600px'
    ];

    /**
     * Constructor
     *
     * @param array $config Configuration options
     *
     * @since 1.0.0
     *
     */
    public function __construct( array $config = [] ) {
        $this->config = array_merge( $this->config, $config );
    }

    /**
     * Add a field to the group
     *
     * @param mixed $field Field object or HTML string
     *
     * @return self
     * @since 1.0.0
     *
     */
    public function add_field( $field ): self {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * Add multiple fields at once
     *
     * @param array $fields Array of field objects or HTML
     *
     * @return self
     * @since 1.0.0
     *
     */
    public function add_fields( array $fields ): self {
        $this->fields = array_merge( $this->fields, $fields );

        return $this;
    }

    /**
     * Get the number of fields
     *
     * @return int
     * @since 1.0.0
     *
     */
    public function count(): int {
        return count( $this->fields );
    }

    /**
     * Render the field group
     *
     * @return string Generated HTML
     * @since 1.0.0
     *
     */
    public function render(): string {
        if ( empty( $this->fields ) ) {
            return '';
        }

        $style_parts = [
                'display: flex',
                'gap: ' . $this->config['gap']
        ];

        if ( $this->config['responsive'] ) {
            $style_parts[] = 'flex-wrap: wrap';
        }

        $style = implode( '; ', $style_parts );

        ob_start();
        ?>
        <div class="<?php echo esc_attr( $this->config['class'] ); ?>"
             style="<?php echo esc_attr( $style ); ?>"
             data-columns="<?php echo esc_attr( (string) $this->config['columns'] ); ?>">
            <?php foreach ( $this->fields as $field ) : ?>
                <div class="field-group-item"
                     style="flex: 1; min-width: <?php echo $this->config['responsive'] ? '200px' : '0'; ?>;">
                    <?php
                    if ( is_object( $field ) && method_exists( $field, 'render' ) ) {
                        echo $field->render();
                    } else {
                        echo $field;
                    }
                    ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Create name fields group (first and last name)
     *
     * Common pattern for name input fields.
     *
     * @param string $first_label Label for first name field
     * @param string $last_label  Label for last name field
     * @param array  $options     Additional configuration options
     *
     * @return self
     * @since 1.0.0
     *
     */
    public static function name_fields(
            string $first_label = '',
            string $last_label = '',
            array $options = []
    ): self {
        $group = new self( $options );

        $group->add_field(
                FormField::text( 'first_name', $first_label ?: __( 'First Name', 'arraypress' ), [
                        'required'    => true,
                        'placeholder' => __( 'John', 'arraypress' )
                ] )
        );

        $group->add_field(
                FormField::text( 'last_name', $last_label ?: __( 'Last Name', 'arraypress' ), [
                        'required'    => true,
                        'placeholder' => __( 'Doe', 'arraypress' )
                ] )
        );

        return $group;
    }

    /**
     * Create password fields group (password and confirmation)
     *
     * Common pattern for password with confirmation.
     *
     * @param bool  $show_strength Whether to show password strength indicator
     * @param array $options       Additional configuration options
     *
     * @return self
     * @since 1.0.0
     *
     */
    public static function password_fields( bool $show_strength = false, array $options = [] ): self {
        $group = new self( $options );

        $group->add_field(
                FormField::text( 'password', __( 'Password', 'arraypress' ), [
                        'type'        => 'password',
                        'required'    => true,
                        'placeholder' => '••••••••'
                ] )
        );

        $group->add_field(
                FormField::text( 'password_confirm', __( 'Confirm Password', 'arraypress' ), [
                        'type'        => 'password',
                        'required'    => true,
                        'placeholder' => '••••••••'
                ] )
        );

        return $group;
    }

    /**
     * Create address fields group (city and ZIP/postal code)
     *
     * Common pattern for address components.
     *
     * @param array $options Additional configuration options
     *
     * @return self
     * @since 1.0.0
     *
     */
    public static function location_fields( array $options = [] ): self {
        $group = new self( $options );

        $group->add_field(
                FormField::text( 'city', __( 'City', 'arraypress' ), [
                        'placeholder' => __( 'New York', 'arraypress' )
                ] )
        );

        $group->add_field(
                FormField::text( 'zip', __( 'ZIP Code', 'arraypress' ), [
                        'placeholder' => '10001'
                ] )
        );

        return $group;
    }

    /**
     * Create date range fields group
     *
     * Common pattern for date ranges.
     *
     * @param string $start_label Label for start date
     * @param string $end_label   Label for end date
     * @param array  $options     Additional configuration options
     *
     * @return self
     * @since 1.0.0
     *
     */
    public static function date_range_fields(
            string $start_label = '',
            string $end_label = '',
            array $options = []
    ): self {
        $group = new self( $options );

        $group->add_field(
                FormField::text( 'date_start', $start_label ?: __( 'Start Date', 'arraypress' ), [
                        'type' => 'date'
                ] )
        );

        $group->add_field(
                FormField::text( 'date_end', $end_label ?: __( 'End Date', 'arraypress' ), [
                        'type' => 'date'
                ] )
        );

        return $group;
    }

    /**
     * Static factory method for creating a new instance
     *
     * @param array $config Configuration options
     *
     * @return self
     * @since 1.0.0
     *
     */
    public static function create( array $config = [] ): self {
        return new self( $config );
    }

}