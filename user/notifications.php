<?php
$page_title = 'Notifications';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/functions_notif.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = BASE_URL . 'user/notifications.php';
    header('Location: ' . BASE_URL . 'auth/login.php'); exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['mark_read']) && isset($_GET['csrf_token'])) {
    $notif_id = filter_input(INPUT_GET, 'mark_read', FILTER_VALIDATE_INT);
    if ($notif_id && verify_csrf_token($_GET['csrf_token'])) {
        mark_notification_read($notif_id, $user_id, $conn);
        header('Location: ' . BASE_URL . 'user/notifications.php'); exit();
    }
}

if (isset($_GET['mark_all_read']) && isset($_GET['csrf_token'])) {
    if (verify_csrf_token($_GET['csrf_token'])) {
        mark_all_notifications_read($user_id, $conn);
        header('Location: ' . BASE_URL . 'user/notifications.php'); exit();
    }
}

$notifications = get_notifications($user_id, $conn, 50);
$unread_count = get_unread_notification_count($user_id, $conn);
$csrf_token = generate_csrf_token();
?>
<section id="notifications-page">
    <div class="container">
        <h2>Notifications <?php if ($unread_count > 0): ?><span class="notif-badge"><?php echo $unread_count; ?></span><?php endif; ?></h2>
        <?php if ($unread_count > 0): ?>
            <p><a href="<?php echo BASE_URL; ?>user/notifications.php?mark_all_read=1&csrf_token=<?php echo $csrf_token; ?>" class="btn">Mark All as Read</a></p>
        <?php endif; ?>
        <?php if (empty($notifications)): ?>
            <p>No notifications yet.</p>
        <?php else: ?>
            <ul class="notif-list">
                <?php foreach ($notifications as $n): ?>
                    <li class="notif-item" style="background:<?php echo $n['is_read'] ? 'rgba(245,197,24,0.03)' : 'rgba(245,197,24,0.1)'; ?>;border-left-color:<?php echo $n['is_read'] ? '#D4C5A0' : '#F5C518'; ?>;">
                        <?php if ($n['link']): ?>
                            <a href="<?php echo htmlspecialchars($n['link']); ?>" class="notif-link" style="font-weight:<?php echo $n['is_read'] ? 'normal' : 'bold'; ?>;"><?php echo htmlspecialchars($n['message']); ?></a>
                        <?php else: ?>
                            <span class="notif-text" style="font-weight:<?php echo $n['is_read'] ? 'normal' : 'bold'; ?>;"><?php echo htmlspecialchars($n['message']); ?></span>
                        <?php endif; ?>
                        <small class="notif-time"><?php echo time_ago($n['created_at']); ?>
                            <?php if (!$n['is_read']): ?>
                                &bull; <a href="<?php echo BASE_URL; ?>user/notifications.php?mark_read=<?php echo $n['id']; ?>&csrf_token=<?php echo $csrf_token; ?>">Mark read</a>
                            <?php endif; ?>
                        </small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <p><a href="<?php echo BASE_URL; ?>user/profile.php">&laquo; Back to Profile</a></p>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
