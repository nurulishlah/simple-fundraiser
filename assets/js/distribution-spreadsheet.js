/**
 * Simple Fundraiser - Distribution Spreadsheet Scripts
 *
 * Handles inline editing, AJAX save/add/delete operations for distributions
 */
(function ($) {
    'use strict';

    var $table, $tbody, campaignId, debounceTimers = {};

    /**
     * Initialize the spreadsheet
     */
    function init() {
        $table = $('.sf-spreadsheet-table');
        $tbody = $('#sf-spreadsheet-body');
        campaignId = $table.data('campaign-id');

        if (!$table.length) {
            return;
        }

        bindEvents();
    }

    /**
     * Bind all event handlers
     */
    function bindEvents() {
        // Add new row
        $('#sf-add-row').on('click', addNewRow);

        // Save on input change (debounced)
        $tbody.on('change', '.sf-row:not(.sf-new-row) .sf-cell-input:not([type="hidden"])', function () {
            var $row = $(this).closest('.sf-row');
            saveRow($row);
        });

        // Save on blur for text inputs (debounced)
        $tbody.on('blur', '.sf-row:not(.sf-new-row) input[type="text"], .sf-row:not(.sf-new-row) input[type="number"], .sf-row:not(.sf-new-row) input[type="date"]', function () {
            var $row = $(this).closest('.sf-row');
            debouncedSave($row);
        });

        // Save new row
        $tbody.on('click', '.sf-save-new', function () {
            var $row = $(this).closest('.sf-row');
            saveNewRow($row);
        });

        // Cancel new row
        $tbody.on('click', '.sf-cancel-new', function () {
            $(this).closest('.sf-row').remove();
            showNoDataRowIfEmpty();
        });

        // Delete row
        $tbody.on('click', '.sf-delete', function () {
            var $row = $(this).closest('.sf-row');
            deleteRow($row);
        });

        // Upload Proof
        $tbody.on('click', '.sf-upload-proof', function (e) {
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest('.sf-row');
            var $input = $row.find('input[data-field="proof_id"]');

            // Create media frame
            var frame = wp.media({
                title: 'Select or Upload Proof of Distribution',
                button: {
                    text: 'Use this media'
                },
                multiple: false
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                $input.val(attachment.id);

                // Update preview icon/link
                var $wrapper = $button.parent();
                $wrapper.find('.sf-proof-link').remove();
                $('<a href="' + attachment.url + '" target="_blank" class="sf-proof-link"><span class="dashicons dashicons-media-default"></span></a>').insertBefore($button);

                // Trigger save
                if (!$row.hasClass('sf-new-row')) {
                    saveRow($row);
                }
            });

            frame.open();
        });

        // Keyboard shortcuts
        $tbody.on('keydown', '.sf-cell-input', function (e) {
            if (e.key === 'Enter') {
                $(this).blur();
            }
            if (e.key === 'Escape') {
                var $row = $(this).closest('.sf-row');
                if ($row.hasClass('sf-new-row')) {
                    $row.remove();
                    showNoDataRowIfEmpty();
                }
            }
        });

        // Bulk selection - Select All
        $('#sf-select-all').on('change', function () {
            var isChecked = $(this).is(':checked');
            $tbody.find('.sf-row-select').prop('checked', isChecked);
            updateBulkActions();
        });

        // Bulk selection - Individual checkbox
        $tbody.on('change', '.sf-row-select', function () {
            updateBulkActions();
            // Update "select all" state
            var total = $tbody.find('.sf-row-select').length;
            var checked = $tbody.find('.sf-row-select:checked').length;
            $('#sf-select-all').prop('checked', total === checked && total > 0);
        });

        // Apply bulk action
        $('#sf-apply-bulk').on('click', applyBulkAction);

        // Show/hide type selector based on action
        $('#sf-bulk-action').on('change', function () {
            var action = $(this).val();
            if (action === 'change_type') {
                $('#sf-bulk-type').show();
            } else {
                $('#sf-bulk-type').hide();
            }
        });

        // Clear selection
        $('#sf-clear-selection').on('click', function () {
            $tbody.find('.sf-row-select').prop('checked', false);
            $('#sf-select-all').prop('checked', false);
            updateBulkActions();
        });
    }

    /**
     * Add a new row at the top
     */
    function addNewRow() {
        var template = $('#sf-row-template').html();
        if (!template) {
            return;
        }

        // Remove "no data" row if present
        $tbody.find('.sf-no-data').remove();

        // Add new row at top
        $tbody.prepend(template);

        // Focus the first input
        $tbody.find('.sf-new-row:first .sf-cell-input:first').focus();

        // Set the campaign dropdown to current filter
        if (campaignId) {
            $tbody.find('.sf-new-row:first select[data-field="campaign_id"]').val(campaignId);
        }
    }

    /**
     * Debounced save to prevent rapid-fire saves
     */
    function debouncedSave($row) {
        var id = $row.data('id');

        if (debounceTimers[id]) {
            clearTimeout(debounceTimers[id]);
        }

        debounceTimers[id] = setTimeout(function () {
            saveRow($row);
        }, 500);
    }

    /**
     * Save an existing row
     */
    function saveRow($row) {
        var id = $row.data('id');
        var data = getRowData($row);

        setRowStatus($row, 'saving');

        $.ajax({
            url: sfDistSpreadsheet.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sf_dist_spreadsheet_save',
                nonce: sfDistSpreadsheet.nonce,
                distribution_id: id,
                data: data
            },
            success: function (response) {
                if (response.success) {
                    setRowStatus($row, 'saved');
                } else {
                    setRowStatus($row, 'error');
                }
            },
            error: function () {
                setRowStatus($row, 'error');
            }
        });
    }

    /**
     * Save a new row
     */
    function saveNewRow($row) {
        var data = getRowData($row);

        // Validate required fields
        if (!data.campaign_id || !data.amount) {
            alert('Campaign and Amount are required.');
            return;
        }

        setRowStatus($row, 'saving');
        $row.addClass('saving');

        $.ajax({
            url: sfDistSpreadsheet.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sf_dist_spreadsheet_add',
                nonce: sfDistSpreadsheet.nonce,
                data: data
            },
            success: function (response) {
                if (response.success) {
                    // Update row with new ID
                    $row.removeClass('sf-new-row saving');
                    $row.data('id', response.data.id);
                    $row.attr('data-id', response.data.id);

                    // Update ID cell
                    $row.find('.sf-col-id').html(response.data.id);

                    // Update action buttons
                    $row.find('.sf-col-actions').html(
                        '<a href="' + response.data.edit_url + '" class="button button-small" title="Edit"><span class="dashicons dashicons-edit"></span></a> ' +
                        '<button type="button" class="button button-small sf-delete" title="Delete"><span class="dashicons dashicons-trash"></span></button> ' +
                        '<span class="sf-row-status"></span>'
                    );

                    // Disable campaign dropdown after save
                    $row.find('select[data-field="campaign_id"]').prop('disabled', true);

                    setRowStatus($row, 'saved');
                } else {
                    setRowStatus($row, 'error');
                    $row.removeClass('saving');
                }
            },
            error: function () {
                setRowStatus($row, 'error');
                $row.removeClass('saving');
            }
        });
    }

    /**
     * Delete a row
     */
    function deleteRow($row) {
        if (!confirm(sfDistSpreadsheet.strings.confirmDelete)) {
            return;
        }

        var id = $row.data('id');

        $row.addClass('saving');

        $.ajax({
            url: sfDistSpreadsheet.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sf_dist_spreadsheet_delete',
                nonce: sfDistSpreadsheet.nonce,
                distribution_id: id
            },
            success: function (response) {
                if (response.success) {
                    $row.fadeOut(300, function () {
                        $(this).remove();
                        showNoDataRowIfEmpty();
                    });
                } else {
                    $row.removeClass('saving');
                    alert(sfDistSpreadsheet.strings.error);
                }
            },
            error: function () {
                $row.removeClass('saving');
                alert(sfDistSpreadsheet.strings.error);
            }
        });
    }

    /**
     * Get data from a row
     */
    function getRowData($row) {
        return {
            campaign_id: $row.find('[data-field="campaign_id"]').val(),
            amount: $row.find('[data-field="amount"]').val(),
            date: $row.find('[data-field="date"]').val(),
            type: $row.find('[data-field="type"]').val(),
            recipient: $row.find('[data-field="recipient"]').val(),
            description: $row.find('[data-field="description"]').val(),
            proof_id: $row.find('[data-field="proof_id"]').val()
        };
    }

    /**
     * Set row status indicator
     */
    function setRowStatus($row, status) {
        var $status = $row.find('.sf-row-status');

        $status.removeClass('saving saved error');

        switch (status) {
            case 'saving':
                $status.addClass('saving').html('<span class="dashicons dashicons-update spin"></span>');
                break;
            case 'saved':
                $status.addClass('saved').html('<span class="dashicons dashicons-yes"></span>');
                setTimeout(function () {
                    $status.fadeOut(200, function () {
                        $(this).html('').show();
                    });
                }, 1500);
                break;
            case 'error':
                $status.addClass('error').html('<span class="dashicons dashicons-warning"></span>');
                break;
        }
    }

    /**
     * Show no data row if table is empty
     */
    function showNoDataRowIfEmpty() {
        if ($tbody.find('.sf-row').length === 0) {
            $tbody.html('<tr class="sf-no-data"><td colspan="10">No distributions found. Add one using the button above.</td></tr>');
        }
    }

    /**
     * Update bulk actions bar visibility and count
     */
    function updateBulkActions() {
        var $checked = $tbody.find('.sf-row-select:checked');
        var count = $checked.length;
        var $bulkBar = $('.sf-bulk-actions');

        if (count > 0) {
            $bulkBar.show();
            $bulkBar.find('.sf-selected-count').text(count + ' ' + sfDistSpreadsheet.strings.selected);
        } else {
            $bulkBar.hide();
        }
    }

    /**
     * Apply the selected bulk action
     */
    function applyBulkAction() {
        var action = $('#sf-bulk-action').val();
        var $checked = $tbody.find('.sf-row-select:checked');

        if (!action) {
            alert('Please select a bulk action.');
            return;
        }

        if ($checked.length === 0) {
            alert('No items selected.');
            return;
        }

        switch (action) {
            case 'delete':
                bulkDelete($checked);
                break;
            case 'change_type':
                var typeValue = $('#sf-bulk-type').val();
                if (!typeValue) {
                    alert('Please select a type.');
                    return;
                }
                bulkUpdateField($checked, 'type', typeValue);
                break;
        }
    }

    /**
     * Bulk delete selected items
     */
    function bulkDelete($checked) {
        var ids = [];
        $checked.each(function () {
            ids.push($(this).val());
        });

        if (!confirm(sfDistSpreadsheet.strings.confirmBulkDelete.replace('%d', ids.length))) {
            return;
        }

        // Disable interactions
        $checked.closest('.sf-row').addClass('saving');

        $.ajax({
            url: sfDistSpreadsheet.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sf_dist_spreadsheet_delete', // We can use the same delete action, or bulk action. 
                // Let's implement bulk delete if needed, or loop? The donation one uses bulk_delete. I'll use separate bulk action.
                action: 'sf_dist_spreadsheet_bulk_delete',
                nonce: sfDistSpreadsheet.nonce,
                ids: ids
            },
            success: function (response) {
                if (response.success) {
                    // Remove deleted rows
                    $checked.closest('.sf-row').fadeOut(300, function () {
                        $(this).remove();
                        showNoDataRowIfEmpty();
                        updateBulkActions();
                        $('#sf-select-all').prop('checked', false);
                    });
                } else {
                    $checked.closest('.sf-row').removeClass('saving');
                    alert(sfDistSpreadsheet.strings.error);
                }
            },
            error: function () {
                $checked.closest('.sf-row').removeClass('saving');
                alert(sfDistSpreadsheet.strings.error);
            }
        });
    }

    /**
     * Bulk update a field
     */
    function bulkUpdateField($checked, field, value) {
        var ids = [];
        $checked.each(function () {
            ids.push($(this).val());
        });

        // Disable interactions
        $checked.closest('.sf-row').addClass('saving');

        $.ajax({
            url: sfDistSpreadsheet.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sf_dist_spreadsheet_bulk_update',
                nonce: sfDistSpreadsheet.nonce,
                ids: ids,
                field: field,
                value: value
            },
            success: function (response) {
                if (response.success) {
                    // Update the UI
                    $checked.each(function () {
                        var $row = $(this).closest('.sf-row');
                        if (field === 'type') {
                            var $typeField = $row.find('[data-field="type"]');
                            if ($typeField.is('select')) {
                                $typeField.val(value);
                            } else {
                                $typeField.val(value);
                            }
                        }
                        setRowStatus($row, 'saved');
                    });

                    $checked.closest('.sf-row').removeClass('saving');
                    $checked.prop('checked', false);
                    $('#sf-select-all').prop('checked', false);
                    updateBulkActions();
                } else {
                    $checked.closest('.sf-row').removeClass('saving');
                    alert(sfDistSpreadsheet.strings.error);
                }
            },
            error: function () {
                $checked.closest('.sf-row').removeClass('saving');
                alert(sfDistSpreadsheet.strings.error);
            }
        });
    }

    // Initialize on DOM ready
    $(document).ready(init);

})(jQuery);
