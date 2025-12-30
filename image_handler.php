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
    // Handle empty or null filenames by returning empty string
    // This prevents errors when displaying images that may not exist
    if (empty($fileName)) {
        return '';
    }
    
    // Check if the filename is already a complete URL (external image)
    // If it's a valid URL or starts with http:// or https://, return it unchanged
    // This allows the function to handle both local and external images
    if (filter_var($fileName, FILTER_VALIDATE_URL) || strpos($fileName, 'http://') === 0 || strpos($fileName, 'https://') === 0) {
        return $fileName;
    }
    
    // Check if the filename already contains a web-accessible path
    // If it starts with /uploads/ or /Movie/uploads/, it's already a proper web path
    // Return it as-is to avoid duplicating path components
    if (strpos($fileName, '/uploads/') === 0 || strpos($fileName, '/Movie/uploads/') === 0) {
        return $fileName;
    }
    
    // Detect the project's base path relative to the web server document root
    // This is necessary because the project may be in a subdirectory (e.g., /xGRAB/)
    // The path detection ensures images work regardless of where the project is installed
    $projectBase = '';
    
    // Primary method: Use DOCUMENT_ROOT to calculate relative path
    if (isset($_SERVER['DOCUMENT_ROOT']) && !empty($_SERVER['DOCUMENT_ROOT'])) {
        // Normalize path separators to forward slashes for cross-platform compatibility
        $handlerPath = str_replace('\\', '/', __FILE__);
        $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        
        // Remove trailing slashes to ensure consistent path comparison
        $docRoot = rtrim($docRoot, '/\\');
        $handlerPath = rtrim($handlerPath, '/\\');
        
        // Check if the handler file is within the document root
        // If so, extract the relative path to determine the project folder name
        if (strpos($handlerPath, $docRoot) === 0) {
            // Calculate relative path by removing document root from handler path
            $relativePath = substr($handlerPath, strlen($docRoot));
            $relativePath = ltrim($relativePath, '/\\');
            
            // Extract the first directory name which represents the project folder
            // Example: "xGRAB/image_handler.php" -> "/xGRAB"
            if (preg_match('#^([^/\\\\]+)#', $relativePath, $matches)) {
                $projectBase = '/' . $matches[1];
            }
        }
    }
    
    // Fallback method: Use SCRIPT_NAME if DOCUMENT_ROOT method failed
    // SCRIPT_NAME contains the path of the currently executing script
    if (empty($projectBase) && isset($_SERVER['SCRIPT_NAME'])) {
        $scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        // Extract project folder from script path (first directory after root)
        if (preg_match('#^/([^/]+)/#', $scriptPath, $matches)) {
            $projectBase = '/' . $matches[1];
        }
    }
    
    // Define image directory paths based on image type
    // Avatars are stored in a subdirectory for better organization
    $imageDirectories = [
        'avatar' => $projectBase . '/uploads/avatars/',
        'poster' => $projectBase . '/uploads/',
        'cast' => $projectBase . '/uploads/',
        'crew' => $projectBase . '/uploads/',
        'general' => $projectBase . '/uploads/'
    ];
    
    // Select the appropriate directory based on image type, defaulting to 'general' if type is invalid
    $imageDirectory = isset($imageDirectories[$type]) ? $imageDirectories[$type] : $imageDirectories['general'];
    
    // Combine directory path with filename to create complete web-accessible path
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
    // Get the directory where this file (image_handler.php) is located
    // This represents the project root directory
    $projectRoot = dirname(__FILE__);
    
    // Convert relative path to absolute path and resolve any symbolic links
    // realpath() returns false if the path doesn't exist
    $projectRoot = realpath($projectRoot);
    if ($projectRoot === false) {
        // If realpath fails, fall back to current working directory
        // This handles edge cases where __FILE__ might not resolve correctly
        $projectRoot = getcwd();
    }
    
    // Define the upload directory name based on image type
    // Avatars are stored in a subdirectory for better organization
    if ($type === 'avatar') {
        $uploadDir = 'uploads' . DIRECTORY_SEPARATOR . 'avatars';
    } else {
        $uploadDir = 'uploads';
    }
    
    // Combine project root with upload directory using platform-appropriate separator
    // DIRECTORY_SEPARATOR is '\' on Windows and '/' on Unix systems
    $fullPath = $projectRoot . DIRECTORY_SEPARATOR . $uploadDir;
    
    // Create the upload directory if it doesn't exist
    // mkdir with recursive flag (true) creates parent directories if needed
    // 0777 permissions allow read/write/execute for all users (may need adjustment for production)
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
    // Initialize result array to track upload status
    // This structure allows callers to check success and handle errors appropriately
    $result = [
        'success' => false,
        'filename' => '',
        'error' => ''
    ];
    
    // Validate that a file was actually uploaded and no upload errors occurred
    // UPLOAD_ERR_OK (value 0) indicates successful upload
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $result['error'] = 'No file uploaded or upload error occurred.';
        return $result;
    }
    
    // Check if file size exceeds the maximum allowed size
    // Default maxSize is 5242880 bytes (5MB)
    // Convert bytes to MB for user-friendly error message
    if ($file['size'] > $maxSize) {
        $result['error'] = 'File size exceeds maximum allowed size (' . ($maxSize / 1024 / 1024) . 'MB).';
        return $result;
    }
    
    // Validate file MIME type to ensure it's actually an image
    // Using finfo extension to detect actual file type, not just extension
    // This prevents users from uploading malicious files with fake extensions
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    // Reject files that don't match allowed image MIME types
    if (!in_array($mimeType, $allowedTypes)) {
        $result['error'] = 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.';
        return $result;
    }
    
    // Extract file extension from original filename for use in new filename
    // Convert to lowercase for consistency
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Generate unique filename to prevent overwriting existing files
    // Format: prefix + timestamp + unique ID + extension
    // Example: "poster_1234567890_abc123def.jpg"
    $filename = $prefix . time() . '_' . uniqid() . '.' . $fileExtension;
    
    // Get the full server path to the upload directory
    $uploadDir = getUploadDirectory($type);
    
    // Verify upload directory exists as a directory
    if (!is_dir($uploadDir)) {
        $result['error'] = 'Upload directory does not exist: ' . $uploadDir;
        return $result;
    }
    
    // Check if directory is writable to ensure file can be saved
    if (!is_writable($uploadDir)) {
        $result['error'] = 'Upload directory is not writable: ' . $uploadDir . '. Please check folder permissions.';
        return $result;
    }
    
    // Verify temporary uploaded file still exists
    // Sometimes temp files can be cleaned up before processing completes
    if (!file_exists($file['tmp_name'])) {
        $result['error'] = 'Temporary file does not exist. Upload may have failed.';
        return $result;
    }
    
    // Construct full destination path using platform-appropriate directory separator
    // Remove any trailing slashes from upload directory before appending filename
    $uploadPath = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
    
    // Attempt to move uploaded file from temporary location to final destination
    // move_uploaded_file() is the secure PHP function for handling uploaded files
    $moveResult = move_uploaded_file($file['tmp_name'], $uploadPath);
    
    if ($moveResult) {
        // Verify the file actually exists at destination after move
        // This catches edge cases where move_uploaded_file returns true but file isn't there
        if (file_exists($uploadPath)) {
            $result['success'] = true;
            $result['filename'] = $filename;
        } else {
            // File was reported as moved but doesn't exist at destination
            // This can happen due to filesystem issues or permission problems
            $result['error'] = 'File was moved but cannot be found at destination: ' . $uploadPath . '. Please check if file exists elsewhere.';
        }
    } else {
        // If move_uploaded_file fails, try copy as fallback
        // This handles cases where move fails due to cross-filesystem issues
        if (copy($file['tmp_name'], $uploadPath)) {
            if (file_exists($uploadPath)) {
                $result['success'] = true;
                $result['filename'] = $filename;
            } else {
                $result['error'] = 'File was copied but cannot be found at destination: ' . $uploadPath;
            }
        } else {
            // Both move and copy failed - provide detailed error information for debugging
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
    // Return false immediately if filename is empty to avoid unnecessary processing
    if (empty($fileName)) {
        return false;
    }
    
    // Get the full server path to the upload directory for this image type
    $uploadDir = getUploadDirectory($type);
    
    // Construct full file path by combining directory with filename
    $filePath = $uploadDir . $fileName;
    
    // Only attempt deletion if file actually exists
    // unlink() will fail silently if file doesn't exist, but checking first is clearer
    if (file_exists($filePath)) {
        // Delete the file and return the result (true on success, false on failure)
        return unlink($filePath);
    }
    
    // Return false if file doesn't exist (can't delete what isn't there)
    return false;
}

/**
 * Validates if a file is a valid image.
 *
 * @param array $file The $_FILES array element.
 * @return bool True if valid image, false otherwise.
 */
function isValidImage($file) {
    // Check if file was uploaded successfully before validating type
    // This prevents errors when trying to validate non-existent files
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Define allowed image MIME types
    // These are the standard MIME types for common image formats
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    
    // Use finfo extension to detect actual file MIME type from file contents
    // This is more secure than trusting the file extension
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    // Return true if detected MIME type is in the allowed list
    return in_array($mimeType, $allowedTypes);
}

?>

