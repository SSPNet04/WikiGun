<?php
/**
 * Render an <img> tag, or a Bootstrap icon if no path is provided.
 * Uses onerror to hide the image silently if the file is missing on disk.
 *
 * @param string $path     Relative path stored in the DB (e.g. "assets/images/foo.jpg")
 * @param string $bi_class Bootstrap Icons class without the "bi-" prefix (e.g. "building")
 * @param string $alt      Alt text for the image
 * @param string $style    Inline CSS for the <img>
 */
function img_or_icon(string $path, string $bi_class, string $alt = '', string $style = 'height:60px;object-fit:contain;'): string {
    if (empty($path)) {
        return '<i class="bi bi-' . $bi_class . ' fs-2 text-secondary"></i>';
    }
    return '<img src="' . htmlspecialchars($path) . '"'
         . ' alt="' . htmlspecialchars($alt) . '"'
         . ' style="' . $style . '"'
         . ' onerror="this.style.display=\'none\'">';
}
