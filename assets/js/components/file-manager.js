/**
 * WP Flyout File Manager Component
 *
 * Handles file/attachment management with drag-and-drop sorting,
 * add/remove functionality, and WordPress Media Library integration.
 *
 * @package WPFlyout
 * @version 1.0.0
 */
(function ($) {
    'use strict';

    /**
     * File Manager component controller
     *
     * @namespace FileManager
     */
    const FileManager = {

        /**
         * Initialize the File Manager component
         *
         * Sets up event listeners for add/remove/browse actions and
         * initializes sortable functionality when flyouts open.
         *
         * @since 1.0.0
         * @return {void}
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

            // Also support custom ready event for compatibility
            $(document).on('flyout:ready', '.wp-flyout', function () {
                self.initSortable(this);
            });
        },

        /**
         * Initialize jQuery UI Sortable on file lists
         *
         * Makes file items draggable for reordering. Only initializes
         * if jQuery UI Sortable is available and list hasn't been
         * initialized already.
         *
         * @since 1.0.0
         * @param {HTMLElement|jQuery} container - Container element to search within
         * @fires filemanager:initialized
         * @fires filemanager:sortable:enabled
         * @return {void}
         */
        initSortable: function (container) {
            const self = this;

            $(container).find('.wp-flyout-file-manager').each(function () {
                const $manager = $(this);

                // Trigger initialized event
                $manager.trigger('filemanager:initialized', {
                    itemCount: $manager.find('.file-manager-item').length
                });
            });

            if (!$.fn.sortable) {
                console.warn('jQuery UI Sortable not available');
                return;
            }

            $(container).find('.file-manager-list[data-sortable="true"]').each(function () {
                const $list = $(this);
                const $manager = $list.closest('.wp-flyout-file-manager');

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
                        // Set placeholder height to match dragged item
                        ui.placeholder.height(ui.item.outerHeight());
                        ui.placeholder.css('visibility', 'visible');

                        // Trigger sort start event
                        $manager.trigger('filemanager:sortstart', {
                            item: ui.item[0],
                            index: ui.item.index()
                        });
                    },
                    stop: function (e, ui) {
                        // Trigger sort stop event
                        $manager.trigger('filemanager:sortstop', {
                            item: ui.item[0],
                            oldIndex: ui.item.data('start-index'),
                            newIndex: ui.item.index()
                        });
                    },
                    update: function (e, ui) {
                        // Store the starting index
                        ui.item.data('start-index', ui.item.index());

                        // Reindex items after sort
                        self.reindex($list);

                        // Trigger sorted event
                        $manager.trigger('filemanager:sorted', {
                            item: ui.item[0],
                            oldIndex: ui.item.data('start-index'),
                            newIndex: ui.item.index()
                        });
                    }
                });

                // Trigger sortable enabled event
                $manager.trigger('filemanager:sortable:enabled');
            });
        },

        /**
         * Handle add file button click
         *
         * Adds a new file item to the list if max limit hasn't been reached.
         * Uses the template defined in data-template attribute.
         *
         * @since 1.0.0
         * @param {jQuery.Event} e - Click event
         * @fires filemanager:beforeadd
         * @fires filemanager:maxreached
         * @fires filemanager:added
         * @return {void}
         */
        handleAdd: function (e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $manager = $button.closest('.wp-flyout-file-manager');
            const $list = $manager.find('.file-manager-list');

            // Check max items limit
            const maxItems = parseInt($manager.data('max')) || 0;
            const currentCount = $list.find('.file-manager-item').length;

            // Fire before add event (cancellable)
            const beforeAddEvent = $.Event('filemanager:beforeadd');
            $manager.trigger(beforeAddEvent, {
                currentCount: currentCount,
                maxItems: maxItems
            });

            if (beforeAddEvent.isDefaultPrevented()) {
                return;
            }

            if (maxItems > 0 && currentCount >= maxItems) {
                $manager.trigger('filemanager:maxreached', {
                    max: maxItems,
                    current: currentCount
                });
                return;
            }

            // Get template and current index
            const template = $manager.data('template');
            if (!template) {
                console.error('File Manager: No template defined');
                $manager.trigger('filemanager:error', {
                    type: 'no_template',
                    message: 'No template defined'
                });
                return;
            }

            const index = currentCount;

            // Add new item from template
            const html = template.replace(/{{index}}/g, index);
            const $newItem = $(html);
            $list.append($newItem);

            // Refresh sortable if active
            if ($list.hasClass('ui-sortable')) {
                $list.sortable('refresh');
            }

            // Focus first input in new item for accessibility
            $newItem.find('input:first').focus();

            // Trigger added event
            $manager.trigger('filemanager:added', {
                item: $newItem[0],
                index: index,
                total: currentCount + 1
            });
        },

        /**
         * Handle remove file button click
         *
         * Removes a file item from the list if min limit allows.
         * If it's the only/last item and min is 0, clears the fields instead of removing.
         * Animates removal and reindexes remaining items.
         *
         * @since 1.0.0
         * @param {jQuery.Event} e - Click event
         * @fires filemanager:beforeremove
         * @fires filemanager:minreached
         * @fires filemanager:removed
         * @fires filemanager:cleared
         * @return {void}
         */
        handleRemove: function (e) {
            e.preventDefault();

            const self = this;
            const $button = $(e.currentTarget);
            const $item = $button.closest('.file-manager-item');
            const $list = $item.closest('.file-manager-list');
            const $manager = $list.closest('.wp-flyout-file-manager');

            // Get item data before removal
            const itemData = {
                name: $item.find('[data-field="name"]').val(),
                url: $item.find('[data-field="url"]').val(),
                id: $item.find('[data-field="id"]').val(),
                index: $item.index()
            };

            // Check minimum items requirement
            const minItems = parseInt($manager.data('min')) || 0;
            const currentCount = $list.find('.file-manager-item').length;

            // Fire before remove event (cancellable)
            const beforeRemoveEvent = $.Event('filemanager:beforeremove');
            $manager.trigger(beforeRemoveEvent, {
                item: $item[0],
                data: itemData,
                currentCount: currentCount,
                minItems: minItems
            });

            if (beforeRemoveEvent.isDefaultPrevented()) {
                return;
            }

            // If this is the only item and min is 0 or 1, clear it instead of removing
            if (currentCount === 1 && minItems <= 1) {
                // Clear all fields in the item
                $item.find('input[type="text"], input[type="hidden"], input[type="url"], input[type="email"], textarea, select').val('');
                $item.find('input[type="checkbox"], input[type="radio"]').prop('checked', false);

                // Clear any preview elements (like image previews)
                $item.find('.file-preview, .file-thumbnail').attr('src', '').hide();
                $item.find('.file-name, .file-info').text('');

                // Focus first input for user convenience
                $item.find('input:first').focus();

                // Trigger cleared event
                $manager.trigger('filemanager:cleared', {
                    item: $item[0],
                    index: 0
                });

                return;
            }

            // If removing would go below minimum (and it's not the last item being cleared)
            if (minItems > 0 && currentCount <= minItems) {
                $manager.trigger('filemanager:minreached', {
                    min: minItems,
                    current: currentCount
                });
                return;
            }

            // Animate removal for multiple items
            $item.fadeOut(200, function () {
                $item.remove();

                // Reindex remaining items
                self.reindex($list);

                // Refresh sortable if active
                if ($list.hasClass('ui-sortable')) {
                    $list.sortable('refresh');
                }

                // Trigger removed event
                $manager.trigger('filemanager:removed', {
                    data: itemData,
                    index: itemData.index,
                    remainingCount: $list.find('.file-manager-item').length
                });
            });
        },

        /**
         * Handle browse button click for Media Library
         *
         * Opens WordPress Media Library modal for file selection.
         * Updates the associated file item with selected media details.
         *
         * @since 1.0.0
         * @param {jQuery.Event} e - Click event
         * @fires filemanager:browse:open
         * @fires filemanager:selected
         * @fires filemanager:browse:cancel
         * @return {void}
         */
        handleBrowse: function (e) {
            e.preventDefault();

            // Check if Media Library is available
            if (!window.wp || !window.wp.media) {
                console.error('WordPress Media Library not available');
                const $button = $(e.currentTarget);
                const $manager = $button.closest('.wp-flyout-file-manager');

                $manager.trigger('filemanager:error', {
                    type: 'media_unavailable',
                    message: 'WordPress Media Library not available'
                });
                return;
            }

            const $button = $(e.currentTarget);
            const $item = $button.closest('.file-manager-item');
            const $manager = $item.closest('.wp-flyout-file-manager');

            // Trigger browse open event
            $manager.trigger('filemanager:browse:open', {
                item: $item[0],
                index: $item.index()
            });

            // Create media frame
            const frame = wp.media({
                title: $button.data('title') || 'Select File',
                button: {
                    text: $button.data('button-text') || 'Use this file'
                },
                multiple: false
            });

            // Handle file selection
            frame.on('select', function () {
                const attachment = frame.state().get('selection').first().toJSON();

                // Update item fields with attachment data
                $item.find('[data-field="name"]').val(attachment.title || attachment.filename);
                $item.find('[data-field="url"]').val(attachment.url);
                $item.find('[data-field="id"]').val(attachment.id);

                // Update additional fields if present
                if (attachment.description) {
                    $item.find('[data-field="description"]').val(attachment.description);
                }
                if (attachment.filesizeHumanReadable) {
                    $item.find('[data-field="size"]').val(attachment.filesizeHumanReadable);
                }
                if (attachment.mime) {
                    $item.find('[data-field="mime"]').val(attachment.mime);
                }

                // Trigger selected event
                $manager.trigger('filemanager:selected', {
                    item: $item[0],
                    index: $item.index(),
                    attachment: attachment
                });
            });

            // Handle cancel/close
            frame.on('close', function () {
                // Check if something was selected
                if (!frame.state().get('selection').length) {
                    $manager.trigger('filemanager:browse:cancel', {
                        item: $item[0],
                        index: $item.index()
                    });
                }
            });

            frame.open();
        },

        /**
         * Reindex form field names after add/remove/sort
         *
         * Updates array indices in field names to maintain proper
         * form submission order (e.g., files[0], files[1], etc.)
         *
         * @since 1.0.0
         * @param {jQuery} $list - The file list container
         * @fires filemanager:reindexed
         * @return {void}
         */
        reindex: function ($list) {
            const $manager = $list.closest('.wp-flyout-file-manager');
            const prefix = $manager.data('prefix');

            if (!prefix) {
                return; // No prefix means no indexing needed
            }

            $list.find('.file-manager-item').each(function (index) {
                const $item = $(this);

                // Update all form fields within this item
                $item.find('input, select, textarea').each(function () {
                    const $field = $(this);
                    const name = $field.attr('name');

                    if (name && name.includes(prefix)) {
                        // Replace array index with new position
                        const newName = name.replace(/\[\d+\]/, '[' + index + ']');
                        $field.attr('name', newName);
                    }
                });

                // Update any display of the index
                $item.find('.file-index').text(index + 1);
            });

            // Trigger reindex complete event
            $manager.trigger('filemanager:reindexed', {
                count: $list.find('.file-manager-item').length,
                prefix: prefix
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