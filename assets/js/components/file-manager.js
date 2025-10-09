/**
 * WP Flyout File Manager Component
 */
(function ($) {
    'use strict';

    const FileManager = {
        init: function () {
            // Bind click events
            $(document).on('click', '.wp-flyout-file-manager [data-action="add"]', this.handleAdd.bind(this));
            $(document).on('click', '.wp-flyout-file-manager [data-action="remove"]', this.handleRemove.bind(this));
            $(document).on('click', '.wp-flyout-file-manager [data-action="browse"]', this.handleBrowse.bind(this));

            // Listen for flyout open event
            $(document).on('wpflyout:opened', function (e, data) {
                FileManager.initSortable(data.element);
            });

            // Also listen for custom ready event
            $(document).on('flyout:ready', '.wp-flyout', function () {
                FileManager.initSortable(this);
            });
        },

        initSortable: function (container) {
            if (!$.fn.sortable) {
                console.warn('jQuery UI Sortable not loaded');
                return;
            }

            $(container).find('.file-manager-list[data-sortable="true"]').each(function () {
                const $list = $(this);

                // Skip if already initialized
                if ($list.hasClass('ui-sortable')) {
                    return;
                }

                $list.sortable({
                    handle: '.file-handle',
                    items: '> .file-manager-item',
                    placeholder: 'file-manager-item-placeholder',
                    tolerance: 'pointer',
                    start: function (e, ui) {
                        ui.placeholder.height(ui.item.outerHeight());
                        ui.placeholder.css('visibility', 'visible');
                    },
                    update: function () {
                        FileManager.reindex($(this));
                    }
                });
            });
        },

        handleAdd: function (e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const $manager = $button.closest('.wp-flyout-file-manager');
            const $list = $manager.find('.file-manager-list');
            const template = $manager.data('template');
            const index = $list.find('.file-manager-item').length;

            const html = template.replace(/{{index}}/g, index);
            $list.append(html);

            // Refresh sortable
            if ($list.hasClass('ui-sortable')) {
                $list.sortable('refresh');
            }

            // Focus new item
            $list.find('.file-manager-item:last input:first').focus();
        },

        handleRemove: function (e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const $item = $button.closest('.file-manager-item');
            const $list = $item.closest('.file-manager-list');
            const $manager = $list.closest('.wp-flyout-file-manager');
            const minItems = parseInt($manager.data('min')) || 1;

            if ($list.find('.file-manager-item').length <= minItems) {
                $item.find('input').val('');
                return;
            }

            $item.fadeOut(200, function () {
                $(this).remove();
                FileManager.reindex($list);

                // Refresh sortable
                if ($list.hasClass('ui-sortable')) {
                    $list.sortable('refresh');
                }
            });
        },

        handleBrowse: function (e) {
            e.preventDefault();

            if (!window.wp || !window.wp.media) {
                alert('Media library not available');
                return;
            }

            const $button = $(e.currentTarget);

            const frame = wp.media({
                title: 'Select File',
                button: {text: 'Use this file'},
                multiple: false
            });

            frame.on('select', function () {
                const attachment = frame.state().get('selection').first().toJSON();
                const $item = $button.closest('.file-manager-item');

                $item.find('[data-field="name"]').val(attachment.title || attachment.filename);
                $item.find('[data-field="url"]').val(attachment.url);
                $item.find('[data-field="id"]').val(attachment.id);
            });

            frame.open();
        },

        reindex: function ($list) {
            const prefix = $list.closest('.wp-flyout-file-manager').data('prefix');

            $list.find('.file-manager-item').each(function (index) {
                $(this).find('input, select, textarea').each(function () {
                    const name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                    }
                });
            });
        }
    };

    // Initialize
    $(function () {
        FileManager.init();
    });

    // Export
    window.WPFlyoutFileManager = FileManager;

})(jQuery);