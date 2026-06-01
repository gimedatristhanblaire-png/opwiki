<?php
$page_title = 'Forgot Password';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<p style='color:red;'>Please enter a valid email address.</p>";
    } else {
        $sql_check = "SELECT id FROM users WHERE email = ?";
        $stmt_check = $conn->prepare($sql_check);
        $user_exists = false;

        if ($stmt_check) {
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows === 1) {
                $user_exists = true;
                $user_row = $result_check->fetch_assoc();
                $user_id = $user_row['id'];

                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

                $sql_token = "INSERT INTO password_resets (user_id, token, expires_at, used) VALUES (?, ?, ?, 0)";
                $stmt_token = $conn->prepare($sql_token);

                if ($stmt_token) {
                    $stmt_token->bind_param("iss", $user_id, $token, $expires_at);
                    if ($stmt_token->execute()) {
                        $reset_link = BASE_URL . "auth/reset_password.php?token=" . urlencode($token);
                        $message = "<p style='color:green;'>Password reset link: <a href='" . $reset_link . "'>" . htmlspecialchars($reset_link) . "</a></p>";
                        $message .= "<p style='color:green;'>If an account with that email exists, a reset link has been generated.</p>";
                    } else {
                        error_log("forgot_password: Error inserting token - " . $stmt_token->error);
                    }
                    $stmt_token->close();
                }
            }
            $stmt_check->close();
        }

        if (!$user_exists) {
            $message = "<p style='color:green;'>If an account with that email exists, a reset link has been generated.</p>";
        }
    }
}
?>
<section id="forgot-password-form">
    <div class="container">
        <h2>Forgot Password</h2>
        <?php echo $message; ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <div class="form-group">
                <label for="email">Enter your email address:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit" class="btn">Send Reset Link</button>
        </form>
        <p><a href="<?php echo BASE_URL; ?>auth/login.php">Back to Login</a></p>
    </div>
</section>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>