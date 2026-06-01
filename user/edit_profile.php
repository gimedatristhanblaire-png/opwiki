<?php
$page_title = 'Edit Profile';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] === null) {
    $_SESSION['redirect_to'] = BASE_URL . 'user/edit_profile.php';
    header('Location: ' . BASE_URL . 'auth/login.php'); exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

$stmt_user = $conn->prepare("SELECT email, bio, avatar, cover_image, favorite_character, favorite_arc, spoiler_tolerance FROM users WHERE id = ?");
$current_email = ''; $current_bio = ''; $current_avatar = ''; $current_cover = ''; $current_fav_char = ''; $current_fav_arc = ''; $current_spoiler_tolerance = 0;
if ($stmt_user) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows === 1) {
        $d = $result_user->fetch_assoc();
        $current_email = $d['email'];
        $current_bio = $d['bio'] ?? '';
        $current_avatar = $d['avatar'] ?? '';
        $current_cover = $d['cover_image'] ?? '';
        $current_fav_char = $d['favorite_character'] ?? '';
        $current_fav_arc = $d['favorite_arc'] ?? '';
        $current_spoiler_tolerance = (int)$d['spoiler_tolerance'];
    }
    $stmt_user->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $bio = filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_STRING);
    $avatar = $current_avatar;
    if (!empty($_FILES['avatar_file']['name'])) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (in_array($_FILES['avatar_file']['type'], $allowed) && $_FILES['avatar_file']['size'] <= 2097152) {
            $ext = pathinfo($_FILES['avatar_file']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('avatar_') . '.' . $ext;
            $dest = __DIR__ . '/../uploads/avatars/' . $filename;
            if (move_uploaded_file($_FILES['avatar_file']['tmp_name'], $dest)) {
                $avatar = 'uploads/avatars/' . $filename;
            }
        } else {
            $message = "<p style='color:red;'>Invalid file. Use JPG, PNG, or WEBP under 2MB.</p>";
        }
    } elseif (!empty($_POST['avatar_url'])) {
        $avatar = filter_input(INPUT_POST, 'avatar_url', FILTER_VALIDATE_URL);
    }
    $cover_image = filter_input(INPUT_POST, 'cover_image', FILTER_VALIDATE_URL);
    $favorite_character = filter_input(INPUT_POST, 'favorite_character', FILTER_SANITIZE_STRING);
    $favorite_arc = filter_input(INPUT_POST, 'favorite_arc', FILTER_SANITIZE_STRING);
    $spoiler_tolerance = filter_input(INPUT_POST, 'spoiler_tolerance', FILTER_VALIDATE_INT) ?: 0;
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $message = "<p style='color:red;'>Invalid or expired security token.</p>";
    } elseif (empty($email) || empty($current_password)) {
        $message = "<p style='color:red;'>Email and current password are required.</p>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<p style='color:red;'>Invalid email format.</p>";
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $message = "<p style='color:red;'>New password must be at least 6 characters.</p>";
    } elseif (!empty($new_password) && $new_password !== $confirm_new_password) {
        $message = "<p style='color:red;'>New passwords do not match.</p>";
    } else {
        $stmt_check = $conn->prepare("SELECT password FROM users WHERE id = ?");
        if ($stmt_check === false) {
            $message = "<p style='color:red;'>An internal error occurred.</p>";
        } else {
            $stmt_check->bind_param("i", $user_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $user_row = $result_check->fetch_assoc();
            $stmt_check->close();
            if (!password_verify($current_password, $user_row['password'])) {
                $message = "<p style='color:red;'>Current password is incorrect.</p>";
            } else {
                if (!empty($new_password)) {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql_update = "UPDATE users SET email=?, password=?, bio=?, avatar=?, cover_image=?, favorite_character=?, favorite_arc=?, spoiler_tolerance=? WHERE id=?";
                    $stmt_update = $conn->prepare($sql_update);
                    if ($stmt_update) {
                        $stmt_update->bind_param("sssssssii", $email, $hashed, $bio, $avatar, $cover_image, $favorite_character, $favorite_arc, $spoiler_tolerance, $user_id);
                    }
                } else {
                    $sql_update = "UPDATE users SET email=?, bio=?, avatar=?, cover_image=?, favorite_character=?, favorite_arc=?, spoiler_tolerance=? WHERE id=?";
                    $stmt_update = $conn->prepare($sql_update);
                    if ($stmt_update) {
                        $stmt_update->bind_param("ssssssii", $email, $bio, $avatar, $cover_image, $favorite_character, $favorite_arc, $spoiler_tolerance, $user_id);
                    }
                }
                if ($stmt_update && $stmt_update->execute()) {
                    $message = "<p style='color:green;'>Profile updated successfully.</p>";
                    $current_email = $email; $current_bio = $bio; $current_avatar = $avatar;
                    $current_cover = $cover_image; $current_fav_char = $favorite_character; $current_fav_arc = $favorite_arc;
                } elseif ($stmt_update) {
                    $message = "<p style='color:red;'>Error updating profile.</p>";
                }
                if ($stmt_update) $stmt_update->close();
            }
        }
    }
}

$csrf_token = generate_csrf_token();
?>
<section id="edit-profile-form">
    <div class="container">
        <h2>Edit Profile</h2>
        <?php echo $message; ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group"><label for="email">Email:</label><input type="email" id="email" name="email" value="<?php echo htmlspecialchars($current_email); ?>" required></div>
            <div class="form-group"><label for="bio">Bio:</label><textarea id="bio" name="bio" rows="3" maxlength="500"><?php echo htmlspecialchars($current_bio); ?></textarea><small>Tell others about yourself (max 500 chars).</small></div>
            <div class="form-group"><label for="avatar_file">Upload Avatar:</label><input type="file" id="avatar_file" name="avatar_file" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG, or WEBP under 2MB. Square crop recommended.</small></div>
            <div class="form-group"><label for="avatar_url">Or Avatar URL:</label><input type="url" id="avatar_url" name="avatar_url" value="<?php echo htmlspecialchars($current_avatar); ?>" placeholder="https://example.com/avatar.jpg"><small>External URL (used if no file uploaded).</small></div>
            <div class="form-group"><label for="cover_image">Cover Image URL:</label><input type="url" id="cover_image" name="cover_image" value="<?php echo htmlspecialchars($current_cover); ?>" placeholder="https://example.com/cover.jpg"><small>URL to your profile cover image.</small></div>
            <div class="form-group"><label for="favorite_character">Favorite Character:</label><input type="text" id="favorite_character" name="favorite_character" value="<?php echo htmlspecialchars($current_fav_char); ?>" placeholder="e.g. Monkey D. Luffy"></div>
            <div class="form-group"><label for="favorite_arc">Favorite Arc:</label><input type="text" id="favorite_arc" name="favorite_arc" value="<?php echo htmlspecialchars($current_fav_arc); ?>" placeholder="e.g. Marineford"></div>
            <div class="form-group"><label for="spoiler_tolerance">Spoiler Tolerance:</label><select id="spoiler_tolerance" name="spoiler_tolerance"><option value="0" <?php if ($current_spoiler_tolerance==0) echo 'selected'; ?>>Blur All Spoilers</option><option value="1" <?php if ($current_spoiler_tolerance==1) echo 'selected'; ?>>Show Mild Spoilers</option><option value="2" <?php if ($current_spoiler_tolerance==2) echo 'selected'; ?>>Show Major Spoilers</option><option value="3" <?php if ($current_spoiler_tolerance==3) echo 'selected'; ?>>Show All Spoilers</option></select><small>Content at or below your tolerance level will be shown. Higher = fewer blur overlays.</small></div>
            <hr><h3>Change Password</h3>
            <div class="form-group"><label for="current_password">Current Password:</label><input type="password" id="current_password" name="current_password" required></div>
            <div class="form-group"><label for="new_password">New Password (leave blank to keep):</label><input type="password" id="new_password" name="new_password"></div>
            <div class="form-group"><label for="confirm_new_password">Confirm New Password:</label><input type="password" id="confirm_new_password" name="confirm_new_password"></div>
            <button type="submit" class="btn">Update Profile</button>
        </form>
        <p><a href="<?php echo BASE_URL; ?>user/profile.php">Back to Profile</a></p>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
