<?php
/**
 * Notes Component - Simplified
 *
 * Displays notes with optional add/delete functionality via AJAX.
 *
 * @package     ArrayPress\WPFlyout\Components\Interactive
 * @version     4.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components\Interactive;

use ArrayPress\WPFlyout\Traits\Renderable;

class Notes {
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
            'id'            => '',
            'name'          => 'notes',
            'items'         => [],
            'editable'      => true,
            'placeholder'   => 'Add a note...',
            'empty_text'    => 'No notes yet.',
            'object_type'   => '',
            'object_id'     => '',
            'add_action'    => '',      // AJAX action for adding notes
            'delete_action' => '',      // AJAX action for deleting notes
            'class'         => ''
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
            $this->config['id'] = 'notes-' . wp_generate_uuid4();
        }

        // Ensure items is array
        if ( ! is_array( $this->config['items'] ) ) {
            $this->config['items'] = [];
        }
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        $classes = [ 'wp-flyout-notes' ];
        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        // Generate nonce for AJAX actions
        $nonce = '';
        if ( $this->config['add_action'] || $this->config['delete_action'] ) {
            $nonce = wp_create_nonce( 'notes_' . $this->config['object_type'] . '_' . $this->config['object_id'] );
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             data-name="<?php echo esc_attr( $this->config['name'] ); ?>"
             data-object-type="<?php echo esc_attr( $this->config['object_type'] ); ?>"
             data-object-id="<?php echo esc_attr( $this->config['object_id'] ); ?>"
             data-add-action="<?php echo esc_attr( $this->config['add_action'] ); ?>"
             data-delete-action="<?php echo esc_attr( $this->config['delete_action'] ); ?>"
             data-nonce="<?php echo esc_attr( $nonce ); ?>">

            <div class="notes-list">
                <?php if ( empty( $this->config['items'] ) ) : ?>
                    <p class="no-notes"><?php echo esc_html( $this->config['empty_text'] ); ?></p>
                <?php else : ?>
                    <?php foreach ( $this->config['items'] as $note ) : ?>
                        <?php $this->render_note( $note ); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ( $this->config['editable'] && $this->config['add_action'] ) : ?>
                <div class="note-add-form">
                    <textarea placeholder="<?php echo esc_attr( $this->config['placeholder'] ); ?>"
                              rows="3"></textarea>
                    <p>
                        <button type="button" class="button button-primary" data-action="add-note">
                            Add Note
                        </button>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render single note
     *
     * @param array $note Note data
     */
    private function render_note( array $note ): void {
        ?>
        <div class="note-item" data-note-id="<?php echo esc_attr( $note['id'] ?? '' ); ?>">
            <div class="note-header">
                <?php if ( ! empty( $note['author'] ) ) : ?>
                    <span class="note-author"><?php echo esc_html( $note['author'] ); ?></span>
                <?php endif; ?>

                <?php if ( ! empty( $note['formatted_date'] ) ) : ?>
                    <span class="note-date"><?php echo esc_html( $note['formatted_date'] ); ?></span>
                <?php endif; ?>

                <?php if ( $this->config['editable'] && $this->config['delete_action'] && ! empty( $note['can_delete'] ) ) : ?>
                    <button type="button" class="button-link" data-action="delete-note">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                <?php endif; ?>
            </div>
            <div class="note-content">
                <?php echo nl2br( esc_html( $note['content'] ?? '' ) ); ?>
            </div>
        </div>
        <?php
    }

}