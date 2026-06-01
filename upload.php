<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db_connection.php';

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/includes/functions.php';

$user_role = $_SESSION['user_role'] ?? null;
$is_admin = ($user_role === 'admin');
if (!$is_admin && $user_role === null && isset($_SESSION['user_id'])) {
    $is_admin = is_admin($_SESSION['user_id'], $conn);
}
if (!$is_admin) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: admins only']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Upload failed']);
    exit();
}

$file = $_FILES['file'];

$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($extension, $allowed_extensions)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type']);
    exit();
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed_mimes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type']);
    exit();
}

$max_size = 10 * 1024 * 1024;
if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large (max 10MB)']);
    exit();
}

$upload_dir = __DIR__ . '/uploads/';
$thumb_dir = $upload_dir . 'thumbs/';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }
if (!is_dir($thumb_dir)) { mkdir($thumb_dir, 0755, true); }

$safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
$filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
$destination = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
    exit();
}

$width = 0; $height = 0;
$webp_filename = null;
$thumb_filename = null;

if (function_exists('imagecreatefromjpeg') && function_exists('imagewebp')) {
    $img = null;
    switch ($mime) {
        case 'image/jpeg': $img = imagecreatefromjpeg($destination); break;
        case 'image/png': $img = imagecreatefrompng($destination); break;
        case 'image/gif': $img = imagecreatefromgif($destination); break;
        case 'image/webp': $img = imagecreatefromwebp($destination); break;
    }

    if ($img) {
        $width = imagesx($img);
        $height = imagesy($img);

        $webp_filename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
        $webp_dest = $upload_dir . $webp_filename;
        if (imagewebp($img, $webp_dest, 80)) {
            $filename = $webp_filename;
            $mime = 'image/webp';
            @unlink($destination);
        }

        $max_thumb = 300;
        if ($width > $max_thumb || $height > $max_thumb) {
            $ratio = min($max_thumb / $width, $max_thumb / $height);
            $tw = (int)round($width * $ratio);
            $th = (int)round($height * $ratio);
            $thumb_img = imagecreatetruecolor($tw, $th);
            imagecopyresampled($thumb_img, $img, 0, 0, 0, 0, $tw, $th, $width, $height);
            $thumb_filename = 'thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.webp';
            imagewebp($thumb_img, $thumb_dir . $thumb_filename, 70);
            imagedestroy($thumb_img);
        }

        imagedestroy($img);
    }
}

if (!$width && !$height && $extension !== 'gif') {
    $info = @getimagesize($destination);
    if ($info) { $width = $info[0]; $height = $info[1]; }
}

$url = BASE_URL . 'uploads/' . $filename;
$thumb_url = $thumb_filename ? BASE_URL . 'uploads/thumbs/' . $thumb_filename : $url;

$stmt = $conn->prepare("INSERT INTO media (user_id, filename, original_name, type, mime, width, height, size, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
$media_id = null;
if ($stmt) {
    $type = pathinfo($file['name'], PATHINFO_EXTENSION);
    $stmt->bind_param("issssiii", $_SESSION['user_id'], $filename, $file['name'], $type, $mime, $width, $height, $file['size']);
    $stmt->execute();
    $media_id = $conn->insert_id;
    $stmt->close();
}

echo json_encode([
    'location' => $url,
    'media_id' => $media_id,
    'width' => $width,
    'height' => $height,
    'thumb' => $thumb_url
]);
