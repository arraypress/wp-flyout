/**
 * WP Flyout Notes Component
 */

(function ($) {
    'use strict';

    const NotesPanel = {
        init: function () {
            // Use event delegation on body for dynamically loaded content
            $('body').on('click', '.wp-flyout-notes-panel [data-action="add-note"]', this.handleAdd.bind(this));
            $('body').on('click', '.wp-flyout-notes-panel [data-action="delete-note"]', this.handleDelete.bind(this));

            // Handle Enter key in textarea
            $('body').on('keydown', '.wp-flyout-notes-panel textarea.note-input', this.handleKeydown.bind(this));
        },

        handleKeydown: function (e) {
            // Submit on Enter (without Shift)
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                const $textarea = $(e.currentTarget);
                const $button = $textarea.siblings('[data-action="add-note"]');
                if ($button.length) {
                    $button.click();
                }
            }
            // Shift+Enter allows new line (default behavior)
        },

        handleAdd: function (e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $form = $button.closest('.note-add-form');
            const $panel = $button.closest('.wp-flyout-notes-panel');

            const $input = $form.find('textarea.note-input');

            if (!$input.length) {
                console.error('Textarea not found in form');
                return;
            }

            const content = $input.val().trim();
            if (!content) {
                $input.focus();
                return;
            }

            // Get all data attributes from the panel
            const panelData = $panel.data();

            // Create note HTML
            const noteId = 'note_' + Date.now();
            const noteHtml = `
                <div class="note-item" data-note-id="${noteId}">
                    <div class="note-header">
                        <span class="note-author">You</span>
                        <span class="note-date">Just now</span>
                        <button type="button" class="button-link" data-action="delete-note">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                    <div class="note-content">${$('<div>').text(content).html()}</div>
                </div>
            `;

            // Add to list
            const $list = $panel.find('.notes-list');
            $list.find('.no-notes').remove();

            const $newNote = $(noteHtml).hide();
            $list.prepend($newNote);
            $newNote.slideDown(200);

            // Clear input
            $input.val('').focus();

            // Trigger event with all panel data
            $panel.trigger('notes:added', {
                id: noteId,
                content: content,
                note: $newNote,
                // Include all data attributes from panel
                object_type: panelData['object-type'] || panelData.objectType,
                object_id: panelData['object-id'] || panelData.objectId,
                action: panelData.action,
                nonce: panelData.nonce,
                // Or just pass all data
                data: panelData
            });
        },

        handleDelete: function (e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const confirm_text = $button.data('confirm');

            if (confirm_text && !confirm(confirm_text)) {
                return;
            }

            const $note = $button.closest('.note-item');
            const $panel = $button.closest('.wp-flyout-notes-panel');
            const noteId = $note.data('note-id');

            // Get panel data
            const panelData = $panel.data();

            // Remove with animation
            $note.slideUp(200, function () {
                $(this).remove();

                // Check if list is empty
                const $list = $panel.find('.notes-list');
                if ($list.find('.note-item').length === 0) {
                    const emptyText = panelData['empty-text'] || panelData.emptyText || 'No notes yet.';
                    $list.html(`<p class="no-notes">${emptyText}</p>`);
                }

                // Trigger event with panel data
                $panel.trigger('notes:deleted', {
                    id: noteId,
                    object_type: panelData['object-type'] || panelData.objectType,
                    object_id: panelData['object-id'] || panelData.objectId,
                    action: panelData.action,
                    nonce: panelData.nonce,
                    data: panelData
                });
            });
        }
    };

    // Initialize when document is ready
    $(function () {
        NotesPanel.init();
    });

    // Export
    window.WPFlyoutNotesPanel = NotesPanel;

})(jQuery);