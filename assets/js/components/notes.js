/**
 * WP Flyout Notes Component
 * Generic handler - implementation decides the AJAX details
 */
(function ($) {
    'use strict';

    const NotesPanel = {
        init: function () {
            $(document).on('click', '[data-action="add-note"]', this.handleAdd);
            $(document).on('click', '[data-action="delete-note"]', this.handleDelete);
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

            // Trigger custom event - let implementation handle it
            $panel.trigger('notes:add', {
                content: content,
                button: $button,
                input: $input,
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

            // Trigger custom event - let implementation handle it
            $panel.trigger('notes:delete', {
                noteId: noteId,
                note: $note,
                button: $button,
                panel: $panel
            });
        }
    };

    // Initialize
    $(function () {
        NotesPanel.init();
    });

    // Export
    window.WPFlyoutNotesPanel = NotesPanel;
})(jQuery);