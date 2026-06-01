<?php
$page_title = 'Manage Lore Database';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'auth/login.php'); exit();
}

$type = $_GET['type'] ?? 'characters';
$valid_types = ['characters', 'devil_fruits', 'arcs', 'timeline'];
if (!in_array($type, $valid_types)) $type = 'characters';

$table = $type;
$title_field = ($type === 'characters') ? 'name' : (($type === 'devil_fruits') ? 'name' : (($type === 'arcs') ? 'name' : 'title'));
$message = '';
$csrf_token = generate_csrf_token();

// DELETE
if (isset($_GET['delete']) && isset($_GET['csrf_token'])) {
    $del_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($del_id && verify_csrf_token($_GET['csrf_token'])) {
        $stmt = $conn->prepare("DELETE FROM `$table` WHERE id = ?");
        if ($stmt) { $stmt->bind_param("i", $del_id); $stmt->execute(); $stmt->close(); $message = "<div class='alert alert-success'>Deleted successfully.</div>"; }
    }
}

// ADD / EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && verify_csrf_token($_POST['csrf_token'])) {
    $edit_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $fields = [];
    $vals = [];
    $types_str = '';

    if ($type === 'characters') {
        $fields = ['name','alias','japanese_name','romanji','affiliation','position','origin','first_appearance','height','birthday','danger_level','bounty','status','haki_types','devil_fruit','description','image','debut_arc'];
        foreach ($fields as $f) { $$f = ($f === 'bounty') ? (filter_input(INPUT_POST,$f,FILTER_VALIDATE_INT)?:0) : filter_input(INPUT_POST,$f,FILTER_SANITIZE_STRING); }
        $vals = [$name,$alias,$japanese_name,$romanji,$affiliation,$position,$origin,$first_appearance,$height,$birthday,$danger_level,$bounty,$status,$haki_types,$devil_fruit,$description,$image,$debut_arc];
        $types_str = str_repeat('s', 11) . 'i' . str_repeat('s', 6);
    } elseif ($type === 'devil_fruits') {
        $fields = ['name','japanese_name','type','description','current_holder','awakening','debut_chapter','strength_level','weakness','combat_rating','rarity_meter','threat_level','image'];
        foreach ($fields as $f) { $$f = in_array($f,['combat_rating','rarity_meter']) ? (filter_input(INPUT_POST,$f,FILTER_VALIDATE_INT)?:0) : filter_input(INPUT_POST,$f,FILTER_SANITIZE_STRING); }
        $vals = [$name,$japanese_name,$type,$description,$current_holder,$awakening,$debut_chapter,$strength_level,$weakness,$combat_rating,$rarity_meter,$threat_level,$image];
        $types_str = 'sssssssssiis';
    } elseif ($type === 'arcs') {
        $fields = ['name','japanese_name','arc_number','saga','chapters','episodes','location','timeline_position','major_deaths','tragedy_meter','hype_rating','lore_importance','description','image','key_villains','major_events'];
        foreach ($fields as $f) { $$f = in_array($f,['arc_number','tragedy_meter','hype_rating']) ? (filter_input(INPUT_POST,$f,FILTER_VALIDATE_INT)?:0) : filter_input(INPUT_POST,$f,FILTER_SANITIZE_STRING); }
        $vals = [$name,$japanese_name,$arc_number,$saga,$chapters,$episodes,$location,$timeline_position,$major_deaths,$tragedy_meter,$hype_rating,$lore_importance,$description,$image,$key_villains,$major_events];
        $types_str = 'ssisssssiiisssss';
    } elseif ($type === 'timeline') {
        $fields = ['title','event_date','description','participants','canon_status','arc_id','article_id','importance'];
        foreach ($fields as $f) { $$f = (in_array($f,['arc_id','article_id'])) ? (filter_input(INPUT_POST,$f,FILTER_VALIDATE_INT)?:null) : filter_input(INPUT_POST,$f,FILTER_SANITIZE_STRING); }
        $vals = [$title,$event_date,$description,$participants,$canon_status,$arc_id,$article_id,$importance];
        $types_str = 'sssssiis';
    }

    if ($edit_id) {
        $sets = implode('=?, ', $fields) . '=?';
        $stmt = $conn->prepare("UPDATE `$table` SET $sets WHERE id = ?");
        if ($stmt) { $vals[] = $edit_id; $stmt->bind_param($types_str . 'i', ...$vals); $stmt->execute(); $stmt->close(); $message = "<div class='alert alert-success'>Updated successfully.</div>"; }
    } else {
        $phs = implode(',', array_fill(0, count($fields), '?'));
        $cols = implode(',', $fields);
        $stmt = $conn->prepare("INSERT INTO `$table` ($cols) VALUES ($phs)");
        if ($stmt) { $stmt->bind_param($types_str, ...$vals); $stmt->execute(); $stmt->close(); $message = "<div class='alert alert-success'>Added successfully.</div>"; }
    }
    $csrf_token = generate_csrf_token();
}

$items = [];
$order = ($type === 'arcs') ? 'arc_number ASC' : "$title_field ASC";
$r = $conn->query("SELECT * FROM `$table` ORDER BY $order");
if ($r) { while ($row = $r->fetch_assoc()) { $items[] = $row; } }

$edit_item = null;
if (isset($_GET['edit'])) {
    $eid = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($eid) { $stmt = $conn->prepare("SELECT * FROM `$table` WHERE id = ?"); if ($stmt) { $stmt->bind_param("i", $eid); $stmt->execute(); $r = $stmt->get_result(); $edit_item = $r->fetch_assoc(); $stmt->close(); } }
}

$type_labels = ['characters'=>'Characters','devil_fruits'=>'Devil Fruits','arcs'=>'Story Arcs','timeline'=>'Timeline'];
$emojis = ['characters'=>'🏴‍☠️','devil_fruits'=>'🍎','arcs'=>'🌊','timeline'=>'⏳'];

$v = function($col) use ($edit_item) { return htmlspecialchars($edit_item[$col] ?? ''); };
$selected = function($col, $val) use ($edit_item) { return (($edit_item[$col]??'')==$val)?'selected':''; };
?>
<section id="lore-manage" class="lore-section">
    <div class="container">
        <div class="lore-header">
            <div class="lore-heading"><span class="lore-heading-icon">⚙️</span> Manage Lore Database</div>
            <p class="lore-subtitle">Admin Panel — Marine Intelligence Records</p>
        </div>

        <?php echo $message; ?>

        <div class="lore-filter-tabs">
            <?php foreach ($valid_types as $t): ?>
                <a href="manage.php?type=<?php echo $t; ?>" class="lore-filter-tab <?php echo ($t===$type)?'active':''; ?>"><?php echo $emojis[$t]; ?> <?php echo $type_labels[$t]; ?></a>
            <?php endforeach; ?>
        </div>

        <!-- Form -->
        <div class="parchment-card lore-manage-card">
            <div class="parchment-seal"><?php echo $emojis[$type]; ?></div>
            <h3 class="parchment-title"><?php echo $edit_item ? 'Edit ' . $type_labels[$type] : 'Add New ' . $type_labels[$type]; ?></h3>
            <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="id" value="<?php echo $edit_item['id'] ?? ''; ?>">
                            <div class="form-grid">
                                <?php if ($type === 'characters'): ?>
                                    <div class="form-group"><label>Name:</label><input type="text" name="name" value="<?php echo $v('name'); ?>" required></div>
                                    <div class="form-group"><label>Alias:</label><input type="text" name="alias" value="<?php echo $v('alias'); ?>" placeholder="e.g. Straw Hat Luffy"></div>
                                    <div class="form-group"><label>Japanese Name:</label><input type="text" name="japanese_name" value="<?php echo $v('japanese_name'); ?>"></div>
                                    <div class="form-group"><label>Romanji:</label><input type="text" name="romanji" value="<?php echo $v('romanji'); ?>"></div>
                                    <div class="form-group"><label>Affiliation:</label><input type="text" name="affiliation" value="<?php echo $v('affiliation'); ?>"></div>
                                    <div class="form-group"><label>Position:</label><input type="text" name="position" value="<?php echo $v('position'); ?>" placeholder="e.g. Captain"></div>
                                    <div class="form-group"><label>Origin:</label><input type="text" name="origin" value="<?php echo $v('origin'); ?>" placeholder="e.g. East Blue"></div>
                                    <div class="form-group"><label>First Appearance:</label><input type="text" name="first_appearance" value="<?php echo $v('first_appearance'); ?>" placeholder="Chapter 1 / Episode 1"></div>
                                    <div class="form-group"><label>Height:</label><input type="text" name="height" value="<?php echo $v('height'); ?>" placeholder="e.g. 174 cm"></div>
                                    <div class="form-group"><label>Birthday:</label><input type="text" name="birthday" value="<?php echo $v('birthday'); ?>" placeholder="e.g. May 5"></div>
                                    <div class="form-group"><label>Danger Level:</label><input type="text" name="danger_level" value="<?php echo $v('danger_level'); ?>" placeholder="Normal / High / Emperor / Legendary"></div>
                                    <div class="form-group"><label>Bounty:</label><input type="number" name="bounty" value="<?php echo $v('bounty')?:0; ?>"></div>
                                    <div class="form-group"><label>Status:</label><input type="text" name="status" value="<?php echo $v('status')?:'Alive'; ?>"></div>
                                    <div class="form-group"><label>Haki Types:</label><input type="text" name="haki_types" value="<?php echo $v('haki_types'); ?>" placeholder="e.g. Haoshoku, Busoshoku"></div>
                                    <div class="form-group"><label>Devil Fruit:</label><input type="text" name="devil_fruit" value="<?php echo $v('devil_fruit'); ?>"></div>
                                    <div class="form-group"><label>Debut Arc:</label><input type="text" name="debut_arc" value="<?php echo $v('debut_arc'); ?>"></div>
                                    <div class="form-group form-group-wide"><label>Description:</label><textarea name="description" rows="4" class="lore-textarea"><?php echo $v('description'); ?></textarea></div>
                                    <div class="form-group form-group-wide"><label>Image URL:</label><input type="url" name="image" value="<?php echo $v('image'); ?>"></div>
                                <?php elseif ($type === 'devil_fruits'): ?>
                                    <div class="form-group"><label>Name:</label><input type="text" name="name" value="<?php echo $v('name'); ?>" required></div>
                                    <div class="form-group"><label>Japanese Name:</label><input type="text" name="japanese_name" value="<?php echo $v('japanese_name'); ?>"></div>
                                    <div class="form-group"><label>Type:</label>
                                        <select name="type"><?php foreach(['Paramecia','Zoan','Logia','Mythical Zoan','Ancient Zoan','Special Paramecia','Unknown'] as $t): ?><option value="<?php echo $t; ?>" <?php echo $selected('type',$t); ?>><?php echo $t; ?></option><?php endforeach; ?></select>
                                    </div>
                                    <div class="form-group"><label>Current Holder:</label><input type="text" name="current_holder" value="<?php echo $v('current_holder'); ?>"></div>
                                    <div class="form-group"><label>Awakening:</label>
                                        <select name="awakening"><option value="No" <?php echo $selected('awakening','No'); ?>>No</option><option value="Yes" <?php echo $selected('awakening','Yes'); ?>>Yes</option><option value="Unknown" <?php echo $selected('awakening','Unknown'); ?>>Unknown</option></select>
                                    </div>
                                    <div class="form-group"><label>Debut Chapter:</label><input type="text" name="debut_chapter" value="<?php echo $v('debut_chapter'); ?>" placeholder="e.g. Chapter 1"></div>
                                    <div class="form-group"><label>Strength Level:</label>
                                        <select name="strength_level"><?php foreach(['D','C','B','A','S','SS','SSS'] as $l): ?><option value="<?php echo $l; ?>" <?php echo $selected('strength_level',$l); ?>><?php echo $l; ?></option><?php endforeach; ?></select>
                                    </div>
                                    <div class="form-group"><label>Weakness:</label><input type="text" name="weakness" value="<?php echo $v('weakness'); ?>" placeholder="Seawater, Seastone"></div>
                                    <div class="form-group"><label>Combat Rating (0-100):</label><input type="number" name="combat_rating" value="<?php echo $v('combat_rating')?:0; ?>" min="0" max="100"></div>
                                    <div class="form-group"><label>Rarity Meter (0-100):</label><input type="number" name="rarity_meter" value="<?php echo $v('rarity_meter')?:0; ?>" min="0" max="100"></div>
                                    <div class="form-group"><label>Threat Level:</label><input type="text" name="threat_level" value="<?php echo $v('threat_level'); ?>" placeholder="Low / Dangerous / Catastrophic"></div>
                                    <div class="form-group form-group-wide"><label>Description:</label><textarea name="description" rows="4" class="lore-textarea"><?php echo $v('description'); ?></textarea></div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn lore-manage-btn"><?php echo $edit_item ? 'Update' : 'Add'; ?> Entry</button>
                <?php if ($edit_item): ?><a href="manage.php?type=<?php echo $type; ?>" class="btn lore-manage-btn">Cancel</a><?php endif; ?>
            </form>
        </div>

        <!-- Table -->
        <div class="parchment-card">
            <div class="parchment-seal">📋</div>
            <h3 class="parchment-title">Existing <?php echo $type_labels[$type]; ?> (<?php echo count($items); ?>)</h3>
            <?php if (empty($items)): ?><p class="home-empty">No entries yet.</p>
            <?php else: ?>
                <div class="lore-manage-table-wrap">
                    <table class="lore-manage-table">
                        <thead><tr class="lore-manage-th">
                            <th>ID</th>
                            <th><?php echo ucfirst($title_field); ?></th>
                            <th class="lore-manage-th-actions">Actions</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr class="lore-manage-tr">
                                <td class="lore-manage-td-id"><?php echo $item['id']; ?></td>
                                <td class="lore-manage-td-name"><a href="view.php?type=<?php echo $type; ?>&id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item[$title_field]); ?></a></td>
                                <td class="lore-manage-td-actions">
                                    <a href="manage.php?type=<?php echo $type; ?>&edit=<?php echo $item['id']; ?>" class="btn-sm lore-manage-edit-btn">Edit</a>
                                    <a href="manage.php?type=<?php echo $type; ?>&delete=<?php echo $item['id']; ?>&csrf_token=<?php echo $csrf_token; ?>" class="btn-sm lore-manage-delete-btn" onclick="return confirm('Delete this entry?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <p class="lore-manage-back"><a href="<?php echo BASE_URL; ?>lore/" class="btn">&laquo; Back to Lore Hub</a></p>
    </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
