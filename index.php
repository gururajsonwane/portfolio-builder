<?php
include 'includes/auth.php';
include 'includes/db.php';

$userId = $_SESSION['user_id'];
$name   = $_SESSION['full_name'];

// stats
$userPortfolioCount = 0;
$total_global_portfolios = 0;
$total_templates = 0;

// total portfolios
$res = mysqli_query($conn, "SELECT COUNT(*) as total FROM portfolios");
if($res){
    $row = mysqli_fetch_assoc($res);
    $total_global_portfolios = $row['total'];
}

// themes
$res2 = mysqli_query($conn, "SELECT COUNT(*) as total FROM themes");
if($res2){
    $row2 = mysqli_fetch_assoc($res2);
    $total_templates = $row2['total'];
}

// user portfolios
$stmt = $conn->prepare("SELECT * FROM portfolios WHERE user_id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userPortfolioCount = $result->num_rows;
?>

<?php include 'includes/header.php'; ?>
<link rel="stylesheet" href="assets/css/dashboard.css">

<?php include 'includes/navbar.php'; ?>

<!-- HERO -->
<div class="hero">
    <h1>Welcome, <?= htmlspecialchars($name) ?> 👋</h1>
    <p>Create and manage your portfolios easily</p>

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

<!-- PORTFOLIOS -->
<div class="portfolio-section">

    <h2>Your Portfolios</h2>

    <?php if($result->num_rows > 0): ?>
        
        <div class="grid">
        <?php while($row = $result->fetch_assoc()): ?>
            
            <div class="portfolio-card">
                <h3><?= htmlspecialchars($row['name']) ?></h3>

                <div class="actions">
                    <a href="view_portfolio.php?id=<?= $row['id'] ?>">View</a>
                    <a href="edit_portfolio.php?id=<?= $row['id'] ?>">Edit</a>
                    <a href="delete_portfolio.php?id=<?= $row['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
                </div>
            </div>

        <?php endwhile; ?>
        </div>

    <?php else: ?>

        <div class="empty">
            <p>No portfolios yet 😢</p>
            <a href="create_portfolio.php">Create your first portfolio</a>
        </div>

    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>