
<?php
require_once '../includes/user_auth.php';
require_once '../includes/db.php';

$userId = $_SESSION['user_id'];
$name   = $_SESSION['full_name'];

// stats
$userPortfolioCount = 0;
$total_global_portfolios = 0;
$total_templates = 0;

// user portfolios count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM portfolios WHERE user_id = ?");
$stmt->execute([$userId]);
$userPortfolioCount = $stmt->fetchColumn();

// total portfolios
$stmt = $pdo->query("SELECT COUNT(*) FROM portfolios");
$total_global_portfolios = $stmt->fetchColumn();

// total themes
$stmt = $pdo->query("SELECT COUNT(*) FROM themes");
$total_templates = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
    <div class="logo">Portfolio Builder</div>

    <div class="nav-right">
        <span>👋 <?= htmlspecialchars($name) ?></span>
        <a href="../auth/logout.php" class="btn">Logout</a>
    </div>
</div>

<!-- HERO SECTION -->
<div class="dashboard-hero">
    <h1>Welcome back, <?= htmlspecialchars($name) ?> 👋</h1>
    <p>Ready to build your next portfolio?</p>

    <a href="create_portfolio.php" class="hero-btn">
        + Create Portfolio
    </a>
</div>

<!-- STATS -->
<div class="stats">
    <div class="card">
        <h2><?= $userPortfolioCount ?></h2>
        <p>Your Portfolios</p>
    </div>

    <div class="card">
        <h2><?= $total_global_portfolios ?></h2>
        <p>Total Portfolios</p>
    </div>

    <div class="card">
        <h2><?= $total_templates ?></h2>
        <p>Templates</p>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div class="actions">
    <h2>Quick Actions</h2>

    <div class="action-grid">
        <a href="create_portfolio.php" class="action-card">
            ➕ Create Portfolio
        </a>

        <a href="my_portfolios.php" class="action-card">
            📁 My Portfolios
        </a>
    </div>
</div>

</body>
</html>