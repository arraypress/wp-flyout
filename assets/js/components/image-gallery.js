/**
 * WP Flyout Image Gallery Component JavaScript
 * Handles media library integration, drag-drop sorting, and gallery management
 */
(function ($) {
    'use strict';

    window.WPFlyoutImageGallery = {

        init: function () {
            this.bindEvents();
            this.initSortable();
        },

        bindEvents: function () {
            // Add images button
            $(document).on('click', '.gallery-add-btn', this.handleAdd.bind(this));

            // Edit image button
            $(document).on('click', '.gallery-item-edit', this.handleEdit.bind(this));

            // Remove image button
            $(document).on('click', '.gallery-item-remove', this.handleRemove.bind(this));

            // Update UI when items change
            $(document).on('image-gallery:update', '.wp-flyout-image-gallery', this.updateUI.bind(this));

            // Re-initialize sortable when flyout opens
            $(document).on('wpflyout:opened flyout:ready', this.initSortable.bind(this));
        },

        initSortable: function () {
            setTimeout(function () {
                $('.wp-flyout-image-gallery.is-sortable .gallery-grid').each(function () {
                    if (!$(this).hasClass('ui-sortable')) {
                        $(this).sortable({
                            items: '.gallery-item',
                            handle: '.gallery-item-handle',
                            placeholder: 'gallery-item ui-sortable-placeholder',
                            tolerance: 'pointer',
                            forcePlaceholderSize: true,
                            update: function (event, ui) {
                                // Re-index items after sorting
                                $(this).find('.gallery-item').each(function (index) {
                                    $(this).attr('data-index', index);
                                    $(this).find('input').each(function () {
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
            }, 100);
        },

        handleAdd: function (e) {
            e.preventDefault();
            const $gallery = $(e.currentTarget).closest('.wp-flyout-image-gallery');
            const multiple = $gallery.data('multiple') === true || $gallery.data('multiple') === 'true';
            this.openMediaLibrary($gallery, null, multiple);
        },

        handleEdit: function (e) {
            e.preventDefault();
            const $item = $(e.currentTarget).closest('.gallery-item');
            const $gallery = $item.closest('.wp-flyout-image-gallery');
            this.openMediaLibrary($gallery, $item, false);
        },

        handleRemove: function (e) {
            e.preventDefault();
            const $item = $(e.currentTarget).closest('.gallery-item');
            const $gallery = $item.closest('.wp-flyout-image-gallery');

            $item.fadeOut(200, function () {
                $(this).remove();
                $gallery.trigger('image-gallery:update');
            });
        },

        openMediaLibrary: function ($gallery, $item, multiple) {
            const self = this;
            const isEdit = !!$item;

            // Create media frame
            const frame = wp.media({
                title: isEdit ? 'Edit Image' : 'Select Images',
                button: {
                    text: isEdit ? 'Update' : 'Add to Gallery'
                },
                library: {
                    type: 'image'
                },
                multiple: multiple
            });

            // If editing, pre-select the image
            if (isEdit) {
                frame.on('open', function () {
                    const selection = frame.state().get('selection');
                    const attachmentId = $item.find('[data-field="attachment_id"]').val();
                    if (attachmentId) {
                        const attachment = wp.media.attachment(attachmentId);
                        attachment.fetch();
                        selection.add(attachment ? [attachment] : []);
                    }
                });
            }

            // Handle selection
            frame.on('select', function () {
                const selection = frame.state().get('selection');

                if (isEdit) {
                    // Update existing item
                    const attachment = selection.first().toJSON();
                    self.updateImageItem($item, attachment);
                } else {
                    // Add new items
                    selection.each(function (attachment) {
                        self.addImageItem($gallery, attachment.toJSON());
                    });
                }

                $gallery.trigger('image-gallery:update');
            });

            frame.open();
        },

        addImageItem: function ($gallery, attachment) {
            const $grid = $gallery.find('.gallery-grid');
            const name = $gallery.data('name');
            const index = $grid.find('.gallery-item').length;
            const showCaption = $gallery.data('show-caption');
            const showAlt = $gallery.data('show-alt');
            const size = $gallery.data('size') || 'thumbnail';

            // Get the appropriate thumbnail URL
            const thumbnail = attachment.sizes && attachment.sizes[size] ?
                attachment.sizes[size].url : attachment.url;

            // Build HTML
            let html = '<div class="gallery-item" data-index="' + index + '">';

            // Handle
            if ($gallery.hasClass('is-sortable')) {
                html += '<div class="gallery-item-handle">' +
                    '<span class="dashicons dashicons-move"></span>' +
                    '</div>';
            }

            // Preview
            html += '<div class="gallery-item-preview">' +
                '<img src="' + thumbnail + '" alt="' + (attachment.alt || '') + '" class="gallery-thumbnail">' +
                '<div class="gallery-item-overlay">' +
                '<button type="button" class="gallery-item-edit" data-action="edit" title="Edit image details">' +
                '<span class="dashicons dashicons-edit"></span>' +
                '</button>' +
                '<button type="button" class="gallery-item-remove" data-action="remove" title="Remove image">' +
                '<span class="dashicons dashicons-trash"></span>' +
                '</button>' +
                '</div>' +
                '</div>';

            // Fields
            html += '<div class="gallery-item-fields">';

            if (showCaption) {
                html += '<input type="text" ' +
                    'name="' + name + '[' + index + '][caption]" ' +
                    'value="' + (attachment.caption || '') + '" ' +
                    'placeholder="Caption" ' +
                    'class="gallery-caption-input">';
            }

            if (showAlt) {
                html += '<input type="text" ' +
                    'name="' + name + '[' + index + '][alt]" ' +
                    'value="' + (attachment.alt || '') + '" ' +
                    'placeholder="Alt text" ' +
                    'class="gallery-alt-input">';
            }

            // Hidden fields
            html += '<input type="hidden" name="' + name + '[' + index + '][attachment_id]" ' +
                'value="' + attachment.id + '" data-field="attachment_id">' +
                '<input type="hidden" name="' + name + '[' + index + '][url]" ' +
                'value="' + attachment.url + '" data-field="url">' +
                '<input type="hidden" name="' + name + '[' + index + '][thumbnail]" ' +
                'value="' + thumbnail + '" data-field="thumbnail">';

            html += '</div></div>';

            // Add to grid
            const $newItem = $(html);
            $grid.append($newItem);

            // Animate in
            $newItem.hide().fadeIn(200);

            // Refresh sortable
            if ($grid.hasClass('ui-sortable')) {
                $grid.sortable('refresh');
            }
        },

        updateImageItem: function ($item, attachment) {
            const $gallery = $item.closest('.wp-flyout-image-gallery');
            const size = $gallery.data('size') || 'thumbnail';

            // Get the appropriate thumbnail URL
            const thumbnail = attachment.sizes && attachment.sizes[size] ?
                attachment.sizes[size].url : attachment.url;

            // Update preview
            $item.find('.gallery-thumbnail').attr({
                'src': thumbnail,
                'alt': attachment.alt || ''
            });

            // Update fields
            $item.find('[data-field="attachment_id"]').val(attachment.id);
            $item.find('[data-field="url"]').val(attachment.url);
            $item.find('[data-field="thumbnail"]').val(thumbnail);
            $item.find('.gallery-caption-input').val(attachment.caption || '');
            $item.find('.gallery-alt-input').val(attachment.alt || '');
        },

        updateUI: function (e) {
            const $gallery = $(e.currentTarget);
            const $container = $gallery.find('.gallery-container');
            const $items = $gallery.find('.gallery-item');
            const count = $items.length;
            const maxImages = parseInt($gallery.data('max-images')) || 0;

            // Update empty state
            if (count === 0) {
                $container.addClass('is-empty');
            } else {
                $container.removeClass('is-empty');
            }

            // Update count display
            $gallery.find('.current-count').text(count);

            // Update add button state
            const $addBtn = $gallery.find('.gallery-add-btn');
            if (maxImages > 0 && count >= maxImages) {
                $addBtn.prop('disabled', true);
            } else {
                $addBtn.prop('disabled', false);
            }
        }
    };

    // Initialize on ready
    $(document).ready(function () {
        WPFlyoutImageGallery.init();
    });

})(jQuery);