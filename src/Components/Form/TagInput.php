<?php
/**
 * Tag Input Component
 *
 * Interactive tag input with keyboard support for adding/removing tags.
 * Perfect for keywords, emails, categories, and multi-value inputs.
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Form;

use ArrayPress\WPFlyout\Traits\Renderable;

/**
 * Class TagInput
 *
 * Creates interactive tag input fields with keyboard controls.
 *
 * @since 1.0.0
 */
class TagInput {
	use Renderable;

	/**
	 * Component configuration
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $config = [
		'name'             => '',
		'id'               => '',
		'label'            => '',
		'description'      => '',
		'value'            => [],
		'placeholder'      => '',
		'max_tags'         => 0, // 0 = unlimited
		'min_tags'         => 0,
		'max_length'       => 0, // per tag, 0 = unlimited
		'allow_duplicates' => false,
		'case_sensitive'   => false,
		'delimiter'        => ',', // for parsing pasted text
		'autocomplete'     => [], // optional autocomplete suggestions
		'class'            => 'wp-flyout-tag-input',
		'readonly'         => false,
		'required'         => false
	];

	/**
	 * Constructor
	 *
	 * @param string $name   Field name
	 * @param string $label  Field label
	 * @param array  $config Configuration options
	 *
	 * @since 1.0.0
	 */
	public function __construct( string $name, string $label = '', array $config = [] ) {
		$this->config          = array_merge( $this->config, $config );
		$this->config['name']  = $name;
		$this->config['label'] = $label;

		// Auto-generate ID if not provided
		if ( empty( $this->config['id'] ) ) {
			$this->config['id'] = sanitize_key( $name );
		}

		// Ensure value is array
		if ( ! is_array( $this->config['value'] ) ) {
			$this->config['value'] = $this->config['value'] ? [ $this->config['value'] ] : [];
		}

		// Set default placeholder
		if ( empty( $this->config['placeholder'] ) ) {
			$this->config['placeholder'] = __( 'Add tag...', 'arraypress' );
		}
	}

	/**
	 * Create a keyword tag input
	 *
	 * @param string $name  Field name
	 * @param string $label Field label
	 * @param array  $value Initial tags
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function keywords( string $name, string $label, array $value = [] ): self {
		return new self( $name, $label, [
			'value'       => $value,
			'placeholder' => __( 'Add keyword...', 'arraypress' ),
			'max_length'  => 50
		] );
	}

	/**
	 * Create an email tag input
	 *
	 * @param string $name  Field name
	 * @param string $label Field label
	 * @param array  $value Initial emails
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function emails( string $name, string $label, array $value = [] ): self {
		return new self( $name, $label, [
			'value'            => $value,
			'placeholder'      => __( 'Add email...', 'arraypress' ),
			'allow_duplicates' => false,
			'case_sensitive'   => false
		] );
	}

	/**
	 * Create a category tag input
	 *
	 * @param string $name         Field name
	 * @param string $label        Field label
	 * @param array  $value        Initial categories
	 * @param array  $autocomplete Available categories
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public static function categories( string $name, string $label, array $value = [], array $autocomplete = [] ): self {
		return new self( $name, $label, [
			'value'        => $value,
			'placeholder'  => __( 'Add category...', 'arraypress' ),
			'autocomplete' => $autocomplete
		] );
	}

	/**
	 * Set maximum number of tags
	 *
	 * @param int $max Maximum tags (0 = unlimited)
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public function max_tags( int $max ): self {
		$this->config['max_tags'] = $max;

		return $this;
	}

	/**
	 * Set whether to allow duplicate tags
	 *
	 * @param bool $allow Allow duplicates
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public function allow_duplicates( bool $allow = true ): self {
		$this->config['allow_duplicates'] = $allow;

		return $this;
	}

	/**
	 * Set autocomplete suggestions
	 *
	 * @param array $suggestions List of suggestions
	 *
	 * @return self
	 * @since 1.0.0
	 */
	public function autocomplete( array $suggestions ): self {
		$this->config['autocomplete'] = $suggestions;

		return $this;
	}

	/**
	 * Render the tag input
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 */
	public function render(): string {
		ob_start();
		?>
        <div class="wp-flyout-field field-type-tag-input">
			<?php if ( $this->config['label'] ) : ?>
                <label for="<?php echo esc_attr( $this->config['id'] ); ?>">
					<?php echo esc_html( $this->config['label'] ); ?>
					<?php if ( $this->config['required'] ) : ?>
                        <span class="required">*</span>
					<?php endif; ?>
                </label>
			<?php endif; ?>

            <div class="<?php echo esc_attr( $this->config['class'] ); ?>"
                 data-name="<?php echo esc_attr( $this->config['name'] ); ?>"
                 data-max-tags="<?php echo esc_attr( (string) $this->config['max_tags'] ); ?>"
                 data-min-tags="<?php echo esc_attr( (string) $this->config['min_tags'] ); ?>"
                 data-max-length="<?php echo esc_attr( (string) $this->config['max_length'] ); ?>"
                 data-allow-duplicates="<?php echo esc_attr( $this->config['allow_duplicates'] ? 'true' : 'false' ); ?>"
                 data-case-sensitive="<?php echo esc_attr( $this->config['case_sensitive'] ? 'true' : 'false' ); ?>"
                 data-delimiter="<?php echo esc_attr( $this->config['delimiter'] ); ?>"
				<?php echo ! empty( $this->config['autocomplete'] ) ? 'data-autocomplete="' . esc_attr( wp_json_encode( $this->config['autocomplete'] ) ) . '"' : ''; ?>>

                <div class="tag-input-container">
                    <!-- Existing tags -->
					<?php foreach ( $this->config['value'] as $tag ) : ?>
						<?php echo $this->render_tag( $tag ); ?>
					<?php endforeach; ?>

                    <!-- Input for new tags -->
                    <input type="text"
                           class="tag-input-field"
                           placeholder="<?php echo esc_attr( $this->config['placeholder'] ); ?>"
                           autocomplete="off"
						<?php echo $this->config['readonly'] ? 'readonly' : ''; ?>>
                </div>

                <!-- Hidden inputs for form submission -->
				<?php foreach ( $this->config['value'] as $index => $tag ) : ?>
                    <input type="hidden"
                           name="<?php echo esc_attr( $this->config['name'] ); ?>[]"
                           value="<?php echo esc_attr( $tag ); ?>"
                           data-tag-value>
				<?php endforeach; ?>

                <!-- Autocomplete dropdown -->
				<?php if ( ! empty( $this->config['autocomplete'] ) ) : ?>
                    <div class="tag-autocomplete" style="display: none;"></div>
				<?php endif; ?>
            </div>

			<?php if ( $this->config['description'] ) : ?>
                <p class="description"><?php echo esc_html( $this->config['description'] ); ?></p>
			<?php endif; ?>
        </div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a single tag
	 *
	 * @param string $tag Tag text
	 *
	 * @return string Generated HTML
	 * @since 1.0.0
	 */
	private function render_tag( string $tag ): string {
		ob_start();
		?>
        <span class="tag-item" data-tag="<?php echo esc_attr( $tag ); ?>">
			<span class="tag-text"><?php echo esc_html( $tag ); ?></span>
			<?php if ( ! $this->config['readonly'] ) : ?>
                <button type="button" class="tag-remove"
                        aria-label="<?php esc_attr_e( 'Remove tag', 'arraypress' ); ?>">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			<?php endif; ?>
		</span>
		<?php
		return ob_get_clean();
	}

}