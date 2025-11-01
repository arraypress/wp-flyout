/**
 * WP Flyout File Manager Component JavaScript
 * Fixed sortable initialization
 */
(function($) {
    'use strict';

    window.WPFlyoutFileManager = {

        fileIcons: {
            'pdf': 'pdf',
            'doc': 'media-document',
            'docx': 'media-document',
            'txt': 'media-text',
            'jpg': 'format-image',
            'jpeg': 'format-image',
            'png': 'format-image',
            'gif': 'format-image',
            'svg': 'format-image',
            'webp': 'format-image',
            'mp3': 'format-audio',
            'wav': 'format-audio',
            'ogg': 'format-audio',
            'mp4': 'format-video',
            'mov': 'format-video',
            'avi': 'format-video',
            'webm': 'format-video',
            'zip': 'media-archive',
            'rar': 'media-archive',
            '7z': 'media-archive',
            'tar': 'media-archive',
            'gz': 'media-archive',
            'js': 'media-code',
            'css': 'media-code',
            'php': 'media-code',
            'html': 'media-code',
            'json': 'media-code',
            'xml': 'media-code',
            'xls': 'media-spreadsheet',
            'xlsx': 'media-spreadsheet',
            'csv': 'media-spreadsheet',
        },

        init: function() {
            this.bindEvents();
            // Initialize sortable for existing file managers
            this.initAllSortables();
        },

        bindEvents: function() {
            // Add file button
            $(document).on('click', '.file-manager-add', this.handleAdd.bind(this));

            // Browse button
            $(document).on('click', '.file-manager-item [data-action="browse"]', this.handleBrowse.bind(this));

            // Remove button
            $(document).on('click', '.file-manager-item [data-action="remove"]', this.handleRemove.bind(this));

            // URL change
            $(document).on('blur change', '.file-url-input', this.handleUrlChange.bind(this));

            // Update UI
            $(document).on('file-manager:update', '.wp-flyout-file-manager', this.updateUI.bind(this));

            // Re-initialize sortable when flyout opens
            $(document).on('flyout:opened', this.initAllSortables.bind(this));
        },

        initAllSortables: function() {
            // Find all file manager item containers and make them sortable
            $('.wp-flyout-file-manager .file-manager-items').each(function() {
                if (!$(this).hasClass('ui-sortable')) {
                    $(this).sortable({
                        handle: '.file-handle',
                        items: '.file-manager-item',
                        placeholder: 'file-manager-item ui-sortable-placeholder',
                        tolerance: 'pointer',
                        forcePlaceholderSize: true,
                        update: function(event, ui) {
                            // Re-index items after sorting
                            $(this).find('.file-manager-item').each(function(index) {
                                $(this).attr('data-index', index);
                                $(this).find('input, select, textarea').each(function() {
                                    const name = $(this).attr('name');
                                    if (name) {
                                        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                                    }
                                });
                            });
                        }
                    });
                }
            });
        },

        handleAdd: function(e) {
            e.preventDefault();
            const $manager = $(e.currentTarget).closest('.wp-flyout-file-manager');
            this.addEmptyItem($manager);
        },

        handleBrowse: function(e) {
            e.preventDefault();
            const $item = $(e.currentTarget).closest('.file-manager-item');
            const $manager = $item.closest('.wp-flyout-file-manager');
            this.openMediaLibrary($manager, $item);
        },

        handleRemove: function(e) {
            e.preventDefault();
            const $item = $(e.currentTarget).closest('.file-manager-item');
            const $manager = $item.closest('.wp-flyout-file-manager');

            $item.fadeOut(200, function() {
                $(this).remove();
                $manager.trigger('file-manager:update');
            });
        },

        handleUrlChange: function(e) {
            const $input = $(e.currentTarget);
            const $item = $input.closest('.file-manager-item');
            const url = $input.val();

            this.updateItemIcon($item, url);
        },

        addEmptyItem: function($manager) {
            const template = $manager.data('template');
            const $items = $manager.find('.file-manager-items');
            const index = $items.find('.file-manager-item').length;

            // Replace template variables
            let html = template
                .replace(/{{index}}/g, index)
                .replace(/{{name}}/g, '')
                .replace(/{{url}}/g, '')
                .replace(/{{id}}/g, '')
                .replace(/{{extension}}/g, '')
                .replace(/{{extension_upper}}/g, '')
                .replace(/{{extension_display}}/g, 'display:none')
                .replace(/{{icon}}/g, 'media-default');

            const $newItem = $(html);
            $items.append($newItem);

            // Animate in and focus
            $newItem.hide().fadeIn(200, function() {
                $newItem.find('.file-name-input').focus();
            });

            // Refresh sortable to include new item
            if ($items.hasClass('ui-sortable')) {
                $items.sortable('refresh');
            } else {
                this.initAllSortables();
            }

            $manager.trigger('file-manager:update');
        },

        openMediaLibrary: function($manager, $item) {
            const self = this;

            // Check if wp.media is available
            if (!wp || !wp.media) {
                console.error('WordPress media library not available');
                return;
            }

            // Create media frame
            const frame = wp.media({
                title: 'Select File',
                button: {
                    text: 'Select'
                },
                multiple: false
            });

            // Handle selection
            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                self.updateFileItem($item, attachment);
                $manager.trigger('file-manager:update');
            });

            frame.open();
        },

        updateFileItem: function($item, attachment) {
            $item.find('[data-field="name"]').val(attachment.title || attachment.filename || '');
            $item.find('[data-field="url"]').val(attachment.url || '');
            $item.find('[data-field="id"]').val(attachment.id || '');

            this.updateItemIcon($item, attachment.url || '');
        },

        updateItemIcon: function($item, url) {
            const extension = this.getFileExtension(url);
            const icon = this.getFileIcon(extension);

            const $icon = $item.find('.file-icon');
            $icon.attr('data-extension', extension);
            $icon.find('.dashicons')
                .removeClass()
                .addClass('dashicons dashicons-' + icon);

            let $extension = $icon.find('.file-extension');
            if (extension) {
                if (!$extension.length) {
                    $extension = $('<span class="file-extension"></span>');
                    $icon.append($extension);
                }
                $extension.text(extension.toUpperCase()).show();
            } else {
                $extension.hide();
            }
        },

        updateUI: function(e) {
            const $manager = $(e.currentTarget);
            const $list = $manager.find('.file-manager-list');
            const $items = $manager.find('.file-manager-item');
            const count = $items.length;

            // Update empty state
            if (count === 0) {
                $list.addClass('is-empty');
            } else {
                $list.removeClass('is-empty');
            }
        },

        getFileExtension: function(url) {
            if (!url) return '';
            const path = url.split('?')[0];
            const filename = path.split('/').pop();
            const parts = filename.split('.');
            return parts.length > 1 ? parts.pop().toLowerCase() : '';
        },

        getFileIcon: function(extension) {
            return this.fileIcons[extension] || 'media-default';
        }
    };

    // Initialize on ready
    $(document).ready(function() {
        // Make sure jQuery UI sortable is loaded
        if (!$.fn.sortable) {
            console.error('jQuery UI Sortable not loaded. File reordering will not work.');
            return;
        }

        WPFlyoutFileManager.init();
    });

    // Also re-init when flyouts are loaded via AJAX
    $(document).on('wp-flyout:loaded', function() {
        WPFlyoutFileManager.initAllSortables();
    });

})(jQuery);