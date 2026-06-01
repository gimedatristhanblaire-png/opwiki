<?php
// --- Set Page Title ---
$page_title = 'Submit/Edit Wiki Article';

// --- Includes ---
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php'; // For BASE_URL, session check, navigation
require_once __DIR__ . '/../includes/functions.php'; // For generate_slug and is_admin

// --- Authentication Check ---
// Redirect to login if the user is not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] === null) {
    $_SESSION['redirect_to'] = BASE_URL . 'wiki/submit.php'; // Store where user wanted to go
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

$is_admin_editing = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// --- Variables for form population ---
$form_message = '';
$article_title = '';
$article_content = '';
$article_category = '';
$article_tags = '';
$article_id = null;
$article_spoiler_level = 0;
$is_editing = false;

// --- Check if editing an existing article ---
if (isset($_GET['id'])) {
    $article_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($article_id) {
        $sql_fetch = "SELECT id, title, content, category, user_id, status, spoiler_level
                      FROM wiki_articles
                      WHERE id = ?";
        if (!$is_admin_editing) {
            $sql_fetch .= " AND user_id = ?";
        }
        $stmt_fetch = $conn->prepare($sql_fetch);

        if ($stmt_fetch === false) {
            error_log("wiki/submit.php: Error preparing fetch statement: " . $conn->error);
            $form_message = "<p style='color:red;'>An internal error occurred while loading the article for editing. Please try again later.</p>";
        } else {
            if ($is_admin_editing) {
                $stmt_fetch->bind_param("i", $article_id);
            } else {
                $stmt_fetch->bind_param("ii", $article_id, $_SESSION['user_id']);
            }
            $stmt_fetch->execute();
            $result_fetch = $stmt_fetch->get_result();

            if ($result_fetch->num_rows === 1) {
                $article_data = $result_fetch->fetch_assoc();
                $article_title = $article_data['title'];
                $article_content = $article_data['content'];
                $article_category = $article_data['category'];
                $article_spoiler_level = $article_data['spoiler_level'];
                $is_editing = true;
                $page_title = 'Edit Wiki Article';
                $stmt_tags = $conn->prepare("SELECT t.name FROM tags t JOIN article_tags at ON t.id = at.tag_id WHERE at.article_id = ?");
                if ($stmt_tags) {
                    $stmt_tags->bind_param("i", $article_id);
                    $stmt_tags->execute();
                    $result_tags = $stmt_tags->get_result();
                    $tag_names = [];
                    while ($tag_row = $result_tags->fetch_assoc()) {
                        $tag_names[] = $tag_row['name'];
                    }
                    $article_tags = implode(', ', $tag_names);
                    $stmt_tags->close();
                } // Update page title
            } else {
                // Article not found, or not owned by this user
                $form_message = "<p style='color:red;'>Article not found or you do not have permission to edit it.</p>";
                // Optionally redirect back to index or user's articles
                // header('Location: ' . BASE_URL . 'wiki/'); exit();
            }
            $stmt_fetch->close();
        }
    } else {
        $form_message = "<p style='color:red;'>Invalid article ID provided for editing.</p>";
    }
}

// --- Handle Form Submission (New or Edit) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $article_title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $article_content = $_POST['content'];
    $article_category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $article_tags = filter_input(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);
    $change_summary = filter_input(INPUT_POST, 'change_summary', FILTER_SANITIZE_STRING);
    $article_id = filter_input(INPUT_POST, 'article_id', FILTER_VALIDATE_INT);
    $article_tags = filter_input(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);
    $article_spoiler_level = filter_input(INPUT_POST, 'spoiler_level', FILTER_VALIDATE_INT) ?: 0;

    $user_id = $_SESSION['user_id']; // Get the logged-in user's ID

    // --- Basic Validation ---
    if (empty($article_title) || empty($article_content) || empty($article_category)) {
        $form_message = "<p style='color:red;'>Please fill in all fields (Title, Content, Category).</p>";
    } else {
        // --- Generate Slug ---
        $generated_slug = generate_slug($article_title);

        // --- Check for Slug Uniqueness (only for new articles or if title changed significantly) ---
        $original_slug = $generated_slug;
        $suffix = 1;
        $sql_check_slug = "SELECT id FROM wiki_articles WHERE slug = ?";

        // If editing, check slug uniqueness against *other* articles, not itself
        if ($article_id) {
            $sql_check_slug .= " AND id != ?";
        }

        $stmt_check_slug = $conn->prepare($sql_check_slug);

        if ($stmt_check_slug === false) {
            error_log("wiki/submit.php: Error preparing slug check statement: " . $conn->error);
            $form_message = "<p style='color:red;'>An internal error occurred. Please try again later.</p>";
        } else {
            // Bind parameters: 's' for slug, 'i' for article_id if editing
            if ($article_id) {
                $stmt_check_slug->bind_param("si", $generated_slug, $article_id);
            } else {
                $stmt_check_slug->bind_param("s", $generated_slug);
            }
            $stmt_check_slug->execute();
            $result_check = $stmt_check_slug->get_result();

            // Loop to find unique slug
            while ($result_check->num_rows > 0) {
                $generated_slug = $original_slug . '-' . $suffix++;
                // Re-prepare and re-bind for the new slug
                if ($article_id) {
                    $stmt_check_slug->bind_param("si", $generated_slug, $article_id);
                } else {
                    $stmt_check_slug->bind_param("s", $generated_slug);
                }
                $stmt_check_slug->execute();
                $result_check = $stmt_check_slug->get_result();
            }
            $stmt_check_slug->close();
        }

        if (empty($form_message)) { // Proceed only if no slug check error
            // --- Determine SQL Statement (INSERT or UPDATE) ---
            if ($article_id) {
                // --- Save current revision before updating ---
                $sql_fetch_current = "SELECT title, content, category FROM wiki_articles WHERE id = ?";
                $stmt_fetch_current = $conn->prepare($sql_fetch_current);
                if ($stmt_fetch_current) {
                    $stmt_fetch_current->bind_param("i", $article_id);
                    $stmt_fetch_current->execute();
                    $result_current = $stmt_fetch_current->get_result();
                    $current_data = $result_current->fetch_assoc();
                    $stmt_fetch_current->close();
                    if ($current_data) {
                        $sql_revision = "INSERT INTO article_revisions (article_id, user_id, title, content, category, change_summary, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                        $stmt_revision = $conn->prepare($sql_revision);
                        if ($stmt_revision) {
                            $stmt_revision->bind_param("iissss", $article_id, $_SESSION['user_id'], $current_data['title'], $current_data['content'], $current_data['category'], $change_summary);
                            $stmt_revision->execute();
                            $stmt_revision->close();
                        }
                    }
                }
                // --- UPDATE Article ---
                $new_status = $is_admin_editing ? '(SELECT status FROM wiki_articles WHERE id = ?)' : "'pending'";
                $sql_save = "UPDATE wiki_articles
                             SET title = ?, slug = ?, content = ?, category = ?, spoiler_level = ?, status = $new_status, updated_at = NOW()
                             WHERE id = ?";
                $stmt_save = $conn->prepare($sql_save);
                if ($stmt_save === false) {
                    error_log("wiki/submit.php: Error preparing update statement: " . $conn->error);
                    $form_message = "<p style='color:red;'>An internal error occurred while preparing to update the article. Please try again later.</p>";
                } else {
                    if ($is_admin_editing) {
                        $stmt_save->bind_param("ssssiii", $article_title, $generated_slug, $article_content, $article_category, $article_spoiler_level, $article_id, $article_id);
                    } else {
                        $stmt_save->bind_param("ssssi", $article_title, $generated_slug, $article_content, $article_category, $article_spoiler_level);
                    }
                }
            } else {
                // --- INSERT New Article ---
                $sql_save = "INSERT INTO wiki_articles (user_id, title, slug, content, category, spoiler_level, status, created_at, updated_at)
                               VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())";
                $stmt_save = $conn->prepare($sql_save);
                if ($stmt_save === false) {
                    error_log("wiki/submit.php: Error preparing insert statement: " . $conn->error);
                    $form_message = "<p style='color:red;'>An internal error occurred while preparing to save the article. Please try again later.</p>";
                } else {
                    $stmt_save->bind_param("issssi", $user_id, $article_title, $generated_slug, $article_content, $article_category, $article_spoiler_level);
                }
            }

            // --- Execute Save Operation ---
            if (!empty($stmt_save)) {
                if ($stmt_save->execute()) {
                    $action_taken = $is_editing ? 'updated' : 'submitted';
                    $saved_article_id = $is_editing ? $article_id : $conn->insert_id;
                    if (!empty($article_tags)) {
                        save_tags($saved_article_id, $article_tags, $conn);
                    }
                    $conn->query("UPDATE wiki_articles SET last_edited_by = " . (int)$_SESSION['user_id'] . " WHERE id = " . (int)$saved_article_id);
                    $_SESSION['submission_message'] = "<p style='color:green; font-weight:bold;'>Your article '" . htmlspecialchars($article_title) . "' has been successfully {$action_taken} and is awaiting admin review.</p>";
                    header('Location: ' . BASE_URL . 'wiki/');
                    exit();
                } else {
                    error_log("wiki/submit.php: Error executing save statement: " . $stmt_save->error);
                    $form_message = "<p style='color:red;'>An error occurred while saving the article. Please try again later.</p>";
                }
                $stmt_save->close();
            }
        }
    }
}
?>

<section id="submit-edit-wiki-article">
    <div class="container">
        <h2><?php echo $is_editing ? 'Edit Wiki Article' : 'Submit New Wiki Article'; ?> for Review</h2>
        <?php echo $form_message; // Display form submission messages ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <!-- Hidden field for article ID if editing -->
            <?php if ($is_editing): ?>
                <input type="hidden" name="article_id" value="<?php echo $article_id; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="title">Article Title:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($article_title); ?>" required>
            </div>

            <div class="form-group">
                <label for="category">Category:</label>
                <select id="category" name="category" required>
                    <option value="">-- Select a Category --</option>
                    <?php
                    $sql_cats = "SELECT name FROM categories ORDER BY name ASC";
                    $result_cats = $conn->query($sql_cats);
                    if ($result_cats && $result_cats->num_rows > 0) {
                        while ($cat_row = $result_cats->fetch_assoc()) {
                            $cat_name = htmlspecialchars($cat_row['name']);
                            $selected = ($article_category === $cat_row['name']) ? 'selected' : '';
                            echo "<option value=\"" . $cat_name . "\" " . $selected . ">" . $cat_name . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="spoiler_level">Spoiler Level:</label>
                <select id="spoiler_level" name="spoiler_level">
                    <option value="0">No Spoilers</option>
                    <option value="1" <?php if (($article_spoiler_level??0)==1) echo 'selected'; ?>>Mild Spoilers</option>
                    <option value="2" <?php if (($article_spoiler_level??0)==2) echo 'selected'; ?>>Major Spoilers</option>
                    <option value="3" <?php if (($article_spoiler_level??0)==3) echo 'selected'; ?>>Ultimate Spoilers</option>
                </select>
                <small>Set the spoiler severity for this article so readers can choose to blur it.</small>
            </div>

            <div class="form-group">
                <label for="tags">Tags:</label>
                <input type="text" id="tags" name="tags" value="<?php echo htmlspecialchars($article_tags); ?>" placeholder="e.g. science, history, technology">
                <small>Comma-separated tags for this article.</small>
            </div>

            <div class="form-group">
                <label for="content">Content:</label>
                <textarea id="content" name="content" class="wysiwyg" rows="10" required><?php echo htmlspecialchars($article_content); ?></textarea>
                <small>Your article will be reviewed by an administrator before it is published. Edits will reset its status to 'pending'.</small>
            </div>

            <div class="form-group">
                <label for="change_summary">Change Summary:</label>
                <input type="text" id="change_summary" name="change_summary" placeholder="Briefly describe what changed (optional)" maxlength="255">
            </div>

            <button type="submit" class="btn"><?php echo $is_editing ? 'Update and Submit for Review' : 'Submit for Review'; ?></button>
        </form>
        <?php if ($is_editing): ?>
            <p><a href="<?php echo BASE_URL; ?>wiki/" class="btn-secondary">Cancel Edit</a></p>
        <?php endif; ?>
    </div>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
