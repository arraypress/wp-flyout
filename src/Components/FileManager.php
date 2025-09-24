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

namespace ArrayPress\WPFlyout\Components;

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
            'empty_text'       => 'No files added yet.',
            'add_button_text'  => 'Add File',
            'placeholder_name' => 'File name',
            'placeholder_url'  => 'File URL'
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
        $this->config      = array_merge( $this->config, $config );

        // Ensure at least one empty item
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
                'lookup_key'    => '',
                'name'          => '',
                'url'           => '',
                'attachment_id' => 0
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
        $classes = [
                $this->config['class'],
                $this->config['reorderable'] ? 'reorderable' : ''
        ];

        ob_start();
        ?>
        <div class="<?php echo esc_attr( implode( ' ', array_filter( $classes ) ) ); ?>"
             data-name-prefix="<?php echo esc_attr( $this->name_prefix ); ?>">

            <div class="file-manager-list" <?php echo $this->config['reorderable'] ? 'data-sortable="true"' : ''; ?>>
                <?php foreach ( $this->files as $index => $file ): ?>
                    <?php echo $this->render_file_item( $file, $index ); ?>
                <?php endforeach; ?>
            </div>

            <button type="button" class="button add-file-button">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php echo esc_html( $this->config['add_button_text'] ); ?>
            </button>

            <?php echo $this->render_template(); ?>
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
        $lookup_key = $file['lookup_key'] ?? '';
        $is_first   = $index === 0;

        ob_start();
        ?>
        <div class="file-manager-item"
             data-index="<?php echo esc_attr( (string) $index ); ?>"
             data-lookup-key="<?php echo esc_attr( $lookup_key ); ?>"
             data-first="<?php echo $is_first ? 'true' : 'false'; ?>">

            <?php if ( $this->config['reorderable'] ): ?>
                <div class="file-handle">
                    <span class="dashicons dashicons-menu"></span>
                </div>
            <?php endif; ?>

            <div class="file-info">
                <!-- Hidden lookup key for tracking existing vs new files -->
                <input type="hidden"
                       name="<?php echo esc_attr( $this->name_prefix ); ?>[<?php echo $index; ?>][lookup_key]"
                       value="<?php echo esc_attr( $lookup_key ); ?>"
                       class="file-lookup-key"/>

                <input type="hidden"
                       name="<?php echo esc_attr( $this->name_prefix ); ?>[<?php echo $index; ?>][attachment_id]"
                       value="<?php echo esc_attr( (string) ( $file['attachment_id'] ?? 0 ) ); ?>"
                       class="file-attachment-id"/>

                <div class="file-details">
                    <input type="text"
                           name="<?php echo esc_attr( $this->name_prefix ); ?>[<?php echo $index; ?>][name]"
                           value="<?php echo esc_attr( $file['name'] ?? '' ); ?>"
                           placeholder="<?php echo esc_attr( $this->config['placeholder_name'] ); ?>"
                           class="file-name-input"/>

                    <div class="file-url-wrapper">
                        <input type="text"
                               name="<?php echo esc_attr( $this->name_prefix ); ?>[<?php echo $index; ?>][url]"
                               value="<?php echo esc_url( $file['url'] ?? '' ); ?>"
                               placeholder="<?php echo esc_attr( $this->config['placeholder_url'] ); ?>"
                               class="file-url-input"
                                <?php echo ! $this->config['external_urls'] ? 'readonly' : ''; ?> />

                        <?php if ( $this->config['media_picker'] ): ?>
                            <button type="button" class="button select-file-media"
                                    title="<?php esc_attr_e( 'Select from Media Library', 'wp-flyout' ); ?>">
                                <span class="dashicons dashicons-admin-media"></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="file-actions">
                <?php if ( $is_first ): ?>
                    <!-- Clear button for first item -->
                    <button type="button" class="button-link clear-file"
                            title="<?php esc_attr_e( 'Clear file', 'wp-flyout' ); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                <?php else: ?>
                    <!-- Remove button for other items -->
                    <button type="button" class="button-link remove-file"
                            title="<?php esc_attr_e( 'Remove file', 'wp-flyout' ); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render JavaScript template
     *
     * @return string Generated HTML
     */
    private function render_template(): string {
        ob_start();
        ?>
        <script type="text/template" class="file-item-template">
            <div class="file-manager-item" data-index="{{index}}" data-lookup-key="">
                <?php if ( $this->config['reorderable'] ): ?>
                    <div class="file-handle">
                        <span class="dashicons dashicons-menu"></span>
                    </div>
                <?php endif; ?>

                <div class="file-info">
                    <input type="hidden"
                           name="<?php echo esc_attr( $this->name_prefix ); ?>[{{index}}][lookup_key]"
                           value=""
                           class="file-lookup-key"/>
                    <input type="hidden"
                           name="<?php echo esc_attr( $this->name_prefix ); ?>[{{index}}][attachment_id]"
                           value=""
                           class="file-attachment-id"/>

                    <div class="file-details">
                        <input type="text"
                               name="<?php echo esc_attr( $this->name_prefix ); ?>[{{index}}][name]"
                               value=""
                               placeholder="<?php echo esc_attr( $this->config['placeholder_name'] ); ?>"
                               class="file-name-input"/>

                        <div class="file-url-wrapper">
                            <input type="text"
                                   name="<?php echo esc_attr( $this->name_prefix ); ?>[{{index}}][url]"
                                   value=""
                                   placeholder="<?php echo esc_attr( $this->config['placeholder_url'] ); ?>"
                                   class="file-url-input"/>

                            <?php if ( $this->config['media_picker'] ): ?>
                                <button type="button" class="button select-file-media"
                                        title="<?php esc_attr_e( 'Select from Media Library', 'wp-flyout' ); ?>">
                                    <span class="dashicons dashicons-admin-media"></span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="file-actions">
                    <button type="button" class="button-link remove-file"
                            title="<?php esc_attr_e( 'Remove file', 'wp-flyout' ); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
            </div>
        </script>
        <?php
        return ob_get_clean();
    }
}