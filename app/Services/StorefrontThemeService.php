<?php

namespace App\Services;

use Illuminate\Validation\Rule;

class StorefrontThemeService
{
    private const DEFAULT_PRESET = 'lucent-tech-bd';

    public function fieldGroups(): array
    {
        $fontFamilies = $this->fontFamilyOptions();
        $fontSizes = $this->fontSizeOptions();

        return [
            'branding' => [
                'label' => 'Logo Settings',
                'description' => 'Choose the logo used in different parts of the website.',
                'fields' => [
                    [
                        'key' => 'logo_variant',
                        'label' => 'Header Logo Variant',
                        'type' => 'select',
                        'default' => 'auto',
                        'options' => [
                            'auto' => 'Auto',
                            'primary' => 'Primary',
                            'light' => 'Light',
                            'dark' => 'Dark',
                        ],
                        'help' => 'Auto picks the best available logo for the current header background.',
                    ],
                    [
                        'key' => 'logo_primary',
                        'label' => 'Primary Logo Path',
                        'type' => 'text',
                        'default' => '',
                        'help' => 'Optional public path or URL for the primary logo asset.',
                    ],
                    [
                        'key' => 'logo_light',
                        'label' => 'Light Logo Path',
                        'type' => 'text',
                        'default' => '',
                        'help' => 'Optional logo used on dark backgrounds.',
                    ],
                    [
                        'key' => 'logo_dark',
                        'label' => 'Dark Logo Path',
                        'type' => 'text',
                        'default' => '',
                        'help' => 'Optional logo used on light backgrounds.',
                    ],
                ],
            ],
            'global' => [
                'label' => 'Main Website Colors',
                'description' => 'Set the main colors used across the website. Other sections can follow these colors automatically.',
                'fields' => [
                    ['key' => 'preset', 'label' => 'Theme Preset', 'type' => 'select', 'default' => self::DEFAULT_PRESET, 'options' => $this->presetOptions(), 'help' => 'A preset fills the theme controls with a curated palette.'],
                    ['key' => 'global_primary', 'label' => 'Primary Color', 'type' => 'color', 'default' => '#0b3d62', 'help' => 'Main brand color used for headings and primary accents.'],
                    ['key' => 'global_secondary', 'label' => 'Secondary Color', 'type' => 'color', 'default' => '#072b47', 'help' => 'Darker companion color used for header and footer surfaces.'],
                    ['key' => 'global_accent', 'label' => 'Accent Color', 'type' => 'color', 'default' => '#f5821f', 'help' => 'CTA and highlight color.'],
                    ['key' => 'global_success', 'label' => 'Success Color', 'type' => 'color', 'default' => '#16a34a', 'help' => 'Used for positive statuses.'],
                    ['key' => 'global_warning', 'label' => 'Warning Color', 'type' => 'color', 'default' => '#f59e0b', 'help' => 'Used for caution states and badges.'],
                    ['key' => 'global_danger', 'label' => 'Danger Color', 'type' => 'color', 'default' => '#dc2626', 'help' => 'Used for destructive actions and errors.'],
                    ['key' => 'global_info', 'label' => 'Info Color', 'type' => 'color', 'default' => '#0ea5e9', 'help' => 'Used for informational badges and notices.'],
                    ['key' => 'global_neutral_dark', 'label' => 'Neutral Dark', 'type' => 'color', 'default' => '#152536', 'help' => 'Text and heading base color.'],
                    ['key' => 'global_neutral_light', 'label' => 'Neutral Light', 'type' => 'color', 'default' => '#f4f7fa', 'help' => 'Soft background and surface color.'],
                    ['key' => 'global_page_background', 'label' => 'Page Background', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Fallback background for the storefront body.'],
                    ['key' => 'global_body_text', 'label' => 'Body Text', 'type' => 'color', 'default' => '#152536', 'help' => 'Default text color for storefront copy.'],
                    ['key' => 'global_body_muted', 'label' => 'Muted Text', 'type' => 'color', 'default' => '#667787', 'help' => 'Used for helper text and secondary copy.'],
                    ['key' => 'global_heading', 'label' => 'Heading Color', 'type' => 'color', 'default' => '#0b3d62', 'help' => 'Used for large headings and section titles.'],
                    ['key' => 'global_link', 'label' => 'Link Color', 'type' => 'color', 'default' => '#0b3d62', 'help' => 'Default link color for the storefront.'],
                    ['key' => 'global_link_hover', 'label' => 'Link Hover Color', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Used when links are hovered or focused.'],
                    ['key' => 'global_border', 'label' => 'Border Color', 'type' => 'color', 'default' => '#e4eaf0', 'help' => 'Default border color for cards and panels.'],
                ],
            ],
            'typography' => [
                'label' => 'Text Size and Font',
                'description' => 'Change the font style and text size for each part of the website.',
                'fields' => [
                    ['key' => 'topbar_font_family', 'label' => 'Announcement Bar Font', 'type' => 'select', 'default' => 'system', 'options' => $fontFamilies, 'help' => 'Text in the announcement and contact strip.'],
                    ['key' => 'topbar_font_size', 'label' => 'Announcement Bar Text Size', 'type' => 'select', 'default' => '13', 'options' => $fontSizes, 'help' => 'Text size in the announcement and contact strip.'],
                    ['key' => 'header_font_family', 'label' => 'Header Font', 'type' => 'select', 'default' => 'system', 'options' => $fontFamilies, 'help' => 'Logo tagline and header text.'],
                    ['key' => 'header_font_size', 'label' => 'Header Text Size', 'type' => 'select', 'default' => '16', 'options' => $fontSizes, 'help' => 'Main text size in the website header.'],
                    ['key' => 'search_font_family', 'label' => 'Search Font', 'type' => 'select', 'default' => 'system', 'options' => $fontFamilies, 'help' => 'Text in the search box and suggestions.'],
                    ['key' => 'search_font_size', 'label' => 'Search Text Size', 'type' => 'select', 'default' => '16', 'options' => $fontSizes, 'help' => 'Text size in the search box and suggestions.'],
                    ['key' => 'actions_font_family', 'label' => 'Wishlist, Account and Cart Font', 'type' => 'select', 'default' => 'system', 'options' => $fontFamilies, 'help' => 'Text beside the wishlist, account, compare, and cart icons.'],
                    ['key' => 'actions_font_size', 'label' => 'Wishlist, Account and Cart Text Size', 'type' => 'select', 'default' => '12', 'options' => $fontSizes, 'help' => 'Text size beside the header action icons.'],
                    ['key' => 'badges_font_family', 'label' => 'Cart and Compare Badge Font', 'type' => 'select', 'default' => 'system', 'options' => $fontFamilies, 'help' => 'Text in cart, compare, and discount badges.'],
                    ['key' => 'badges_font_size', 'label' => 'Cart and Compare Badge Text Size', 'type' => 'select', 'default' => '11', 'options' => $fontSizes, 'help' => 'Text size in counters and badges.'],
                    ['key' => 'pc_builder_font_family', 'label' => 'PC Builder Button Font', 'type' => 'select', 'default' => 'system', 'options' => $fontFamilies, 'help' => 'Text in the PC Builder button.'],
                    ['key' => 'pc_builder_font_size', 'label' => 'PC Builder Button Text Size', 'type' => 'select', 'default' => '13', 'options' => $fontSizes, 'help' => 'Text size in the PC Builder button.'],
                    ['key' => 'navigation_font_family', 'label' => 'Category Menu Font', 'type' => 'select', 'default' => 'system', 'options' => $fontFamilies, 'help' => 'Text in the category menu and dropdown.'],
                    ['key' => 'navigation_font_size', 'label' => 'Category Menu Text Size', 'type' => 'select', 'default' => '14', 'options' => $fontSizes, 'help' => 'Text size in the category menu.'],
                    ['key' => 'body_font_family', 'label' => 'Page Text Font', 'type' => 'select', 'default' => 'system', 'options' => $fontFamilies, 'help' => 'Default font for website pages.'],
                    ['key' => 'body_font_size', 'label' => 'Page Text Size', 'type' => 'select', 'default' => '16', 'options' => $fontSizes, 'help' => 'Default text size for website pages.'],
                    ['key' => 'key_features_font_family', 'label' => 'Key Features Font', 'type' => 'select', 'default' => 'inherit', 'options' => ['inherit' => 'Use Page Text Font'] + $fontFamilies, 'help' => 'Text in the Key Features panel on product pages.'],
                    ['key' => 'key_features_font_size', 'label' => 'Key Features Text Size', 'type' => 'select', 'default' => '13', 'options' => $fontSizes, 'help' => 'Base text size in the Key Features panel. The heading remains slightly larger.'],
                    ['key' => 'key_features_text_color', 'label' => 'Key Features Text Color', 'type' => 'color', 'default' => '#506474', 'help' => 'List text color in the Key Features panel.'],
                    ['key' => 'key_features_heading_color', 'label' => 'Key Features Heading Color', 'type' => 'color', 'default' => '#0b3d62', 'help' => 'Heading color in the Key Features panel.'],
                    ['key' => 'specification_font_family', 'label' => 'Specification Font', 'type' => 'select', 'default' => 'inherit', 'options' => ['inherit' => 'Use Page Text Font'] + $fontFamilies, 'help' => 'Text in specification and product-information tables.'],
                    ['key' => 'specification_font_size', 'label' => 'Specification Text Size', 'type' => 'select', 'default' => '13', 'options' => $fontSizes, 'help' => 'Text size for specification groups, labels, and values. The section heading remains larger.'],
                    ['key' => 'specification_text_color', 'label' => 'Specification Value Color', 'type' => 'color', 'default' => '#000000', 'help' => 'Color of specification values.'],
                    ['key' => 'specification_label_color', 'label' => 'Specification Label Color', 'type' => 'color', 'default' => '#666666', 'help' => 'Color of labels in the left table column.'],
                    ['key' => 'specification_group_color', 'label' => 'Specification Group Color', 'type' => 'color', 'default' => '#3749bb', 'help' => 'Color of group headings such as Camera Feature and Warranty.'],
                    ['key' => 'specification_heading_color', 'label' => 'Specification Heading Color', 'type' => 'color', 'default' => '#0b3d62', 'help' => 'Color of the main Specification section heading.'],
                    ['key' => 'cards_font_family', 'label' => 'Product Card Font', 'type' => 'select', 'default' => 'system', 'options' => $fontFamilies, 'help' => 'Product names, prices, and card controls.'],
                    ['key' => 'cards_font_size', 'label' => 'Product Card Text Size', 'type' => 'select', 'default' => '15', 'options' => $fontSizes, 'help' => 'Text size inside product cards.'],
                    ['key' => 'buttons_font_family', 'label' => 'Button Font', 'type' => 'select', 'default' => 'system', 'options' => $fontFamilies, 'help' => 'Text in website buttons.'],
                    ['key' => 'buttons_font_size', 'label' => 'Button Text Size', 'type' => 'select', 'default' => '16', 'options' => $fontSizes, 'help' => 'Text size inside website buttons.'],
                    ['key' => 'forms_font_family', 'label' => 'Form Font', 'type' => 'select', 'default' => 'system', 'options' => $fontFamilies, 'help' => 'Text in input boxes, labels, and forms.'],
                    ['key' => 'forms_font_size', 'label' => 'Form Text Size', 'type' => 'select', 'default' => '16', 'options' => $fontSizes, 'help' => 'Text size in input boxes, labels, and forms.'],
                    ['key' => 'footer_font_family', 'label' => 'Footer Font', 'type' => 'select', 'default' => 'system', 'options' => $fontFamilies, 'help' => 'Text in footer links and information.'],
                    ['key' => 'footer_font_size', 'label' => 'Footer Text Size', 'type' => 'select', 'default' => '16', 'options' => $fontSizes, 'help' => 'Text size in the website footer.'],
                    ['key' => 'breadcrumbs_font_family', 'label' => 'Breadcrumb Font', 'type' => 'select', 'default' => 'system', 'options' => $fontFamilies, 'help' => 'Text in the page path shown above content.'],
                    ['key' => 'breadcrumbs_font_size', 'label' => 'Breadcrumb Text Size', 'type' => 'select', 'default' => '13', 'options' => $fontSizes, 'help' => 'Text size in the page path shown above content.'],
                ],
            ],
            'topbar' => [
                'label' => 'Announcement Bar',
                'description' => 'Controls the message and contact strip at the top of the website.',
                'use_global' => true,
                'fields' => [
                    ['key' => 'topbar_use_global', 'label' => 'Use Main Website Colors', 'type' => 'boolean', 'default' => 1, 'help' => 'When enabled, this section follows Main Website Colors.'],
                    ['key' => 'topbar_background', 'label' => 'Background Color', 'type' => 'color', 'default' => '#073451', 'help' => 'Top bar background.'],
                    ['key' => 'topbar_text', 'label' => 'Text Color', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Top bar text color.'],
                    ['key' => 'topbar_link', 'label' => 'Link Color', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Top bar link color.'],
                    ['key' => 'topbar_link_hover', 'label' => 'Link Hover Color', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Top bar link hover color.'],
                ],
            ],
            'header' => [
                'label' => 'Website Header',
                'description' => 'Controls the logo, search box, wishlist, account, compare, cart, and PC Builder area.',
                'use_global' => true,
                'fields' => [
                    ['key' => 'header_use_global', 'label' => 'Use Main Website Colors', 'type' => 'boolean', 'default' => 1, 'help' => 'When enabled, this section follows Main Website Colors.'],
                    ['key' => 'header_background', 'label' => 'Header Background', 'type' => 'color', 'default' => '#0b2742', 'help' => 'Header surface color.'],
                    ['key' => 'header_text', 'label' => 'Header Text', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Header text color.'],
                    ['key' => 'header_link', 'label' => 'Header Link', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Header link color.'],
                    ['key' => 'header_link_hover', 'label' => 'Header Link Hover', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Header link hover color.'],
                    ['key' => 'header_icon', 'label' => 'Header Icon', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Header icon color.'],
                    ['key' => 'header_icon_hover', 'label' => 'Header Icon Hover', 'type' => 'color', 'default' => '#ffb36b', 'help' => 'Header icon hover color.'],
                ],
            ],
            'search' => [
                'label' => 'Search Box',
                'description' => 'Controls the search box background, text, border, and button colors.',
                'use_global' => true,
                'fields' => [
                    ['key' => 'search_use_global', 'label' => 'Use Main Website Colors', 'type' => 'boolean', 'default' => 1, 'help' => 'When enabled, this section follows Main Website Colors.'],
                    ['key' => 'search_background', 'label' => 'Search Background', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Search field background.'],
                    ['key' => 'search_text', 'label' => 'Search Text', 'type' => 'color', 'default' => '#152536', 'help' => 'Search field text color.'],
                    ['key' => 'search_placeholder', 'label' => 'Search Placeholder', 'type' => 'color', 'default' => '#7b8a97', 'help' => 'Placeholder text color.'],
                    ['key' => 'search_border', 'label' => 'Search Border', 'type' => 'color', 'default' => '#0b3d62', 'help' => 'Search input border.'],
                    ['key' => 'search_focus_border', 'label' => 'Search Focus Border', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Focus border color.'],
                    ['key' => 'search_button_background', 'label' => 'Search Button Background', 'type' => 'color', 'default' => '#0b3d62', 'help' => 'Search button background.'],
                    ['key' => 'search_button_icon', 'label' => 'Search Button Icon', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Search button icon color.'],
                    ['key' => 'search_button_hover', 'label' => 'Search Button Hover', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Search button hover background.'],
                ],
            ],
            'actions' => [
                'label' => 'Wishlist, Account and Cart',
                'description' => 'Controls the wishlist, account, compare, and cart icons and text.',
                'use_global' => true,
                'fields' => [
                    ['key' => 'actions_use_global', 'label' => 'Use Main Website Colors', 'type' => 'boolean', 'default' => 1, 'help' => 'When enabled, this section follows Main Website Colors.'],
                    ['key' => 'actions_icon', 'label' => 'Icon Color', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Action icon color.'],
                    ['key' => 'actions_text', 'label' => 'Text Color', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Action text color.'],
                    ['key' => 'actions_hover', 'label' => 'Hover Color', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Action hover color.'],
                    ['key' => 'actions_active', 'label' => 'Active Color', 'type' => 'color', 'default' => '#ffb36b', 'help' => 'Active state color.'],
                ],
            ],
            'badges' => [
                'label' => 'Cart and Compare Badges',
                'description' => 'Controls the small number badges shown on cart and compare items.',
                'use_global' => true,
                'fields' => [
                    ['key' => 'badges_use_global', 'label' => 'Use Main Website Colors', 'type' => 'boolean', 'default' => 1, 'help' => 'When enabled, this section follows Main Website Colors.'],
                    ['key' => 'badges_background', 'label' => 'Badge Background', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Counter background color.'],
                    ['key' => 'badges_text', 'label' => 'Badge Text', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Counter text color.'],
                    ['key' => 'badges_border', 'label' => 'Badge Border', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Counter border color.'],
                ],
            ],
            'pc_builder' => [
                'label' => 'PC Builder Button',
                'description' => 'Controls the colors of the PC Builder button.',
                'use_global' => true,
                'fields' => [
                    ['key' => 'pc_builder_use_global', 'label' => 'Use Main Website Colors', 'type' => 'boolean', 'default' => 1, 'help' => 'When enabled, this section follows Main Website Colors.'],
                    ['key' => 'pc_builder_background', 'label' => 'Background', 'type' => 'color', 'default' => '#f5821f', 'help' => 'PC Builder button background.'],
                    ['key' => 'pc_builder_text', 'label' => 'Text', 'type' => 'color', 'default' => '#ffffff', 'help' => 'PC Builder button text.'],
                    ['key' => 'pc_builder_icon', 'label' => 'Icon', 'type' => 'color', 'default' => '#ffffff', 'help' => 'PC Builder icon color.'],
                    ['key' => 'pc_builder_border', 'label' => 'Border', 'type' => 'color', 'default' => '#f5821f', 'help' => 'PC Builder border color.'],
                    ['key' => 'pc_builder_hover_background', 'label' => 'Hover Background', 'type' => 'color', 'default' => '#ff9b43', 'help' => 'PC Builder hover background.'],
                    ['key' => 'pc_builder_hover_text', 'label' => 'Hover Text', 'type' => 'color', 'default' => '#ffffff', 'help' => 'PC Builder hover text.'],
                ],
            ],
            'navigation' => [
                'label' => 'Category Menu',
                'description' => 'Controls the main category menu and its dropdown list.',
                'use_global' => true,
                'fields' => [
                    ['key' => 'navigation_use_global', 'label' => 'Use Main Website Colors', 'type' => 'boolean', 'default' => 1, 'help' => 'When enabled, this section follows Main Website Colors.'],
                    ['key' => 'navigation_background', 'label' => 'Navigation Background', 'type' => 'color', 'default' => '#0b3d62', 'help' => 'Navigation background color.'],
                    ['key' => 'navigation_text', 'label' => 'Navigation Text', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Navigation text color.'],
                    ['key' => 'navigation_hover_background', 'label' => 'Navigation Hover Background', 'type' => 'color', 'default' => $this->blendForHover('#0b3d62', 'light'), 'help' => 'Hover background color.'],
                    ['key' => 'navigation_hover_text', 'label' => 'Navigation Hover Text', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Hover text color.'],
                    ['key' => 'navigation_active_background', 'label' => 'Active Background', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Active tab background color.'],
                    ['key' => 'navigation_active_text', 'label' => 'Active Text', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Active tab text color.'],
                    ['key' => 'navigation_dropdown_background', 'label' => 'Dropdown Background', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Dropdown surface color.'],
                    ['key' => 'navigation_dropdown_text', 'label' => 'Dropdown Text', 'type' => 'color', 'default' => '#152536', 'help' => 'Dropdown text color.'],
                    ['key' => 'navigation_dropdown_hover', 'label' => 'Dropdown Hover', 'type' => 'color', 'default' => '#f7f8fa', 'help' => 'Dropdown hover background.'],
                    ['key' => 'navigation_border', 'label' => 'Border', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Dropdown and strip border color.'],
                ],
            ],
            'body' => [
                'label' => 'Page Content',
                'description' => 'Controls the main page background, text, headings, and links.',
                'use_global' => true,
                'fields' => [
                    ['key' => 'body_use_global', 'label' => 'Use Main Website Colors', 'type' => 'boolean', 'default' => 1, 'help' => 'When enabled, this section follows Main Website Colors.'],
                    ['key' => 'body_background', 'label' => 'Page Background', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Page background color.'],
                    ['key' => 'body_text', 'label' => 'Main Text Color', 'type' => 'color', 'default' => '#152536', 'help' => 'Default body text color.'],
                    ['key' => 'body_muted', 'label' => 'Muted Text Color', 'type' => 'color', 'default' => '#667787', 'help' => 'Muted body text color.'],
                    ['key' => 'body_heading', 'label' => 'Heading Color', 'type' => 'color', 'default' => '#0b3d62', 'help' => 'Body heading color.'],
                    ['key' => 'body_link', 'label' => 'Link Color', 'type' => 'color', 'default' => '#0b3d62', 'help' => 'Body link color.'],
                    ['key' => 'body_link_hover', 'label' => 'Link Hover Color', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Body link hover color.'],
                ],
            ],
            'cards' => [
                'label' => 'Product Cards',
                'description' => 'Controls product card backgrounds, borders, prices, badges, and hover effects.',
                'use_global' => true,
                'fields' => [
                    ['key' => 'cards_use_global', 'label' => 'Use Main Website Colors', 'type' => 'boolean', 'default' => 1, 'help' => 'When enabled, this section follows Main Website Colors.'],
                    ['key' => 'cards_background', 'label' => 'Card Background', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Card background color.'],
                    ['key' => 'cards_border', 'label' => 'Card Border', 'type' => 'color', 'default' => '#e4eaf0', 'help' => 'Card border color.'],
                    ['key' => 'cards_title', 'label' => 'Product Name', 'type' => 'color', 'default' => '#152536', 'help' => 'Card title color.'],
                    ['key' => 'cards_title_hover', 'label' => 'Product Name Hover', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Card title hover color.'],
                    ['key' => 'cards_price', 'label' => 'Price', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Current price color.'],
                    ['key' => 'cards_old_price', 'label' => 'Old Price', 'type' => 'color', 'default' => '#8996a1', 'help' => 'Strikethrough price color.'],
                    ['key' => 'cards_discount_badge', 'label' => 'Discount Badge', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Discount badge background.'],
                    ['key' => 'cards_stock', 'label' => 'Stock Status', 'type' => 'color', 'default' => '#16703a', 'help' => 'In-stock status color.'],
                    ['key' => 'cards_rating', 'label' => 'Rating', 'type' => 'color', 'default' => '#f3a51f', 'help' => 'Rating star color.'],
                    ['key' => 'cards_hover_border', 'label' => 'Hover Border', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Card hover border color.'],
                    ['key' => 'cards_hover_shadow', 'label' => 'Card Hover Shadow', 'type' => 'text', 'default' => '0 10px 30px rgba(11,61,98,.1)', 'help' => 'Shadow used on hover.'],
                ],
            ],
            'buttons' => [
                'label' => 'Buttons',
                'description' => 'Controls the colors of primary, secondary, accent, and danger buttons.',
                'use_global' => true,
                'fields' => [
                    ['key' => 'buttons_use_global', 'label' => 'Use Main Website Colors', 'type' => 'boolean', 'default' => 1, 'help' => 'When enabled, this section follows Main Website Colors.'],
                    ['key' => 'button_primary_background', 'label' => 'Primary Background', 'type' => 'color', 'default' => '#0b3d62', 'help' => 'Primary button background.'],
                    ['key' => 'button_primary_text', 'label' => 'Primary Text', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Primary button text.'],
                    ['key' => 'button_primary_border', 'label' => 'Primary Border', 'type' => 'color', 'default' => '#0b3d62', 'help' => 'Primary button border.'],
                    ['key' => 'button_primary_hover_background', 'label' => 'Primary Hover Background', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Primary hover background.'],
                    ['key' => 'button_primary_hover_text', 'label' => 'Primary Hover Text', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Primary hover text.'],
                    ['key' => 'button_primary_disabled_background', 'label' => 'Primary Disabled Background', 'type' => 'color', 'default' => '#a8b7c4', 'help' => 'Disabled primary background.'],
                    ['key' => 'button_primary_disabled_text', 'label' => 'Primary Disabled Text', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Disabled primary text.'],
                    ['key' => 'button_secondary_background', 'label' => 'Secondary Background', 'type' => 'color', 'default' => '#123f61', 'help' => 'Secondary button background.'],
                    ['key' => 'button_secondary_text', 'label' => 'Secondary Text', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Secondary button text.'],
                    ['key' => 'button_secondary_border', 'label' => 'Secondary Border', 'type' => 'color', 'default' => '#123f61', 'help' => 'Secondary button border.'],
                    ['key' => 'button_secondary_hover_background', 'label' => 'Secondary Hover Background', 'type' => 'color', 'default' => '#0b2742', 'help' => 'Secondary hover background.'],
                    ['key' => 'button_secondary_hover_text', 'label' => 'Secondary Hover Text', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Secondary hover text.'],
                    ['key' => 'button_accent_background', 'label' => 'Accent Background', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Accent button background.'],
                    ['key' => 'button_accent_text', 'label' => 'Accent Text', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Accent button text.'],
                    ['key' => 'button_accent_border', 'label' => 'Accent Border', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Accent button border.'],
                    ['key' => 'button_accent_hover_background', 'label' => 'Accent Hover Background', 'type' => 'color', 'default' => '#ff9b43', 'help' => 'Accent hover background.'],
                    ['key' => 'button_accent_hover_text', 'label' => 'Accent Hover Text', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Accent hover text.'],
                    ['key' => 'button_danger_background', 'label' => 'Danger Background', 'type' => 'color', 'default' => '#dc2626', 'help' => 'Danger button background.'],
                    ['key' => 'button_danger_text', 'label' => 'Danger Text', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Danger button text.'],
                    ['key' => 'button_danger_border', 'label' => 'Danger Border', 'type' => 'color', 'default' => '#dc2626', 'help' => 'Danger button border.'],
                    ['key' => 'button_danger_hover_background', 'label' => 'Danger Hover Background', 'type' => 'color', 'default' => '#b91c1c', 'help' => 'Danger hover background.'],
                    ['key' => 'button_danger_hover_text', 'label' => 'Danger Hover Text', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Danger hover text.'],
                ],
            ],
            'forms' => [
                'label' => 'Forms and Input Boxes',
                'description' => 'Controls the appearance of search, login, checkout, and contact forms.',
                'use_global' => true,
                'fields' => [
                    ['key' => 'forms_use_global', 'label' => 'Use Main Website Colors', 'type' => 'boolean', 'default' => 1, 'help' => 'When enabled, this section follows Main Website Colors.'],
                    ['key' => 'form_input_background', 'label' => 'Input Background', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Input surface color.'],
                    ['key' => 'form_input_text', 'label' => 'Input Text', 'type' => 'color', 'default' => '#152536', 'help' => 'Input text color.'],
                    ['key' => 'form_placeholder', 'label' => 'Placeholder', 'type' => 'color', 'default' => '#7b8a97', 'help' => 'Placeholder text color.'],
                    ['key' => 'form_border', 'label' => 'Border', 'type' => 'color', 'default' => '#ccd8e0', 'help' => 'Input border color.'],
                    ['key' => 'form_focus_border', 'label' => 'Focus Border', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Input focus border.'],
                    ['key' => 'form_focus_ring', 'label' => 'Focus Ring', 'type' => 'text', 'default' => 'rgba(245,130,31,.15)', 'help' => 'Focus outline glow.'],
                    ['key' => 'form_label', 'label' => 'Label', 'type' => 'color', 'default' => '#152536', 'help' => 'Form label color.'],
                    ['key' => 'form_required', 'label' => 'Required Indicator', 'type' => 'color', 'default' => '#db4b4b', 'help' => 'Required field indicator color.'],
                ],
            ],
            'footer' => [
                'label' => 'Footer Colors',
                'description' => 'Controls the footer background, text, links, icons, and bottom strip.',
                'use_global' => true,
                'fields' => [
                    ['key' => 'footer_use_global', 'label' => 'Use Main Website Colors', 'type' => 'boolean', 'default' => 1, 'help' => 'When enabled, this section follows Main Website Colors.'],
                    ['key' => 'footer_background', 'label' => 'Footer Background', 'type' => 'color', 'default' => '#072b47', 'help' => 'Footer background color.'],
                    ['key' => 'footer_heading', 'label' => 'Footer Heading', 'type' => 'color', 'default' => '#ffffff', 'help' => 'Footer heading color.'],
                    ['key' => 'footer_text', 'label' => 'Footer Text', 'type' => 'color', 'default' => '#b9ccdc', 'help' => 'Footer text color.'],
                    ['key' => 'footer_link', 'label' => 'Footer Link', 'type' => 'color', 'default' => '#b9ccdc', 'help' => 'Footer link color.'],
                    ['key' => 'footer_link_hover', 'label' => 'Footer Link Hover', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Footer link hover color.'],
                    ['key' => 'footer_border', 'label' => 'Footer Border', 'type' => 'color', 'default' => '#25445d', 'help' => 'Footer border color.'],
                    ['key' => 'footer_icon', 'label' => 'Footer Icon', 'type' => 'color', 'default' => '#f5821f', 'help' => 'Footer icon color.'],
                    ['key' => 'footer_bottom_background', 'label' => 'Bottom Footer Background', 'type' => 'color', 'default' => '#061b2c', 'help' => 'Lower footer strip background.'],
                    ['key' => 'footer_bottom_text', 'label' => 'Bottom Footer Text', 'type' => 'color', 'default' => '#b9ccdc', 'help' => 'Lower footer text color.'],
                ],
            ],
            'breadcrumbs' => [
                'label' => 'Page Path Colors',
                'description' => 'Controls the colors of the page path shown above catalog content.',
                'use_global' => true,
                'fields' => [
                    ['key' => 'breadcrumbs_use_global', 'label' => 'Use Main Website Colors', 'type' => 'boolean', 'default' => 1, 'help' => 'When enabled, this section follows Main Website Colors.'],
                    ['key' => 'breadcrumbs_background', 'label' => 'Breadcrumb Background', 'type' => 'color', 'default' => '#f7fbfe', 'help' => 'Breadcrumb background color.'],
                    ['key' => 'breadcrumbs_text', 'label' => 'Breadcrumb Text', 'type' => 'color', 'default' => '#667787', 'help' => 'Breadcrumb text color.'],
                    ['key' => 'breadcrumbs_link', 'label' => 'Breadcrumb Link', 'type' => 'color', 'default' => '#0b3d62', 'help' => 'Breadcrumb link color.'],
                    ['key' => 'breadcrumbs_active_text', 'label' => 'Breadcrumb Active Text', 'type' => 'color', 'default' => '#152536', 'help' => 'Breadcrumb active item color.'],
                    ['key' => 'breadcrumbs_separator', 'label' => 'Breadcrumb Separator', 'type' => 'color', 'default' => '#a7b4bf', 'help' => 'Breadcrumb separator color.'],
                ],
            ],
        ];
    }

    public function presetOptions(): array
    {
        return [
            'default' => 'Default',
            'lucent-tech-bd' => 'Lucent Tech BD',
            'modern-blue' => 'Modern Blue',
            'blue-orange' => 'Blue & Orange',
            'dark-technology' => 'Dark Technology',
            'light-technology' => 'Light Technology',
            'minimal' => 'Minimal',
        ];
    }

    public function fontFamilyOptions(): array
    {
        return [
            'system' => 'System UI (recommended)',
            'segoe' => 'Segoe UI',
            'arial' => 'Arial',
            'tahoma' => 'Tahoma',
            'bengali' => 'Noto Sans Bengali',
            'georgia' => 'Georgia (serif)',
            'mono' => 'Monospace',
        ];
    }

    public function fontFamilyStacks(): array
    {
        return [
            'inherit' => 'var(--theme-body-font-family, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", "Noto Sans Bengali", Arial, sans-serif)',
            'system' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", "Noto Sans Bengali", "Nirmala UI", Arial, sans-serif',
            'segoe' => '"Segoe UI", "Noto Sans Bengali", "Nirmala UI", Arial, sans-serif',
            'arial' => 'Arial, "Noto Sans Bengali", "Nirmala UI", sans-serif',
            'tahoma' => 'Tahoma, "Noto Sans Bengali", "Nirmala UI", Arial, sans-serif',
            'bengali' => '"Noto Sans Bengali", "Nirmala UI", "Vrinda", "Segoe UI", sans-serif',
            'georgia' => 'Georgia, "Noto Serif Bengali", "Noto Sans Bengali", serif',
            'mono' => '"Courier New", "Noto Sans Bengali", monospace',
        ];
    }

    public function fontSizeOptions(): array
    {
        $sizes = [];
        foreach (range(10, 22) as $size) {
            $sizes[(string) $size] = $size.' px';
        }

        return $sizes;
    }

    public function presetPalettes(): array
    {
        return [
            'default' => [
                'global_primary' => '#0b3d62',
                'global_secondary' => '#072b47',
                'global_accent' => '#f5821f',
                'global_success' => '#16a34a',
                'global_warning' => '#f59e0b',
                'global_danger' => '#dc2626',
                'global_info' => '#0ea5e9',
                'global_neutral_dark' => '#152536',
                'global_neutral_light' => '#f4f7fa',
                'global_page_background' => '#ffffff',
                'global_body_text' => '#152536',
                'global_body_muted' => '#667787',
                'global_heading' => '#0b3d62',
                'global_link' => '#0b3d62',
                'global_link_hover' => '#f5821f',
                'global_border' => '#e4eaf0',
                'topbar_background' => '#073451',
                'topbar_text' => '#ffffff',
                'topbar_link' => '#ffffff',
                'topbar_link_hover' => '#f5821f',
                'header_background' => '#0b2742',
                'header_text' => '#ffffff',
                'header_link' => '#ffffff',
                'header_link_hover' => '#f5821f',
                'header_icon' => '#f5821f',
                'header_icon_hover' => '#ffb36b',
                'search_background' => '#ffffff',
                'search_text' => '#152536',
                'search_placeholder' => '#7b8a97',
                'search_border' => '#0b3d62',
                'search_focus_border' => '#f5821f',
                'search_button_background' => '#0b3d62',
                'search_button_icon' => '#ffffff',
                'search_button_hover' => '#f5821f',
                'actions_icon' => '#f5821f',
                'actions_text' => '#ffffff',
                'actions_hover' => '#f5821f',
                'actions_active' => '#ffb36b',
                'badges_background' => '#f5821f',
                'badges_text' => '#ffffff',
                'badges_border' => '#f5821f',
                'pc_builder_background' => '#f5821f',
                'pc_builder_text' => '#ffffff',
                'pc_builder_icon' => '#ffffff',
                'pc_builder_border' => '#f5821f',
                'pc_builder_hover_background' => '#ff9b43',
                'pc_builder_hover_text' => '#ffffff',
                'navigation_background' => '#0b3d62',
                'navigation_text' => '#ffffff',
                'navigation_hover_background' => $this->blendForHover('#0b3d62', 'light'),
                'navigation_hover_text' => '#ffffff',
                'navigation_active_background' => '#f5821f',
                'navigation_active_text' => '#ffffff',
                'navigation_dropdown_background' => '#ffffff',
                'navigation_dropdown_text' => '#152536',
                'navigation_dropdown_hover' => '#f7f8fa',
                'navigation_border' => '#f5821f',
                'body_background' => '#ffffff',
                'body_text' => '#152536',
                'body_muted' => '#667787',
                'body_heading' => '#0b3d62',
                'body_link' => '#0b3d62',
                'body_link_hover' => '#f5821f',
                'cards_background' => '#ffffff',
                'cards_border' => '#e4eaf0',
                'cards_title' => '#152536',
                'cards_title_hover' => '#f5821f',
                'cards_price' => '#f5821f',
                'cards_old_price' => '#8996a1',
                'cards_discount_badge' => '#f5821f',
                'cards_stock' => '#16703a',
                'cards_rating' => '#f3a51f',
                'cards_hover_border' => '#f5821f',
                'cards_hover_shadow' => '0 10px 30px rgba(11,61,98,.1)',
                'button_primary_background' => '#0b3d62',
                'button_primary_text' => '#ffffff',
                'button_primary_border' => '#0b3d62',
                'button_primary_hover_background' => '#f5821f',
                'button_primary_hover_text' => '#ffffff',
                'button_primary_disabled_background' => '#a8b7c4',
                'button_primary_disabled_text' => '#ffffff',
                'button_secondary_background' => '#123f61',
                'button_secondary_text' => '#ffffff',
                'button_secondary_border' => '#123f61',
                'button_secondary_hover_background' => '#0b2742',
                'button_secondary_hover_text' => '#ffffff',
                'button_accent_background' => '#f5821f',
                'button_accent_text' => '#ffffff',
                'button_accent_border' => '#f5821f',
                'button_accent_hover_background' => '#ff9b43',
                'button_accent_hover_text' => '#ffffff',
                'button_danger_background' => '#dc2626',
                'button_danger_text' => '#ffffff',
                'button_danger_border' => '#dc2626',
                'button_danger_hover_background' => '#b91c1c',
                'button_danger_hover_text' => '#ffffff',
                'form_input_background' => '#ffffff',
                'form_input_text' => '#152536',
                'form_placeholder' => '#7b8a97',
                'form_border' => '#ccd8e0',
                'form_focus_border' => '#f5821f',
                'form_focus_ring' => 'rgba(245,130,31,.15)',
                'form_label' => '#152536',
                'form_required' => '#db4b4b',
                'footer_background' => '#072b47',
                'footer_heading' => '#ffffff',
                'footer_text' => '#b9ccdc',
                'footer_link' => '#b9ccdc',
                'footer_link_hover' => '#f5821f',
                'footer_border' => '#25445d',
                'footer_icon' => '#f5821f',
                'footer_bottom_background' => '#061b2c',
                'footer_bottom_text' => '#b9ccdc',
                'breadcrumbs_background' => '#f7fbfe',
                'breadcrumbs_text' => '#667787',
                'breadcrumbs_link' => '#0b3d62',
                'breadcrumbs_active_text' => '#152536',
                'breadcrumbs_separator' => '#a7b4bf',
                'logo_variant' => 'auto',
                'logo_primary' => '',
                'logo_light' => '',
                'logo_dark' => '',
            ],
            'lucent-tech-bd' => [
                'global_primary' => '#0b3d62',
                'global_secondary' => '#072b47',
                'global_accent' => '#f5821f',
                'global_success' => '#16a34a',
                'global_warning' => '#f59e0b',
                'global_danger' => '#dc2626',
                'global_info' => '#0ea5e9',
                'global_neutral_dark' => '#152536',
                'global_neutral_light' => '#f4f7fa',
                'global_page_background' => '#ffffff',
                'global_body_text' => '#152536',
                'global_body_muted' => '#667787',
                'global_heading' => '#0b3d62',
                'global_link' => '#0b3d62',
                'global_link_hover' => '#f5821f',
                'global_border' => '#e4eaf0',
                'topbar_background' => '#073451',
                'header_background' => '#0b2742',
                'navigation_background' => '#0b3d62',
                'footer_background' => '#072b47',
                'footer_bottom_background' => '#061b2c',
            ],
            'modern-blue' => [
                'global_primary' => '#0067c5',
                'global_secondary' => '#023e73',
                'global_accent' => '#ff6a21',
                'global_heading' => '#023e73',
                'global_link' => '#0067c5',
                'global_link_hover' => '#ff6a21',
                'header_background' => '#023e73',
                'navigation_background' => '#0067c5',
                'footer_background' => '#023e73',
            ],
            'blue-orange' => [
                'global_primary' => '#123f61',
                'global_secondary' => '#072b47',
                'global_accent' => '#f5821f',
                'header_background' => '#072b47',
                'navigation_background' => '#123f61',
                'footer_background' => '#072b47',
            ],
            'dark-technology' => [
                'global_primary' => '#0f172a',
                'global_secondary' => '#020617',
                'global_accent' => '#ff7a18',
                'global_body_text' => '#e2e8f0',
                'global_body_muted' => '#94a3b8',
                'global_page_background' => '#f8fafc',
                'global_heading' => '#0f172a',
                'global_link' => '#0f172a',
                'global_link_hover' => '#ff7a18',
                'header_background' => '#020617',
                'navigation_background' => '#0f172a',
                'footer_background' => '#020617',
            ],
            'light-technology' => [
                'global_primary' => '#0b5ea8',
                'global_secondary' => '#0f172a',
                'global_accent' => '#ff7a18',
                'global_body_text' => '#1f2937',
                'global_body_muted' => '#6b7280',
                'global_page_background' => '#ffffff',
                'global_heading' => '#0b5ea8',
                'global_link' => '#0b5ea8',
                'global_link_hover' => '#ff7a18',
                'header_background' => '#ffffff',
                'header_text' => '#0f172a',
                'navigation_background' => '#0b5ea8',
                'footer_background' => '#0f172a',
            ],
            'minimal' => [
                'global_primary' => '#1f3a5f',
                'global_secondary' => '#334155',
                'global_accent' => '#0ea5e9',
                'global_body_text' => '#1f2937',
                'global_body_muted' => '#64748b',
                'global_border' => '#dbe2ea',
                'header_background' => '#ffffff',
                'navigation_background' => '#1f3a5f',
                'footer_background' => '#1f3a5f',
            ],
        ];
    }

    public function defaults(): array
    {
        return array_replace($this->baseDefaults(), $this->presetPalettes()[self::DEFAULT_PRESET]);
    }

    public function baseDefaults(): array
    {
        $defaults = [];
        foreach ($this->fieldDefinitions() as $field) {
            $defaults[$field['key']] = $field['default'];
        }

        return $defaults;
    }

    public function fieldDefinitions(): array
    {
        $fields = [];
        foreach ($this->fieldGroups() as $groupKey => $group) {
            foreach ($group['fields'] as $field) {
                $field['group'] = $groupKey;
                $fields[$field['key']] = $field;
            }
        }

        return $fields;
    }

    public function fieldOrder(): array
    {
        return array_keys($this->fieldDefinitions());
    }

    public function groupedFields(): array
    {
        return $this->fieldGroups();
    }

    public function validationRules(string $root = 'storefront_theme'): array
    {
        $rules = [
            $root => ['nullable', 'array'],
        ];

        foreach ($this->fieldDefinitions() as $field) {
            $path = $root.'.'.$field['key'];
            $rules[$path] = $this->validationRuleForField($field);
        }

        return $rules;
    }

    public function validationMessages(string $root = 'storefront_theme'): array
    {
        $messages = [];
        foreach ($this->fieldDefinitions() as $field) {
            if (($field['type'] ?? null) === 'color') {
                $messages[$root.'.'.$field['key'].'.regex'] = 'Enter a valid hex color value for '.$field['label'].'.';
            }
        }

        return $messages;
    }

    public function fromSettings($settings): array
    {
        $raw = [];
        if (is_array($settings)) {
            $raw = $settings;
        } elseif (is_object($settings) && method_exists($settings, 'get')) {
            $decoded = json_decode((string) $settings->get('storefront_theme', ''), true);
            if (is_array($decoded)) {
                $raw = $decoded;
            }
        }

        return $this->normalize($raw);
    }

    public function normalize(array $theme): array
    {
        $values = $this->defaults();

        foreach ($theme as $key => $value) {
            if (!array_key_exists($key, $values)) {
                continue;
            }

            $field = $this->fieldDefinitions()[$key] ?? null;
            $values[$key] = $this->normalizeValue($value, $field, $values[$key]);
        }

        if (!array_key_exists('preset', $values) || !$values['preset'] || !array_key_exists($values['preset'], $this->presetOptions())) {
            $values['preset'] = self::DEFAULT_PRESET;
        }

        return $values;
    }

    public function resolved(array $theme): array
    {
        $theme = $this->normalize($theme);
        $defaults = $this->defaults();
        $global = $this->globalPalette($theme);
        $sectionDefaults = $this->derivedSectionDefaults($global);

        foreach ($this->sectionGroupsWithGlobalToggle() as $group) {
            $useGlobalKey = $group.'_use_global';
            if ($this->truthy($theme[$useGlobalKey] ?? $defaults[$useGlobalKey] ?? 1)) {
                foreach ($sectionDefaults[$group] as $key => $value) {
                    $theme[$key] = $value;
                }
            }
        }

        return $theme;
    }

    public function cssVariables(array $theme): array
    {
        $resolved = $this->resolved($theme);
        $global = $this->globalPalette($resolved);
        $shadow = $this->rgbaFromHex($global['global_primary'], 0.10);
        $focusRing = $this->rgbaFromHex($resolved['form_focus_border'], 0.15);
        $cardShadow = $resolved['cards_hover_shadow'] ?: '0 10px 30px rgba(11,61,98,.1)';
        $footerBorder = $resolved['footer_border'] ?: '#25445d';
        $fontStacks = $this->fontFamilyStacks();
        $font = static fn ($key, $fallback = 'system') => $fontStacks[$key] ?? $fontStacks[$fallback];

        return [
            '--navy' => $global['global_primary'],
            '--navy-dark' => $global['global_secondary'],
            '--orange' => $global['global_accent'],
            '--ink' => $global['global_body_text'],
            '--muted' => $global['global_body_muted'],
            '--line' => $global['global_border'],
            '--soft' => $global['global_neutral_light'],
            '--white' => $global['global_page_background'],
            '--shadow' => '0 10px 30px '.$shadow,
            '--tb-bg' => $resolved['topbar_background'],
            '--tb-text' => $resolved['topbar_text'],
            '--tb-link' => $resolved['topbar_link'],
            '--tb-link-hover' => $resolved['topbar_link_hover'],
            '--theme-topbar-font-family' => $font($resolved['topbar_font_family']),
            '--theme-topbar-font-size' => (int) $resolved['topbar_font_size'].'px',
            '--theme-header-font-family' => $font($resolved['header_font_family']),
            '--theme-header-font-size' => (int) $resolved['header_font_size'].'px',
            '--theme-search-font-family' => $font($resolved['search_font_family']),
            '--theme-search-font-size' => (int) $resolved['search_font_size'].'px',
            '--theme-actions-font-family' => $font($resolved['actions_font_family']),
            '--theme-actions-font-size' => (int) $resolved['actions_font_size'].'px',
            '--theme-badges-font-family' => $font($resolved['badges_font_family']),
            '--theme-badges-font-size' => (int) $resolved['badges_font_size'].'px',
            '--theme-pc-builder-font-family' => $font($resolved['pc_builder_font_family']),
            '--theme-pc-builder-font-size' => (int) $resolved['pc_builder_font_size'].'px',
            '--theme-navigation-font-family' => $font($resolved['navigation_font_family']),
            '--theme-navigation-font-size' => (int) $resolved['navigation_font_size'].'px',
            '--theme-body-font-family' => $font($resolved['body_font_family']),
            '--theme-body-font-size' => (int) $resolved['body_font_size'].'px',
            '--theme-key-features-font-family' => $font($resolved['key_features_font_family'], 'inherit'),
            '--theme-key-features-font-size' => (int) $resolved['key_features_font_size'].'px',
            '--theme-key-features-text' => $resolved['key_features_text_color'],
            '--theme-key-features-heading' => $resolved['key_features_heading_color'],
            '--theme-specification-font-family' => $font($resolved['specification_font_family'], 'inherit'),
            '--theme-specification-font-size' => (int) $resolved['specification_font_size'].'px',
            '--theme-specification-text' => $resolved['specification_text_color'],
            '--theme-specification-label' => $resolved['specification_label_color'],
            '--theme-specification-group' => $resolved['specification_group_color'],
            '--theme-specification-heading' => $resolved['specification_heading_color'],
            '--theme-cards-font-family' => $font($resolved['cards_font_family']),
            '--theme-cards-font-size' => (int) $resolved['cards_font_size'].'px',
            '--theme-buttons-font-family' => $font($resolved['buttons_font_family']),
            '--theme-buttons-font-size' => (int) $resolved['buttons_font_size'].'px',
            '--theme-forms-font-family' => $font($resolved['forms_font_family']),
            '--theme-forms-font-size' => (int) $resolved['forms_font_size'].'px',
            '--theme-footer-font-family' => $font($resolved['footer_font_family']),
            '--theme-footer-font-size' => (int) $resolved['footer_font_size'].'px',
            '--theme-breadcrumbs-font-family' => $font($resolved['breadcrumbs_font_family']),
            '--theme-breadcrumbs-font-size' => (int) $resolved['breadcrumbs_font_size'].'px',
            '--theme-page-bg' => $resolved['body_background'],
            '--theme-body-text' => $resolved['body_text'],
            '--theme-muted' => $resolved['body_muted'],
            '--theme-heading' => $resolved['body_heading'],
            '--theme-link' => $resolved['body_link'],
            '--theme-link-hover' => $resolved['body_link_hover'],
            '--theme-header-bg' => $resolved['header_background'],
            '--theme-header-text' => $resolved['header_text'],
            '--theme-header-link' => $resolved['header_link'],
            '--theme-header-link-hover' => $resolved['header_link_hover'],
            '--theme-header-icon' => $resolved['header_icon'],
            '--theme-header-icon-hover' => $resolved['header_icon_hover'],
            '--theme-search-bg' => $resolved['search_background'],
            '--theme-search-text' => $resolved['search_text'],
            '--theme-search-placeholder' => $resolved['search_placeholder'],
            '--theme-search-border' => $resolved['search_border'],
            '--theme-search-focus-border' => $resolved['search_focus_border'],
            '--theme-search-focus-ring' => $focusRing,
            '--theme-search-button-bg' => $resolved['search_button_background'],
            '--theme-search-button-icon' => $resolved['search_button_icon'],
            '--theme-search-button-hover' => $resolved['search_button_hover'],
            '--theme-actions-icon' => $resolved['actions_icon'],
            '--theme-actions-text' => $resolved['actions_text'],
            '--theme-actions-hover' => $resolved['actions_hover'],
            '--theme-actions-active' => $resolved['actions_active'],
            '--theme-badge-bg' => $resolved['badges_background'],
            '--theme-badge-text' => $resolved['badges_text'],
            '--theme-badge-border' => $resolved['badges_border'],
            '--theme-pc-builder-bg' => $resolved['pc_builder_background'],
            '--theme-pc-builder-text' => $resolved['pc_builder_text'],
            '--theme-pc-builder-icon' => $resolved['pc_builder_icon'],
            '--theme-pc-builder-border' => $resolved['pc_builder_border'],
            '--theme-pc-builder-hover-bg' => $resolved['pc_builder_hover_background'],
            '--theme-pc-builder-hover-text' => $resolved['pc_builder_hover_text'],
            '--theme-nav-bg' => $resolved['navigation_background'],
            '--theme-nav-text' => $resolved['navigation_text'],
            '--theme-nav-hover-bg' => $resolved['navigation_hover_background'],
            '--theme-nav-hover-text' => $resolved['navigation_hover_text'],
            '--theme-nav-active-bg' => $resolved['navigation_active_background'],
            '--theme-nav-active-text' => $resolved['navigation_active_text'],
            '--theme-dropdown-bg' => $resolved['navigation_dropdown_background'],
            '--theme-dropdown-text' => $resolved['navigation_dropdown_text'],
            '--theme-dropdown-hover' => $resolved['navigation_dropdown_hover'],
            '--theme-nav-border' => $resolved['navigation_border'],
            '--theme-card-bg' => $resolved['cards_background'],
            '--theme-card-border' => $resolved['cards_border'],
            '--theme-card-title' => $resolved['cards_title'],
            '--theme-card-title-hover' => $resolved['cards_title_hover'],
            '--theme-card-price' => $resolved['cards_price'],
            '--theme-card-old-price' => $resolved['cards_old_price'],
            '--theme-card-discount-badge' => $resolved['cards_discount_badge'],
            '--theme-card-stock' => $resolved['cards_stock'],
            '--theme-card-rating' => $resolved['cards_rating'],
            '--theme-card-hover-border' => $resolved['cards_hover_border'],
            '--theme-card-hover-shadow' => $cardShadow,
            '--theme-button-primary-bg' => $resolved['button_primary_background'],
            '--theme-button-primary-text' => $resolved['button_primary_text'],
            '--theme-button-primary-border' => $resolved['button_primary_border'],
            '--theme-button-primary-hover-bg' => $resolved['button_primary_hover_background'],
            '--theme-button-primary-hover-text' => $resolved['button_primary_hover_text'],
            '--theme-button-primary-disabled-bg' => $resolved['button_primary_disabled_background'],
            '--theme-button-primary-disabled-text' => $resolved['button_primary_disabled_text'],
            '--theme-button-secondary-bg' => $resolved['button_secondary_background'],
            '--theme-button-secondary-text' => $resolved['button_secondary_text'],
            '--theme-button-secondary-border' => $resolved['button_secondary_border'],
            '--theme-button-secondary-hover-bg' => $resolved['button_secondary_hover_background'],
            '--theme-button-secondary-hover-text' => $resolved['button_secondary_hover_text'],
            '--theme-button-accent-bg' => $resolved['button_accent_background'],
            '--theme-button-accent-text' => $resolved['button_accent_text'],
            '--theme-button-accent-border' => $resolved['button_accent_border'],
            '--theme-button-accent-hover-bg' => $resolved['button_accent_hover_background'],
            '--theme-button-accent-hover-text' => $resolved['button_accent_hover_text'],
            '--theme-button-danger-bg' => $resolved['button_danger_background'],
            '--theme-button-danger-text' => $resolved['button_danger_text'],
            '--theme-button-danger-border' => $resolved['button_danger_border'],
            '--theme-button-danger-hover-bg' => $resolved['button_danger_hover_background'],
            '--theme-button-danger-hover-text' => $resolved['button_danger_hover_text'],
            '--theme-form-input-bg' => $resolved['form_input_background'],
            '--theme-form-input-text' => $resolved['form_input_text'],
            '--theme-form-placeholder' => $resolved['form_placeholder'],
            '--theme-form-border' => $resolved['form_border'],
            '--theme-form-focus-border' => $resolved['form_focus_border'],
            '--theme-form-focus-ring' => $focusRing,
            '--theme-form-label' => $resolved['form_label'],
            '--theme-form-required' => $resolved['form_required'],
            '--theme-footer-bg' => $resolved['footer_background'],
            '--theme-footer-heading' => $resolved['footer_heading'],
            '--theme-footer-text' => $resolved['footer_text'],
            '--theme-footer-link' => $resolved['footer_link'],
            '--theme-footer-link-hover' => $resolved['footer_link_hover'],
            '--theme-footer-border' => $footerBorder,
            '--theme-footer-icon' => $resolved['footer_icon'],
            '--theme-footer-bottom-bg' => $resolved['footer_bottom_background'],
            '--theme-footer-bottom-text' => $resolved['footer_bottom_text'],
            '--theme-breadcrumb-bg' => $resolved['breadcrumbs_background'],
            '--theme-breadcrumb-text' => $resolved['breadcrumbs_text'],
            '--theme-breadcrumb-link' => $resolved['breadcrumbs_link'],
            '--theme-breadcrumb-active' => $resolved['breadcrumbs_active_text'],
            '--theme-breadcrumb-separator' => $resolved['breadcrumbs_separator'],
        ];
    }

    public function resolvedLogoPath(array $theme, ?string $primaryLogo = null): ?string
    {
        $resolved = $this->resolved($theme);
        $logoVariant = $resolved['logo_variant'] ?: 'auto';
        $primary = trim((string) ($resolved['logo_primary'] ?: $primaryLogo));
        $light = trim((string) $resolved['logo_light']);
        $dark = trim((string) $resolved['logo_dark']);

        $candidates = [
            'primary' => $primary,
            'light' => $light ?: $primary,
            'dark' => $dark ?: $primary,
        ];

        if ($logoVariant === 'light') {
            return $candidates['light'] ?: $candidates['primary'] ?: null;
        }

        if ($logoVariant === 'dark') {
            return $candidates['dark'] ?: $candidates['primary'] ?: null;
        }

        if ($logoVariant === 'primary') {
            return $candidates['primary'] ?: $candidates['light'] ?: $candidates['dark'] ?: null;
        }

        $headerBackground = $resolved['header_background'];
        if ($this->isDarkColor($headerBackground)) {
            return $candidates['light'] ?: $candidates['primary'] ?: $candidates['dark'] ?: null;
        }

        return $candidates['dark'] ?: $candidates['primary'] ?: $candidates['light'] ?: null;
    }

    public function contrastReport(array $theme): array
    {
        $resolved = $this->resolved($theme);
        $pairs = [
            'Top Bar' => [$resolved['topbar_text'], $resolved['topbar_background']],
            'Header' => [$resolved['header_text'], $resolved['header_background']],
            'Search' => [$resolved['search_text'], $resolved['search_background']],
            'Buttons' => [$resolved['button_primary_text'], $resolved['button_primary_background']],
            'Footer' => [$resolved['footer_text'], $resolved['footer_background']],
        ];

        $report = [];
        foreach ($pairs as $label => [$text, $background]) {
            $ratio = $this->contrastRatio($text, $background);
            $report[$label] = [
                'ratio' => $ratio,
                'level' => $ratio >= 7 ? 'Good' : ($ratio >= 4.5 ? 'Acceptable' : 'Poor'),
                'suggested_text' => $this->suggestTextColor($background),
            ];
        }

        return $report;
    }

    public function suggestTextColor(string $background): string
    {
        return $this->isDarkColor($background) ? '#ffffff' : '#111827';
    }

    public function isDarkColor(string $color): bool
    {
        $color = trim($color);
        if (!$this->isHexColor($color)) {
            return false;
        }

        $color = ltrim($color, '#');
        if (strlen($color) === 3) {
            $color = preg_replace('/(.)/', '$1$1', $color);
        } elseif (strlen($color) === 8) {
            $color = substr($color, 0, 6);
        }

        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        $luminance = (0.2126 * ($r / 255)) + (0.7152 * ($g / 255)) + (0.0722 * ($b / 255));

        return $luminance < 0.5;
    }

    public function contrastRatio(string $foreground, string $background): float
    {
        $fg = $this->relativeLuminance($foreground);
        $bg = $this->relativeLuminance($background);
        if ($fg === null || $bg === null) {
            return 0.0;
        }

        $lighter = max($fg, $bg);
        $darker = min($fg, $bg);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    public function isHexColor($value): bool
    {
        return is_string($value) && preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', trim($value)) === 1;
    }

    private function validationRuleForField(array $field): array
    {
        switch ($field['type'] ?? 'text') {
            case 'color':
                return ['nullable', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/'];
            case 'boolean':
                return ['nullable', 'boolean'];
            case 'select':
                return ['nullable', 'string', Rule::in(array_map('strval', array_keys($field['options'] ?? [])))];
            default:
                return ['nullable', 'string', 'max:255'];
        }
    }

    private function normalizeValue($value, ?array $field, $fallback)
    {
        if ($field === null) {
            return $fallback;
        }

        $type = $field['type'] ?? 'text';
        if ($type === 'boolean') {
            return $this->truthy($value) ? 1 : 0;
        }

        if ($type === 'select') {
            $value = trim((string) $value);
            $options = array_map('strval', array_keys($field['options'] ?? []));
            return in_array($value, $options, true) ? $value : $fallback;
        }

        if ($type === 'color') {
            $value = trim((string) $value);
            return $this->isHexColor($value) ? strtolower($value) : $fallback;
        }

        $value = trim(strip_tags((string) $value));
        return $value === '' ? $fallback : $value;
    }

    private function globalPalette(array $theme): array
    {
        return [
            'global_primary' => $theme['global_primary'],
            'global_secondary' => $theme['global_secondary'],
            'global_accent' => $theme['global_accent'],
            'global_success' => $theme['global_success'],
            'global_warning' => $theme['global_warning'],
            'global_danger' => $theme['global_danger'],
            'global_info' => $theme['global_info'],
            'global_neutral_dark' => $theme['global_neutral_dark'],
            'global_neutral_light' => $theme['global_neutral_light'],
            'global_page_background' => $theme['global_page_background'],
            'global_body_text' => $theme['global_body_text'],
            'global_body_muted' => $theme['global_body_muted'],
            'global_heading' => $theme['global_heading'],
            'global_link' => $theme['global_link'],
            'global_link_hover' => $theme['global_link_hover'],
            'global_border' => $theme['global_border'],
        ];
    }

    private function derivedSectionDefaults(array $global): array
    {
        $accent = $global['global_accent'];
        $primary = $global['global_primary'];
        $secondary = $global['global_secondary'];
        $body = $global['global_body_text'];
        $muted = $global['global_body_muted'];
        $page = $global['global_page_background'];
        $neutral = $global['global_neutral_light'];
        $border = $global['global_border'];

        return [
            'topbar' => [
                'topbar_background' => $secondary,
                'topbar_text' => $page,
                'topbar_link' => $page,
                'topbar_link_hover' => $accent,
            ],
            'header' => [
                'header_background' => $secondary,
                'header_text' => $page,
                'header_link' => $page,
                'header_link_hover' => $accent,
                'header_icon' => $accent,
                'header_icon_hover' => $page,
            ],
            'search' => [
                'search_background' => $page,
                'search_text' => $body,
                'search_placeholder' => $muted,
                'search_border' => $primary,
                'search_focus_border' => $accent,
                'search_button_background' => $primary,
                'search_button_icon' => $page,
                'search_button_hover' => $accent,
            ],
            'actions' => [
                'actions_icon' => $accent,
                'actions_text' => $page,
                'actions_hover' => $accent,
                'actions_active' => $page,
            ],
            'badges' => [
                'badges_background' => $accent,
                'badges_text' => $page,
                'badges_border' => $accent,
            ],
            'pc_builder' => [
                'pc_builder_background' => $accent,
                'pc_builder_text' => $page,
                'pc_builder_icon' => $page,
                'pc_builder_border' => $accent,
                'pc_builder_hover_background' => $this->blendForHover($accent, 'light'),
                'pc_builder_hover_text' => $page,
            ],
            'navigation' => [
                'navigation_background' => $primary,
                'navigation_text' => $page,
                'navigation_hover_background' => $this->blendForHover('#0b3d62', 'light'),
                'navigation_hover_text' => $page,
                'navigation_active_background' => $accent,
                'navigation_active_text' => $page,
                'navigation_dropdown_background' => $page,
                'navigation_dropdown_text' => $body,
                'navigation_dropdown_hover' => $neutral,
                'navigation_border' => $accent,
            ],
            'body' => [
                'body_background' => $page,
                'body_text' => $body,
                'body_muted' => $muted,
                'body_heading' => $global['global_heading'],
                'body_link' => $global['global_link'],
                'body_link_hover' => $global['global_link_hover'],
            ],
            'cards' => [
                'cards_background' => $page,
                'cards_border' => $border,
                'cards_title' => $body,
                'cards_title_hover' => $accent,
                'cards_price' => $accent,
                'cards_old_price' => '#8996a1',
                'cards_discount_badge' => $accent,
                'cards_stock' => $global['global_success'],
                'cards_rating' => $global['global_warning'],
                'cards_hover_border' => $accent,
                'cards_hover_shadow' => '0 10px 30px rgba(11,61,98,.1)',
            ],
            'buttons' => [
                'button_primary_background' => $primary,
                'button_primary_text' => $page,
                'button_primary_border' => $primary,
                'button_primary_hover_background' => $accent,
                'button_primary_hover_text' => $page,
                'button_primary_disabled_background' => '#a8b7c4',
                'button_primary_disabled_text' => $page,
                'button_secondary_background' => $secondary,
                'button_secondary_text' => $page,
                'button_secondary_border' => $secondary,
                'button_secondary_hover_background' => $primary,
                'button_secondary_hover_text' => $page,
                'button_accent_background' => $accent,
                'button_accent_text' => $page,
                'button_accent_border' => $accent,
                'button_accent_hover_background' => $this->blendForHover($accent, 'light'),
                'button_accent_hover_text' => $page,
                'button_danger_background' => $global['global_danger'],
                'button_danger_text' => $page,
                'button_danger_border' => $global['global_danger'],
                'button_danger_hover_background' => '#b91c1c',
                'button_danger_hover_text' => $page,
            ],
            'forms' => [
                'form_input_background' => $page,
                'form_input_text' => $body,
                'form_placeholder' => $muted,
                'form_border' => '#ccd8e0',
                'form_focus_border' => $accent,
                'form_focus_ring' => $this->rgbaFromHex($accent, 0.15),
                'form_label' => $body,
                'form_required' => '#db4b4b',
            ],
            'footer' => [
                'footer_background' => $secondary,
                'footer_heading' => $page,
                'footer_text' => '#b9ccdc',
                'footer_link' => '#b9ccdc',
                'footer_link_hover' => $accent,
                'footer_border' => '#25445d',
                'footer_icon' => $accent,
                'footer_bottom_background' => '#061b2c',
                'footer_bottom_text' => '#b9ccdc',
            ],
            'breadcrumbs' => [
                'breadcrumbs_background' => '#f7fbfe',
                'breadcrumbs_text' => $muted,
                'breadcrumbs_link' => $primary,
                'breadcrumbs_active_text' => $body,
                'breadcrumbs_separator' => '#a7b4bf',
            ],
        ];
    }

    private function sectionGroupsWithGlobalToggle(): array
    {
        return ['topbar', 'header', 'search', 'actions', 'badges', 'pc_builder', 'navigation', 'body', 'cards', 'buttons', 'forms', 'footer', 'breadcrumbs'];
    }

    private function truthy($value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on'], true);
    }

    private function relativeLuminance(string $color): ?float
    {
        if (!$this->isHexColor($color)) {
            return null;
        }

        $color = ltrim(trim($color), '#');
        if (strlen($color) === 3) {
            $color = preg_replace('/(.)/', '$1$1', $color);
        } elseif (strlen($color) === 8) {
            $color = substr($color, 0, 6);
        }

        $rgb = [
            hexdec(substr($color, 0, 2)) / 255,
            hexdec(substr($color, 2, 2)) / 255,
            hexdec(substr($color, 4, 2)) / 255,
        ];

        $transform = function (float $channel): float {
            return $channel <= 0.03928 ? $channel / 12.92 : pow(($channel + 0.055) / 1.055, 2.4);
        };

        return 0.2126 * $transform($rgb[0]) + 0.7152 * $transform($rgb[1]) + 0.0722 * $transform($rgb[2]);
    }

    private function rgbaFromHex(string $color, float $alpha): string
    {
        if (!$this->isHexColor($color)) {
            return 'rgba(11,61,98,'.$alpha.')';
        }

        $color = ltrim(trim($color), '#');
        if (strlen($color) === 3) {
            $color = preg_replace('/(.)/', '$1$1', $color);
        } elseif (strlen($color) === 8) {
            $color = substr($color, 0, 6);
        }

        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));

        return 'rgba('.$r.','.$g.','.$b.','.$alpha.')';
    }

    private function blendForHover(string $color, string $direction = 'light'): string
    {
        if (!$this->isHexColor($color)) {
            return $color;
        }

        $color = ltrim($color, '#');
        if (strlen($color) === 3) {
            $color = preg_replace('/(.)/', '$1$1', $color);
        } elseif (strlen($color) === 8) {
            $color = substr($color, 0, 6);
        }

        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        $ratio = $direction === 'light' ? 0.12 : 0.12;
        $mix = function (int $channel, int $target) use ($ratio): int {
            return (int) round($channel + (($target - $channel) * $ratio));
        };

        if ($direction === 'light') {
            return sprintf('#%02x%02x%02x', $mix($r, 255), $mix($g, 255), $mix($b, 255));
        }

        return sprintf('#%02x%02x%02x', $mix($r, 0), $mix($g, 0), $mix($b, 0));
    }
}
