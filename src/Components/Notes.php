<?php
/**
 * Notes Panel Component
 *
 * Displays notes/comments in a chronological list with optional add/delete UI.
 * All data handling is done by the implementing plugin via callbacks.
 *
 * @package     ArrayPress\WPFlyout\Components
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     2.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\WPFlyout\Components;

use ArrayPress\WPFlyout\Traits\Renderable;

/**
 * Class NotesPanel
 */
class Notes {
    use Renderable;

    /**
     * Notes array
     * @var array
     */
    private array $notes = [];

    /**
     * Panel configuration
     * @var array
     */
    private array $config = [
            'class'          => 'wp-flyout-notes-panel',
            'editable'       => false,
            'empty_text'     => 'No notes yet.',
            'placeholder'    => 'Add a note...',
            'button_text'    => 'Add Note',
            'confirm_delete' => 'Delete this note?',
        // Data attributes for JS
            'data'           => []
    ];

    /**
     * Constructor
     */
    public function __construct( array $notes = [], array $config = [] ) {
        $this->notes  = $notes;
        $this->config = array_merge( $this->config, $config );
    }

    /**
     * Add a note
     */
    public function add_note( string $content, array $meta = [] ): self {
        $this->notes[] = array_merge( [
                'id'        => uniqid(),
                'content'   => $content,
                'author'    => '',
                'date'      => '',
                'deletable' => $this->config['editable']
        ], $meta );

        return $this;
    }

    /**
     * Render the panel
     */
    public function render(): string {
        ob_start();
        ?>
        <div class="<?php echo esc_attr( $this->config['class'] ); ?>"
                <?php echo $this->render_data_attributes(); ?>>

            <div class="notes-list">
                <?php if ( empty( $this->notes ) ) : ?>
                    <p class="no-notes"><?php echo esc_html( $this->config['empty_text'] ); ?></p>
                <?php else : ?>
                    <?php foreach ( $this->notes as $note ) : ?>
                        <?php echo $this->render_note( $note ); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ( $this->config['editable'] ) : ?>
                <div class="note-add-form">
					<textarea class="note-input"
                              placeholder="<?php echo esc_attr( $this->config['placeholder'] ); ?>"
                              data-field="content"></textarea>
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
     * Render a single note
     */
    private function render_note( array $note ): string {
        ob_start();
        ?>
        <div class="note-item" data-note-id="<?php echo esc_attr( $note['id'] ); ?>">
            <div class="note-header">
                <?php if ( ! empty( $note['author'] ) ) : ?>
                    <span class="note-author"><?php echo esc_html( $note['author'] ); ?></span>
                <?php endif; ?>

                <?php if ( ! empty( $note['date'] ) ) : ?>
                    <span class="note-date"><?php echo esc_html( $note['date'] ); ?></span>
                <?php endif; ?>

                <?php if ( ! empty( $note['deletable'] ) && $this->config['editable'] ) : ?>
                    <button type="button" class="button-link" data-action="delete-note"
                            data-confirm="<?php echo esc_attr( $this->config['confirm_delete'] ); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                <?php endif; ?>
            </div>
            <div class="note-content">
                <?php echo wp_kses_post( $note['content'] ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render data attributes
     */
    private function render_data_attributes(): string {
        if ( empty( $this->config['data'] ) ) {
            return '';
        }

        $attrs = [];
        foreach ( $this->config['data'] as $key => $value ) {
            $attrs[] = sprintf( 'data-%s="%s"', esc_attr( $key ), esc_attr( $value ) );
        }

        return implode( ' ', $attrs );
    }

    /**
     * Static factory for activity log
     */
    public static function activity_log( array $entries = [] ): self {
        return new self( $entries, [
                'editable'   => false,
                'empty_text' => 'No activity yet.'
        ] );
    }

    /**
     * Static factory for editable notes
     */
    public static function editable( array $notes = [], array $data = [] ): self {
        return new self( $notes, [
                'editable' => true,
                'data'     => $data
        ] );
    }

}