<?php
// --- START OF FUNCTIONS.PHP CONTENT ---

/**
 * Generates a URL-friendly slug from a given title.
 * Ensures lowercase, hyphenated, and sanitized string.
 *
 * @param string $title The original title string.
 * @return string The generated slug.
 */
function generate_slug($title) {
    $slug = strtolower($title);
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    $slug = trim($slug, '-');
    $slug = preg_replace('/-+/', '-', $slug);
    if (empty($slug)) {
        $slug = 'untitled';
    }
    return $slug;
}


/**
 * Checks if a user is an administrator based on their role in the database.
 *
 * @param int|null $user_id The ID of the user to check.
 * @param mysqli $conn Database connection object.
 * @return bool True if the user is an admin, false otherwise.
 */
function is_admin($user_id, $conn) {
    if ($user_id === null) {
        return false; // Not logged in, so not an admin
    }

    // Fetch user's role from the database
    $sql = "SELECT role FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("is_admin: Error preparing statement - " . $conn->error);
        return false; // Error preparing statement
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result === false) {
         error_log("is_admin: Error getting result - " . $conn->error);
         return false; // Error getting results
    }

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        return ($user['role'] === 'admin');
    }
    return false;
}

function is_staff($user_id, $conn) {
    if ($user_id === null) return false;
    $sql = "SELECT role FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        return in_array($user['role'], ['moderator', 'admin']);
    }
    return false;
}

function get_user_role($user_id, $conn) {
    if ($user_id === null) return null;
    $sql = "SELECT role FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        return $user['role'];
    }
    return null;
}

/**
 * Generates a CSRF token, storing it in the session if not already set.
 *
 * @return string The CSRF token.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies a CSRF token against the one stored in the session.
 *
 * @param string $token The token to verify.
 * @return bool True if valid, false otherwise.
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Saves tags for an article: creates tags as needed, links via article_tags.
 *
 * @param int $article_id The article ID.
 * @param string $tag_string Comma-separated tag names.
 * @param mysqli $conn Database connection.
 */
function save_tags($article_id, $tag_string, $conn) {
    $tags = array_map('trim', explode(',', $tag_string));
    $tags = array_filter($tags);
    $tags = array_unique($tags);

    $conn->query("DELETE FROM article_tags WHERE article_id = $article_id");

    foreach ($tags as $tag_name) {
        if (empty($tag_name)) continue;
        $slug = generate_slug($tag_name);
        $stmt = $conn->prepare("INSERT IGNORE INTO tags (name, slug) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("ss", $tag_name, $slug);
            $stmt->execute();
            $stmt->close();
        }
        $stmt2 = $conn->prepare("SELECT id FROM tags WHERE slug = ?");
        if ($stmt2) {
            $stmt2->bind_param("s", $slug);
            $stmt2->execute();
            $result = $stmt2->get_result();
            if ($row = $result->fetch_assoc()) {
                $tag_id = $row['id'];
                $stmt3 = $conn->prepare("INSERT IGNORE INTO article_tags (article_id, tag_id) VALUES (?, ?)");
                if ($stmt3) {
                    $stmt3->bind_param("ii", $article_id, $tag_id);
                    $stmt3->execute();
                    $stmt3->close();
                }
            }
            $stmt2->close();
        }
    }
}

/**
 * Get a display-friendly relative time string.
 */
function time_ago($datetime) {
    $now = time();
    $ts = strtotime($datetime);
    $diff = $now - $ts;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
    return date('M j', $ts);
}

// --- END OF FUNCTIONS.PHP CONTENT ---
?>
