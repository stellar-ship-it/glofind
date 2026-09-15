<?php
/* 이미지 업로드 — /assets/uploads/insights/YYYY/MM/ 에 저장. jpg/png 는 GD 로 webp 변환(사이트 이미지 규격), 1920px 초과는 축소. */
require_once __DIR__ . '/../config.php';
require_admin_auth();
verify_csrf();

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) json_out(['ok' => false, 'error' => '파일이 없거나 업로드에 실패했습니다.'], 422);
$f = $_FILES['file'];
if ($f['size'] > 20 * 1024 * 1024) json_out(['ok' => false, 'error' => '20MB 이하만 업로드할 수 있습니다.'], 422);

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
if (!isset($allowed[$mime])) json_out(['ok' => false, 'error' => 'JPG · PNG · WEBP · GIF 이미지만 업로드할 수 있습니다.'], 422);
$ext = $allowed[$mime];

$relDir = '/assets/uploads/insights/' . date('Y/m');
$absDir = SITE_ROOT . $relDir;
if (!is_dir($absDir) && !mkdir($absDir, 0755, true)) json_out(['ok' => false, 'error' => '업로드 폴더를 만들 수 없습니다.'], 500);
$base = date('Ymd-His') . '-' . bin2hex(random_bytes(3));

$converted = false;
if (in_array($ext, ['jpg', 'png'], true) && function_exists('imagewebp')) {
    $img = $ext === 'jpg' ? @imagecreatefromjpeg($f['tmp_name']) : @imagecreatefrompng($f['tmp_name']);
    if ($img) {
        $w = imagesx($img); $h = imagesy($img);
        if ($w > 1920) { $nh = (int)round($h * 1920 / $w); $img = imagescale($img, 1920, $nh, IMG_BICUBIC); $w = 1920; $h = $nh; }
        if ($ext === 'png') { imagepalettetotruecolor($img); imagealphablending($img, true); imagesavealpha($img, true); }
        if (imagewebp($img, "$absDir/$base.webp", 82)) { $ext = 'webp'; $converted = true; }
        imagedestroy($img);
    }
}
if (!$converted && !move_uploaded_file($f['tmp_name'], "$absDir/$base.$ext")) json_out(['ok' => false, 'error' => '파일 저장에 실패했습니다.'], 500);

$url = "$relDir/$base.$ext";
[$w, $h] = @getimagesize(SITE_ROOT . $url) ?: [0, 0];
json_out(['ok' => true, 'url' => $url, 'width' => $w, 'height' => $h, 'converted' => $converted]);
