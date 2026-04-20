<?php
require_once '../includes/user_auth.php';
require_once '../includes/db.php';

$userId = $_SESSION['user_id'];
$portfolioId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($portfolioId <= 0) {
    header("Location: my_portfolios.php");
    exit;
}

// आधी verify कर की हा portfolio ह्याच user चा आहे
$checkStmt = $pdo->prepare("
    SELECT id 
    FROM portfolios 
    WHERE id = ? AND user_id = ? AND is_deleted = 0
    LIMIT 1
");
$checkStmt->execute([$portfolioId, $userId]);
$portfolio = $checkStmt->fetch();

if (!$portfolio) {
    header("Location: my_portfolios.php");
    exit;
}

// soft delete
$deleteStmt = $pdo->prepare("
    UPDATE portfolios 
    SET is_deleted = 1
    WHERE id = ? AND user_id = ?
");
$deleteStmt->execute([$portfolioId, $userId]);

header("Location: my_portfolios.php");
exit;