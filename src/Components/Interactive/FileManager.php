<?php
/**
 * File Manager Component
 *
 * Reorderable file list with media library integration for managing
 * file bundles and attachments.
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Interactive;

use ArrayPress\WPFlyout\Traits\Renderable;

/**
 * Class FileManager
 *
 * Manages a list of files with drag-and-drop reordering and media picker integration.
 */
class FileManager {
    use Renderable;

    /**
     * Files array
     *
     * @var array
     */
    private array $files = [];

    /**
     * Input name prefix
     *
     * @var string
     */
    private string $name_prefix = 'files';

    /**
     * Component configuration
     *
     * @var array
     */
    private array $config = [
            'reorderable'      => true,
            'media_picker'     => true,
            'external_urls'    => true,
            'class'            => 'wp-flyout-file-manager',
            'empty_text'       => '',
            'add_button_text'  => '',
            'placeholder_name' => '',
            'placeholder_url'  => ''
    ];

    /**
     * Constructor
     *
     * @param array  $files       Initial files array
     * @param string $name_prefix Input name prefix
     * @param array  $config      Optional configuration
     */
    public function __construct( array $files = [], string $name_prefix = 'files', array $config = [] ) {
        $this->files       = $files;
        $this->name_prefix = $name_prefix;

        // Set default translatable strings
        $defaults = [
                'empty_text'       => __( 'No files added yet.', 'arraypress' ),
                'add_button_text'  => __( 'Add File', 'arraypress' ),
                'placeholder_name' => __( 'File name', 'arraypress' ),
                'placeholder_url'  => __( 'File URL', 'arraypress' )
        ];

        $this->config = array_merge( $this->config, $defaults, $config );

        // Ensure at least one empty row
        if ( empty( $this->files ) ) {
            $this->files[] = $this->get_empty_file();
        }
    }

    /**
     * Get empty file structure
     *
     * @return array
     */
    private function get_empty_file(): array {
        return [
                'id'   => 0,
                'name' => '',
                'url'  => ''
        ];
    }

    /**
     * Add a file
     *
     * @param array $file File data
     *
     * @return self
     */
    public function add_file( array $file ): self {
        $this->files[] = array_merge( $this->get_empty_file(), $file );

        return $this;
    }

    /**
     * Render the file manager
     *
     * @return string Generated HTML
     */
    public function render(): string {
        $classes = array_filter( [
                $this->config['class'],
                $this->config['reorderable'] ? 'reorderable' : ''
        ] );

        $template = $this->get_template();

        ob_start();
        ?>
        <div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             data-prefix="<?php echo esc_attr( $this->name_prefix ); ?>"
             data-template="<?php echo esc_attr( $template ); ?>">

            <div class="file-manager-list" <?php echo $this->config['reorderable'] ? 'data-sortable="true"' : ''; ?>>
                <?php foreach ( $this->files as $index => $file ) : ?>
                    <?php echo $this->render_file_item( $file, $index ); ?>
                <?php endforeach; ?>
            </div>

            <button type="button" class="button" data-action="add">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php echo esc_html( $this->config['add_button_text'] ); ?>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render a file item
     *
     * @param array $file  File data
     * @param int   $index Item index
     *
     * @return string Generated HTML
     */
    private function render_file_item( array $file, int $index ): string {
        ob_start();
        ?>
        <div class="file-manager-item">
            <?php if ( $this->config['reorderable'] ) : ?>
                <div class="file-handle" data-handle>
                    <span class="dashicons dashicons-menu"></span>
                </div>
            <?php endif; ?>

            <div class="file-info">
                <input type="hidden"
                       name="<?php echo esc_attr( $this->name_prefix ); ?>[<?php echo $index; ?>][id]"
                       value="<?php echo esc_attr( (string) ( $file['id'] ?? 0 ) ); ?>"
                       data-field="id">

                <input type="text"
                       name="<?php echo esc_attr( $this->name_prefix ); ?>[<?php echo $index; ?>][name]"
                       value="<?php echo esc_attr( $file['name'] ?? '' ); ?>"
                       placeholder="<?php echo esc_attr( $this->config['placeholder_name'] ); ?>"
                       data-field="name"
                       class="regular-text">

                <div class="file-url-wrapper">
                    <input type="url"
                           name="<?php echo esc_attr( $this->name_prefix ); ?>[<?php echo $index; ?>][url]"
                           value="<?php echo esc_url( $file['url'] ?? '' ); ?>"
                           placeholder="<?php echo esc_attr( $this->config['placeholder_url'] ); ?>"
                           data-field="url"
                           class="regular-text"
                            <?php echo ! $this->config['external_urls'] ? 'readonly' : ''; ?>>

                    <?php if ( $this->config['media_picker'] ) : ?>
                        <button type="button" class="button" data-action="browse"
                                title="<?php echo esc_attr__( 'Select from Media Library', 'arraypress' ); ?>">
                            <span class="dashicons dashicons-admin-media"></span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <button type="button" class="button-link" data-action="remove"
                    title="<?php echo esc_attr__( 'Remove file', 'arraypress' ); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get template for new items
     *
     * @return string HTML template
     */
    private function get_template(): string {
        ob_start();
        ?>
        <div class="file-manager-item">
            <?php if ( $this->config['reorderable'] ) : ?>
                <div class="file-handle" data-handle>
                    <span class="dashicons dashicons-menu"></span>
                </div>
            <?php endif; ?>

            <div class="file-info">
                <input type="hidden"
                       name="<?php echo esc_attr( $this->name_prefix ); ?>[{{index}}][id]"
                       value=""
                       data-field="id">

                <input type="text"
                       name="<?php echo esc_attr( $this->name_prefix ); ?>[{{index}}][name]"
                       value=""
                       placeholder="<?php echo esc_attr( $this->config['placeholder_name'] ); ?>"
                       data-field="name"
                       class="regular-text">

                <div class="file-url-wrapper">
                    <input type="url"
                           name="<?php echo esc_attr( $this->name_prefix ); ?>[{{index}}][url]"
                           value=""
                           placeholder="<?php echo esc_attr( $this->config['placeholder_url'] ); ?>"
                           data-field="url"
                           class="regular-text"
                            <?php echo ! $this->config['external_urls'] ? 'readonly' : ''; ?>>

                    <?php if ( $this->config['media_picker'] ) : ?>
                        <button type="button" class="button" data-action="browse"
                                title="<?php echo esc_attr__( 'Select from Media Library', 'arraypress' ); ?>">
                            <span class="dashicons dashicons-admin-media"></span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <button type="button" class="button-link" data-action="remove"
                    title="<?php echo esc_attr__( 'Remove file', 'arraypress' ); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>
        <?php
        return trim( preg_replace( '/\s+/', ' ', ob_get_clean() ) );
    }

    /**
     * Create a FileManager for downloads
     *
     * @param array  $files  Initial files
     * @param string $prefix Input name prefix
     *
     * @return self
     */
    public static function downloads( array $files = [], string $prefix = 'downloads' ): self {
        return new self( $files, $prefix, [
                'add_button_text'  => __( 'Add Download', 'arraypress' ),
                'placeholder_name' => __( 'Download title', 'arraypress' ),
        ] );
    }

    /**
     * Create a FileManager for resources
     *
     * @param array  $files  Initial files
     * @param string $prefix Input name prefix
     *
     * @return self
     */
    public static function resources( array $files = [], string $prefix = 'resources' ): self {
        return new self( $files, $prefix, [
                'add_button_text'  => __( 'Add Resource', 'arraypress' ),
                'placeholder_name' => __( 'Resource name', 'arraypress' ),
                'placeholder_url'  => 'https://example.com',
        ] );
    }

}