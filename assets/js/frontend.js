jQuery(document).ready(function ($) {
    var $container = $('#sf-donations-wrapper');
    if (!$container.length) {
        return;
    }

    var campaignId = $container.data('campaign-id');
    var $listContainer = $container.find('.sf-donations-list-wrapper'); // We will add this wrapper
    var $sortSelect = $('#sf_sort');

    function loadDonations(page, sort) {
        // Show loading
        $container.addClass('sf-loading');
        $container.find('.sf-donations-list, .sf-pagination').css('opacity', '0.5');

        $.ajax({
            url: sf_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'sf_get_donations',
                nonce: sf_ajax_obj.nonce,
                campaign_id: campaignId,
                page: page,
                sort: sort
            },
            success: function (response) {
                if (response.success) {
                    // Update content
                    // We expect response.data.html to be the <ul> list
                    // And response.data.pagination to be the pagination div content

                    // Replace list
                    var $oldList = $container.find('.sf-donations-list');
                    if ($oldList.length) {
                        $oldList.replaceWith(response.data.html);
                    } else {
                        // If empty state previously
                        $container.find('.sf-donation-controls').after(response.data.html);
                    }

                    // Update pagination
                    var $pagination = $container.find('.sf-pagination');
                    if ($pagination.length) {
                        $pagination.html(response.data.pagination);
                    } else if (response.data.pagination) {
                        $container.append('<div class="sf-pagination">' + response.data.pagination + '</div>');
                    }
                }

                $container.removeClass('sf-loading');
                $container.find('.sf-donations-list, .sf-pagination').css('opacity', '1');
            },
            error: function () {
                console.log('Error loading donations');
                $container.removeClass('sf-loading');
                $container.find('.sf-donations-list, .sf-pagination').css('opacity', '1');
            }
        });
    }

    // Sort Change
    $sortSelect.on('change', function (e) {
        e.preventDefault();
        var sort = $(this).val();
        loadDonations(1, sort); // Reset to page 1
    });

    // Pagination Click
    // Use delegated event since pagination is replaced via AJAX
    $container.on('click', '.sf-pagination a', function (e) {
        e.preventDefault();
        var href = $(this).attr('href');
        // href is like "#2"
        var page = 1;
        if (href && href.indexOf('#') !== -1) {
            var parts = href.split('#');
            if (parts[1]) {
                page = parseInt(parts[1]);
            }
        }

        var sort = $sortSelect.val();
        loadDonations(page, sort);
    });

    // Prevent form submit if user hits enter (though it's a select)
    $('.sf-sort-form').on('submit', function (e) {
        e.preventDefault();
    });
});
