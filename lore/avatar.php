<?php
$name = isset($_GET['name']) ? $_GET['name'] : '?';
$bg = isset($_GET['bg']) ? $_GET['bg'] : '1A3A5C';
$color = isset($_GET['color']) ? $_GET['color'] : 'F5C518';
$size = isset($_GET['size']) ? min(200, max(40, (int)$_GET['size'])) : 100;

// Parse hex colors
$bg_r = hexdec(substr($bg,0,2)); $bg_g = hexdec(substr($bg,2,2)); $bg_b = hexdec(substr($bg,4,2));
$co_r = hexdec(substr($color,0,2)); $co_g = hexdec(substr($color,2,2)); $co_b = hexdec(substr($color,4,2));

// Get initials
$parts = explode(' ', $name);
$initials = '';
foreach ($parts as $p) { if (!empty($p)) $initials .= strtoupper($p[0]); }
$initials = substr($initials, 0, 3);

// Lighter version for gradient
$l_r = min(255, $bg_r + 40); $l_g = min(255, $bg_g + 40); $l_b = min(255, $bg_b + 40);
$bg2 = sprintf("%02x%02x%02x", $l_r, $l_g, $l_b);

$font_size = $size * 0.38;
$border_radius = $size * 0.12;

header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=86400');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<svg xmlns="http://www.w3.org/2000/svg" width="<?php echo $size; ?>" height="<?php echo $size; ?>" viewBox="0 0 <?php echo $size; ?> <?php echo $size; ?>">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#<?php echo $bg; ?>"/>
      <stop offset="100%" style="stop-color:#<?php echo $bg2; ?>"/>
    </linearGradient>
  </defs>
  <rect width="<?php echo $size; ?>" height="<?php echo $size; ?>" rx="<?php echo $border_radius; ?>" fill="url(#bg)"/>
  <rect width="<?php echo $size; ?>" height="<?php echo $size; ?>" rx="<?php echo $border_radius; ?>" fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="2"/>
  <text x="50%" y="50%" dominant-baseline="central" text-anchor="middle" font-family="Arial,Impact,sans-serif" font-weight="700" font-size="<?php echo $font_size; ?>px" fill="#<?php echo $color; ?>"><?php echo htmlspecialchars($initials); ?></text>
  <rect x="0" y="<?php echo $size - $size/4; ?>" width="<?php echo $size; ?>" height="<?php echo $size/4; ?>" fill="rgba(0,0,0,0.08)" rx="<?php echo $border_radius; ?>"/>
</svg>