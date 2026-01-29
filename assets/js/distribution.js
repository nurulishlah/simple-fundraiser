jQuery(document).ready(function ($) {
    var $modal = $('#sf-password-modal');
    var $overlay = $('#sf-password-overlay');
    var $reportContainer = $('#sf-distribution-report-container');
    var campaignId = $reportContainer.data('campaign-id');

    // Show password modal
    $('.sf-view-report-btn').on('click', function (e) {
        e.preventDefault();
        $overlay.addClass('active');
        $('#sf-password-input').focus();
    });

    // Close modal
    $overlay.on('click', function (e) {
        if (e.target === this) {
            $overlay.removeClass('active');
        }
    });

    // Handle password submission
    $('#sf-password-submit').on('click', function () {
        var password = $('#sf-password-input').val();
        if (!password) {
            $('.sf-password-error').text('Please enter a password').show();
            return;
        }

        verifyPassword(password);
    });

    // Enter key support
    $('#sf-password-input').on('keypress', function (e) {
        if (e.which === 13) {
            $('#sf-password-submit').click();
        }
    });

    function verifyPassword(password) {
        var $btn = $('#sf-password-submit');
        var originalText = $btn.text();

        $btn.text('Verifying...').prop('disabled', true);
        $('.sf-password-error').hide();

        $.ajax({
            url: sf_dist_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'sf_verify_dist_password',
                campaign_id: campaignId,
                password: password,
                nonce: sf_dist_obj.nonce
            },
            success: function (response) {
                if (response.success) {
                    $overlay.removeClass('active');
                    loadDistributionReport(1, response.data.token);
                    // Hide the locked message and button
                    $('.sf-distribution-locked').hide();
                } else {
                    $('.sf-password-error').text(response.data.message || 'Incorrect password').show();
                    $btn.text(originalText).prop('disabled', false);
                }
            },
            error: function () {
                $('.sf-password-error').text('Server error. Please try again.').show();
                $btn.text(originalText).prop('disabled', false);
            }
        });
    }

    // Tab Switching
    $('.sf-tabs-nav li').on('click', function () {
        var tabId = $(this).data('tab');

        // Update nav
        $('.sf-tabs-nav li').removeClass('active');
        $(this).addClass('active');

        // Update content
        $('.sf-tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
    });

    // Load report (for public or verified access)
    if ($reportContainer.hasClass('sf-public-access')) {
        loadDistributionReport(1);
    }

    function loadDistributionReport(page, token) {
        $reportContainer.html('<div class="sf-loading"><span class="dashicons dashicons-update dashicons-spin"></span> Loading report...</div>');

        var data = {
            action: 'sf_get_distributions',
            campaign_id: campaignId,
            page: page,
            nonce: sf_dist_obj.nonce
        };

        if (token) {
            data.token = token;
        }

        $.ajax({
            url: sf_dist_obj.ajax_url,
            type: 'POST',
            data: data,
            success: function (response) {
                if (response.success) {
                    $reportContainer.html(response.data.html);

                    // Re-bind pagination links
                    $reportContainer.find('.page-numbers').on('click', function (e) {
                        e.preventDefault();
                        var href = $(this).attr('href');
                        var pageMatch = href.match(/sf_dpage=(\d+)/);
                        var nextPage = pageMatch ? pageMatch[1] : 1;
                        loadDistributionReport(nextPage, token);
                    });
                } else {
                    $reportContainer.html('<p class="sf-error">' + (response.data.message || 'Error loading report') + '</p>');
                }
            }
        });
    }
});
