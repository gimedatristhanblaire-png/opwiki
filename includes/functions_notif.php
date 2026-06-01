<?php
function create_notification($user_id, $type, $message, $conn, $link = null, $ref_type = null, $ref_id = null) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message, link, reference_type, reference_id, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
    if ($stmt) {
        $stmt->bind_param("issssi", $user_id, $type, $message, $link, $ref_type, $ref_id);
        $stmt->execute();
        $stmt->close();
    }
}

function get_unread_notification_count($user_id, $conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['c'];
    }
    return 0;
}

function get_notifications($user_id, $conn, $limit = 20) {
    $notifs = [];
    $stmt = $conn->prepare("SELECT id, type, message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    if ($stmt) {
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $notifs[] = $row;
        }
        $stmt->close();
    }
    return $notifs;
}

function mark_notification_read($notif_id, $user_id, $conn) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $notif_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

function mark_all_notifications_read($user_id, $conn) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
}
