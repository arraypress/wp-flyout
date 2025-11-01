/**
 * WP Flyout Notes Component - Simplified
 *
 * Handles note management with AJAX endpoints for add/delete operations.
 *
 * @package WPFlyout
 * @version 2.0.0
 */
(function ($) {
    'use strict';

    const Notes = {

        /**
         * Initialize the Notes component
         */
        init: function () {
            const self = this;

            // Bind actions using delegation
            $(document)
                .on('click', '.wp-flyout-notes [data-action="add-note"]', function (e) {
                    self.handleAdd(e);
                })
                .on('click', '.wp-flyout-notes [data-action="delete-note"]', function (e) {
                    self.handleDelete(e);
                })
                .on('keydown', '.wp-flyout-notes textarea', function (e) {
                    // Submit on Enter (without Shift)
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        $(this).siblings('[data-action="add-note"]').click();
                    }
                });
        },

        /**
         * Handle add note
         */
        handleAdd: function (e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $component = $button.closest('.wp-flyout-notes');
            const $textarea = $component.find('textarea');
            const content = $textarea.val().trim();

            if (!content) {
                $textarea.focus();
                return;
            }

            // Get AJAX configuration from data attributes
            const addAction = $component.data('add-action');
            const objectType = $component.data('object-type');
            const objectId = $component.data('object-id');
            const nonce = $component.data('nonce');

            if (!addAction) {
                console.error('Notes: No add action configured');
                return;
            }

            // Disable button and show loading
            $button.prop('disabled', true).text('Adding...');

            $.ajax({
                url: window.ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: addAction,
                    content: content,
                    object_type: objectType,
                    object_id: objectId,
                    _wpnonce: nonce
                },
                success: function (response) {
                    if (response.success && response.data.note) {
                        // Add the note to the list
                        const noteHtml = Notes.createNoteHtml(response.data.note);
                        const $list = $component.find('.notes-list');

                        // Remove empty message if exists
                        $list.find('.no-notes').remove();

                        // Add new note at the top
                        $list.prepend(noteHtml);

                        // Clear textarea
                        $textarea.val('').focus();
                    } else {
                        alert(response.data?.message || 'Failed to add note');
                    }
                },
                error: function () {
                    alert('Error adding note');
                },
                complete: function () {
                    $button.prop('disabled', false).text('Add Note');
                }
            });
        },

        /**
         * Handle delete note
         */
        handleDelete: function (e) {
            e.preventDefault();

            if (!confirm('Delete this note?')) {
                return;
            }

            const $button = $(e.currentTarget);
            const $note = $button.closest('.note-item');
            const $component = $button.closest('.wp-flyout-notes');

            const noteId = $note.data('note-id');
            const deleteAction = $component.data('delete-action');
            const nonce = $component.data('nonce');

            if (!deleteAction) {
                console.error('Notes: No delete action configured');
                return;
            }

            // Disable button
            $button.prop('disabled', true);

            $.ajax({
                url: window.ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: deleteAction,
                    note_id: noteId,
                    _wpnonce: nonce
                },
                success: function (response) {
                    if (response.success) {
                        // Remove the note with animation
                        $note.slideUp(200, function () {
                            $note.remove();

                            // Check if list is empty
                            const $list = $component.find('.notes-list');
                            if ($list.find('.note-item').length === 0) {
                                $list.html('<p class="no-notes">No notes yet.</p>');
                            }
                        });
                    } else {
                        alert(response.data?.message || 'Failed to delete note');
                    }
                },
                error: function () {
                    alert('Error deleting note');
                },
                complete: function () {
                    $button.prop('disabled', false);
                }
            });
        },

        /**
         * Create note HTML
         */
        createNoteHtml: function (note) {
            const escapeHtml = function(text) {
                const div = document.createElement('div');
                div.textContent = text || '';
                return div.innerHTML;
            };

            let html = '<div class="note-item" data-note-id="' + note.id + '">';
            html += '<div class="note-header">';

            if (note.author) {
                html += '<span class="note-author">' + escapeHtml(note.author) + '</span>';
            }

            if (note.formatted_date) {
                html += '<span class="note-date">' + escapeHtml(note.formatted_date) + '</span>';
            }

            if (note.can_delete) {
                html += '<button type="button" class="button-link" data-action="delete-note">';
                html += '<span class="dashicons dashicons-trash"></span>';
                html += '</button>';
            }

            html += '</div>';
            html += '<div class="note-content">' + escapeHtml(note.content).replace(/\n/g, '<br>') + '</div>';
            html += '</div>';

            return html;
        }
    };

    // Initialize on document ready
    $(function () {
        Notes.init();
    });

    // Export for external use
    window.WPFlyoutNotes = Notes;

})(jQuery);