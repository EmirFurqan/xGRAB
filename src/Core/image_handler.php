<?php

/**
 * Simplified Image Path Handler for src/App directory
 * Returns the web-accessible path for an image file relative to the web server root.
 * This is a simplified version that assumes images are always in /uploads/ directory.
 *
 * @param string $fileName The image file name (e.g., "avatar.png").
 * @return string HTML <img> src attribute compatible path (e.g., "/uploads/avatar.png").
 */
function getImagePath($fileName) {
    // Define the image directory path relative to web server root
    // This simplified version assumes all images are in the /uploads/ directory
    // Unlike the root image_handler.php, this doesn't detect project subdirectory
    $imageDirectory = '/uploads/'; 
    
    // Combine directory path with filename to create complete web-accessible path
    $imagePath = $imageDirectory . $fileName;
    
    return $imagePath;
}

?>