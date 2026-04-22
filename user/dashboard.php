<?php
require_once '../includes/user_auth.php';
require_once '../includes/db.php';

$userId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'User';

$baseUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST'] . '/portfolio-builder/';

// total portfolios
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM portfolios 
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$totalPortfolios = (int) $stmt->fetchColumn();

// total views
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(views), 0) 
    FROM portfolios 
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$totalViews = (int) $stmt->fetchColumn();

// latest portfolio
$stmt = $pdo->prepare("
    SELECT id, name, slug, type, profile_image, created_at, views
    FROM portfolios
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([$userId]);
$latestPortfolio = $stmt->fetch();

// recent portfolios
$stmt = $pdo->prepare("
    SELECT id, name, slug, type, profile_image, created_at, views
    FROM portfolios
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 4
");
$stmt->execute([$userId]);
$recentPortfolios = $stmt->fetchAll();

// most viewed portfolio
$stmt = $pdo->prepare("
    SELECT id, name, slug, views, profile_image, type
    FROM portfolios
    WHERE user_id = ? AND views > 0
    ORDER BY views DESC, id DESC
    LIMIT 1
");
$stmt->execute([$userId]);
$mostViewedPortfolio = $stmt->fetch();

// top 3 portfolios
$stmt = $pdo->prepare("
    SELECT id, name, slug, views, type
    FROM portfolios
    WHERE user_id = ? AND views > 0
    ORDER BY views DESC, id DESC
    LIMIT 3
");
$stmt->execute([$userId]);
$topPortfolios = $stmt->fetchAll();

// recent view activity (last 7 days)
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM portfolio_views pv
    INNER JOIN portfolios p ON pv.portfolio_id = p.id
    WHERE p.user_id = ?
      AND pv.viewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$stmt->execute([$userId]);
$recentViews7Days = (int) $stmt->fetchColumn();

// average views
$averageViews = $totalPortfolios > 0 ? round($totalViews / $totalPortfolios, 1) : 0;

// profile summary
$stmt = $pdo->prepare("
    SELECT 
        up.phone,
        up.location,
        up.headline,
        up.about_me,
        up.linkedin_url,
        up.github_url,
        up.resume_file,
        u.profile_image
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE u.id = ?
    LIMIT 1
");
$stmt->execute([$userId]);
$profileSummary = $stmt->fetch();

$hasProfileData = !empty($profileSummary['profile_image']) ||
    !empty($profileSummary['phone']) ||
    !empty($profileSummary['location']) ||
    !empty($profileSummary['headline']) ||
    !empty($profileSummary['about_me']) ||
    !empty($profileSummary['github_url']) ||
    !empty($profileSummary['linkedin_url']) ||
    !empty($profileSummary['resume_file']);

// profile completion
$completionItems = [
    'Profile Image' => !empty($profileSummary['profile_image']),
    'Phone' => !empty($profileSummary['phone']),
    'Location' => !empty($profileSummary['location']),
    'Headline' => !empty($profileSummary['headline']),
    'About Me' => !empty($profileSummary['about_me']),
    'GitHub URL' => !empty($profileSummary['github_url']),
    'LinkedIn URL' => !empty($profileSummary['linkedin_url']),
    'Resume' => !empty($profileSummary['resume_file']),
];

$totalItems = count($completionItems);
$completedItems = count(array_filter($completionItems));
$completionPercent = (int) round(($completedItems / $totalItems) * 100);

$missingItems = [];
foreach ($completionItems as $label => $done) {
    if (!$done) {
        $missingItems[] = $label;
    }
}

function profileImageUrl(?string $path): string {
    if (!empty($path)) {
        return '../' . htmlspecialchars($path);
    }
    return '../assets/images/default.png';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
    <link rel="stylesheet" href="../assets/css/user_dashboard.css">
</head>
<body>

<?php include '../includes/user_navbar.php'; ?>

<div class="page-shell">
    <div class="dashboard-wrapper">

    <div class="welcome-box">
    <h1>Welcome, <?= htmlspecialchars($fullName) ?> 👋</h1>
    <p>Build, manage, and share your professional portfolio with ease.</p>

    
</div>
       

        <div class="stats-grid">
            <div class="stat-card">
                <h2><?= $totalPortfolios ?></h2>
                <p>Total Portfolios</p>
            </div>

            <div class="stat-card">
                <h2><?= $totalViews ?></h2>
                <p>Total Views</p>
            </div>

            <div class="stat-card">
                <h2><?= $latestPortfolio ? htmlspecialchars(ucfirst($latestPortfolio['type'])) : 'N/A' ?></h2>
                <p>Latest Portfolio Type</p>
            </div>

            <div class="stat-card">
                <h2><?= htmlspecialchars((string) $averageViews) ?></h2>
                <p>Average Views</p>
            </div>
        </div>

        <div class="section">
            <h2>My Profile Summary</h2>

            <?php if ($hasProfileData): ?>
                <div class="profile-summary-box">
                    <div class="profile-summary-left">
                        <img 
                            src="<?= profileImageUrl($profileSummary['profile_image'] ?? null) ?>" 
                            alt="Profile Image"
                        >
                    </div>

                    <div class="profile-summary-right">
                        <h3><?= htmlspecialchars($fullName) ?></h3>

                        <p class="meta">
                            <?= !empty($profileSummary['headline']) ? htmlspecialchars($profileSummary['headline']) : 'No headline added yet.' ?>
                        </p>

                        <?php if (!empty($profileSummary['location'])): ?>
                            <p class="meta">📍 <?= htmlspecialchars($profileSummary['location']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($profileSummary['phone'])): ?>
                            <p class="meta">📞 <?= htmlspecialchars($profileSummary['phone']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($profileSummary['about_me'])): ?>
                            <p class="meta">
                                <?= nl2br(htmlspecialchars(mb_strimwidth($profileSummary['about_me'], 0, 180, '...'))) ?>
                            </p>
                        <?php endif; ?>

                        <div class="profile-links">
                            <?php if (!empty($profileSummary['github_url'])): ?>
                                <a href="<?= htmlspecialchars($profileSummary['github_url']) ?>" target="_blank" class="profile-link-btn">GitHub</a>
                            <?php endif; ?>

                            <?php if (!empty($profileSummary['linkedin_url'])): ?>
                                <a href="<?= htmlspecialchars($profileSummary['linkedin_url']) ?>" target="_blank" class="profile-link-btn">LinkedIn</a>
                            <?php endif; ?>

                            <?php if (!empty($profileSummary['resume_file'])): ?>
                                <a href="../<?= htmlspecialchars($profileSummary['resume_file']) ?>" target="_blank" class="profile-link-btn">Resume</a>
                            <?php endif; ?>

                            <a href="profile.php" class="profile-link-btn edit-profile-btn">Edit Profile</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-box">
                    <p>Your profile is not completed yet.</p>
                    <a href="profile.php" class="btn btn-primary">Complete Profile</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Profile Completion</h2>

            <div class="completion-card">
                <div class="completion-top">
                    <div>
                        <h3><?= $completionPercent ?>% Complete</h3>
                        <p class="meta">Complete your profile to make your portfolio stronger and more professional.</p>
                    </div>

                    <a href="profile.php" class="btn btn-primary">
                        <?= $completionPercent < 100 ? 'Complete Profile' : 'Update Profile' ?>
                    </a>
                </div>

                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $completionPercent ?>%;"></div>
                </div>

                <?php if (!empty($missingItems)): ?>
                    <div class="missing-list">
                        <h4>Missing Details</h4>
                        <ul>
                            <?php foreach ($missingItems as $item): ?>
                                <li><?= htmlspecialchars($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="completion-success">
                        🎉 Your profile is fully completed.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <h2>Most Viewed Portfolio</h2>

            <?php if ($mostViewedPortfolio): ?>
                <div class="latest-card">
                    <img 
                        src="<?= profileImageUrl($mostViewedPortfolio['profile_image'] ?? null) ?>" 
                        alt="Most Viewed Portfolio"
                    >

                    <div class="latest-info">
                        <h3><?= htmlspecialchars($mostViewedPortfolio['name']) ?></h3>
                        <div class="meta">Type: <?= htmlspecialchars(ucfirst($mostViewedPortfolio['type'])) ?></div>
                        <div class="meta">Views: <?= (int) $mostViewedPortfolio['views'] ?></div>

                        <div class="slug-box">
                            <?= htmlspecialchars($baseUrl . $mostViewedPortfolio['slug']) ?>
                        </div>

                        <div class="portfolio-actions">
                            <a class="view-btn" href="/portfolio-builder/<?= urlencode($mostViewedPortfolio['slug']) ?>" target="_blank">View</a>
                            <button class="copy-btn" onclick="copyPortfolioLink('<?= htmlspecialchars($mostViewedPortfolio['slug'], ENT_QUOTES) ?>')">Copy Link</button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-box">
                    <p>No portfolio views yet. Share your portfolio link to start getting traffic.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Latest Portfolio</h2>

            <?php if ($latestPortfolio): ?>
                <div class="latest-card">
                    <img 
                        src="<?= profileImageUrl($latestPortfolio['profile_image'] ?? null) ?>" 
                        alt="Latest Portfolio"
                    >

                    <div class="latest-info">
                        <h3><?= htmlspecialchars($latestPortfolio['name']) ?></h3>
                        <div class="meta">Type: <?= htmlspecialchars(ucfirst($latestPortfolio['type'])) ?></div>
                        <div class="meta">Views: <?= (int) $latestPortfolio['views'] ?></div>
                        <div class="meta">Created: <?= htmlspecialchars(date('d M Y', strtotime($latestPortfolio['created_at']))) ?></div>

                        <div class="slug-box" id="latestLink">
                            <?= htmlspecialchars($baseUrl . $latestPortfolio['slug']) ?>
                        </div>

                        <div class="portfolio-actions">
                            <a class="view-btn" href="/portfolio-builder/<?= urlencode($latestPortfolio['slug']) ?>" target="_blank">View</a>
                            <a class="edit-btn" href="edit_portfolio.php?id=<?= (int) $latestPortfolio['id'] ?>">Edit</a>
                            <button class="copy-btn" onclick="copyLatestLink()">Copy Link</button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-box">
                    <p>No portfolio created yet.</p>
                    <a href="create_portfolio.php" class="btn btn-primary">Create Your First Portfolio</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Analytics Insights</h2>

            <div class="analytics-grid">
                <div class="analytics-box">
                    <h3><?= $recentViews7Days ?></h3>
                    <p>Views in Last 7 Days</p>
                </div>

                <div class="analytics-box">
                    <h3><?= $mostViewedPortfolio ? (int) $mostViewedPortfolio['views'] : 0 ?></h3>
                    <p>Highest Views on One Portfolio</p>
                </div>

                <div class="analytics-box">
                    <h3><?= $totalPortfolios ?></h3>
                    <p>Published Portfolios</p>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Top Performing Portfolios</h2>

            <?php if (!empty($topPortfolios)): ?>
                <div class="top-list">
                    <?php foreach ($topPortfolios as $index => $portfolio): ?>
                        <div class="top-list-item">
                            <div class="top-rank">#<?= $index + 1 ?></div>

                            <div class="top-content">
                                <h3><?= htmlspecialchars($portfolio['name']) ?></h3>
                                <div class="meta">Type: <?= htmlspecialchars(ucfirst($portfolio['type'])) ?></div>
                                <div class="meta">Views: <?= (int) $portfolio['views'] ?></div>
                            </div>

                            <div class="top-actions">
                                <a class="view-btn" href="/portfolio-builder/<?= urlencode($portfolio['slug']) ?>" target="_blank">View</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-box">
                    <p>No portfolio performance data available yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>Recent Portfolios</h2>

            <?php if (!empty($recentPortfolios)): ?>
                <div class="portfolio-grid">
                    <?php foreach ($recentPortfolios as $portfolio): ?>
                        <div class="portfolio-card">
                            <img 
                                src="<?= profileImageUrl($portfolio['profile_image'] ?? null) ?>" 
                                alt="Portfolio"
                            >

                            <div class="portfolio-card-body">
                                <h3><?= htmlspecialchars($portfolio['name']) ?></h3>
                                <div class="meta">Type: <?= htmlspecialchars(ucfirst($portfolio['type'])) ?></div>
                                <div class="meta">Views: <?= (int) $portfolio['views'] ?></div>

                                <div class="portfolio-actions">
                                    <a class="view-btn" href="/portfolio-builder/<?= urlencode($portfolio['slug']) ?>" target="_blank">View</a>
                                    <a class="edit-btn" href="edit_portfolio.php?id=<?= (int) $portfolio['id'] ?>">Edit</a>
                                    <button class="copy-btn" onclick="copyPortfolioLink('<?= htmlspecialchars($portfolio['slug'], ENT_QUOTES) ?>')">Copy Link</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-box">
                    <p>No portfolios available yet.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="../assets/js/user_dashboard.js"></script>
</body>
</html>