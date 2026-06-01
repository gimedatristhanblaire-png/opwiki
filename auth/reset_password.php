<?php
$page_title = 'Reset Password';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';

$message = '';
$token_valid = false;
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

if (empty($token)) {
    $token = $_POST['token'] ?? '';
}

if (!empty($token)) {
    $sql_token = "SELECT pr.user_id, pr.expires_at, pr.used
                  FROM password_resets pr
                  WHERE pr.token = ?";
    $stmt_token = $conn->prepare($sql_token);

    if ($stmt_token) {
        $stmt_token->bind_param("s", $token);
        $stmt_token->execute();
        $result_token = $stmt_token->get_result();

        if ($result_token->num_rows === 1) {
            $token_data = $result_token->fetch_assoc();

            if ($token_data['used'] == 1) {
                $message = "<p style='color:red;'>This reset link has already been used.</p>";
            } elseif (strtotime($token_data['expires_at']) < time()) {
                $message = "<p style='color:red;'>This reset link has expired.</p>";
            } else {
                $token_valid = true;
                $reset_user_id = $token_data['user_id'];
            }
        } else {
            $message = "<p style='color:red;'>Invalid reset link.</p>";
        }
        $stmt_token->close();
    }

    if ($token_valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($new_password) || empty($confirm_password)) {
            $message = "<p style='color:red;'>All fields are required.</p>";
        } elseif (strlen($new_password) < 6) {
            $message = "<p style='color:red;'>Password must be at least 6 characters long.</p>";
        } elseif ($new_password !== $confirm_password) {
            $message = "<p style='color:red;'>Passwords do not match.</p>";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $conn->begin_transaction();
            try {
                $sql_update_user = "UPDATE users SET password = ? WHERE id = ?";
                $stmt_user = $conn->prepare($sql_update_user);
                $stmt_user->bind_param("si", $hashed_password, $reset_user_id);
                $stmt_user->execute();
                $stmt_user->close();

                $sql_update_token = "UPDATE password_resets SET used = 1 WHERE token = ?";
                $stmt_tok = $conn->prepare($sql_update_token);
                $stmt_tok->bind_param("s", $token);
                $stmt_tok->execute();
                $stmt_tok->close();

                $conn->commit();

                $_SESSION['login_message'] = "<p style='color:green;'>Your password has been reset successfully. Please log in.</p>";
                header('Location: ' . BASE_URL . 'auth/login.php');
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                error_log("reset_password: Error updating password - " . $e->getMessage());
                $message = "<p style='color:red;'>An error occurred while resetting your password. Please try again later.</p>";
            }
        }
    }
}
?>
<section id="reset-password-form">
    <div class="container">
        <h2>Reset Password</h2>
        <?php echo $message; ?>
        <?php if ($token_valid): ?>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div class="form-group">
                    <label for="new_password">New Password (min 6 characters):</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn">Reset Password</button>
            </form>
        <?php endif; ?>
        <p><a href="<?php echo BASE_URL; ?>auth/login.php">Back to Login</a></p>
    </div>
</section>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>