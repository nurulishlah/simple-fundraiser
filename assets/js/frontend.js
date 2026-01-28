jQuery(document).ready(function ($) {
    // ============================================
    // DONATION LIST AJAX FUNCTIONALITY
    // ============================================
    var $container = $('#sf-donations-wrapper');

    if ($container.length) {
        var campaignId = $container.data('campaign-id');
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
                        // Replace list
                        var $oldList = $container.find('.sf-donations-list');
                        if ($oldList.length) {
                            $oldList.replaceWith(response.data.html);
                        } else {
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
            loadDonations(1, sort);
        });

        // Pagination Click
        $container.on('click', '.sf-pagination a', function (e) {
            e.preventDefault();
            var href = $(this).attr('href');
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

        // Prevent form submit
        $('.sf-sort-form').on('submit', function (e) {
            e.preventDefault();
        });

        // Toggle Donations
        $('#sf-toggle-donations').on('click', function (e) {
            e.preventDefault();
            var $content = $('#sf-donations-content');
            var $btn = $(this);

            if ($content.is(':visible')) {
                $content.slideUp();
                $btn.text(sf_ajax_obj.i18n.show_donations);
            } else {
                $content.slideDown();
                $btn.text(sf_ajax_obj.i18n.hide_donations);
            }
        });
    }

    // ============================================
    // CAROUSEL WIDGET FUNCTIONALITY
    // ============================================
    function initCarousels() {
        $('.sf-widget-carousel').each(function () {
            var $carousel = $(this);

            // Skip if already initialized
            if ($carousel.data('sf-carousel-init')) {
                return;
            }
            $carousel.data('sf-carousel-init', true);

            var $track = $carousel.find('.sf-carousel-track');
            var $slides = $carousel.find('.sf-carousel-slide');
            var $dots = $carousel.find('.sf-carousel-dot');
            var $prevBtn = $carousel.find('.sf-carousel-prev');
            var $nextBtn = $carousel.find('.sf-carousel-next');
            var currentIndex = 0;
            var slideCount = $slides.length;

            if (slideCount <= 1) {
                $prevBtn.hide();
                $nextBtn.hide();
                $dots.parent().hide();
                return;
            }

            function goToSlide(index) {
                if (index < 0) {
                    index = slideCount - 1;
                } else if (index >= slideCount) {
                    index = 0;
                }

                currentIndex = index;
                var translateX = -index * 100;
                $track.css('transform', 'translateX(' + translateX + '%)');

                // Update dots
                $dots.removeClass('active');
                $dots.eq(index).addClass('active');
            }

            // Navigation buttons
            $prevBtn.on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                goToSlide(currentIndex - 1);
            });

            $nextBtn.on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                goToSlide(currentIndex + 1);
            });

            // Dot navigation
            $dots.on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var index = $(this).data('index');
                goToSlide(index);
            });

            // Touch/swipe support
            var touchStartX = 0;

            $track.on('touchstart', function (e) {
                touchStartX = e.originalEvent.touches[0].clientX;
            });

            $track.on('touchend', function (e) {
                var touchEndX = e.originalEvent.changedTouches[0].clientX;
                var diff = touchStartX - touchEndX;

                if (Math.abs(diff) > 50) {
                    if (diff > 0) {
                        goToSlide(currentIndex + 1);
                    } else {
                        goToSlide(currentIndex - 1);
                    }
                }
            });

            // Keyboard navigation
            $carousel.attr('tabindex', '0');
            $carousel.on('keydown', function (e) {
                if (e.key === 'ArrowLeft') {
                    goToSlide(currentIndex - 1);
                } else if (e.key === 'ArrowRight') {
                    goToSlide(currentIndex + 1);
                }
            });
        });
    }

    // Initialize carousels on page load
    initCarousels();

    // Re-initialize on AJAX content load
    $(document).ajaxComplete(function () {
        initCarousels();
    });
});
