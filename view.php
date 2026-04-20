<?php
require_once 'includes/db.php';

$slug = trim($_GET['slug'] ?? '');

if ($slug === '') {
    die("Portfolio not found.");
}

// Main portfolio + theme
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        u.full_name AS user_full_name,
        t.theme_name,
        t.folder_name
    FROM portfolios p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN themes t ON p.theme_id = t.id
    WHERE p.slug = ? AND p.is_deleted = 0
    LIMIT 1
");
$stmt->execute([$slug]);
$portfolio = $stmt->fetch();

if (!$portfolio) {
    die("Portfolio not found.");
}

$portfolioId = $portfolio['id'];

// Analytics insert
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$device = preg_match('/mobile/i', $userAgent) ? 'Mobile' : 'Desktop';

$viewStmt = $pdo->prepare("
    INSERT INTO portfolio_views (portfolio_id, ip_address, user_agent, device)
    VALUES (?, ?, ?, ?)
");
$viewStmt->execute([$portfolioId, $ipAddress, $userAgent, $device]);

$updateViewsStmt = $pdo->prepare("
    UPDATE portfolios SET views = views + 1 WHERE id = ?
");
$updateViewsStmt->execute([$portfolioId]);

// Skills
$skillsStmt = $pdo->prepare("
    SELECT skill_name, proficiency
    FROM skills
    WHERE portfolio_id = ?
    ORDER BY id ASC
");
$skillsStmt->execute([$portfolioId]);
$skills = $skillsStmt->fetchAll();

// Projects
$projectsStmt = $pdo->prepare("
    SELECT title, description, project_link, source
    FROM projects
    WHERE portfolio_id = ?
    ORDER BY id ASC
");
$projectsStmt->execute([$portfolioId]);
$projects = $projectsStmt->fetchAll();

// Education
$educationStmt = $pdo->prepare("
    SELECT degree, college, year, percentage
    FROM education
    WHERE portfolio_id = ?
    ORDER BY id ASC
");
$educationStmt->execute([$portfolioId]);
$education = $educationStmt->fetchAll();

// Experience
$experienceStmt = $pdo->prepare("
    SELECT company, role, start_date, end_date, description
    FROM experience
    WHERE portfolio_id = ?
    ORDER BY id ASC
");
$experienceStmt->execute([$portfolioId]);
$experience = $experienceStmt->fetchAll();

// Social Links
$socialStmt = $pdo->prepare("
    SELECT platform, url
    FROM social_links
    WHERE portfolio_id = ?
    ORDER BY id ASC
");
$socialStmt->execute([$portfolioId]);
$socialLinks = $socialStmt->fetchAll();

// Theme include
$folderName = $portfolio['folder_name'] ?? 'theme1';
$themeFile = __DIR__ . '/themes/' . $folderName . '/index.php';

if (!file_exists($themeFile)) {
    die("Theme file not found.");
}

include $themeFile;