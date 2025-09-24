/**
 * WP Flyout Notes Component
 */
(function ($) {
    'use strict';

    const WPFlyoutNotes = {

        init: function() {
            this.bindEvents();
        },

        initIn: function($container) {
            // Re-bind events for newly loaded content
            this.bindEvents($container);
        },

        bindEvents: function($context) {
            const $root = $context || $(document);

            // Add note
            $root.off('click.notes', '.wp-flyout-notes-panel .add-note').on('click.notes', '.wp-flyout-notes-panel .add-note', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const $button = $(this);
                const $panel = $button.closest('.wp-flyout-notes-panel');
                const $textarea = $panel.find('.note-input');
                const content = $textarea.val().trim();

                if (!content) {
                    $textarea.focus();
                    return false;
                }

                const ajaxPrefix = $panel.data('ajax-prefix');
                const nonce = $panel.data('nonce');
                const objectType = $panel.data('object-type');
                const objectId = $panel.data('object-id');

                $button.prop('disabled', true);

                $.ajax({
                    url: wpFlyoutConfig.ajaxUrl || ajaxurl,
                    type: 'POST',
                    data: {
                        action: ajaxPrefix + '_add',
                        _wpnonce: nonce,
                        content: content,
                        object_type: objectType,
                        object_id: objectId
                    },
                    success: function(response) {
                        if (response.success) {
                            WPFlyoutNotes.addNoteToList($panel, response.data);
                            $textarea.val('');
                        }
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                    }
                });

                return false;
            });

            // Delete note
            $root.off('click.notes', '.wp-flyout-notes-panel .delete-note').on('click.notes', '.wp-flyout-notes-panel .delete-note', function(e) {
                e.preventDefault();
                e.stopPropagation();

                if (!confirm('Delete this note?')) {
                    return false;
                }

                const $button = $(this);
                const $note = $button.closest('.note-item');
                const $panel = $button.closest('.wp-flyout-notes-panel');
                const noteId = $note.data('note-id');

                const ajaxPrefix = $panel.data('ajax-prefix');
                const nonce = $panel.data('nonce');

                $.ajax({
                    url: wpFlyoutConfig.ajaxUrl || ajaxurl,
                    type: 'POST',
                    data: {
                        action: ajaxPrefix + '_delete',
                        _wpnonce: nonce,
                        note_id: noteId
                    },
                    success: function(response) {
                        if (response.success) {
                            $note.fadeOut(function() {
                                $(this).remove();
                                WPFlyoutNotes.checkEmpty($panel);
                            });
                        }
                    }
                });

                return false;
            });
        },

        addNoteToList: function($panel, noteData) {
            const $list = $panel.find('.notes-list');
            $list.find('.no-notes').remove();

            const noteHtml = `
                <div class="note-item" data-note-id="${noteData.id}">
                    <div class="note-header">
                        <span class="note-author">${noteData.author}</span>
                        <span class="note-timestamp">${noteData.date}</span>
                        <button type="button" class="delete-note" title="Delete note">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                    <div class="note-content">${noteData.content}</div>
                </div>
            `;

            $list.prepend(noteHtml);
        },

        checkEmpty: function($panel) {
            const $list = $panel.find('.notes-list');
            if ($list.find('.note-item').length === 0) {
                $list.html('<p class="no-notes">No notes yet.</p>');
            }
        }
    };

    window.WPFlyoutNotes = WPFlyoutNotes;

    $(document).ready(function() {
        WPFlyoutNotes.init();
    });

})(jQuery);