<?php
session_start();
require("../connect.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_POST['entity_type']) || !isset($_POST['entity_id'])) {
    header("Location: ../movies/browse.php?error=Invalid request");
    exit();
}

$user_id = $_SESSION['user_id'];
$entity_type = escapeString($_POST['entity_type']);
$entity_id = (int)$_POST['entity_id'];

// Validate entity_type
if (!in_array($entity_type, ['movie', 'cast', 'user'])) {
    header("Location: ../movies/browse.php?error=Invalid entity type");
    exit();
}

// Validate entity_id exists in respective table
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
    // Prevent users from favoriting themselves
    if ($entity_id == $user_id) {
        $valid = false;
    }
}

if (!$valid) {
    header("Location: ../movies/browse.php?error=Invalid entity");
    exit();
}

// Check if favorite already exists
$check_favorite_sql = "SELECT favorite_id FROM favorites 
                       WHERE user_id = $user_id 
                       AND entity_type = '$entity_type' 
                       AND entity_id = $entity_id";
$check_favorite_result = myQuery($check_favorite_sql);

// Check if this is an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Determine redirect URL based on entity type
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

if (mysqli_num_rows($check_favorite_result) > 0) {
    // Remove favorite
    $delete_sql = "DELETE FROM favorites 
                   WHERE user_id = $user_id 
                   AND entity_type = '$entity_type' 
                   AND entity_id = $entity_id";
    if (myQuery($delete_sql)) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Removed from favorites']);
        } else {
            header("Location: $redirect_url?success=Removed from favorites");
        }
    } else {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to remove favorite']);
        } else {
            header("Location: $redirect_url?error=Failed to remove favorite");
        }
    }
} else {
    // Add favorite
    $insert_sql = "INSERT INTO favorites (user_id, entity_type, entity_id) 
                   VALUES ($user_id, '$entity_type', $entity_id)";
    if (myQuery($insert_sql)) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Added to favorites']);
        } else {
            header("Location: $redirect_url?success=Added to favorites");
        }
    } else {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to add favorite']);
        } else {
            header("Location: $redirect_url?error=Failed to add favorite");
        }
    }
}
exit();
?>

