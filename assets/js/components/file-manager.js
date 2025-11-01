/**
 * WP Flyout File Manager Component - Simplified
 *
 * Handles file management with drag-and-drop sorting and Media Library integration.
 *
 * @package WPFlyout
 * @version 2.0.0
 */
(function ($) {
    'use strict';

    const FileManager = {

        /**
         * Initialize the File Manager component
         */
        init: function () {
            const self = this;

            // Bind action events using delegation
            $(document)
                .on('click', '.wp-flyout-file-manager [data-action="add"]', function (e) {
                    self.handleAdd(e);
                })
                .on('click', '.wp-flyout-file-manager [data-action="remove"]', function (e) {
                    self.handleRemove(e);
                })
                .on('click', '.wp-flyout-file-manager [data-action="browse"]', function (e) {
                    self.handleBrowse(e);
                });

            // Initialize sortable when flyouts open
            $(document).on('wpflyout:opened', function (e, data) {
                self.initSortable(data.element);
            });
        },

        /**
         * Initialize jQuery UI Sortable on file lists
         */
        initSortable: function (container) {
            const self = this;

            if (!$.fn.sortable) {
                return; // jQuery UI not available
            }

            $(container).find('.file-manager-list').each(function () {
                const $list = $(this);

                // Skip if already initialized
                if ($list.hasClass('ui-sortable')) {
                    return;
                }

                $list.sortable({
                    handle: '.file-handle',
                    items: '> .file-manager-item',
                    placeholder: 'file-manager-item-placeholder',
                    update: function () {
                        self.reindex($list);
                    }
                });
            });
        },

        /**
         * Handle add file button click
         */
        handleAdd: function (e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $manager = $button.closest('.wp-flyout-file-manager');
            const $list = $manager.find('.file-manager-list');
            const currentCount = $list.find('.file-manager-item').length;

            // Get template
            const template = $manager.data('template');
            if (!template) {
                console.error('File Manager: No template defined');
                return;
            }

            // Add new item from template
            const html = template.replace(/{{index}}/g, currentCount);
            const $newItem = $(html);
            $list.append($newItem);

            // Refresh sortable
            if ($list.hasClass('ui-sortable')) {
                $list.sortable('refresh');
            }

            // Focus first input
            $newItem.find('input[type="text"]:first').focus();
        },

        /**
         * Handle remove file button click
         */
        handleRemove: function (e) {
            e.preventDefault();

            const self = this;
            const $button = $(e.currentTarget);
            const $item = $button.closest('.file-manager-item');
            const $list = $item.closest('.file-manager-list');
            const currentCount = $list.find('.file-manager-item').length;

            // If only one item, just clear the fields
            if (currentCount === 1) {
                $item.find('input').val('');
                $item.find('input[type="text"]:first').focus();
                return;
            }

            // Remove the item
            $item.fadeOut(200, function () {
                $item.remove();
                self.reindex($list);

                if ($list.hasClass('ui-sortable')) {
                    $list.sortable('refresh');
                }
            });
        },

        /**
         * Handle browse button click for Media Library
         */
        handleBrowse: function (e) {
            e.preventDefault();

            // Check if Media Library is available
            if (!window.wp || !window.wp.media) {
                console.error('WordPress Media Library not available');
                return;
            }

            const $button = $(e.currentTarget);
            const $item = $button.closest('.file-manager-item');

            // Create media frame
            const frame = wp.media({
                title: 'Select File',
                button: {
                    text: 'Use this file'
                },
                multiple: false
            });

            // Handle file selection
            frame.on('select', function () {
                const attachment = frame.state().get('selection').first().toJSON();

                // Update item fields
                $item.find('[data-field="name"]').val(attachment.title || attachment.filename);
                $item.find('[data-field="url"]').val(attachment.url);
                $item.find('[data-field="id"]').val(attachment.id);
            });

            frame.open();
        },

        /**
         * Reindex form field names after add/remove/sort
         */
        reindex: function ($list) {
            const $manager = $list.closest('.wp-flyout-file-manager');
            const prefix = $manager.data('prefix');

            if (!prefix) {
                return;
            }

            $list.find('.file-manager-item').each(function (index) {
                const $item = $(this);

                // Update all input names with new index
                $item.find('input').each(function () {
                    const $field = $(this);
                    const name = $field.attr('name');

                    if (name && name.includes(prefix)) {
                        const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                        $field.attr('name', newName);
                    }
                });
            });
        }
    };

    // Initialize on document ready
    $(function () {
        FileManager.init();
    });

    // Export for external use
    window.WPFlyoutFileManager = FileManager;

})(jQuery);