/**
 * Simple Fundraiser - Spreadsheet Scripts
 *
 * Handles inline editing, AJAX save/add/delete operations
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
        $tbody.on('change', '.sf-row:not(.sf-new-row) .sf-cell-input', function () {
            var $row = $(this).closest('.sf-row');
            saveRow($row);
        });

        // Save on blur for text inputs (debounced)
        $tbody.on('blur', '.sf-row:not(.sf-new-row) input[type="text"], .sf-row:not(.sf-new-row) input[type="number"]', function () {
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
            url: sfSpreadsheet.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sf_spreadsheet_save',
                nonce: sfSpreadsheet.nonce,
                donation_id: id,
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
            url: sfSpreadsheet.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sf_spreadsheet_add',
                nonce: sfSpreadsheet.nonce,
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
        if (!confirm(sfSpreadsheet.strings.confirmDelete)) {
            return;
        }

        var id = $row.data('id');

        $row.addClass('saving');

        $.ajax({
            url: sfSpreadsheet.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sf_spreadsheet_delete',
                nonce: sfSpreadsheet.nonce,
                donation_id: id
            },
            success: function (response) {
                if (response.success) {
                    $row.fadeOut(300, function () {
                        $(this).remove();
                        showNoDataRowIfEmpty();
                    });
                } else {
                    $row.removeClass('saving');
                    alert(sfSpreadsheet.strings.error);
                }
            },
            error: function () {
                $row.removeClass('saving');
                alert(sfSpreadsheet.strings.error);
            }
        });
    }

    /**
     * Get data from a row
     */
    function getRowData($row) {
        return {
            campaign_id: $row.find('[data-field="campaign_id"]').val(),
            donor_name: $row.find('[data-field="donor_name"]').val(),
            amount: $row.find('[data-field="amount"]').val(),
            date: $row.find('[data-field="date"]').val(),
            type: $row.find('[data-field="type"]').val(),
            anonymous: $row.find('[data-field="anonymous"]').is(':checked') ? '1' : '0'
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
            $tbody.html('<tr class="sf-no-data"><td colspan="8">No donations found. Add one using the button above.</td></tr>');
        }
    }

    // Initialize on DOM ready
    $(document).ready(init);

})(jQuery);
