<?php
/**
 * Toggle Component
 *
 * WordPress-style toggle switch for boolean settings and preferences.
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
 * Class Toggle
 *
 * Renders a toggle switch control with optional label and description.
 */
class Toggle {
    use Renderable;

    /**
     * Toggle configuration
     *
     * @var array
     */
    private array $config = [
            'name'        => '',
            'id'          => '',
            'label'       => '',
            'description' => '',
            'checked'     => false,
            'value'       => '1',
            'disabled'    => false,
            'class'       => '',
            'size'        => 'default' // default, small, large
    ];

    /**
     * Constructor
     *
     * @param string $name   Field name
     * @param string $label  Toggle label
     * @param array  $config Optional configuration
     */
    public function __construct( string $name, string $label = '', array $config = [] ) {
        $this->config          = array_merge( $this->config, $config );
        $this->config['name']  = $name;
        $this->config['label'] = $label;

        // Auto-generate ID if not provided
        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = sanitize_key( $name );
        }
    }

    /**
     * Create a feature toggle
     *
     * @param string $name    Field name
     * @param string $label   Label text
     * @param bool   $checked Whether checked
     *
     * @return self
     */
    public static function feature( string $name, string $label, bool $checked = false ): self {
        return new self( $name, $label, [ 'checked' => $checked ] );
    }

    /**
     * Create a settings toggle
     *
     * @param string $name        Field name
     * @param string $label       Label text
     * @param string $description Description text
     * @param bool   $checked     Whether checked
     *
     * @return self
     */
    public static function setting( string $name, string $label, string $description = '', bool $checked = false ): self {
        return new self( $name, $label, [
                'checked'     => $checked,
                'description' => $description
        ] );
    }

    /**
     * Create a permission toggle
     *
     * @param string $name        Field name
     * @param string $label       Label text
     * @param string $description Description text
     * @param bool   $checked     Whether checked
     *
     * @return self
     */
    public static function permission( string $name, string $label, string $description = '', bool $checked = false ): self {
        return new self( $name, $label, [
                'checked'     => $checked,
                'description' => $description,
                'class'       => 'permission-toggle'
        ] );
    }

    /**
     * Create a basic toggle
     *
     * @param string $name    Field name
     * @param string $label   Toggle label
     * @param bool   $checked Whether checked
     * @param array  $config  Optional configuration
     *
     * @return self
     */
    public static function create( string $name, string $label, bool $checked = false, array $config = [] ): self {
        return new self( $name, $label, array_merge( [ 'checked' => $checked ], $config ) );
    }

    /**
     * Render the toggle
     *
     * @return string Generated HTML
     */
    public function render(): string {
        $wrapper_classes = [ 'wp-flyout-toggle-wrapper' ];
        if ( $this->config['class'] ) {
            $wrapper_classes[] = $this->config['class'];
        }

        $toggle_classes   = [ 'wp-flyout-toggle' ];
        $toggle_classes[] = 'size-' . $this->config['size'];
        if ( $this->config['disabled'] ) {
            $toggle_classes[] = 'disabled';
        }

        ob_start();
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>">
            <label class="<?php echo esc_attr( implode( ' ', $toggle_classes ) ); ?>">
                <input type="checkbox"
                       name="<?php echo esc_attr( $this->config['name'] ); ?>"
                       id="<?php echo esc_attr( $this->config['id'] ); ?>"
                       value="<?php echo esc_attr( $this->config['value'] ); ?>"
                        <?php checked( $this->config['checked'] ); ?>
                        <?php disabled( $this->config['disabled'] ); ?> />
                <span class="toggle-slider"></span>
                <?php if ( $this->config['label'] ): ?>
                    <span class="toggle-label"><?php echo esc_html( $this->config['label'] ); ?></span>
                <?php endif; ?>
            </label>
            <?php if ( $this->config['description'] ): ?>
                <p class="toggle-description"><?php echo esc_html( $this->config['description'] ); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

}