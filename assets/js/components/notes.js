/**
 * WordPress Notes Component - Standardized Nonce
 *
 * @package ArrayPress\WPFlyout
 * @version 2.0.0
 */
(function ($) {
    'use strict';

    class WPNotes {
        constructor(element, options = {}) {
            this.$container = $(element);
            this.options = $.extend({
                ajaxAdd: null,
                ajaxDelete: null,
                nonce: '',
                objectType: '',
                placeholder: 'Add a note...',
                editable: false,
                sortable: false,
                addText: 'Add Note',
                confirmDelete: 'Are you sure you want to delete this note?'
            }, options, this.$container.data());

            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            // Add note
            this.$container.on('click', '.wp-notes-add-button', (e) => {
                e.preventDefault();
                const $textarea = this.$container.find('.wp-notes-input');
                const content = $textarea.val().trim();

                if (!content) return;

                this.addNote(content);
            });

            // Delete note
            this.$container.on('click', '.wp-note-delete', (e) => {
                e.preventDefault();
                if (confirm(this.options.confirmDelete)) {
                    const $note = $(e.currentTarget).closest('.wp-note');
                    this.deleteNote($note);
                }
            });

            // Enter key to add note
            this.$container.on('keydown', '.wp-notes-input', (e) => {
                if (e.key === 'Enter' && e.ctrlKey) {
                    e.preventDefault();
                    this.$container.find('.wp-notes-add-button').click();
                }
            });
        }

        addNote(content) {
            if (!this.options.ajaxAdd) return;

            const $button = this.$container.find('.wp-notes-add-button');
            const $textarea = this.$container.find('.wp-notes-input');

            $button.prop('disabled', true);

            $.ajax({
                url: window.ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: this.options.ajaxAdd,
                    content: content,
                    object_type: this.options.objectType,
                    _wpnonce: this.options.nonce  // Changed from 'nonce' to '_wpnonce'
                },
                success: (response) => {
                    if (response.success && response.data.note) {
                        this.renderNote(response.data.note);
                        $textarea.val('');
                    } else {
                        alert(response.data || 'Error adding note');
                    }
                },
                error: () => {
                    alert('Error adding note');
                },
                complete: () => {
                    $button.prop('disabled', false);
                }
            });
        }

        deleteNote($note) {
            const noteId = $note.data('id');
            if (!noteId || !this.options.ajaxDelete) return;

            $note.css('opacity', '0.5');

            $.ajax({
                url: window.ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: this.options.ajaxDelete,
                    note_id: noteId,
                    _wpnonce: this.options.nonce  // Changed from 'nonce' to '_wpnonce'
                },
                success: (response) => {
                    if (response.success) {
                        $note.slideUp(() => $note.remove());
                        this.checkEmpty();
                    } else {
                        alert(response.data || 'Error deleting note');
                        $note.css('opacity', '1');
                    }
                },
                error: () => {
                    alert('Error deleting note');
                    $note.css('opacity', '1');
                }
            });
        }

        renderNote(note) {
            const $list = this.$container.find('.wp-notes-list');
            const $empty = this.$container.find('.wp-notes-empty');

            const html = `
                <div class="wp-note" data-id="${note.id}">
                    <div class="wp-note-header">
                        <strong>${note.author}</strong>
                        <span class="wp-note-date">${note.formatted_date}</span>
                        ${note.can_delete ? '<button type="button" class="wp-note-delete">×</button>' : ''}
                    </div>
                    <div class="wp-note-content">${note.content}</div>
                </div>
            `;

            $list.prepend(html);
            $empty.hide();
        }

        checkEmpty() {
            const $list = this.$container.find('.wp-notes-list');
            const $empty = this.$container.find('.wp-notes-empty');

            if ($list.children().length === 0) {
                $empty.show();
            }
        }
    }

    // jQuery plugin
    $.fn.wpNotes = function (options) {
        return this.each(function () {
            if (!$(this).data('wpNotes')) {
                $(this).data('wpNotes', new WPNotes(this, options));
            }
        });
    };

    // Auto-initialize
    $(document).ready(function () {
        $('.wp-notes').wpNotes();
    });

    // Initialize in flyouts
    $(document).on('wpflyout:opened', function (e, data) {
        $(data.element).find('.wp-notes').each(function () {
            if (!$(this).data('wpNotes')) {
                new WPNotes(this);
            }
        });
    });

    window.WPNotes = WPNotes;

})(jQuery);