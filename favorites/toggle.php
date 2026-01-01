<?php
/**
 * Favorite Toggle Handler
 * Adds or removes favorites for movies, cast members, or users.
 * Supports both AJAX and standard form submissions.
 * Validates entity existence before toggling favorite status.
 */

session_start();
// Include config if not already loaded
if (!defined('BASE_URL') && file_exists(__DIR__ . '/../includes/config.php')) {
    require_once __DIR__ . '/../includes/config.php';
}
require("../connect.php");

// Require user to be logged in to manage favorites
// Require user to be logged in to manage favorites
if (!isset($_SESSION['user_id'])) {
    // Check if it's an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'You must be logged in to manage favorites.'
        ]);
        exit();
    }

    header("Location: ../login.php");
    exit();
}

// Validate required parameters are present
if (!isset($_POST['entity_type']) || !isset($_POST['entity_id'])) {
    header("Location: ../movies/browse.php?error=Invalid request");
    exit();
}

$user_id = $_SESSION['user_id'];
$entity_type = escapeString($_POST['entity_type']);
$entity_id = (int) $_POST['entity_id'];

// Validate entity_type is one of the allowed types
// The favorites table supports movies, cast members, and users
if (!in_array($entity_type, ['movie', 'cast', 'user'])) {
    header("Location: ../movies/browse.php?error=Invalid entity type");
    exit();
}

// Verify that the entity actually exists in the database
// This prevents favoriting non-existent items
$valid = false;
if ($entity_type == 'movie') {
    $check_sql = "SELECT movie_id FROM movies WHERE movie_id = $entity_id";
    $check_result = myQuery($check_sql);
    $valid = mysqli_num_rows($check_result) > 0;
} elseif ($entity_type == 'cast') {
    $check_sql = "SELECT cast_id FROM cast_members WHERE cast_id = $entity_id";
    $check_result = myQuery($check_sql);
    $valid = mysqli_num_rows($check_result) > 0;
} elseif ($entity_type == 'user') {
    $check_sql = "SELECT user_id FROM users WHERE user_id = $entity_id";
    $check_result = myQuery($check_sql);
    $valid = mysqli_num_rows($check_result) > 0;
    // Prevent users from favoriting themselves (no self-following)
    if ($entity_id == $user_id) {
        $valid = false;
    }
}

if (!$valid) {
    header("Location: ../movies/browse.php?error=Invalid entity");
    exit();
}

// Check if favorite relationship already exists
$check_favorite_sql = "SELECT favorite_id FROM favorites 
                       WHERE user_id = $user_id 
                       AND entity_type = '$entity_type' 
                       AND entity_id = $entity_id";
$check_favorite_result = myQuery($check_favorite_sql);

// Detect if request is AJAX (for dynamic UI updates without page refresh)
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Determine redirect URL based on entity type
// Uses custom redirect_url if provided, otherwise defaults to entity's detail page
$redirect_url = "../movies/browse.php";
if (isset($_POST['redirect_url'])) {
    $redirect_url = escapeString($_POST['redirect_url']);
} else {
    if ($entity_type == 'movie') {
        $redirect_url = "../movies/details.php?id=$entity_id";
    } elseif ($entity_type == 'cast') {
        $redirect_url = "../cast/details.php?id=$entity_id";
    } elseif ($entity_type == 'user') {
        $redirect_url = "../profile/view.php?user_id=$entity_id";
    }
}

// Toggle favorite: remove if exists, add if doesn't exist
// Check if an explicit intent is provided (add/remove) to handle state mismatches
$intent = isset($_POST['intent']) ? $_POST['intent'] : null;
$action_performed = '';

if ($intent === 'add') {
    // User wants to add
    if (mysqli_num_rows($check_favorite_result) > 0) {
        // Already favorited - treat as success
        $action_performed = 'added';
        $success = true;
    } else {
        // Not favorited - insert
        $insert_sql = "INSERT INTO favorites (user_id, entity_type, entity_id) 
                       VALUES ($user_id, '$entity_type', $entity_id)";
        if (myQuery($insert_sql)) {
            $action_performed = 'added';
            $success = true;
        } else {
            $success = false;
        }
    }
} elseif ($intent === 'remove') {
    // User wants to remove
    if (mysqli_num_rows($check_favorite_result) == 0) {
        // Already removed - treat as success
        $action_performed = 'removed';
        $success = true;
    } else {
        // Favorited - delete
        $delete_sql = "DELETE FROM favorites 
                       WHERE user_id = $user_id 
                       AND entity_type = '$entity_type' 
                       AND entity_id = $entity_id";
        if (myQuery($delete_sql)) {
            $action_performed = 'removed';
            $success = true;
        } else {
            $success = false;
        }
    }
} else {
    // Default Toggle Behavior
    if (mysqli_num_rows($check_favorite_result) > 0) {
        // Remove
        $delete_sql = "DELETE FROM favorites 
                       WHERE user_id = $user_id 
                       AND entity_type = '$entity_type' 
                       AND entity_id = $entity_id";
        if (myQuery($delete_sql)) {
            $action_performed = 'removed';
            $success = true;
        } else {
            $success = false;
        }
    } else {
        // Add
        $insert_sql = "INSERT INTO favorites (user_id, entity_type, entity_id) 
                       VALUES ($user_id, '$entity_type', $entity_id)";
        if (myQuery($insert_sql)) {
            $action_performed = 'added';
            $success = true;
        } else {
            $success = false;
        }
    }
}

if ($success) {
    $message = ($action_performed == 'added') ? 'Added to favorites' : 'Removed from favorites';
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'action' => $action_performed, 'message' => $message]);
    } else {
        header("Location: $redirect_url?success=$message");
    }
} else {
    $error_msg = ($intent === 'add' || ($intent === null && mysqli_num_rows($check_favorite_result) == 0))
        ? 'Failed to add favorite'
        : 'Failed to remove favorite';

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $error_msg]);
    } else {
        header("Location: $redirect_url?error=$error_msg");
    }
}
exit();
?>