<?php

function clean($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function generateSlug($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    $text = trim($text, '-');
    return $text ?: 'portfolio';
}

function uniqueSlug(PDO $pdo, string $baseSlug): string {
    $slug = $baseSlug;
    $count = 1;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM portfolios WHERE slug = ?");
    $stmt->execute([$slug]);

    while ($stmt->fetchColumn() > 0) {
        $slug = $baseSlug . '-' . $count;
        $stmt->execute([$slug]);
        $count++;
    }

    return $slug;
}

function validateImage(array $file, int $maxSize = 2097152) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return "Image upload failed.";
    }

    if ($file['size'] > $maxSize) {
        return "Image must be less than 2MB.";
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt, true)) {
        return "Only JPG, JPEG, PNG, WEBP allowed.";
    }

    return true;
}

function uploadImage(array $file, string $targetDir, int $maxSize = 2097152): array {
    $validation = validateImage($file, $maxSize);

    if ($validation !== true) {
        return ['success' => false, 'message' => $validation];
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newName = uniqid('img_', true) . '.' . $ext;
    $fullPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $newName;

    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        return ['success' => false, 'message' => 'Unable to save image.'];
    }

    return ['success' => true, 'filename' => $newName];
}