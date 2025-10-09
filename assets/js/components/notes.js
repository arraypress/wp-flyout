/**
 * WP Flyout Notes Component
 * Handles UI updates internally, triggers events for data persistence
 */
(function ($) {
    'use strict';

    const NotesPanel = {
        init: function () {
            $(document).on('click', '[data-action="add-note"]', this.handleAdd.bind(this));
            $(document).on('click', '[data-action="delete-note"]', this.handleDelete.bind(this));
        },

        handleAdd: function (e) {
            e.preventDefault();
            const $button = $(this);
            const $panel = $button.closest('.wp-flyout-notes-panel');
            const $input = $panel.find('[data-field="content"]');
            const content = $input.val().trim();

            if (!content) {
                $input.focus();
                return;
            }

            // Generate temporary ID
            const tempId = 'temp_' + Date.now();

            // Add note to UI immediately for better UX
            const noteHtml = this.createNoteHtml({
                id: tempId,
                content: content,
                author: $panel.data('current-user') || 'You',
                date: 'Just now',
                deletable: true
            });

            const $list = $panel.find('.notes-list');
            $list.find('.no-notes').remove();
            const $newNote = $(noteHtml).hide();
            $list.prepend($newNote);
            $newNote.slideDown(200);

            // Clear input
            $input.val('');

            // Trigger event for persistence - implementation can update the note ID
            $panel.trigger('notes:add', {
                tempId: tempId,
                content: content,
                note: $newNote,
                panel: $panel,
                onSuccess: function (data) {
                    // Update with real data from server
                    if (data.id) {
                        $newNote.attr('data-note-id', data.id);
                    }
                    if (data.author) {
                        $newNote.find('.note-author').text(data.author);
                    }
                    if (data.date) {
                        $newNote.find('.note-date').text(data.date);
                    }
                },
                onError: function () {
                    // Remove the note if save failed
                    $newNote.slideUp(200, function () {
                        $(this).remove();
                        NotesPanel.checkEmpty($panel);
                    });
                    $input.val(content); // Restore content so user doesn't lose it
                }
            });
        },

        handleDelete: function (e) {
            e.preventDefault();
            const $button = $(this);
            const confirm_text = $button.data('confirm');

            if (confirm_text && !confirm(confirm_text)) {
                return;
            }

            const $note = $button.closest('.note-item');
            const $panel = $button.closest('.wp-flyout-notes-panel');
            const noteId = $note.data('note-id');

            // Optimistically remove from UI
            $note.slideUp(200);

            // Trigger event for persistence
            $panel.trigger('notes:delete', {
                noteId: noteId,
                note: $note,
                panel: $panel,
                onSuccess: function () {
                    // Complete removal
                    $note.remove();
                    NotesPanel.checkEmpty($panel);
                },
                onError: function () {
                    // Restore if delete failed
                    $note.slideDown(200);
                }
            });
        },

        createNoteHtml: function (data) {
            const deletable = data.deletable ?
                `<button type="button" class="button-link" data-action="delete-note" data-confirm="Delete this note?">
                    <span class="dashicons dashicons-trash"></span>
                </button>` : '';

            return `
                <div class="note-item" data-note-id="${data.id}">
                    <div class="note-header">
                        <span class="note-author">${this.escapeHtml(data.author)}</span>
                        <span class="note-date">${this.escapeHtml(data.date)}</span>
                        ${deletable}
                    </div>
                    <div class="note-content">${this.escapeHtml(data.content)}</div>
                </div>
            `;
        },

        checkEmpty: function ($panel) {
            const $list = $panel.find('.notes-list');
            if ($list.find('.note-item').length === 0) {
                const emptyText = $panel.data('empty-text') || 'No notes yet.';
                $list.html(`<p class="no-notes">${emptyText}</p>`);
            }
        },

        escapeHtml: function (text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    };

    // Initialize
    $(function () {
        NotesPanel.init();
    });

    // Export
    window.WPFlyoutNotesPanel = NotesPanel;
})(jQuery);