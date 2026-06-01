<?php
$page_title = 'Manage Users';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] === null) {
    $_SESSION['redirect_to'] = BASE_URL . 'admin/users.php';
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? null;
$is_current_user_admin = ($user_role === 'admin');

if (!$is_current_user_admin && $user_role === null && $user_id !== null) {
    $is_current_user_admin = is_admin($user_id, $conn);
}

if (!$is_current_user_admin) {
    header('Location: ' . BASE_URL);
    exit();
}

$message = '';

if (isset($_GET['toggle_role'])) {
    $target_id = filter_input(INPUT_GET, 'toggle_role', FILTER_VALIDATE_INT);
    $csrf_token = filter_input(INPUT_GET, 'csrf_token', FILTER_SANITIZE_STRING);
    if ($target_id && $csrf_token && verify_csrf_token($csrf_token)) {
        if ($target_id == $user_id) {
            $_SESSION['admin_feedback'] = "error:You cannot change your own role.";
        } else {
            $sql = "UPDATE users SET role = IF(role='admin','user','admin') WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $target_id);
                if ($stmt->execute()) {
                    $_SESSION['admin_feedback'] = "success:User role updated successfully.";
                } else {
                    error_log("admin/users.php: Error toggling role - " . $stmt->error);
                    $_SESSION['admin_feedback'] = "error:An error occurred while updating the user role.";
                }
                $stmt->close();
            }
        }
    } else {
        $_SESSION['admin_feedback'] = "error:Invalid or expired security token.";
    }
    header('Location: ' . BASE_URL . 'admin/users.php');
    exit();
}

if (isset($_GET['toggle_ban'])) {
    $target_id = filter_input(INPUT_GET, 'toggle_ban', FILTER_VALIDATE_INT);
    $csrf_token = filter_input(INPUT_GET, 'csrf_token', FILTER_SANITIZE_STRING);
    if ($target_id && $csrf_token && verify_csrf_token($csrf_token)) {
        if ($target_id == $user_id) {
            $_SESSION['admin_feedback'] = "error:You cannot ban or unban yourself.";
        } else {
            $sql = "UPDATE users SET banned = IF(banned=1,0,1) WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $target_id);
                if ($stmt->execute()) {
                    $_SESSION['admin_feedback'] = "success:User ban status updated successfully.";
                } else {
                    error_log("admin/users.php: Error toggling ban - " . $stmt->error);
                    $_SESSION['admin_feedback'] = "error:An error occurred while updating the ban status.";
                }
                $stmt->close();
            }
        }
    } else {
        $_SESSION['admin_feedback'] = "error:Invalid or expired security token.";
    }
    header('Location: ' . BASE_URL . 'admin/users.php');
    exit();
}

if (isset($_SESSION['admin_feedback'])) {
    $admin_fb = $_SESSION['admin_feedback'];
    unset($_SESSION['admin_feedback']);
    $parts = explode(':', $admin_fb, 2);
    if (count($parts) === 2) {
        list($type, $text) = $parts;
        $class = ($type === 'success') ? 'green' : 'red';
        $message = "<p style='color:" . $class . ";'>" . htmlspecialchars($text) . "</p>";
    }
}

$users = [];
$sql_users = "SELECT id, username, email, role, banned, created_at FROM users ORDER BY username ASC";
$result_users = $conn->query($sql_users);
if ($result_users && $result_users->num_rows > 0) {
    while ($row = $result_users->fetch_assoc()) {
        $users[] = $row;
    }
}

$csrf_token = generate_csrf_token();

require_once __DIR__ . '/../includes/header.php';
?>
<section id="admin-users">
    <div class="container">
        <h2>Manage Users</h2>
        <?php echo $message; ?>
        <?php if (!empty($users)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td data-label="ID"><?php echo htmlspecialchars($u['id']); ?></td>
                            <td data-label="Username"><?php echo htmlspecialchars($u['username']); ?></td>
                            <td data-label="Email"><?php echo htmlspecialchars($u['email']); ?></td>
                            <td data-label="Role"><?php echo htmlspecialchars($u['role']); ?></td>
                            <td data-label="Registered"><?php echo date('F j, Y', strtotime($u['created_at'])); ?></td>
                            <td data-label="Actions">
                                <a href="<?php echo BASE_URL; ?>admin/users.php?toggle_role=<?php echo $u['id']; ?>&csrf_token=<?php echo $csrf_token; ?>" class="btn-action btn-edit"><?php echo ($u['role'] === 'admin') ? 'Demote to User' : 'Promote to Admin'; ?></a>
                                <a href="<?php echo BASE_URL; ?>admin/users.php?toggle_ban=<?php echo $u['id']; ?>&csrf_token=<?php echo $csrf_token; ?>" class="btn-action btn-reject"><?php echo ($u['banned'] == 1) ? 'Unban' : 'Ban'; ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No users found.</p>
        <?php endif; ?>
        <p><a href="<?php echo BASE_URL; ?>admin/dashboard.php">&laquo; Back to Dashboard</a></p>
    </div>
</section>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
