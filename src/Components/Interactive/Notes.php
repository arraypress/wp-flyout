<?php
/**
 * Notes Component
 *
 * Displays notes/comments with optional add/edit functionality.
 *
 * @package     ArrayPress\WPFlyout\Components\Interactive
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     3.0.0
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
            'id'          => '',
            'name'        => 'notes',
            'items'       => [],
            'editable'    => false,
            'show_author' => true,
            'show_date'   => true,
            'date_format' => 'M j, Y g:i A',
            'empty_text'  => 'No notes yet.',
            'placeholder' => 'Add a note...',
            'button_text' => 'Add Note',
            'class'       => '',
        // AJAX-related attributes
            'object_type' => '',
            'object_id'   => '',
            'author_name' => '',
            'ajax_action' => '',
            'nonce'       => ''
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

        // Normalize notes
        $this->config['items'] = $this->normalize_notes( $this->config['items'] );
    }

    /**
     * Normalize note data
     *
     * @param array $notes Raw notes array
     *
     * @return array
     */
    private function normalize_notes( array $notes ): array {
        $normalized = [];

        foreach ( $notes as $note ) {
            if ( is_string( $note ) ) {
                $note = [ 'content' => $note ];
            }

            $normalized[] = wp_parse_args( $note, [
                    'id'        => uniqid(),
                    'content'   => '',
                    'author'    => '',
                    'date'      => current_time( 'mysql' ),
                    'deletable' => false
            ] );
        }

        return $normalized;
    }

    /**
     * Render the component
     *
     * @return string
     */
    public function render(): string {
        $classes = [ 'wp-flyout-notes-panel' ];
        if ( ! empty( $this->config['class'] ) ) {
            $classes[] = $this->config['class'];
        }

        ob_start();
        ?>
        <div id="<?php echo esc_attr( $this->config['id'] ); ?>"
             class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
             data-name="<?php echo esc_attr( $this->config['name'] ); ?>"
             data-object-type="<?php echo esc_attr( $this->config['object_type'] ); ?>"
             data-object-id="<?php echo esc_attr( $this->config['object_id'] ); ?>"
             data-author-name="<?php echo esc_attr( $this->config['author_name'] ); ?>"
             data-action="<?php echo esc_attr( $this->config['ajax_action'] ); ?>"
             data-nonce="<?php echo esc_attr( $this->config['nonce'] ); ?>"
             data-note-count="<?php echo count( $this->config['items'] ); ?>">

            <div class="notes-list">
                <?php if ( empty( $this->config['items'] ) ) : ?>
                    <p class="no-notes"><?php echo esc_html( $this->config['empty_text'] ); ?></p>
                <?php else : ?>
                    <?php foreach ( $this->config['items'] as $note ) : ?>
                        <?php $this->render_note( $note ); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ( $this->config['editable'] ) : ?>
                <div class="note-add-form">
					<textarea class="note-input"
                              name="<?php echo esc_attr( $this->config['name'] ); ?>_new"
                              placeholder="<?php echo esc_attr( $this->config['placeholder'] ); ?>"
                              rows="3"></textarea>
                    <button type="button" class="button button-primary" data-action="add-note">
                        <?php echo esc_html( $this->config['button_text'] ); ?>
                    </button>
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
        <div class="note-item" data-note-id="<?php echo esc_attr( $note['id'] ); ?>">
            <div class="note-header">
                <?php if ( $this->config['show_author'] && $note['author'] ) : ?>
                    <span class="note-author"><?php echo esc_html( $note['author'] ); ?></span>
                <?php endif; ?>

                <?php if ( $this->config['show_date'] && $note['date'] ) : ?>
                    <span class="note-date">
						<?php echo esc_html( date( $this->config['date_format'], strtotime( $note['date'] ) ) ); ?>
					</span>
                <?php endif; ?>

                <?php if ( $this->config['editable'] && $note['deletable'] ) : ?>
                    <button type="button" class="button-link" data-action="delete-note" data-confirm="Are you sure?">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                <?php endif; ?>
            </div>
            <div class="note-content">
                <?php echo wp_kses_post( nl2br( $note['content'] ) ); ?>
            </div>
        </div>
        <?php
    }

}