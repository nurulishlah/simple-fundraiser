/**
 * Simple Fundraiser - Widget Admin Scripts
 * 
 * Handles reactive behavior for the Classic Widget form
 */
(function ($) {
    'use strict';

    /**
     * Initialize widget form reactivity
     * @param {jQuery} $widget The widget form container
     */
    function initWidgetForm($widget) {
        var $layoutSelect = $widget.find('select[id$="-layout"]');

        if (!$layoutSelect.length) {
            return;
        }

        // Get field rows
        var $navArrowsRow = $widget.find('input[id$="-show_nav_arrows"]').closest('p');
        var $campaignIdRow = $widget.find('.sf-field-campaign-id');
        var $countRow = $widget.find('.sf-field-count');
        var $orderByRow = $widget.find('.sf-field-order-by');
        var $statusRow = $widget.find('.sf-field-status');

        /**
         * Update field visibility based on layout
         */
        function updateFieldVisibility() {
            var layout = $layoutSelect.val();
            var isCarousel = layout === 'carousel';
            var isHeroSpotlight = layout === 'hero-spotlight';

            // Navigation arrows - only for carousel
            if (isCarousel) {
                $navArrowsRow.slideDown(200);
            } else {
                $navArrowsRow.slideUp(200);
            }

            // Campaign selector - only for hero-spotlight
            if (isHeroSpotlight) {
                $campaignIdRow.slideDown(200);
            } else {
                $campaignIdRow.slideUp(200);
            }

            // Count, Order By, Status - hide for hero-spotlight
            if (isHeroSpotlight) {
                $countRow.slideUp(200);
                $orderByRow.slideUp(200);
                $statusRow.slideUp(200);
            } else {
                $countRow.slideDown(200);
                $orderByRow.slideDown(200);
                $statusRow.slideDown(200);
            }
        }

        // Initial state (without animation)
        var layout = $layoutSelect.val();
        var isCarousel = layout === 'carousel';
        var isHeroSpotlight = layout === 'hero-spotlight';

        $navArrowsRow.toggle(isCarousel);
        $campaignIdRow.toggle(isHeroSpotlight);
        $countRow.toggle(!isHeroSpotlight);
        $orderByRow.toggle(!isHeroSpotlight);
        $statusRow.toggle(!isHeroSpotlight);

        // Listen for layout changes
        $layoutSelect.on('change', function () {
            updateFieldVisibility();
        });
    }

    /**
     * Initialize all widget forms on page load
     */
    function initAllWidgets() {
        $('.widget[id*="sf_campaigns_widget"]').each(function () {
            initWidgetForm($(this));
        });
    }

    // Initialize on document ready
    $(document).ready(function () {
        initAllWidgets();
    });

    // Re-initialize when widget is added or updated (for widget area admin)
    $(document).on('widget-added widget-updated', function (event, $widget) {
        if ($widget.is('[id*="sf_campaigns_widget"]')) {
            initWidgetForm($widget);
        }
    });

    // For Customizer - re-initialize when section is expanded
    if (typeof wp !== 'undefined' && wp.customize) {
        wp.customize.bind('ready', function () {
            wp.customize.section.each(function (section) {
                section.expanded.bind(function (isExpanded) {
                    if (isExpanded) {
                        setTimeout(function () {
                            initAllWidgets();
                        }, 100);
                    }
                });
            });
        });
    }

})(jQuery);
