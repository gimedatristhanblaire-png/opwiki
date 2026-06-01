<?php
$page_title = 'Submit/Edit Theory';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] === null) {
    $_SESSION['redirect_to'] = BASE_URL . 'theories/submit.php';
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

$is_admin_editing = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

$form_message = '';
$theory_title = '';
$theory_content = '';
$theory_tags = '';
$theory_id = null;
$theory_spoiler_level = 0;
$is_editing = false;

if (isset($_GET['id'])) {
    $theory_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($theory_id) {
        $sql_fetch = "SELECT id, title, content, user_id, status, spoiler_level
                      FROM theories
                      WHERE id = ?";
        if (!$is_admin_editing) {
            $sql_fetch .= " AND user_id = ?";
        }
        $stmt_fetch = $conn->prepare($sql_fetch);

        if ($stmt_fetch === false) {
            error_log("theories/submit.php: Error preparing fetch statement: " . $conn->error);
            $form_message = "<p style='color:red;'>An internal error occurred. Please try again later.</p>";
        } else {
            if ($is_admin_editing) {
                $stmt_fetch->bind_param("i", $theory_id);
            } else {
                $stmt_fetch->bind_param("ii", $theory_id, $_SESSION['user_id']);
            }
            $stmt_fetch->execute();
            $result_fetch = $stmt_fetch->get_result();

            if ($result_fetch->num_rows === 1) {
                $theory_data = $result_fetch->fetch_assoc();
                $theory_title = $theory_data['title'];
                $theory_content = $theory_data['content'];
                $theory_spoiler_level = $theory_data['spoiler_level'];
                $is_editing = true;
                $page_title = 'Edit Theory';
                $stmt_tags = $conn->prepare("SELECT t.name FROM tags t JOIN theory_tags tt ON t.id = tt.tag_id WHERE tt.theory_id = ?");
                if ($stmt_tags) {
                    $stmt_tags->bind_param("i", $theory_id);
                    $stmt_tags->execute();
                    $result_tags = $stmt_tags->get_result();
                    $tag_names = [];
                    while ($tag_row = $result_tags->fetch_assoc()) {
                        $tag_names[] = $tag_row['name'];
                    }
                    $theory_tags = implode(', ', $tag_names);
                    $stmt_tags->close();
                }
            } else {
                $form_message = "<p style='color:red;'>Theory not found or you do not have permission to edit it.</p>";
            }
            $stmt_fetch->close();
        }
    } else {
        $form_message = "<p style='color:red;'>Invalid theory ID provided for editing.</p>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theory_title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $theory_content = $_POST['content'];
    $theory_tags = filter_input(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);
    $theory_id = filter_input(INPUT_POST, 'theory_id', FILTER_VALIDATE_INT);
    $theory_spoiler_level = filter_input(INPUT_POST, 'spoiler_level', FILTER_VALIDATE_INT) ?: 0;

    $user_id = $_SESSION['user_id'];

    if (empty($theory_title) || empty($theory_content)) {
        $form_message = "<p style='color:red;'>Please fill in all fields (Title, Content).</p>";
    } else {
        $generated_slug = generate_slug($theory_title);

        $original_slug = $generated_slug;
        $suffix = 1;
        $sql_check_slug = "SELECT id FROM theories WHERE slug = ?";

        if ($theory_id) {
            $sql_check_slug .= " AND id != ?";
        }

        $stmt_check_slug = $conn->prepare($sql_check_slug);

        if ($stmt_check_slug === false) {
            error_log("theories/submit.php: Error preparing slug check: " . $conn->error);
            $form_message = "<p style='color:red;'>An internal error occurred. Please try again later.</p>";
        } else {
            if ($theory_id) {
                $stmt_check_slug->bind_param("si", $generated_slug, $theory_id);
            } else {
                $stmt_check_slug->bind_param("s", $generated_slug);
            }
            $stmt_check_slug->execute();
            $result_check = $stmt_check_slug->get_result();

            while ($result_check->num_rows > 0) {
                $generated_slug = $original_slug . '-' . $suffix++;
                if ($theory_id) {
                    $stmt_check_slug->bind_param("si", $generated_slug, $theory_id);
                } else {
                    $stmt_check_slug->bind_param("s", $generated_slug);
                }
                $stmt_check_slug->execute();
                $result_check = $stmt_check_slug->get_result();
            }
            $stmt_check_slug->close();
        }

        if (empty($form_message)) {
            if ($theory_id) {
                if ($is_admin_editing) {
                    $sql_save = "UPDATE theories
                                 SET title = ?, slug = ?, content = ?, spoiler_level = ?, updated_at = NOW()
                                 WHERE id = ?";
                    $stmt_save = $conn->prepare($sql_save);
                    if ($stmt_save === false) {
                        error_log("theories/submit.php: Error preparing admin update statement: " . $conn->error);
                        $form_message = "<p style='color:red;'>An internal error occurred. Please try again later.</p>";
                    } else {
                        $stmt_save->bind_param("sssii", $theory_title, $generated_slug, $theory_content, $theory_spoiler_level, $theory_id);
                    }
                } else {
                    $sql_save = "UPDATE theories
                                 SET title = ?, slug = ?, content = ?, spoiler_level = ?, status = 'pending', updated_at = NOW()
                                 WHERE id = ? AND user_id = ?";
                    $stmt_save = $conn->prepare($sql_save);
                    if ($stmt_save === false) {
                        error_log("theories/submit.php: Error preparing update statement: " . $conn->error);
                        $form_message = "<p style='color:red;'>An internal error occurred. Please try again later.</p>";
                    } else {
                        $stmt_save->bind_param("sssiii", $theory_title, $generated_slug, $theory_content, $theory_spoiler_level, $theory_id, $user_id);
                    }
                }
            } else {
                $sql_save = "INSERT INTO theories (user_id, title, slug, content, spoiler_level, status, created_at, updated_at)
                                VALUES (?, ?, ?, ?, ?, 'pending', NOW(), NOW())";
                $stmt_save = $conn->prepare($sql_save);
                if ($stmt_save === false) {
                    error_log("theories/submit.php: Error preparing insert statement: " . $conn->error);
                    $form_message = "<p style='color:red;'>An internal error occurred. Please try again later.</p>";
                } else {
                    $stmt_save->bind_param("isssi", $user_id, $theory_title, $generated_slug, $theory_content, $theory_spoiler_level);
                }
            }

            if (!empty($stmt_save)) {
                if ($stmt_save->execute()) {
                    $saved_id = $is_editing ? $theory_id : $conn->insert_id;
                    if (!empty($theory_tags)) {
                        $tags = array_map('trim', explode(',', $theory_tags));
                        $tags = array_filter($tags);
                        $tags = array_unique($tags);
                        $conn->query("DELETE FROM theory_tags WHERE theory_id = $saved_id");
                        foreach ($tags as $tag_name) {
                            $slug = generate_slug($tag_name);
                            $conn->query("INSERT IGNORE INTO tags (name, slug) VALUES ('" . $conn->real_escape_string($tag_name) . "', '$slug')");
                            $tr = $conn->query("SELECT id FROM tags WHERE slug = '$slug'");
                            if ($tr && $tag_row = $tr->fetch_assoc()) {
                                $conn->query("INSERT IGNORE INTO theory_tags (theory_id, tag_id) VALUES ($saved_id, {$tag_row['id']})");
                            }
                        }
                    }
                    $action_taken = $is_editing ? 'updated' : 'submitted';
                    $review_msg = $is_admin_editing ? '.' : ' and is awaiting admin review.';
                    $_SESSION['submission_message'] = "<p style='color:green; font-weight:bold;'>Your theory '" . htmlspecialchars($theory_title) . "' has been successfully {$action_taken}{$review_msg}</p>";
                    header('Location: ' . BASE_URL . 'theories/');
                    exit();
                } else {
                    error_log("theories/submit.php: Error executing save: " . $stmt_save->error);
                    $form_message = "<p style='color:red;'>An error occurred while saving the theory. Please try again later.</p>";
                }
                $stmt_save->close();
            }
        }
    }
}
?>

<section id="submit-edit-theory">
    <div class="container">
        <h2><?php echo $is_editing ? 'Edit Theory' : 'Submit New Theory'; ?></h2>
        <?php echo $form_message; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <?php if ($is_editing): ?>
                <input type="hidden" name="theory_id" value="<?php echo $theory_id; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="title">Theory Title:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($theory_title); ?>" required>
            </div>

            <div class="form-group">
                <label for="spoiler_level">Spoiler Level:</label>
                <select id="spoiler_level" name="spoiler_level">
                    <option value="0">No Spoilers</option>
                    <option value="1" <?php if ($theory_spoiler_level==1) echo 'selected'; ?>>Mild Spoilers</option>
                    <option value="2" <?php if ($theory_spoiler_level==2) echo 'selected'; ?>>Major Spoilers</option>
                    <option value="3" <?php if ($theory_spoiler_level==3) echo 'selected'; ?>>Ultimate Spoilers</option>
                </select>
                <small>Set the spoiler severity for this theory.</small>
            </div>

            <div class="form-group">
                <label for="tags">Tags:</label>
                <input type="text" id="tags" name="tags" value="<?php echo htmlspecialchars($theory_tags); ?>" placeholder="e.g. science, history, philosophy">
                <small>Comma-separated tags for this theory.</small>
            </div>

            <div class="form-group">
                <label for="content">Content:</label>
                <textarea id="content" name="content" class="wysiwyg" rows="10" required><?php echo htmlspecialchars($theory_content); ?></textarea>
            </div>

            <button type="submit" class="btn"><?php echo $is_editing ? 'Update Theory' : 'Submit Theory'; ?></button>
        </form>
        <?php if ($is_editing): ?>
            <p><a href="<?php echo BASE_URL; ?>theories/" class="btn-secondary">Cancel Edit</a></p>
        <?php endif; ?>
    </div>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
