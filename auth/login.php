<?php
$page_title = 'Enter the Grand Line';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// --- Login Logic (must run before header.php outputs anything) ---
$message = '';

if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if (!isset($_SESSION['login_block_time'])) $_SESSION['login_block_time'] = 0;

$blocked = false;
if ($_SESSION['login_attempts'] >= 5) {
    $wait = 30 - (time() - $_SESSION['login_block_time']);
    if ($wait > 0) {
        $blocked = true;
        $message = "<div class='auth-message auth-error'>Too many attempts. Wait {$wait}s.</div>";
    } else {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_block_time'] = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$blocked) {
    $username_or_email = filter_input(INPUT_POST, 'username_or_email', FILTER_SANITIZE_STRING);
    $password = $_POST['password'] ?? '';

    if (empty($username_or_email) || empty($password)) {
        $message = "<div class='auth-message auth-error'>Both fields are required.</div>";
    } else {
        $sql = "SELECT id, username, email, password, role, banned FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            error_log("Login: Error preparing statement - " . $conn->error);
            $message = "<div class='auth-message auth-error'>An internal error occurred. Please try again later.</div>";
        } else {
            $stmt->bind_param("ss", $username_or_email, $username_or_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    if (isset($user['banned']) && $user['banned'] == 1) {
                        $message = "<div class='auth-message auth-error'>Your account has been suspended.</div>";
                        $stmt->close();
                    } else {
                        session_regenerate_id(true);
                        $_SESSION['login_attempts'] = 0;
                        $_SESSION['login_block_time'] = 0;
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['logged_in'] = true;

                        $redirect = isset($_SESSION['redirect_to']) ? $_SESSION['redirect_to'] : BASE_URL;
                        unset($_SESSION['redirect_to']);
                        $stmt->close();
                        header('Location: ' . $redirect);
                        exit();
                    }
                } else {
                    $_SESSION['login_attempts']++;
                    if ($_SESSION['login_attempts'] >= 5) $_SESSION['login_block_time'] = time();
                    $message = "<div class='auth-message auth-error'>Invalid credentials.</div>";
                }
            } else {
                $_SESSION['login_attempts']++;
                if ($_SESSION['login_attempts'] >= 5) $_SESSION['login_block_time'] = time();
                $message = "<div class='auth-message auth-error'>Invalid credentials.</div>";
            }
            $stmt->close();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';

if (isset($_SESSION['login_message'])) {
    $message = $_SESSION['login_message'] . $message;
    unset($_SESSION['login_message']);
}
?>
<section id="grand-line-login">
    <div class="gl-container">
        <!-- Left Panel: Cinematic Illustration -->
        <div class="gl-illustration-panel">
            <div class="gl-illustration-bg"></div>
            <div class="gl-illustration-content">
                <div class="gl-compass-icon">🧭</div>
                <h2 class="gl-quote">"The sea calls<br>to those who seek<br>freedom."</h2>
                <p class="gl-quote-author">— Gol D. Roger</p>
                <div class="gl-ship-silhouette">
                    <div class="gl-wave"></div>
                    <div class="gl-wave gl-wave-delay"></div>
                </div>
            </div>
        </div>

        <!-- Right Panel: Parchment Login Card -->
        <div class="gl-form-panel">
            <div class="parchment-card parchment-login-card">
                <div class="parchment-seal">☠️</div>
                <h2 class="parchment-title">WELCOME BACK, PIRATE</h2>
                <p class="parchment-subtitle">Continue your journey through the Grand Line archives.</p>

                <?php echo $message; ?>

                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="pirate-form" id="loginForm">
                    <div class="pf-group">
                        <label for="username_or_email" class="pf-label">🧭 Pirate Name or Transponder</label>
                        <input type="text" id="username_or_email" name="username_or_email" class="pf-input pf-input-compass" required placeholder="e.g. Straw Hat Luffy">
                    </div>
                    <div class="pf-group">
                        <label for="password" class="pf-label">🔒 Secret Crew Code</label>
                        <input type="password" id="password" name="password" class="pf-input pf-input-lock" required placeholder="Enter your code">
                    </div>
                    <div class="pf-group pf-group-row remember-row">
                        <input type="checkbox" id="remember_me" name="remember_me" value="1" class="remember-checkbox">
                        <label for="remember_me" class="pf-label remember-label">Remember my vessel</label>
                    </div>
                    <button type="submit" class="pf-submit" id="loginSubmit">ENTER THE GRAND LINE</button>
                    <div class="pf-links">
                        <a href="<?php echo BASE_URL; ?>auth/forgot_password.php" class="pf-link">🔑 Forgot your treasure key?</a>
                        <a href="<?php echo BASE_URL; ?>auth/register.php" class="pf-link">🏴‍☠️ First voyage? Recruit here</a>
                    </div>
                </form>
                <div id="loginLoader" class="login-loader" style="display:none;">
                    <div class="login-loader-ship">⛵</div>
                    <div class="login-loader-text">Hoisting the sails...</div>
                </div>
            </div>
        </div>
    </div>
<script>
document.getElementById('loginForm')?.addEventListener('submit', function() {
    document.getElementById('loginSubmit').style.display = 'none';
    document.getElementById('loginLoader').style.display = 'flex';
});
</script>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
