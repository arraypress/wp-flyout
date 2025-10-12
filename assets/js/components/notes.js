/**
 * WP Flyout Notes Panel Component
 *
 * Handles note management with add/delete functionality,
 * keyboard shortcuts, and custom event triggers for AJAX integration.
 *
 * @package WPFlyout
 * @version 1.0.0
 */
(function ($) {
    'use strict';

    /**
     * Notes Panel component controller
     *
     * @namespace NotesPanel
     */
    const NotesPanel = {

        /**
         * Initialize the Notes Panel component
         *
         * Sets up event listeners for add/delete actions and
         * keyboard shortcuts for better UX.
         *
         * @since 1.0.0
         * @return {void}
         */
        init: function () {
            const self = this;

            // Use event delegation for dynamically loaded content
            $(document)
                .on('click', '.wp-flyout-notes-panel [data-action="add-note"]', function (e) {
                    self.handleAdd(e);
                })
                .on('click', '.wp-flyout-notes-panel [data-action="delete-note"]', function (e) {
                    self.handleDelete(e);
                })
                .on('keydown', '.wp-flyout-notes-panel textarea.note-input', function (e) {
                    self.handleKeydown(e);
                });

            // Initialize existing panels when flyouts open
            $(document).on('wpflyout:opened', function (e, data) {
                self.initializePanels(data.element);
            });
        },

        /**
         * Initialize notes panels
         *
         * Sets up any existing notes panels found in container.
         *
         * @since 1.0.0
         * @param {HTMLElement|jQuery} container - Container to search
         * @fires notes:initialized
         * @return {void}
         */
        initializePanels: function (container) {
            const self = this;

            $(container).find('.wp-flyout-notes-panel').each(function () {
                const $panel = $(this);
                const noteCount = $panel.find('.note-item').length;

                // Update initial count
                self.updateNoteCount($panel);

                // Trigger initialized event
                $panel.trigger('notes:initialized', {
                    panel: $panel[0],
                    noteCount: noteCount,
                    objectType: $panel.data('object-type'),
                    objectId: $panel.data('object-id')
                });
            });
        },

        /**
         * Handle keyboard shortcuts in note textarea
         *
         * Submits note on Enter key (without Shift).
         * Shift+Enter allows new line for multi-line notes.
         *
         * @since 1.0.0
         * @param {jQuery.Event} e - Keydown event
         * @fires notes:keydown
         * @return {void}
         */
        handleKeydown: function (e) {
            const $textarea = $(e.currentTarget);
            const $panel = $textarea.closest('.wp-flyout-notes-panel');

            // Trigger keydown event for custom handling
            $panel.trigger('notes:keydown', {
                key: e.key,
                shiftKey: e.shiftKey,
                textarea: $textarea[0]
            });

            // Submit on Enter (without Shift for multi-line support)
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                const $button = $textarea.siblings('[data-action="add-note"]');

                if ($button.length) {
                    $button.click();
                }
            }
            // Shift+Enter allows new line (default behavior)
        },

        /**
         * Handle add note action
         *
         * Creates a new note from textarea input, adds it to the list,
         * and triggers a custom event for AJAX handling.
         *
         * @since 1.0.0
         * @param {jQuery.Event} e - Click event
         * @fires notes:beforeadd
         * @fires notes:error
         * @fires notes:added
         * @return {void}
         */
        handleAdd: function (e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $form = $button.closest('.note-add-form');
            const $panel = $button.closest('.wp-flyout-notes-panel');
            const $input = $form.find('textarea.note-input');

            // Validate textarea exists
            if (!$input.length) {
                console.error('Notes Panel: Textarea not found in form');
                $panel.trigger('notes:error', {
                    type: 'no_textarea',
                    message: 'Textarea not found in form'
                });
                return;
            }

            // Validate content
            const content = $input.val().trim();
            if (!content) {
                $input.focus();
                // Add visual feedback for empty submission attempt
                $input.addClass('error');
                setTimeout(function () {
                    $input.removeClass('error');
                }, 300);

                $panel.trigger('notes:error', {
                    type: 'empty_content',
                    message: 'Note content is required'
                });
                return;
            }

            // Get all data attributes from the panel for AJAX
            const panelData = $panel.data();

            // Fire before add event (cancellable)
            const beforeAddEvent = $.Event('notes:beforeadd');
            $panel.trigger(beforeAddEvent, {
                content: content,
                noteCount: $panel.find('.note-item').length,
                data: panelData
            });

            if (beforeAddEvent.isDefaultPrevented()) {
                return;
            }

            // Generate unique note ID
            const noteId = 'note_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

            // Get author name from data attribute or default
            const authorName = panelData.authorName || panelData['author-name'] || 'You';

            // Create note HTML with proper escaping
            const noteHtml = this.createNoteHtml({
                id: noteId,
                author: authorName,
                content: content,
                date: 'Just now'
            });

            // Add to list with animation
            const $list = $panel.find('.notes-list');
            $list.find('.no-notes').remove();

            const $newNote = $(noteHtml).hide();
            $list.prepend($newNote);
            $newNote.slideDown(200);

            // Clear input and maintain focus
            $input.val('').focus();

            // Update note count if displayed
            this.updateNoteCount($panel);

            /**
             * Trigger custom event for AJAX handling
             * @event notes:added
             */
            $panel.trigger('notes:added', {
                id: noteId,
                content: content,
                author: authorName,
                note: $newNote[0],
                noteCount: $panel.find('.note-item').length,
                object_type: panelData['object-type'] || panelData.objectType,
                object_id: panelData['object-id'] || panelData.objectId,
                action: panelData.action,
                nonce: panelData.nonce,
                data: panelData
            });
        },

        /**
         * Handle delete note action
         *
         * Removes a note with confirmation (if configured) and
         * triggers a custom event for AJAX handling.
         *
         * @since 1.0.0
         * @param {jQuery.Event} e - Click event
         * @fires notes:beforedelete
         * @fires notes:deleted
         * @return {void}
         */
        handleDelete: function (e) {
            e.preventDefault();

            const self = this;
            const $button = $(e.currentTarget);
            const $note = $button.closest('.note-item');
            const $panel = $button.closest('.wp-flyout-notes-panel');
            const noteId = $note.data('note-id');
            const confirmText = $button.data('confirm');

            // Get note content before deletion
            const noteContent = $note.find('.note-content').text();
            const noteAuthor = $note.find('.note-author').text();

            // Get panel data for AJAX
            const panelData = $panel.data();

            // Fire before delete event (cancellable)
            const beforeDeleteEvent = $.Event('notes:beforedelete');
            $panel.trigger(beforeDeleteEvent, {
                id: noteId,
                content: noteContent,
                author: noteAuthor,
                note: $note[0],
                data: panelData
            });

            if (beforeDeleteEvent.isDefaultPrevented()) {
                return;
            }

            // Optional confirmation dialog
            if (confirmText && !confirm(confirmText)) {
                $panel.trigger('notes:delete:cancelled', {
                    id: noteId
                });
                return;
            }

            // Remove with animation
            $note.slideUp(200, function () {
                $(this).remove();

                // Check if list is now empty
                const $list = $panel.find('.notes-list');
                if ($list.find('.note-item').length === 0) {
                    const emptyText = panelData['empty-text'] || panelData.emptyText || 'No notes yet.';
                    $list.html('<p class="no-notes">' + self.escapeHtml(emptyText) + '</p>');

                    $panel.trigger('notes:empty', {
                        emptyText: emptyText
                    });
                }

                // Update note count
                self.updateNoteCount($panel);

                /**
                 * Trigger custom event for AJAX handling
                 * @event notes:deleted
                 */
                $panel.trigger('notes:deleted', {
                    id: noteId,
                    content: noteContent,
                    author: noteAuthor,
                    remainingCount: $list.find('.note-item').length,
                    object_type: panelData['object-type'] || panelData.objectType,
                    object_id: panelData['object-id'] || panelData.objectId,
                    action: panelData.action,
                    nonce: panelData.nonce,
                    data: panelData
                });
            });
        },

        /**
         * Create HTML for a note item
         *
         * Generates properly escaped HTML for a note with all
         * necessary data attributes and controls.
         *
         * @since 1.0.0
         * @param {Object} noteData - Note information
         * @param {string} noteData.id - Unique note identifier
         * @param {string} noteData.author - Author name
         * @param {string} noteData.content - Note content
         * @param {string} noteData.date - Date string
         * @return {string} HTML string for note item
         */
        createNoteHtml: function (noteData) {
            const escapedContent = this.escapeHtml(noteData.content);
            const escapedAuthor = this.escapeHtml(noteData.author);
            const escapedDate = this.escapeHtml(noteData.date);

            return `
                <div class="note-item" data-note-id="${noteData.id}">
                    <div class="note-header">
                        <span class="note-author">${escapedAuthor}</span>
                        <span class="note-date">${escapedDate}</span>
                        <button type="button" class="button-link" data-action="delete-note" title="Delete note">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                    <div class="note-content">${escapedContent.replace(/\n/g, '<br>')}</div>
                </div>
            `;
        },

        /**
         * Update note count display
         *
         * Updates any element showing the total note count
         * within the panel.
         *
         * @since 1.0.0
         * @param {jQuery} $panel - Notes panel element
         * @fires notes:countchanged
         * @return {void}
         */
        updateNoteCount: function ($panel) {
            const oldCount = parseInt($panel.attr('data-note-count')) || 0;
            const newCount = $panel.find('.note-item').length;
            const $counter = $panel.find('.notes-count');

            if ($counter.length) {
                $counter.text(newCount);
            }

            // Update panel data attribute
            $panel.attr('data-note-count', newCount);

            // Trigger count changed event if different
            if (oldCount !== newCount) {
                $panel.trigger('notes:countchanged', {
                    oldCount: oldCount,
                    newCount: newCount,
                    difference: newCount - oldCount
                });
            }
        },

        /**
         * Escape HTML to prevent XSS
         *
         * Converts special characters to HTML entities for safe display.
         *
         * @since 1.0.0
         * @param {string} text - Text to escape
         * @return {string} Escaped HTML string
         */
        escapeHtml: function (text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }
    };

    // Initialize when document is ready
    $(function () {
        NotesPanel.init();
    });

    // Export for external use
    window.WPFlyoutNotesPanel = NotesPanel;

})(jQuery);