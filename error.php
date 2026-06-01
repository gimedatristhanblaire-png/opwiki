<?php
$code = filter_input(INPUT_GET, 'code', FILTER_SANITIZE_STRING);
$valid = ['403', '404', '500'];
if (!in_array($code, $valid)) $code = '404';

$messages = [
    '403' => ['title' => 'Forbidden', 'message' => 'You do not have permission to access this page.'],
    '404' => ['title' => 'Page Not Found', 'message' => 'The page you are looking for does not exist or has been moved.'],
    '500' => ['title' => 'Server Error', 'message' => 'Something went wrong on our end. Please try again later.'],
];

$page_title = $messages[$code]['title'];
http_response_code((int)$code);

require_once __DIR__ . '/includes/header.php';
?>
<section id="error-page">
    <div class="container error-container">
        <h1 class="error-code"><?php echo $code; ?></h1>
        <h2><?php echo htmlspecialchars($messages[$code]['title']); ?></h2>
        <p><?php echo htmlspecialchars($messages[$code]['message']); ?></p>
        <p><a href="<?php echo BASE_URL; ?>" class="btn">Go to Home</a></p>
    </div>
</section>
<?php
require_once __DIR__ . '/includes/footer.php';
?>
