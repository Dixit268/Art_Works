-- Database creation
CREATE DATABASE IF NOT EXISTS `art_gallery` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `art_gallery`;

-- Drop tables if exists (maintaining foreign key order)
DROP TABLE IF EXISTS `inquiries`;
DROP TABLE IF EXISTS `wishlists`;
DROP TABLE IF EXISTS `artworks`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

-- 1. users table
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. categories table
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. artworks table
CREATE TABLE `artworks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `category_id` INT DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `image_type` ENUM('upload', 'link') NOT NULL DEFAULT 'link',
    `image_path` VARCHAR(255) DEFAULT NULL,
    `image_url` VARCHAR(500) DEFAULT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `medium` VARCHAR(100) DEFAULT NULL,
    `dimensions` VARCHAR(100) DEFAULT NULL,
    `year_created` INT DEFAULT NULL,
    `availability_status` ENUM('available', 'sold') NOT NULL DEFAULT 'available',
    `featured` TINYINT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_artworks_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. wishlists table
CREATE TABLE `wishlists` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `artwork_id` INT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_artwork` (`user_id`, `artwork_id`),
    CONSTRAINT `fk_wishlists_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_wishlists_artwork` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. inquiries table
CREATE TABLE `inquiries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `artwork_id` INT DEFAULT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('pending', 'contacted', 'resolved') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_inquiries_artwork` FOREIGN KEY (`artwork_id`) REFERENCES `artworks` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Sample Data Inserts
-- -------------------------------------------------------------

-- Users (Admin: Admin@123, User: User@123)
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(1, 'Gallery Administrator', 'admin@gallery.com', '+1 (555) 019-2834', '$2y$10$DamKFi6a9YqBlSB1nd.r3ucoBCHOSZNr9HknDsgmst.wRmDa2Fty2', 'admin', NOW()),
(2, 'Eleanor Vance', 'user@gallery.com', '+1 (555) 014-9821', '$2y$10$.IlbqWEg9wc70Op3H1dZMu.2QPmpBQV5fn4Rn9m1FRn/0xRomsmda', 'user', NOW());

-- 5 Sample Categories
INSERT INTO `categories` (`id`, `name`, `description`, `status`) VALUES
(1, 'Oil Paintings', 'Original oil on canvas and linen compositions by contemporary and classical masters.', 'active'),
(2, 'Sculptures', 'Three-dimensional artistic expressions in bronze, marble, ceramic, and mixed media.', 'active'),
(3, 'Fine Art Photography', 'Limited edition archival prints capturing moments of surreal light and architecture.', 'active'),
(4, 'Abstract Expressionism', 'Dynamic non-representational visual art focused on color harmony and raw emotion.', 'active'),
(5, 'Digital & Mixed Media', 'Cutting-edge modern digital creations, composite media, and graphic illustrations.', 'active');

-- 8 Sample Artworks
INSERT INTO `artworks` (`id`, `title`, `category_id`, `description`, `image_type`, `image_path`, `image_url`, `price`, `medium`, `dimensions`, `year_created`, `availability_status`, `featured`, `created_at`) VALUES
(1, 'Whispers of Solitude', 1, 'An evocative landscape exploring the delicate balance of autumn hues against a quiet mountain backdrop.', 'link', NULL, 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?auto=format&fit=crop&w=1000&q=80', 3200.00, 'Oil on Canvas', '36 x 48 in', 2024, 'available', 1, NOW()),
(2, 'Elysian Harmony', 4, 'Vibrant geometric abstractions with layered golden and charcoal impasto textures.', 'link', NULL, 'https://images.unsplash.com/photo-1541701494587-cb58502866ab?auto=format&fit=crop&w=1000&q=80', 4500.00, 'Acrylic and Gold Leaf on Wood Panel', '40 x 40 in', 2023, 'available', 1, NOW()),
(3, 'The Bronze Sentinel', 2, 'Hand-cast patinated bronze sculpture capturing human posture in contemplative stillness.', 'link', NULL, 'https://images.unsplash.com/photo-1561839561-b13bcfe95249?auto=format&fit=crop&w=1000&q=80', 6800.00, 'Patinated Bronze', '24 x 12 x 10 in', 2022, 'available', 1, NOW()),
(4, 'Monochrome Serenade', 3, 'High contrast architectural photography exploring light, shadow, and modernist structural lines.', 'link', NULL, 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=1000&q=80', 1850.00, 'Archival Pigment Print', '24 x 36 in', 2024, 'available', 0, NOW()),
(5, 'Ochre Genesis', 4, 'Raw earth pigments and textured canvas inspired by desert topography and mineral strata.', 'link', NULL, 'https://images.unsplash.com/photo-1547891654-e66ed7ebb968?auto=format&fit=crop&w=1000&q=80', 2900.00, 'Mixed Media & Natural Pigments', '30 x 40 in', 2023, 'sold', 0, NOW()),
(6, 'Temporal Drift', 5, 'Modern digital composite printed on metallic acrylic sheet, exploring cosmic rhythms.', 'link', NULL, 'https://images.unsplash.com/photo-1550684848-fac1c5b4e853?auto=format&fit=crop&w=1000&q=80', 2100.00, 'Digital Archival Print on Acrylic', '32 x 48 in', 2024, 'available', 1, NOW()),
(7, 'Verdant Awakening', 1, 'Lush botanic oil composition celebrating light filtering through a dense rainforest canopy.', 'link', NULL, 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?auto=format&fit=crop&w=1000&q=80', 3750.00, 'Oil on Linen', '30 x 30 in', 2023, 'available', 0, NOW()),
(8, 'Torso in White Carrara', 2, 'Hand-carved Carrara marble statue reflecting classical Hellenistic elegance.', 'link', NULL, 'https://images.unsplash.com/photo-1544531586-fde5298cdd40?auto=format&fit=crop&w=1000&q=80', 8200.00, 'Carrara Marble', '28 x 14 x 12 in', 2021, 'available', 1, NOW());

-- Sample Wishlist
INSERT INTO `wishlists` (`user_id`, `artwork_id`, `created_at`) VALUES
(2, 1, NOW()),
(2, 3, NOW());

-- Sample Inquiries
INSERT INTO `inquiries` (`name`, `email`, `phone`, `artwork_id`, `message`, `status`, `created_at`) VALUES
('Marcus Brody', 'marcus.brody@example.com', '+1 (555) 329-8471', 1, 'Hello, I am interested in acquiring "Whispers of Solitude". Is insured international shipping available to London?', 'pending', NOW()),
('Sophia Rossi', 'sophia.rossi@example.com', '+1 (555) 782-9012', 3, 'Could you provide a certificate of authenticity and provenance details for "The Bronze Sentinel"?', 'contacted', NOW());
