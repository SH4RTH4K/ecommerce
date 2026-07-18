-- cPanel/phpMyAdmin alternative for 2026_07_18_010000_create_top_bar_management_tables.php
-- Select the application database, then import this file once.
CREATE TABLE IF NOT EXISTS `top_announcements` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) DEFAULT NULL,
  `message` varchar(500) NOT NULL,
  `announcement_type` varchar(30) NOT NULL DEFAULT 'general',
  `display_location` varchar(30) NOT NULL DEFAULT 'top_bar',
  `display_mode` varchar(20) NOT NULL DEFAULT 'static',
  `link_url` varchar(500) DEFAULT NULL,
  `link_text` varchar(100) DEFAULT NULL,
  `open_in_new_tab` tinyint(1) NOT NULL DEFAULT 0,
  `priority` tinyint unsigned NOT NULL DEFAULT 2,
  `display_order` int unsigned NOT NULL DEFAULT 0,
  `starts_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `show_on_desktop` tinyint(1) NOT NULL DEFAULT 1,
  `show_on_mobile` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `top_announcement_schedule` (`is_active`,`starts_at`,`expires_at`),
  KEY `top_announcement_order` (`priority`,`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `site_contact_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `contact_type` varchar(30) NOT NULL,
  `label` varchar(100) NOT NULL,
  `value` varchar(255) NOT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `default_message` text DEFAULT NULL,
  `display_order` int unsigned NOT NULL DEFAULT 0,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `show_on_desktop` tinyint(1) NOT NULL DEFAULT 1,
  `show_on_mobile` tinyint(1) NOT NULL DEFAULT 1,
  `open_in_new_tab` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `site_contact_active_order` (`is_active`,`display_order`),
  KEY `site_contact_primary` (`contact_type`,`is_primary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `site_settings` (`setting_key`,`setting_value`,`created_at`,`updated_at`) VALUES
('top_bar_enabled','1',NOW(),NOW()),('top_bar_mobile_enabled','1',NOW(),NOW()),
('top_bar_background_color','#073451',NOW(),NOW()),('top_bar_text_color','#ffffff',NOW(),NOW()),
('top_bar_link_color','#ffffff',NOW(),NOW()),('top_bar_height','36',NOW(),NOW()),
('top_bar_sticky','0',NOW(),NOW()),('top_bar_show_announcement','1',NOW(),NOW()),
('top_bar_show_contacts','1',NOW(),NOW()),('top_bar_show_support_link','1',NOW(),NOW()),
('announcement_rotation_interval','5000',NOW(),NOW()),('support_link_enabled','0',NOW(),NOW());
