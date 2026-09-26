<?php
/**
 * Artwork Image Resolver Helper (PHP Backend)
 */

if (!function_exists('getArtworkImageSrc')) {
    /**
     * Resolves artwork image source from upload path or link with placeholder fallback
     * @param array|null $artwork
     * @param string $context 'admin' | 'api' | 'root'
     * @return string
     */
    function getArtworkImageSrc($artwork, $context = 'admin') {
        $placeholder = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='600' height='600' viewBox='0 0 600 600'><rect width='600' height='600' fill='%23f1ede6'/><text x='50%' y='48%' dominant-baseline='middle' text-anchor='middle' font-family='serif' font-size='24' fill='%238c6a43'>Art Gallery</text><text x='50%' y='54%' dominant-baseline='middle' text-anchor='middle' font-family='sans-serif' font-size='14' fill='%23888888'>No Image Available</text></svg>";

        if (!$artwork || !is_array($artwork)) {
            return $placeholder;
        }

        $image_type = $artwork['image_type'] ?? '';
        $image_path = trim($artwork['image_path'] ?? '');
        $image_url = trim($artwork['image_url'] ?? '');

        // 1. If upload type
        if ($image_type === 'upload' && !empty($image_path)) {
            if ($context === 'admin') {
                return '../uploads/artworks/' . htmlspecialchars(basename($image_path), ENT_QUOTES, 'UTF-8');
            } elseif ($context === 'root') {
                return 'uploads/artworks/' . htmlspecialchars(basename($image_path), ENT_QUOTES, 'UTF-8');
            } else {
                return 'backend/uploads/artworks/' . htmlspecialchars(basename($image_path), ENT_QUOTES, 'UTF-8');
            }
        }

        // 2. If link type
        if ($image_type === 'link' && !empty($image_url)) {
            return htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8');
        }

        // 3. Fallback if type is not strictly set
        if (!empty($image_path)) {
            $prefix = ($context === 'admin') ? '../uploads/artworks/' : 'backend/uploads/artworks/';
            return $prefix . htmlspecialchars(basename($image_path), ENT_QUOTES, 'UTF-8');
        }

        if (!empty($image_url)) {
            return htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8');
        }

        return $placeholder;
    }
}
