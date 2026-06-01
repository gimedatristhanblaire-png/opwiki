<?php
$page_title = 'Recruit — Join the Crew';

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/header.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $profile_theme = filter_input(INPUT_POST, 'profile_theme', FILTER_SANITIZE_STRING) ?: 'pirate';

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "<div class='auth-message auth-error'>All fields are required.</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='auth-message auth-error'>Invalid transponder snail address.</div>";
    } elseif (strlen($password) < 6) {
        $message = "<div class='auth-message auth-error'>Crew code must be at least 6 characters.</div>";
    } elseif ($password !== $confirm_password) {
        $message = "<div class='auth-message auth-error'>Crew codes do not match.</div>";
    } else {
        $sql_check = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt_check = $conn->prepare($sql_check);
        if ($stmt_check === false) {
            error_log("Register: Error preparing check statement - " . $conn->error);
            $message = "<div class='auth-message auth-error'>An internal error occurred. Please try again later.</div>";
        } else {
            $stmt_check->bind_param("ss", $username, $email);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $message = "<div class='auth-message auth-error'>Pirate name or transponder already registered.</div>";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql_insert = "INSERT INTO users (username, email, password, profile_theme) VALUES (?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);

                if ($stmt_insert === false) {
                    error_log("Register: Error preparing insert statement - " . $conn->error);
                    $message = "<div class='auth-message auth-error'>An internal error occurred. Please try again later.</div>";
                } else {
                    $stmt_insert->bind_param("ssss", $username, $email, $hashed_password, $profile_theme);

                    if ($stmt_insert->execute()) {
                        $message = "<div class='auth-message auth-success'>🏴‍☠️ Welcome aboard, <strong>" . htmlspecialchars($username) . "</strong>! You can now <a href='" . BASE_URL . "auth/login.php'>set sail</a>.</div>";
                    } else {
                        error_log("Register: Error executing insert statement - " . $stmt_insert->error);
                        $message = "<div class='auth-message auth-error'>An error occurred while saving your account. Please try again later.</div>";
                    }
                    $stmt_insert->close();
                }
            }
            $stmt_check->close();
        }
    }
}
?>
<section id="wanted-register">
    <div class="wr-container">
        <!-- Wanted Poster Card -->
        <div class="wanted-recruit-card">
            <!-- Wanted Header -->
            <div class="wr-wanted-stamp">
                <div class="wr-wanted-line1">WANTED</div>
                <div class="wr-wanted-line2">DEAD OR ALIVE</div>
            </div>

            <div class="wr-avatar-area">
                <div class="wr-avatar-preview" id="avatar-preview">
                    <img src="<?php echo BASE_URL; ?>lore/avatar.php?name=☠&bg=D4A843&color=fff&size=80" alt="">
                </div>
                <div class="wr-avatar-hint">☠️ New Recruit</div>
            </div>

            <h2 class="wr-title">BEGIN YOUR PIRATE JOURNEY</h2>
            <p class="wr-subtitle">Register your pirate identity for the Grand Line</p>

            <?php echo $message; ?>

            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="pirate-form" id="registerForm">
                <div class="pf-group">
                    <label for="username" class="pf-label">🏴‍☠️ Pirate Name</label>
                    <input type="text" id="username" name="username" class="pf-input" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required placeholder="e.g. Straw Hat Luffy">
                </div>
                <div class="pf-group">
                    <label for="email" class="pf-label">📞 Transponder Contact</label>
                    <input type="email" id="email" name="email" class="pf-input" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required placeholder="e.g. crew@sunny.go">
                </div>
                <div class="pf-group">
                    <label for="password" class="pf-label">🔒 Secret Crew Code</label>
                    <input type="password" id="password" name="password" class="pf-input" required placeholder="Min 6 characters" onkeyup="registerStrength(this)">
                    <div class="pw-strength-bar pw-meter-bar" id="pw-strength-bar"></div>
                    <div class="pw-tier-label pw-meter-text" id="pw-tier-label">Weak Pirate</div>
                </div>
                <div class="pf-group">
                    <label for="confirm_password" class="pf-label">🔒 Confirm Crew Code</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="pf-input" required placeholder="Confirm your code">
                </div>
                <div class="pf-group">
                    <label for="profile_theme" class="pf-label">🏴 Crew Allegiance</label>
                    <select id="profile_theme" name="profile_theme" class="pf-input pf-select">
                        <option value="pirate" <?php echo (isset($_POST['profile_theme']) && $_POST['profile_theme'] === 'pirate') ? 'selected' : ''; ?>>🏴‍☠️ Pirate</option>
                        <option value="marine" <?php echo (isset($_POST['profile_theme']) && $_POST['profile_theme'] === 'marine') ? 'selected' : ''; ?>>⚓ Marine</option>
                        <option value="revolutionary" <?php echo (isset($_POST['profile_theme']) && $_POST['profile_theme'] === 'revolutionary') ? 'selected' : ''; ?>>✊ Revolutionary</option>
                        <option value="yonko" <?php echo (isset($_POST['profile_theme']) && $_POST['profile_theme'] === 'yonko') ? 'selected' : ''; ?>>👑 Yonko</option>
                        <option value="bounty_hunter" <?php echo (isset($_POST['profile_theme']) && $_POST['profile_theme'] === 'bounty_hunter') ? 'selected' : ''; ?>>💰 Bounty Hunter</option>
                    </select>
                </div>
                <button type="submit" class="pf-submit wr-submit">⚓ SET SAIL</button>
                <div class="pf-links">
                    <a href="<?php echo BASE_URL; ?>auth/login.php" class="pf-link">Already in a crew? Sign the log</a>
                </div>
            </form>

            <div class="wr-footer-stamp">☠️ BOUNTY: 0 ⓑ</div>
        </div>
    </div>
</section>

<script>
function registerStrength(input) {
    var val = input.value;
    var bar = document.getElementById('pw-strength-bar');
    var label = document.getElementById('pw-tier-label');
    var strength = 0;
    if (val.length >= 6) strength += 20;
    if (val.length >= 10) strength += 15;
    if (/[A-Z]/.test(val)) strength += 15;
    if (/[0-9]/.test(val)) strength += 15;
    if (/[^A-Za-z0-9]/.test(val)) strength += 15;
    if (val.length >= 14) strength += 20;
    if (val.length === 0) { strength = 0; }
    bar.style.width = Math.min(100, strength) + '%';
    if (strength < 20) {
        bar.style.background = '#C62828';
        label.textContent = '🐟 Weak Pirate';
        label.style.color = '#C62828';
    } else if (strength < 40) {
        bar.style.background = '#E65100';
        label.textContent = '⚔️ Rookie';
        label.style.color = '#E65100';
    } else if (strength < 60) {
        bar.style.background = '#FDD835';
        label.textContent = '🏴‍☠️ Veteran Pirate';
        label.style.color = '#FDD835';
    } else if (strength < 80) {
        bar.style.background = '#2E7D32';
        label.textContent = '⚡ Supernova';
        label.style.color = '#2E7D32';
    } else {
        bar.style.background = '#D4A843';
        label.textContent = '👑 Pirate King Tier';
        label.style.color = '#D4A843';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
