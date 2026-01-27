/**
 * Simple Fundraiser - Campaigns Block
 *
 * Gutenberg block for displaying fundraising campaigns
 */

(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var ServerSideRender = wp.serverSideRender;
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var RangeControl = wp.components.RangeControl;
    var ToggleControl = wp.components.ToggleControl;
    var __ = wp.i18n.__;
    var el = wp.element.createElement;

    // Block icon
    var blockIcon = el('svg', {
        width: 24,
        height: 24,
        viewBox: '0 0 24 24'
    },
        el('path', {
            d: 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z',
            fill: 'currentColor'
        })
    );

    registerBlockType('simple-fundraiser/campaigns', {
        title: __('Campaigns', 'simple-fundraiser'),
        description: __('Display fundraising campaigns with various layouts.', 'simple-fundraiser'),
        icon: blockIcon,
        category: 'widgets',
        keywords: [
            __('fundraiser', 'simple-fundraiser'),
            __('donation', 'simple-fundraiser'),
            __('campaign', 'simple-fundraiser')
        ],

        edit: function (props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            return el('div', { className: props.className },
                // Inspector Controls (Sidebar)
                el(InspectorControls, {},
                    el(PanelBody, {
                        title: __('Display Settings', 'simple-fundraiser'),
                        initialOpen: true
                    },
                        el(TextControl, {
                            label: __('Title', 'simple-fundraiser'),
                            value: attributes.title,
                            onChange: function (value) {
                                setAttributes({ title: value });
                            }
                        }),
                        el(SelectControl, {
                            label: __('Layout', 'simple-fundraiser'),
                            value: attributes.layout,
                            options: sfBlockData.layouts,
                            onChange: function (value) {
                                setAttributes({ layout: value });
                            }
                        }),
                        el(RangeControl, {
                            label: __('Number of Campaigns', 'simple-fundraiser'),
                            value: attributes.count,
                            onChange: function (value) {
                                setAttributes({ count: value });
                            },
                            min: 1,
                            max: 12
                        }),
                        el(SelectControl, {
                            label: __('Order By', 'simple-fundraiser'),
                            value: attributes.orderBy,
                            options: sfBlockData.orderOptions,
                            onChange: function (value) {
                                setAttributes({ orderBy: value });
                            }
                        }),
                        el(SelectControl, {
                            label: __('Status Filter', 'simple-fundraiser'),
                            value: attributes.status,
                            options: sfBlockData.statusOptions,
                            onChange: function (value) {
                                setAttributes({ status: value });
                            }
                        })
                    ),
                    el(PanelBody, {
                        title: __('Display Options', 'simple-fundraiser'),
                        initialOpen: false
                    },
                        el(ToggleControl, {
                            label: __('Show Progress Bar', 'simple-fundraiser'),
                            checked: attributes.showProgressBar,
                            onChange: function (value) {
                                setAttributes({ showProgressBar: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show Goal Amount', 'simple-fundraiser'),
                            checked: attributes.showGoal,
                            onChange: function (value) {
                                setAttributes({ showGoal: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show Donation Count', 'simple-fundraiser'),
                            checked: attributes.showDonationCount,
                            onChange: function (value) {
                                setAttributes({ showDonationCount: value });
                            }
                        })
                    ),
                    el(PanelBody, {
                        title: __('Advanced', 'simple-fundraiser'),
                        initialOpen: false
                    },
                        el(TextControl, {
                            label: __('Custom CSS Class', 'simple-fundraiser'),
                            value: attributes.customClass,
                            onChange: function (value) {
                                setAttributes({ customClass: value });
                            }
                        })
                    )
                ),

                // Server Side Render Preview
                el(ServerSideRender, {
                    block: 'simple-fundraiser/campaigns',
                    attributes: attributes
                })
            );
        },

        save: function () {
            // Server-side rendering, return null
            return null;
        }
    });
})(window.wp);
