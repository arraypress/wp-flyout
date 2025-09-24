/**
 * WP Flyout File Manager Component
 */
(function ($) {
    'use strict';

    const FileManager = {

        init: function () {
            this.bindEvents();
            this.initSortable();
        },

        initIn: function ($container) {
            const $managers = $container.find('.wp-flyout-file-manager');
            if ($managers.length) {
                $managers.each(function () {
                    const $list = $(this).find('.file-manager-list[data-sortable="true"]');
                    if ($list.length && $.fn.sortable) {
                        $list.sortable({
                            handle: '.file-handle',
                            placeholder: 'file-manager-item-placeholder',
                            tolerance: 'pointer',
                            update: function () {
                                FileManager.reindexFiles($(this));
                            }
                        });
                    }
                });
            }
        },

        bindEvents: function () {
            // Add file
            $(document).on('click', '.add-file-button', function (e) {
                e.preventDefault();
                const $manager = $(this).closest('.wp-flyout-file-manager');
                FileManager.addFile($manager);
            });

            // Remove file
            $(document).on('click', '.remove-file', function (e) {
                e.preventDefault();
                const $item = $(this).closest('.file-manager-item');
                const isFirst = $item.attr('data-first') === 'true';

                if (isFirst) {
                    // Clear first item instead of removing
                    FileManager.clearFile($item);
                } else {
                    FileManager.removeFile($item);
                }
            });

            // Media picker
            $(document).on('click', '.select-file-media', function (e) {
                e.preventDefault();
                const $button = $(this);
                FileManager.openMediaPicker($button);
            });
        },

        initSortable: function () {
            if (!$.fn.sortable) return;

            $('.file-manager-list[data-sortable="true"]').sortable({
                handle: '.file-handle',
                placeholder: 'file-manager-item-placeholder',
                tolerance: 'pointer',
                update: function () {
                    FileManager.reindexFiles($(this));
                }
            });
        },

        addFile: function ($manager) {
            const $list = $manager.find('.file-manager-list');
            const $template = $manager.find('.file-item-template');

            let template = $template.html() || $template.text();
            if (!template) {
                console.error('File item template not found');
                return;
            }

            const newIndex = $list.find('.file-manager-item').length;
            const html = template.replace(/{{index}}/g, newIndex);
            $list.append(html);

            if ($list.data('sortable') && $.fn.sortable) {
                $list.sortable('refresh');
            }

            // Focus on new item
            $list.find('.file-manager-item:last .file-name-input').focus();
        },

        removeFile: function ($item) {
            const $list = $item.closest('.file-manager-list');

            // Don't remove if it's the only item
            if ($list.find('.file-manager-item').length <= 1) {
                FileManager.clearFile($item);
                return;
            }

            $item.remove();
            FileManager.reindexFiles($list);
        },

        clearFile: function ($item) {
            // Clear all inputs but keep the item
            $item.find('.file-name-input').val('');
            $item.find('.file-url-input').val('');
            $item.find('.file-attachment-id').val('');
            // Don't clear lookup_key - keep it for tracking
        },

        reindexFiles: function ($list) {
            const namePrefix = $list.closest('.wp-flyout-file-manager').data('name-prefix') || 'files';

            $list.find('.file-manager-item').each(function (index) {
                const $item = $(this);
                $item.attr('data-index', index);
                $item.attr('data-first', index === 0 ? 'true' : 'false');

                $item.find('input').each(function () {
                    const name = $(this).attr('name');
                    if (name) {
                        const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                        $(this).attr('name', newName);
                    }
                });
            });
        },

        openMediaPicker: function ($button) {
            if (!window.wp || !window.wp.media) {
                alert('WordPress media library not available');
                return;
            }

            const frame = wp.media({
                title: 'Select File',
                button: {
                    text: 'Use this file'
                },
                multiple: false
            });

            frame.on('select', function () {
                const attachment = frame.state().get('selection').first().toJSON();
                const $item = $button.closest('.file-manager-item');

                $item.find('.file-name-input').val(attachment.filename || attachment.title);
                $item.find('.file-url-input').val(attachment.url);
                $item.find('.file-attachment-id').val(attachment.id);

                // If this is a new item, generate a lookup key
                if (!$item.find('.file-lookup-key').val()) {
                    $item.find('.file-lookup-key').val('file_' + Date.now());
                }
            });

            frame.open();
        }
    };

    // Export for use
    window.WPFlyoutFileManager = FileManager;

    // Initialize on ready
    $(document).ready(function () {
        FileManager.init();
    });

})(jQuery);