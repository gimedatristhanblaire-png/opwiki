<?php
$page_title = 'Manage Categories';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] === null) {
    $_SESSION['redirect_to'] = BASE_URL . 'admin/categories.php';
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

if (isset($_GET['delete'])) {
    $delete_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    $csrf_token = filter_input(INPUT_GET, 'csrf_token', FILTER_SANITIZE_STRING);
    if ($delete_id && $csrf_token && verify_csrf_token($csrf_token)) {
        $sql_delete = "DELETE FROM categories WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        if ($stmt_delete) {
            $stmt_delete->bind_param("i", $delete_id);
            if ($stmt_delete->execute()) {
                $_SESSION['admin_feedback'] = "success:Category deleted successfully.";
            } else {
                error_log("admin/categories.php: Error deleting category - " . $stmt_delete->error);
                $_SESSION['admin_feedback'] = "error:An error occurred while deleting the category.";
            }
            $stmt_delete->close();
        }
    } else {
        $_SESSION['admin_feedback'] = "error:Invalid or expired security token.";
    }
    header('Location: ' . BASE_URL . 'admin/categories.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        $_SESSION['admin_feedback'] = "error:Invalid or expired security token. Please try again.";
    } elseif (empty($category_name)) {
        $_SESSION['admin_feedback'] = "error:Category name is required.";
    } else {
        $sql_insert = "INSERT INTO categories (name) VALUES (?)";
        $stmt_insert = $conn->prepare($sql_insert);
        if ($stmt_insert) {
            $stmt_insert->bind_param("s", $category_name);
            if ($stmt_insert->execute()) {
                $_SESSION['admin_feedback'] = "success:Category added successfully.";
            } else {
                error_log("admin/categories.php: Error inserting category - " . $stmt_insert->error);
                $_SESSION['admin_feedback'] = "error:An error occurred while adding the category.";
            }
            $stmt_insert->close();
        }
    }
    header('Location: ' . BASE_URL . 'admin/categories.php');
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

$categories = [];
$sql_cats = "SELECT id, name, created_at FROM categories ORDER BY name ASC";
$result_cats = $conn->query($sql_cats);
if ($result_cats && $result_cats->num_rows > 0) {
    while ($row = $result_cats->fetch_assoc()) {
        $categories[] = $row;
    }
}

$csrf_token = generate_csrf_token();

require_once __DIR__ . '/../includes/header.php';
?>
<section id="admin-categories">
    <div class="container">
        <h2>Manage Categories</h2>
        <?php echo $message; ?>
        <div class="category-form">
            <h3>Add New Category</h3>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="text" name="name" placeholder="Category name" required>
                <button type="submit" class="btn">Add Category</button>
            </form>
        </div>
        <?php if (!empty($categories)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td data-label="ID"><?php echo htmlspecialchars($cat['id']); ?></td>
                            <td data-label="Name"><?php echo htmlspecialchars($cat['name']); ?></td>
                            <td data-label="Created At"><?php echo date('F j, Y', strtotime($cat['created_at'])); ?></td>
                            <td data-label="Actions">
                                <a href="<?php echo BASE_URL; ?>admin/categories.php?delete=<?php echo $cat['id']; ?>&csrf_token=<?php echo $csrf_token; ?>" class="btn-action btn-reject" onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No categories found.</p>
        <?php endif; ?>
        <p><a href="<?php echo BASE_URL; ?>admin/dashboard.php">&laquo; Back to Dashboard</a></p>
    </div>
</section>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>