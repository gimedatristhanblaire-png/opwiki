<?php
function get_theory_vote_count($theory_id, $vote_type, $conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM theory_votes WHERE theory_id = ? AND vote = ?");
    if ($stmt) {
        $stmt->bind_param("is", $theory_id, $vote_type);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $r['c'];
    }
    return 0;
}

function get_vote_score($theory_id, $conn) {
    $up = get_theory_vote_count($theory_id, 'up', $conn);
    $down = get_theory_vote_count($theory_id, 'down', $conn);
    return $up - $down;
}

function get_user_vote($theory_id, $user_id, $conn) {
    $stmt = $conn->prepare("SELECT vote FROM theory_votes WHERE theory_id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $theory_id, $user_id);
        $stmt->execute();
        $r = $stmt->get_result();
        if ($r && $row = $r->fetch_assoc()) { $stmt->close(); return $row['vote']; }
        $stmt->close();
    }
    return null;
}

function is_bookmarked($user_id, $target_type, $target_id, $conn) {
    $stmt = $conn->prepare("SELECT 1 FROM bookmarks WHERE user_id = ? AND target_type = ? AND target_id = ?");
    if ($stmt) {
        $stmt->bind_param("isi", $user_id, $target_type, $target_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $exists = $r && $r->num_rows > 0;
        $stmt->close();
        return $exists;
    }
    return false;
}

function is_following($follower_id, $following_id, $conn) {
    $stmt = $conn->prepare("SELECT 1 FROM user_follows WHERE follower_id = ? AND following_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $follower_id, $following_id);
        $stmt->execute();
        $r = $stmt->get_result();
        $exists = $r && $r->num_rows > 0;
        $stmt->close();
        return $exists;
    }
    return false;
}

function get_follower_count($user_id, $conn) {
    $r = $conn->query("SELECT COUNT(*) as c FROM user_follows WHERE following_id = $user_id");
    return $r ? $r->fetch_assoc()['c'] : 0;
}

function get_following_count($user_id, $conn) {
    $r = $conn->query("SELECT COUNT(*) as c FROM user_follows WHERE follower_id = $user_id");
    return $r ? $r->fetch_assoc()['c'] : 0;
}

function get_bookmarks($user_id, $conn, $limit = 20) {
    $items = [];
    $stmt = $conn->prepare("SELECT b.target_type, b.target_id, b.created_at FROM bookmarks b WHERE b.user_id = ? ORDER BY b.created_at DESC LIMIT ?");
    if ($stmt) {
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $r = $stmt->get_result();
        while ($row = $r->fetch_assoc()) {
            $detail = null;
            if ($row['target_type'] === 'article') {
                $d = $conn->query("SELECT id, title, slug FROM wiki_articles WHERE id = {$row['target_id']}");
                if ($d) $detail = $d->fetch_assoc();
            } else {
                $d = $conn->query("SELECT id, title, slug FROM theories WHERE id = {$row['target_id']}");
                if ($d) $detail = $d->fetch_assoc();
            }
            if ($detail) {
                $items[] = [
                    'type' => $row['target_type'],
                    'id' => $row['target_id'],
                    'title' => $detail['title'],
                    'slug' => $detail['slug'],
                    'bookmarked_at' => $row['created_at']
                ];
            }
        }
        $stmt->close();
    }
    return $items;
}
