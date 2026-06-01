<?php
// --- Includes ---
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/functions_rep.php';
require_once __DIR__ . '/../includes/functions_notif.php';

// --- Admin Authentication Check ---
// Redirect to login if the user is not logged in or is not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] === null) {
    $_SESSION['redirect_to'] = BASE_URL . 'admin/pending_articles.php'; // Store where user wanted to go
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? null;
$is_current_user_admin = ($user_role === 'admin');
$is_current_user_staff = $is_current_user_admin || ($user_role === 'moderator');

if (!$is_current_user_staff && $user_role === null && $user_id !== null) {
    $role = get_user_role($user_id, $conn);
    $is_current_user_admin = ($role === 'admin');
    $is_current_user_staff = in_array($role, ['moderator', 'admin']);
}

if (!$is_current_user_staff) {
    header('Location: ' . BASE_URL);
    exit();
}

if (!$is_current_user_admin) {
    // If not admin, redirect to home or a page saying they don't have access.
    header('Location: ' . BASE_URL);
    exit();
}

// --- CSRF Validation ---
$csrf_token = filter_input(INPUT_GET, 'csrf_token', FILTER_SANITIZE_STRING);
if (!$csrf_token || !verify_csrf_token($csrf_token)) {
    $_SESSION['admin_feedback'] = "error:Invalid or expired security token. Please try again.";
    header('Location: ' . BASE_URL . 'admin/pending_articles.php');
    exit();
}

// --- Process Article Management ---
$article_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$feedback_message = ''; // Message to redirect back with

if ($article_id && $action) {
    // Get current article details to form feedback messages
    $sql_fetch_title = "SELECT title FROM wiki_articles WHERE id = ?";
    $stmt_title = $conn->prepare($sql_fetch_title);
    $article_title = 'Article'; // Default title

    if ($stmt_title) {
        $stmt_title->bind_param("i", $article_id);
        $stmt_title->execute();
        $result_title = $stmt_title->get_result();
        if ($result_title->num_rows === 1) {
            $article_data = $result_title->fetch_assoc();
            $article_title = htmlspecialchars($article_data['title']);
        }
        $stmt_title->close();
    }

    switch ($action) {
        case 'approve':
            $sql_fetch_user = "SELECT user_id, slug FROM wiki_articles WHERE id = ?";
            $stmt_fetch_user = $conn->prepare($sql_fetch_user);
            $author_id = null;
            $article_slug = '';
            if ($stmt_fetch_user) {
                $stmt_fetch_user->bind_param("i", $article_id);
                $stmt_fetch_user->execute();
                $result_user = $stmt_fetch_user->get_result();
                if ($result_user->num_rows === 1) {
                    $row_user = $result_user->fetch_assoc();
                    $author_id = $row_user['user_id'];
                    $article_slug = $row_user['slug'];
                }
                $stmt_fetch_user->close();
            }
            $sql_update = "UPDATE wiki_articles SET status = 'approved', updated_at = NOW() WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            if ($stmt_update === false) {
                error_log("admin/manage_article.php: Error preparing approve statement - " . $conn->error);
                $feedback_message = "error:An internal error occurred while approving the article. Please try again.";
            } else {
                $stmt_update->bind_param("i", $article_id);
                if ($stmt_update->execute()) {
                    if ($author_id) {
                        add_reputation($author_id, 20, 'Article approved', $conn, 'article', $article_id);
                        create_notification($author_id, 'article_approved', 'Your article "' . $article_title . '" has been approved!', $conn, BASE_URL . 'wiki/view.php?slug=' . urlencode($article_slug), 'article', $article_id);
                    }
                    $feedback_message = "success:Article '" . $article_title . "' has been approved (+20 reputation).";
                } else {
                    error_log("admin/manage_article.php: Error executing approve statement - " . $stmt_update->error);
                    $feedback_message = "error:An error occurred while approving the article. Please try again.";
                }
                $stmt_update->close();
            }
            break;

        case 'reject':
            // Update status to 'rejected'
            $sql_update = "UPDATE wiki_articles SET status = 'rejected', updated_at = NOW() WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);

            if ($stmt_update === false) {
                error_log("admin/manage_article.php: Error preparing reject statement - " . $conn->error);
                $feedback_message = "error:An internal error occurred while rejecting the article. Please try again.";
            } else {
                $stmt_update->bind_param("i", $article_id);
                if ($stmt_update->execute()) {
                    $feedback_message = "success:Article '" . $article_title . "' has been rejected.";
                } else {
                    error_log("admin/manage_article.php: Error executing reject statement - " . $stmt_update->error);
                    $feedback_message = "error:An error occurred while rejecting the article. Please try again.";
                }
                $stmt_update->close();
            }
            break;

        case 'delete':
            $sql_delete = "DELETE FROM wiki_articles WHERE id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            if ($stmt_delete === false) {
                error_log("admin/manage_article.php: Error preparing delete statement - " . $conn->error);
                $feedback_message = "error:An internal error occurred while deleting the article. Please try again.";
            } else {
                $stmt_delete->bind_param("i", $article_id);
                if ($stmt_delete->execute()) {
                    $feedback_message = "success:Article '" . $article_title . "' has been deleted.";
                } else {
                    error_log("admin/manage_article.php: Error executing delete statement - " . $stmt_delete->error);
                    $feedback_message = "error:An error occurred while deleting the article. Please try again.";
                }
                $stmt_delete->close();
            }
            break;

        default:
            $feedback_message = "error:Invalid action specified.";
            break;
    }
} else {
    $feedback_message = "error:Invalid article ID or action.";
}

// --- Redirect back to the pending articles list with a feedback message ---
// Store the message in the session to be displayed on the next page
$_SESSION['admin_feedback'] = $feedback_message;
header('Location: ' . BASE_URL . 'admin/pending_articles.php');
exit();
?>
