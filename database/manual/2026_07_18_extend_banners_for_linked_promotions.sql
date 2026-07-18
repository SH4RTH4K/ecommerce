-- One-time cPanel/phpMyAdmin fallback for deployments without Terminal.
-- Back up the database before importing. Do not import this file more than once.
ALTER TABLE `banners`
  ADD COLUMN `banner_type` VARCHAR(30) NOT NULL DEFAULT 'custom' AFTER `id`,
  ADD COLUMN `product_id` INT UNSIGNED NULL AFTER `banner_type`,
  ADD COLUMN `category_id` INT UNSIGNED NULL AFTER `product_id`,
  ADD COLUMN `button_text` VARCHAR(100) NULL AFTER `subtitle`,
  ADD COLUMN `mobile_image` VARCHAR(255) NULL AFTER `image_path`,
  ADD COLUMN `use_product_image` TINYINT(1) NOT NULL DEFAULT 0 AFTER `mobile_image`,
  ADD COLUMN `image_position` VARCHAR(30) NOT NULL DEFAULT 'center' AFTER `use_product_image`,
  ADD COLUMN `show_overlay` TINYINT(1) NOT NULL DEFAULT 1 AFTER `image_position`,
  ADD COLUMN `starts_at` TIMESTAMP NULL AFTER `display_order`,
  ADD COLUMN `expires_at` TIMESTAMP NULL AFTER `starts_at`,
  ADD COLUMN `open_in_new_tab` TINYINT(1) NOT NULL DEFAULT 0 AFTER `expires_at`,
  ADD INDEX `banners_visibility_schedule_index` (`is_active`, `starts_at`, `expires_at`),
  ADD INDEX `banners_product_id_index` (`product_id`),
  ADD INDEX `banners_category_id_index` (`category_id`);

SET @banner_migration_batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_18_000000_extend_banners_for_linked_promotions', @banner_migration_batch
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_07_18_000000_extend_banners_for_linked_promotions'
);
