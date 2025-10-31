<?php
/**
 * CodeBlock Component
 *
 * Displays syntax-highlighted code with copy functionality.
 *
 * @package     ArrayPress\WPFlyout\Components\Interactive
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Interactive;

use ArrayPress\WPFlyout\Traits\Renderable;

class CodeBlock {
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
            'id'                => '',
            'code'              => '',
            'language'          => 'php',
            'show_line_numbers' => true,
            'show_copy_button'  => true,
            'show_highlighting' => true,
            'max_height'        => '400px',
            'title'             => '',
            'class'             => ''
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
            $this->config['id'] = 'code-block-' . wp_generate_uuid4();
        }
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        if ( empty( $this->config['code'] ) ) {
            return '';
        }

        $classes = [
                'wp-flyout-code-block',
                'language-' . $this->config['language']
        ];

        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             data-language="<?php echo esc_attr( $this->config['language'] ); ?>"
                <?php echo $this->config['show_highlighting'] ? 'data-highlight="true"' : ''; ?>>

            <?php if ( $this->config['title'] ) : ?>
                <div class="code-block-header">
                    <span class="code-block-title"><?php echo esc_html( $this->config['title'] ); ?></span>
                    <span class="code-block-language"><?php echo esc_html( strtoupper( $this->config['language'] ) ); ?></span>
                </div>
            <?php endif; ?>

            <div class="code-block-wrapper"
                    <?php if ( $this->config['max_height'] ) : ?>
                        style="max-height: <?php echo esc_attr( $this->config['max_height'] ); ?>"
                    <?php endif; ?>>

                <?php if ( $this->config['show_copy_button'] ) : ?>
                    <button type="button" class="code-block-copy" data-action="copy-code" title="Copy to clipboard">
                        <span class="dashicons dashicons-clipboard"></span>
                        <span class="copy-text">Copy</span>
                    </button>
                <?php endif; ?>

                <pre class="code-block-pre"><code
                            class="code-block-code <?php echo $this->config['show_line_numbers'] ? 'line-numbers' : ''; ?>"
                            data-language="<?php echo esc_attr( $this->config['language'] ); ?>"><?php echo esc_html( $this->config['code'] ); ?></code></pre>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

}