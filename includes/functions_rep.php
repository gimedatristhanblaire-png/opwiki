<?php
function add_reputation($user_id, $points, $reason, $conn, $ref_type = null, $ref_id = null) {
    $stmt = $conn->prepare("UPDATE users SET reputation_points = reputation_points + ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $points, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    $stmt2 = $conn->prepare("INSERT INTO reputation_log (user_id, points, reason, reference_type, reference_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    if ($stmt2) {
        $stmt2->bind_param("iissi", $user_id, $points, $reason, $ref_type, $ref_id);
        $stmt2->execute();
        $stmt2->close();
    }
}

function get_reputation_title($points) {
    if ($points >= 100000) return 'Pirate King Analyst';
    if ($points >= 50000) return 'Yonko Analyst';
    if ($points >= 25000) return 'Void Century Scholar';
    if ($points >= 10000) return 'Ancient Historian';
    if ($points >= 5000) return 'Theory Emperor';
    if ($points >= 2000) return 'Pirate Archivist';
    if ($points >= 1000) return 'Grand Line Explorer';
    if ($points >= 500) return 'Rookie Researcher';
    if ($points >= 200) return 'Cabin Boy';
    return 'Landlubber';
}

function get_rep_class($points) {
    if ($points >= 10000) return 'rep-pk';
    if ($points >= 5000) return 'rep-yonko';
    if ($points >= 2500) return 'rep-commander';
    if ($points >= 1000) return 'rep-warlord';
    if ($points >= 500) return 'rep-supernova';
    if ($points >= 200) return 'rep-pirate';
    if ($points >= 50) return 'rep-cabin';
    return 'rep-lubber';
}

function get_like_count($type, $id, $conn) {
    $table = ($type === 'article') ? 'article_likes' : 'theory_likes';
    $col = ($type === 'article') ? 'article_id' : 'theory_id';
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM $table WHERE $col = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['c'];
    }
    return 0;
}

function user_has_liked($type, $id, $user_id, $conn) {
    $table = ($type === 'article') ? 'article_likes' : 'theory_likes';
    $col = ($type === 'article') ? 'article_id' : 'theory_id';
    $stmt = $conn->prepare("SELECT id FROM $table WHERE $col = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $has = $result->num_rows > 0;
        $stmt->close();
        return $has;
    }
    return false;
}
