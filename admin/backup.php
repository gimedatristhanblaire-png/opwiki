<?php
if (session_status() == PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id']) || !is_admin($_SESSION['user_id'], $conn)) {
    header('Location: ' . BASE_URL);
    exit();
}

if (isset($_GET['export'])) {
    $csrf_token = $_GET['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        echo '<section><div class="container"><p class="msg-error">Invalid security token.</p><p><a href="' . BASE_URL . 'admin/backup.php">&laquo; Back</a></p></div></section>';
        require_once __DIR__ . '/../includes/footer.php';
        exit();
    }
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    $output = "# Database: " . DB_NAME . "\n# Export Date: " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($tables as $table) {
        $result = $conn->query("SHOW CREATE TABLE `$table`");
        $row = $result->fetch_row();
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $output .= $row[1] . ";\n\n";

        $rows = $conn->query("SELECT * FROM `$table`");
        while ($row = $rows->fetch_assoc()) {
            $cols = array_map(function($v) use ($conn) {
                return $v === null ? 'NULL' : "'" . $conn->real_escape_string($v) . "'";
            }, array_values($row));
            $output .= "INSERT INTO `$table` VALUES (" . implode(',', $cols) . ");\n";
        }
        $output .= "\n";
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="opwiki_backup_' . date('Y-m-d_H-i-s') . '.sql"');
    echo $output;
    exit();
}

$page_title = 'Database Backup';

$tables = [];
$result = $conn->query("SHOW TABLE STATUS");
$total_size = 0;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tables[] = $row;
        $total_size += $row['Data_length'] + $row['Index_length'];
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<section id="admin-backup">
    <div class="container">
        <h2>Database Backup</h2>
        <p>Export your entire database as a SQL file. This includes all tables, data, and structure.</p>

        <table>
            <thead>
                <tr>
                    <th>Table</th>
                    <th>Rows</th>
                    <th>Size</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tables as $t): ?>
                    <tr>
                        <td data-label="Table"><?php echo htmlspecialchars($t['Name']); ?></td>
                        <td data-label="Rows"><?php echo $t['Rows']; ?></td>
                        <td data-label="Size"><?php echo round(($t['Data_length'] + $t['Index_length']) / 1024, 1); ?> KB</td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td><strong>Total</strong></td>
                    <td></td>
                    <td data-label="Total Size"><strong><?php echo round($total_size / 1024, 1); ?> KB</strong></td>
                </tr>
            </tbody>
        </table>

        <p class="admin-backup-spacer"><a href="<?php echo BASE_URL; ?>admin/backup.php?export=1&csrf_token=<?php echo generate_csrf_token(); ?>" class="btn">Download SQL Backup</a></p>
        <p><a href="<?php echo BASE_URL; ?>admin/dashboard.php">&laquo; Back to Dashboard</a></p>
    </div>
</section>
<?php
require_once __DIR__ . '/../includes/footer.php';
?>
