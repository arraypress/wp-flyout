<?php
/**
 * CardChoice Component
 *
 * Visual card-style checkboxes and radio buttons.
 *
 * @package     ArrayPress\WPFlyout\Components\Form
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Form;

use ArrayPress\WPFlyout\Traits\Renderable;

class CardChoice {
    use Renderable;

    /**
     * Component configuration
     *
     * @var array
     */
    private array $config;

    /**
     * Default configuration
     *
     * @var array
     */
    private const DEFAULTS = [
            'id'          => '',
            'name'        => '',
            'type'        => 'radio', // radio or checkbox
            'options'     => [],
            'value'       => null,
            'columns'     => 2,
            'required'    => false,
            'compact'     => false,
            'show_checks' => true,
            'class'       => ''
    ];

    /**
     * Constructor
     *
     * @param array $config Configuration options
     */
    public function __construct( array $config = [] ) {
        $this->config = wp_parse_args( $config, self::DEFAULTS );

        // Auto-generate ID if not provided
        if ( empty( $this->config['id'] ) ) {
            $this->config['id'] = 'card-choice-' . wp_generate_uuid4();
        }

        // Ensure value is array for checkboxes
        if ( $this->config['type'] === 'checkbox' && ! is_array( $this->config['value'] ) ) {
            $this->config['value'] = $this->config['value'] ? [ $this->config['value'] ] : [];
        }

        // Process options
        $this->config['options'] = $this->process_options( $this->config['options'] );
    }

    /**
     * Process options to ensure consistent structure
     *
     * @param array $options
     *
     * @return array
     */
    private function process_options( array $options ): array {
        $processed = [];

        foreach ( $options as $value => $option ) {
            if ( is_string( $option ) ) {
                $option = [ 'title' => $option ];
            }

            $processed[ $value ] = wp_parse_args( $option, [
                    'title'       => '',
                    'description' => '',
                    'icon'        => null,
                    'badge'       => null,
                    'badge_type'  => null,
                    'disabled'    => false
            ] );
        }

        return $processed;
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        if ( empty( $this->config['options'] ) ) {
            return '';
        }

        $classes = [
                'wp-flyout-card-choice-group',
                'columns-' . $this->config['columns']
        ];

        if ( $this->config['compact'] ) {
            $classes[] = 'compact';
        }

        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             role="<?php echo $this->config['type'] === 'radio' ? 'radiogroup' : 'group'; ?>">
            <?php foreach ( $this->config['options'] as $value => $option ) : ?>
                <?php $this->render_option( $value, $option ); ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a single option card
     *
     * @param string $value  Option value
     * @param array  $option Option configuration
     */
    private function render_option( string $value, array $option ): void {
        $is_checked = $this->is_checked( $value );
        $input_id   = sanitize_key( $this->config['name'] . '_' . $value );
        $input_name = $this->config['name'];

        // For checkboxes, use array notation
        if ( $this->config['type'] === 'checkbox' ) {
            $input_name .= '[]';
        }
        ?>
        <div class="card-choice">
            <input type="<?php echo esc_attr( $this->config['type'] ); ?>"
                   id="<?php echo esc_attr( $input_id ); ?>"
                   name="<?php echo esc_attr( $input_name ); ?>"
                   value="<?php echo esc_attr( $value ); ?>"
                    <?php checked( $is_checked ); ?>
                    <?php disabled( $option['disabled'] ); ?>
                    <?php echo $this->config['required'] ? 'required' : ''; ?>>

            <label class="card-choice-label" for="<?php echo esc_attr( $input_id ); ?>">
                <div class="card-choice-header">
                    <?php if ( ! empty( $option['icon'] ) ) : ?>
                        <div class="card-choice-icon">
                            <span class="dashicons dashicons-<?php echo esc_attr( $option['icon'] ); ?>"></span>
                        </div>
                    <?php endif; ?>

                    <div class="card-choice-content">
                        <div class="card-choice-title">
                            <?php echo esc_html( $option['title'] ); ?>

                            <?php if ( ! empty( $option['badge'] ) ) : ?>
                                <span class="card-choice-badge <?php echo $option['badge_type'] ? 'badge-' . esc_attr( $option['badge_type'] ) : ''; ?>">
									<?php echo esc_html( $option['badge'] ); ?>
								</span>
                            <?php endif; ?>
                        </div>

                        <?php if ( ! empty( $option['description'] ) ) : ?>
                            <p class="card-choice-description">
                                <?php echo esc_html( $option['description'] ); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php if ( $this->config['show_checks'] ) : ?>
                        <div class="card-choice-check"></div>
                    <?php endif; ?>
                </div>
            </label>
        </div>
        <?php
    }

    /**
     * Check if option is selected
     *
     * @param string $value Option value
     *
     * @return bool
     */
    private function is_checked( string $value ): bool {
        if ( $this->config['type'] === 'checkbox' ) {
            return is_array( $this->config['value'] ) && in_array( $value, $this->config['value'], true );
        }

        return $this->config['value'] === $value;
    }

}