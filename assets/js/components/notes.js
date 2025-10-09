/**
 * WP Flyout Notes Component
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

            // More flexible selector - find textarea or input
            const $input = $panel.find('.note-input, [data-field="content"], textarea').first();

            if (!$input.length) {
                console.error('Note input field not found');
                return;
            }

            const content = ($input.val() || '').trim();

            if (!content) {
                $input.focus();
                return;
            }

            // Generate temporary ID
            const tempId = 'temp_' + Date.now();

            // Add note to UI immediately
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
            $input.val('').focus();

            // Trigger event for persistence
            $panel.trigger('notes:add', {
                tempId: tempId,
                content: content,
                note: $newNote,
                panel: $panel
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
            $note.slideUp(200, function () {
                $(this).remove();
                NotesPanel.checkEmpty($panel);
            });

            // Trigger event for persistence
            $panel.trigger('notes:delete', {
                noteId: noteId,
                note: $note,
                panel: $panel
            });
        },

        createNoteHtml: function (data) {
            const deletable = data.deletable ?
                `<button type="button" class="button-link" data-action="delete-note" data-confirm="Delete this note?">
                    <span class="dashicons dashicons-trash"></span>
                </button>` : '';

            return `
                <div class="note-item" data-note-id="${this.escapeHtml(String(data.id))}">
                    <div class="note-header">
                        <span class="note-author">${this.escapeHtml(data.author || '')}</span>
                        <span class="note-date">${this.escapeHtml(data.date || '')}</span>
                        ${deletable}
                    </div>
                    <div class="note-content">${this.escapeHtml(data.content || '')}</div>
                </div>
            `;
        },

        checkEmpty: function ($panel) {
            const $list = $panel.find('.notes-list');
            if ($list.find('.note-item').length === 0) {
                const emptyText = $panel.data('empty-text') || 'No notes yet.';
                $list.html(`<p class="no-notes">${this.escapeHtml(emptyText)}</p>`);
            }
        },

        escapeHtml: function (text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }
    };

    // Initialize
    $(function () {
        NotesPanel.init();
    });

    // Export
    window.WPFlyoutNotesPanel = NotesPanel;
})(jQuery);