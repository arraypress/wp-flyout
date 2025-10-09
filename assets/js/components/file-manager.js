/**
 * WP Flyout File Manager Component
 * Handles client-side interactions only - no AJAX
 */
(function ($) {
    'use strict';

    const FileManager = {
        init: function () {
            // Use event delegation for all interactions
            $(document).on('click', '.wp-flyout-file-manager [data-action]', this.handleAction);

            // Initialize sortable on existing managers
            this.initSortable($('.wp-flyout-file-manager'));
        },

        handleAction: function (e) {
            e.preventDefault();
            const $button = $(this);
            const action = $button.data('action');
            const $manager = $button.closest('.wp-flyout-file-manager');

            switch (action) {
                case 'add':
                    FileManager.addItem($manager);
                    break;
                case 'remove':
                    FileManager.removeItem($button.closest('.file-manager-item'));
                    break;
                case 'browse':
                    FileManager.openMediaPicker($button);
                    break;
            }
        },

        initSortable: function ($managers) {
            $managers.each(function () {
                const $list = $(this).find('[data-sortable="true"]');
                if ($list.length && $.fn.sortable) {
                    $list.sortable({
                        handle: '[data-handle]',
                        update: function () {
                            FileManager.reindex($(this));
                        }
                    });
                }
            });
        },

        addItem: function ($manager) {
            const template = $manager.data('template');
            const $list = $manager.find('.file-manager-list');
            const index = $list.find('.file-manager-item').length;

            // Simple template replacement
            const html = template.replace(/{{index}}/g, index);
            $list.append(html);

            // Focus new item
            $list.find('.file-manager-item:last input:first').focus();
        },

        removeItem: function ($item) {
            const $list = $item.closest('.file-manager-list');

            // Keep at least one item
            if ($list.find('.file-manager-item').length <= 1) {
                $item.find('input').val('');
                return;
            }

            $item.remove();
            this.reindex($list);
        },

        reindex: function ($list) {
            // Update array indices in input names
            const prefix = $list.closest('.wp-flyout-file-manager').data('prefix');

            $list.find('.file-manager-item').each(function (index) {
                $(this).find('input, select, textarea').each(function () {
                    const name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                    }
                });
            });
        },

        openMediaPicker: function ($button) {
            if (!wp?.media) return;

            const frame = wp.media({
                multiple: false
            });

            frame.on('select', function () {
                const attachment = frame.state().get('selection').first().toJSON();
                const $item = $button.closest('.file-manager-item');

                // Update inputs with attachment data
                $item.find('[data-field="name"]').val(attachment.title || attachment.filename);
                $item.find('[data-field="url"]').val(attachment.url);
                $item.find('[data-field="id"]').val(attachment.id);
            });

            frame.open();
        }
    };

    // Auto-initialize
    $(function () {
        FileManager.init();

        // Re-init when flyouts are opened
        $(document).on('flyout:opened', function (e, data) {
            FileManager.initSortable($(data.element).find('.wp-flyout-file-manager'));
        });
    });

    // Export if needed
    window.WPFlyoutFileManager = FileManager;

})(jQuery);