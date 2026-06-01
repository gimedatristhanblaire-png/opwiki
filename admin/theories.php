<?php
$page_title = 'Manage Theories';

if (session_status() == PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/functions_rep.php';
require_once __DIR__ . '/../includes/functions_notif.php';

if (!isset($_SESSION['user_id']) || !is_staff($_SESSION['user_id'], $conn)) {
    header('Location: ' . BASE_URL);
    exit();
}

$message = '';

if (isset($_GET['approve']) && isset($_GET['csrf_token'])) {
    $theory_id = filter_input(INPUT_GET, 'approve', FILTER_VALIDATE_INT);
    if ($theory_id && verify_csrf_token($_GET['csrf_token'])) {
        $stmt_fetch_user = $conn->prepare("SELECT user_id, title, slug FROM theories WHERE id = ?");
        $author_id = null;
        $theory_title = '';
        $theory_slug = '';
        if ($stmt_fetch_user) {
            $stmt_fetch_user->bind_param("i", $theory_id);
            $stmt_fetch_user->execute();
            $result_user = $stmt_fetch_user->get_result();
            if ($result_user->num_rows === 1) {
                $row_user = $result_user->fetch_assoc();
                $author_id = $row_user['user_id'];
                $theory_title = $row_user['title'];
                $theory_slug = $row_user['slug'];
            }
            $stmt_fetch_user->close();
        }
        $stmt = $conn->prepare("UPDATE theories SET status = 'approved' WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $theory_id);
            if ($stmt->execute()) {
                if ($author_id) {
                    add_reputation($author_id, 30, 'Theory approved', $conn, 'theory', $theory_id);
                    create_notification($author_id, 'theory_approved', 'Your theory "' . htmlspecialchars($theory_title) . '" has been approved!', $conn, BASE_URL . 'theories/view.php?slug=' . urlencode($theory_slug), 'theory', $theory_id);
                }
                $message = "<p style='color:green;'>Theory approved (+30 reputation).</p>";
            } else {
                $message = "<p style='color:red;'>Error approving theory.</p>";
            }
            $stmt->close();
        }
    } else {
        $message = "<p style='color:red;'>Invalid request.</p>";
    }
}

if (isset($_GET['reject']) && isset($_GET['csrf_token'])) {
    $theory_id = filter_input(INPUT_GET, 'reject', FILTER_VALIDATE_INT);
    if ($theory_id && verify_csrf_token($_GET['csrf_token'])) {
        $stmt = $conn->prepare("UPDATE theories SET status = 'rejected' WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $theory_id);
            $stmt->execute() ? $message = "<p style='color:green;'>Theory rejected.</p>" : $message = "<p style='color:red;'>Error rejecting theory.</p>";
            $stmt->close();
        }
    } else {
        $message = "<p style='color:red;'>Invalid request.</p>";
    }
}

if (isset($_GET['delete']) && isset($_GET['csrf_token'])) {
    $theory_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($theory_id && verify_csrf_token($_GET['csrf_token'])) {
        $stmt = $conn->prepare("DELETE FROM theories WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $theory_id);
            if ($stmt->execute()) {
                $message = "<p style='color:green;'>Theory deleted.</p>";
            } else {
                $message = "<p style='color:red;'>Error deleting theory.</p>";
            }
            $stmt->close();
        }
    } else {
        $message = "<p style='color:red;'>Invalid request.</p>";
    }
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where = '';
if ($filter === 'pending') $where = "WHERE t.status = 'pending'";
elseif ($filter === 'approved') $where = "WHERE t.status = 'approved'";
elseif ($filter === 'rejected') $where = "WHERE t.status = 'rejected'";

$theories = [];
$sql = "SELECT t.id, t.title, t.slug, t.status, t.created_at, t.updated_at, u.username
        FROM theories t JOIN users u ON t.user_id = u.id $where
        ORDER BY t.status ASC, t.created_at DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $theories[] = $row;
    }
}

$csrf_token = generate_csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>
<section id="admin-theories">
    <div class="container">
        <h2>Manage Theories</h2>
        <?php echo $message; ?>
        <p>
            <a href="<?php echo BASE_URL; ?>admin/theories.php" class="btn <?php echo $filter==='all'?'btn-secondary':'';?>">All</a>
            <a href="<?php echo BASE_URL; ?>admin/theories.php?filter=pending" class="btn <?php echo $filter==='pending'?'btn-secondary':'';?>">Pending</a>
            <a href="<?php echo BASE_URL; ?>admin/theories.php?filter=approved" class="btn <?php echo $filter==='approved'?'btn-secondary':'';?>">Approved</a>
            <a href="<?php echo BASE_URL; ?>admin/theories.php?filter=rejected" class="btn <?php echo $filter==='rejected'?'btn-secondary':'';?>">Rejected</a>
        </p>
        <?php if (empty($theories)): ?>
            <p>No theories found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($theories as $t): ?>
                        <tr>
                            <td data-label="Title"><a href="<?php echo BASE_URL; ?>theories/view.php?slug=<?php echo urlencode($t['slug']); ?>"><?php echo htmlspecialchars($t['title']); ?></a></td>
                            <td data-label="Author"><?php echo htmlspecialchars($t['username']); ?></td>
                            <td data-label="Status" style="font-weight:bold;color:<?php echo ($t['status']==='pending'?'orange':($t['status']==='approved'?'green':'red'));?>;"><?php echo ucfirst($t['status']); ?></td>
                            <td data-label="Created"><?php echo date('M j, Y', strtotime($t['created_at'])); ?></td>
                            <td data-label="Updated"><?php echo date('M j, Y', strtotime($t['updated_at'])); ?></td>
                            <td data-label="Actions">
                                <a href="<?php echo BASE_URL; ?>theories/view.php?slug=<?php echo urlencode($t['slug']); ?>" class="btn-action btn-approve">View</a>
                                <?php if ($t['status'] !== 'approved'): ?>
                                    <a href="<?php echo BASE_URL; ?>admin/theories.php?approve=<?php echo $t['id']; ?>&csrf_token=<?php echo $csrf_token; ?>" class="btn-action btn-approve">Approve</a>
                                <?php endif; ?>
                                <?php if ($t['status'] !== 'rejected'): ?>
                                    <a href="<?php echo BASE_URL; ?>admin/theories.php?reject=<?php echo $t['id']; ?>&csrf_token=<?php echo $csrf_token; ?>" class="btn-action btn-reject">Reject</a>
                                <?php endif; ?>
                                <a href="<?php echo BASE_URL; ?>theories/submit.php?id=<?php echo $t['id']; ?>" class="btn-action btn-edit">Edit</a>
                                <a href="<?php echo BASE_URL; ?>admin/theories.php?delete=<?php echo $t['id']; ?>&csrf_token=<?php echo $csrf_token; ?>" class="btn-action btn-reject" onclick="return confirm('Delete theory &quot;<?php echo htmlspecialchars($t['title'], ENT_QUOTES); ?>&quot;?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <p><a href="<?php echo BASE_URL; ?>admin/dashboard.php">&laquo; Back to Dashboard</a></p>
    </div>
</section>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
