<?php
require_once '../includes/user_auth.php';
require_once '../includes/db.php';

$userId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'User';

$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.name,
        p.slug,
        p.type,
        p.profile_image,
        p.views,
        p.created_at,
        t.theme_name
    FROM portfolios p
    LEFT JOIN themes t ON p.theme_id = t.id
    WHERE p.user_id = ? AND p.is_deleted = 0
    ORDER BY p.id DESC
");
$stmt->execute([$userId]);
$portfolios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Portfolios</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        body{
            margin:0;
            font-family:'Poppins', sans-serif;
            background:#2e2647;
            color:#fff;
        }

        .page-wrapper{
            max-width:1200px;
            margin:30px auto;
            padding:20px;
        }

        .top-bar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:25px;
            gap:15px;
            flex-wrap:wrap;
        }

        .top-bar h1{
            margin:0;
            font-size:32px;
        }

        .btn{
            display:inline-block;
            padding:12px 18px;
            border-radius:12px;
            text-decoration:none;
            font-weight:600;
            border:none;
            cursor:pointer;
        }

        .btn-primary{
            background:#7f5af0;
            color:#fff;
        }

        .btn-primary:hover{
            background:#6946df;
        }

        .btn-light{
            background:#fff;
            color:#222;
        }

        .grid{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));
            gap:20px;
        }

        .card{
            background:rgba(255,255,255,0.08);
            backdrop-filter:blur(12px);
            border-radius:20px;
            overflow:hidden;
            box-shadow:0 10px 25px rgba(0,0,0,0.2);
        }

        .card-img{
            width:100%;
            height:180px;
            object-fit:cover;
            background:#ddd;
        }

        .card-body{
            padding:18px;
        }

        .card-body h3{
            margin:0 0 10px;
            font-size:22px;
        }

        .meta{
            font-size:14px;
            color:#ddd;
            margin-bottom:8px;
        }

        .slug{
            word-break:break-all;
            font-size:13px;
            color:#cfc3ff;
            margin-bottom:12px;
        }

        .actions{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            margin-top:15px;
        }

        .actions a{
            flex:1;
            min-width:90px;
            text-align:center;
            padding:10px 12px;
            border-radius:10px;
            text-decoration:none;
            font-weight:600;
        }

        .view-btn{
            background:#fff;
            color:#222;
        }

        .edit-btn{
            background:#9b8cff;
            color:#fff;
        }

        .delete-btn{
            background:#ff6b6b;
            color:#fff;
        }

        .empty-box{
            background:rgba(255,255,255,0.08);
            padding:40px 25px;
            border-radius:20px;
            text-align:center;
        }

        .empty-box h2{
            margin-top:0;
        }

        .empty-box p{
            color:#ddd;
            margin-bottom:20px;
        }
    </style>
</head>
<body>
<?php include '../includes/user_navbar.php'; ?>
<div class="page-wrapper">

    <div class="top-bar">
        <div>
            <h1>My Portfolios</h1>
            <p>Welcome, <?= htmlspecialchars($fullName) ?> 👋</p>
        </div>

        <div>
            <a href="dashboard.php" class="btn btn-light">← Dashboard</a>
            <a href="create_portfolio.php" class="btn btn-primary">+ Create Portfolio</a>
        </div>
    </div>

    <?php if (!empty($portfolios)): ?>
        <div class="grid">
            <?php foreach ($portfolios as $portfolio): ?>
                <div class="card">
                    <img 
                        class="card-img"
                        src="<?= !empty($portfolio['profile_image']) ? '../' . htmlspecialchars($portfolio['profile_image']) : '../assets/images/default.png' ?>" 
                        alt="Portfolio Image"
                    >

                    <div class="card-body">
                        <h3><?= htmlspecialchars($portfolio['name']) ?></h3>

                        <div class="meta">Type: <?= htmlspecialchars(ucfirst($portfolio['type'])) ?></div>
                        <div class="meta">Theme: <?= htmlspecialchars($portfolio['theme_name'] ?? 'N/A') ?></div>
                        <div class="meta">Views: <?= (int)$portfolio['views'] ?></div>
                        <div class="meta">Created: <?= htmlspecialchars(date('d M Y', strtotime($portfolio['created_at']))) ?></div>

                        <div class="slug">
                            URL: /<?= htmlspecialchars($portfolio['slug']) ?>
                        </div>

                        <div class="actions">
                            <a class="view-btn" href="/portfolio-builder/<?= urlencode($portfolio['slug']) ?>" target="_blank">View</a>
                            <a class="edit-btn" href="edit_portfolio.php?id=<?= (int)$portfolio['id'] ?>">Edit</a>
                            <a class="delete-btn" href="delete_portfolio.php?id=<?= (int)$portfolio['id'] ?>" onclick="return confirm('Are you sure you want to delete this portfolio?')">Delete</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-box">
            <h2>No Portfolios Yet</h2>
            <p>You haven’t created any portfolio yet. Start with your first one.</p>
            <a href="create_portfolio.php" class="btn btn-primary">Create Portfolio</a>
        </div>
    <?php endif; ?>

</div>

</body>
</html>