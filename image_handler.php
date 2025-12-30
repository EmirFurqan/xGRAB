<?php

/**
 * Image Handler Utility
 * Provides functions for handling image paths and uploads in the Movie project.
 * This file should be included in any PHP file that needs to work with images.
 */

/**
 * Returns the web-accessible path for an image file.
 * This path is relative to the web server root (/) and works regardless of
 * which directory the PHP file is located in.
 *
 * @param string $fileName The image file name (e.g., "avatar_123_1234567890.jpg").
 * @param string $type The type of image: 'avatar', 'poster', 'cast', or 'general' (default).
 * @return string HTML <img> src attribute compatible path (e.g., "/Movie/uploads/avatar_123.jpg").
 */
function getImagePath($fileName, $type = 'general') {
    // If fileName is empty or null, return empty string
    if (empty($fileName)) {
        return '';
    }
    
    // If fileName already contains a full URL or absolute path, return as is
    if (filter_var($fileName, FILTER_VALIDATE_URL) || strpos($fileName, 'http://') === 0 || strpos($fileName, 'https://') === 0) {
        return $fileName;
    }
    
    // If fileName already starts with /uploads/ or /Movie/uploads/, return as is (already a web path)
    if (strpos($fileName, '/uploads/') === 0 || strpos($fileName, '/Movie/uploads/') === 0) {
        return $fileName;
    }
    
    // Detect project base path
    // image_handler.php is in the project root, so we can use its location relative to DOCUMENT_ROOT
    $projectBase = '';
    
    if (isset($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['DOCUMENT_ROOT'])) {
        $handlerPath = str_replace('\\', '/', __FILE__);
        $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        
        // Remove trailing slashes for comparison
        $docRoot = rtrim($docRoot, '/\\');
        $handlerPath = rtrim($handlerPath, '/\\');
        
        // Get the relative path from document root to image_handler.php
        if (strpos($handlerPath, $docRoot) === 0) {
            $relativePath = substr($handlerPath, strlen($docRoot));
            $relativePath = ltrim($relativePath, '/\\');
            
            // Extract the project folder name (first directory after document root)
            // e.g., "Movie/image_handler.php" -> "/Movie"
            if (preg_match('#^([^/\\\\]+)#', $relativePath, $matches)) {
                $projectBase = '/' . $matches[1];
            }
        }
    }
    
    // Fallback: try to detect from SCRIPT_NAME if available
    if (empty($projectBase) && isset($_SERVER['SCRIPT_NAME'])) {
        $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        if (preg_match('#^/([^/]+)/#', $scriptPath, $matches)) {
            $projectBase = '/' . $matches[1];
        }
    }
    
    // Define image directories based on type - all images are in uploads folder
    $imageDirectories = [
        'avatar' => $projectBase . '/uploads/',
        'poster' => $projectBase . '/uploads/',
        'cast' => $projectBase . '/uploads/',
        'crew' => $projectBase . '/uploads/',
        'general' => $projectBase . '/uploads/'
    ];
    
    // Get the appropriate directory for the image type
    $imageDirectory = isset($imageDirectories[$type]) ? $imageDirectories[$type] : $imageDirectories['general'];
    
    // Combine and return the path
    $imagePath = $imageDirectory . $fileName;
    
    return $imagePath;
}

/**
 * Gets the full server path for uploading files.
 * This is the physical path on the server, not the web-accessible URL.
 *
 * @param string $type The type of image: 'avatar', 'poster', 'cast', or 'general' (default).
 * @return string Full server path to the upload directory.
 */
function getUploadDirectory($type = 'general') {
    // Get the directory where image_handler.php is located (project root)
    $projectRoot = dirname(__FILE__);
    
    // Get absolute path of project root
    $projectRoot = realpath($projectRoot);
    if ($projectRoot === false) {
        // Fallback: use current working directory
        $projectRoot = getcwd();
    }
    
    // All uploads go directly to the uploads folder
    $uploadDir = 'uploads';
    
    // Combine project root with upload directory
    $fullPath = $projectRoot . DIRECTORY_SEPARATOR . $uploadDir;
    
    // Create directory if it doesn't exist
    if (!file_exists($fullPath)) {
        mkdir($fullPath, 0777, true);
    }
    
    return $fullPath;
}

/**
 * Uploads an image file and returns the filename.
 * Handles file validation, naming, and storage.
 *
 * @param array $file The $_FILES array element (e.g., $_FILES['avatar']).
 * @param string $type The type of image: 'avatar', 'poster', 'cast', or 'general'.
 * @param string $prefix Optional prefix for the filename (e.g., "avatar_" or "poster_").
 * @param int $maxSize Maximum file size in bytes (default: 5MB).
 * @return array ['success' => bool, 'filename' => string, 'error' => string]
 */
function uploadImage($file, $type = 'general', $prefix = '', $maxSize = 5242880) {
    $result = [
        'success' => false,
        'filename' => '',
        'error' => ''
    ];
    
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'No file uploaded or upload error occurred.';
        return $result;
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $result['error'] = 'File size exceeds maximum allowed size (' . ($maxSize / 1024 / 1024) . 'MB).';
        return $result;
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        $result['error'] = 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.';
        return $result;
    }
    
    // Get file extension
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Generate unique filename
    $filename = $prefix . time() . '_' . uniqid() . '.' . $fileExtension;
    
    // Get upload directory
    $uploadDir = getUploadDirectory($type);
    
    // Ensure directory exists and is writable
    if (!is_dir($uploadDir)) {
        $result['error'] = 'Upload directory does not exist: ' . $uploadDir;
        return $result;
    }
    
    if (!is_writable($uploadDir)) {
        $result['error'] = 'Upload directory is not writable: ' . $uploadDir . '. Please check folder permissions.';
        return $result;
    }
    
    // Check if temp file exists
    if (!file_exists($file['tmp_name'])) {
        $result['error'] = 'Temporary file does not exist. Upload may have failed.';
        return $result;
    }
    
    // Construct full path using DIRECTORY_SEPARATOR
    $uploadPath = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
    
    // Move uploaded file
    $moveResult = move_uploaded_file($file['tmp_name'], $uploadPath);
    
    if ($moveResult) {
        // Verify file was actually moved
        if (file_exists($uploadPath)) {
            $result['success'] = true;
            $result['filename'] = $filename;
        } else {
            // File was "moved" but doesn't exist at destination - check if it's in a different location
            $result['error'] = 'File was moved but cannot be found at destination: ' . $uploadPath . '. Please check if file exists elsewhere.';
        }
    } else {
        // Try alternative: copy instead of move
        if (copy($file['tmp_name'], $uploadPath)) {
            if (file_exists($uploadPath)) {
                $result['success'] = true;
                $result['filename'] = $filename;
            } else {
                $result['error'] = 'File was copied but cannot be found at destination: ' . $uploadPath;
            }
        } else {
            $result['error'] = 'Failed to move/copy uploaded file. Upload dir: ' . $uploadDir . ', Target path: ' . $uploadPath . ', Temp file: ' . $file['tmp_name'] . ', is_writable: ' . (is_writable($uploadDir) ? 'yes' : 'no');
        }
    }
    
    return $result;
}

/**
 * Deletes an image file from the server.
 *
 * @param string $fileName The filename to delete.
 * @param string $type The type of image: 'avatar', 'poster', 'cast', or 'general'.
 * @return bool True if file was deleted successfully, false otherwise.
 */
function deleteImage($fileName, $type = 'general') {
    if (empty($fileName)) {
        return false;
    }
    
    // Get upload directory
    $uploadDir = getUploadDirectory($type);
    $filePath = $uploadDir . $fileName;
    
    // Check if file exists and delete
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    
    return false;
}

/**
 * Validates if a file is a valid image.
 *
 * @param array $file The $_FILES array element.
 * @return bool True if valid image, false otherwise.
 */
function isValidImage($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    return in_array($mimeType, $allowedTypes);
}

?>

