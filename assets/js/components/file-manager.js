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
            $(document).on('click', '.add-file-button', function (e) {
                e.preventDefault();
                const $manager = $(this).closest('.wp-flyout-file-manager');
                FileManager.addFile($manager);
            });

            $(document).on('click', '.remove-file', function (e) {
                e.preventDefault();
                const $item = $(this).closest('.file-manager-item');
                FileManager.removeFile($item);
            });

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
            const maxFiles = parseInt($manager.data('max-files')) || 0;

            const currentCount = $list.find('.file-manager-item').length;
            if (maxFiles > 0 && currentCount >= maxFiles) {
                alert('Maximum number of files reached');
                return;
            }

            $list.find('.file-manager-empty').remove();

            let template = $template.html() || $template.text();
            if (!template) {
                console.error('File item template not found');
                return;
            }

            const newIndex = currentCount;
            const html = template.replace(/{{index}}/g, newIndex);
            $list.append(html);

            if ($list.data('sortable') && $.fn.sortable) {
                $list.sortable('refresh');
            }

            $list.find('.file-manager-item:last .file-name-input').focus();
        },

        removeFile: function ($item) {
            const $list = $item.closest('.file-manager-list');
            const $manager = $list.closest('.wp-flyout-file-manager');
            const minFiles = parseInt($manager.data('min-files')) || 0;
            const currentCount = $list.find('.file-manager-item').length;

            if (minFiles > 0 && currentCount <= minFiles) {
                alert('Minimum number of files required: ' + minFiles);
                return;
            }

            $item.remove();
            FileManager.reindexFiles($list);

            if ($list.find('.file-manager-item').length === 0 && minFiles === 0) {
                $list.html('<p class="file-manager-empty">No files added yet.</p>');
            }
        },

        reindexFiles: function ($list) {
            const namePrefix = $list.closest('.wp-flyout-file-manager').data('name-prefix') || 'files';

            $list.find('.file-manager-item').each(function (index) {
                const $item = $(this);
                $item.attr('data-index', index);

                $item.find('input, select, textarea').each(function () {
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
                $item.find('[name*="[attachment_id]"]').val(attachment.id);

                if (attachment.filesizeInBytes) {
                    $item.find('[name*="[size]"]').val(attachment.filesizeInBytes);
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