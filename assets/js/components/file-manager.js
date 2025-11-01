/**
 * WP Flyout File Manager Component JavaScript
 * Handles media library integration, drag-drop sorting, and file operations
 */
(function ($) {
    'use strict';

    window.WPFlyoutFileManager = {

        // File icon mapping
        fileIcons: {
            // Documents
            'pdf': 'pdf',
            'doc': 'media-document',
            'docx': 'media-document',
            'txt': 'media-text',

            // Images
            'jpg': 'format-image',
            'jpeg': 'format-image',
            'png': 'format-image',
            'gif': 'format-image',
            'svg': 'format-image',

            // Media
            'mp3': 'format-audio',
            'wav': 'format-audio',
            'mp4': 'format-video',
            'mov': 'format-video',

            // Archives
            'zip': 'media-archive',
            'rar': 'media-archive',
            '7z': 'media-archive',
            'tar': 'media-archive',
            'gz': 'media-archive',

            // Code
            'js': 'media-code',
            'css': 'media-code',
            'php': 'media-code',
            'html': 'media-code',
            'json': 'media-code',

            // Spreadsheets
            'xls': 'media-spreadsheet',
            'xlsx': 'media-spreadsheet',
            'csv': 'media-spreadsheet',
        },

        init: function () {
            this.bindEvents();
            this.initSortable();
        },

        bindEvents: function () {
            // Add file button
            $(document).on('click', '.file-manager-add', this.handleAdd.bind(this));

            // Browse button
            $(document).on('click', '.file-manager-item [data-action="browse"]', this.handleBrowse.bind(this));

            // Remove button
            $(document).on('click', '.file-manager-item [data-action="remove"]', this.handleRemove.bind(this));

            // Update file count when items change
            $(document).on('file-manager:update', '.wp-flyout-file-manager', this.updateUI.bind(this));
        },

        initSortable: function () {
            $('.wp-flyout-file-manager.is-sortable .file-manager-items').sortable({
                handle: '.file-handle',
                items: '.file-manager-item',
                placeholder: 'file-manager-item ui-sortable-placeholder',
                tolerance: 'pointer',
                forcePlaceholderSize: true,
                update: function (event, ui) {
                    // Re-index items after sorting
                    $(this).find('.file-manager-item').each(function (index) {
                        $(this).attr('data-index', index);
                        $(this).find('input, select, textarea').each(function () {
                            const name = $(this).attr('name');
                            if (name) {
                                $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                            }
                        });
                    });
                }
            });
        },

        handleAdd: function (e) {
            e.preventDefault();
            const $manager = $(e.currentTarget).closest('.wp-flyout-file-manager');
            this.openMediaLibrary($manager, null);
        },

        handleBrowse: function (e) {
            e.preventDefault();
            const $item = $(e.currentTarget).closest('.file-manager-item');
            const $manager = $item.closest('.wp-flyout-file-manager');
            this.openMediaLibrary($manager, $item);
        },

        handleRemove: function (e) {
            e.preventDefault();
            const $item = $(e.currentTarget).closest('.file-manager-item');
            const $manager = $item.closest('.wp-flyout-file-manager');

            $item.fadeOut(200, function () {
                $(this).remove();
                $manager.trigger('file-manager:update');
            });
        },

        openMediaLibrary: function ($manager, $existingItem) {
            const self = this;

            // Create media frame
            const frame = wp.media({
                title: $existingItem ? 'Replace File' : 'Select File',
                button: {
                    text: $existingItem ? 'Replace' : 'Select'
                },
                multiple: !$existingItem
            });

            // Handle selection
            frame.on('select', function () {
                const attachments = frame.state().get('selection').toJSON();

                if ($existingItem) {
                    // Replace existing item
                    self.updateFileItem($existingItem, attachments[0]);
                } else {
                    // Add new items
                    attachments.forEach(attachment => {
                        self.addFileItem($manager, attachment);
                    });
                }

                $manager.trigger('file-manager:update');
            });

            frame.open();
        },

        addFileItem: function ($manager, attachment) {
            const template = $manager.data('template');
            const $items = $manager.find('.file-manager-items');
            const index = $items.find('.file-manager-item').length;
            const extension = this.getFileExtension(attachment.url);
            const icon = this.getFileIcon(extension);

            // Replace template variables
            let html = template
                .replace(/{{index}}/g, index)
                .replace(/{{name}}/g, attachment.title || attachment.filename || '')
                .replace(/{{url}}/g, attachment.url || '')
                .replace(/{{id}}/g, attachment.id || '')
                .replace(/{{extension}}/g, extension)
                .replace(/{{extension_upper}}/g, extension.toUpperCase())
                .replace(/{{icon}}/g, icon);

            const $newItem = $(html);
            $items.append($newItem);

            // Animate in
            $newItem.hide().fadeIn(200);

            // Re-initialize sortable
            if ($manager.hasClass('is-sortable')) {
                this.initSortable();
            }
        },

        updateFileItem: function ($item, attachment) {
            const extension = this.getFileExtension(attachment.url);
            const icon = this.getFileIcon(extension);

            // Update inputs
            $item.find('[data-field="name"]').val(attachment.title || attachment.filename || '');
            $item.find('[data-field="url"]').val(attachment.url || '');
            $item.find('[data-field="id"]').val(attachment.id || '');

            // Update icon
            const $icon = $item.find('.file-icon');
            $icon.attr('data-extension', extension);
            $icon.find('.dashicons')
                .removeClass()
                .addClass('dashicons dashicons-' + icon);

            // Update extension badge
            let $extension = $icon.find('.file-extension');
            if (extension) {
                if (!$extension.length) {
                    $extension = $('<span class="file-extension"></span>');
                    $icon.append($extension);
                }
                $extension.text(extension.toUpperCase());
            } else {
                $extension.remove();
            }

            // Update view link
            const url = attachment.url || '';
            let $viewLink = $item.find('a[title="View file"]');
            if (url) {
                if (!$viewLink.length) {
                    // Add view link if it doesn't exist
                    const $browseBtn = $item.find('[data-action="browse"]');
                    $viewLink = $('<a href="' + url + '" target="_blank" class="file-action-btn" title="View file"><span class="dashicons dashicons-external"></span></a>');
                    if ($browseBtn.length) {
                        $viewLink.insertAfter($browseBtn);
                    } else {
                        $item.find('.file-actions').prepend($viewLink);
                    }
                } else {
                    $viewLink.attr('href', url);
                }
            } else {
                $viewLink.remove();
            }
        },

        updateUI: function (e) {
            const $manager = $(e.currentTarget);
            const $list = $manager.find('.file-manager-list');
            const $items = $manager.find('.file-manager-item');
            const count = $items.length;
            const maxFiles = parseInt($manager.data('max-files')) || 0;

            // Update empty state
            if (count === 0) {
                $list.addClass('is-empty');
            } else {
                $list.removeClass('is-empty');
            }

            // Update count display
            $manager.find('.current-count').text(count);

            // Update add button state
            const $addBtn = $manager.find('.file-manager-add');
            if (maxFiles > 0 && count >= maxFiles) {
                $addBtn.prop('disabled', true);
                $list.addClass('max-reached');
            } else {
                $addBtn.prop('disabled', false);
                $list.removeClass('max-reached');
            }
        },

        getFileExtension: function (url) {
            if (!url) return '';
            const path = url.split('?')[0]; // Remove query string
            const filename = path.split('/').pop();
            const parts = filename.split('.');
            return parts.length > 1 ? parts.pop().toLowerCase() : '';
        },

        getFileIcon: function (extension) {
            return this.fileIcons[extension] || 'media-default';
        }
    };

    // Initialize on ready
    $(document).ready(function () {
        WPFlyoutFileManager.init();
    });

})(jQuery);