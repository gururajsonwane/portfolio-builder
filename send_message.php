<?php
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $portfolioId = (int)($_POST['portfolio_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($portfolioId <= 0 || $name === '' || $email === '' || $message === '') {
        die("Invalid input.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email.");
    }

    try {
        // message save
        $stmt = $pdo->prepare("
            INSERT INTO contacts (portfolio_id, name, email, message)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$portfolioId, $name, $email, $message]);

        // slug fetch
        $stmt = $pdo->prepare("SELECT slug FROM portfolios WHERE id = ?");
        $stmt->execute([$portfolioId]);
        $slug = $stmt->fetchColumn();

        if (!$slug) {
            die("Portfolio not found.");
        }

        // redirect back
        header("Location: /portfolio-builder/" . urlencode($slug) . "?success=1");
        exit;

    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}
?>