<?php
require_once '../includes/db.php';
session_start();

// admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// delete portfolio
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM portfolios WHERE id = ?");
    $stmt->execute([$deleteId]);

    header("Location: portfolios.php");
    exit;
}

// fetch all portfolios with user + theme
$stmt = $pdo->query("
    SELECT 
        p.id,
        p.name,
        p.slug,
        p.type,
        p.views,
        p.created_at,
        p.profile_image,
        u.full_name,
        u.email,
        t.theme_name
    FROM portfolios p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN themes t ON p.theme_id = t.id
    ORDER BY p.id DESC
");
$portfolios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Portfolios</title>
    <style>
        body{
            font-family:Arial, sans-serif;
            margin:0;
            background:#1f1f2e;
            color:#fff;
        }

        .wrapper{
            max-width:1300px;
            margin:30px auto;
            padding:20px;
        }

        .top-bar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:20px;
            flex-wrap:wrap;
            gap:10px;
        }

        h1{
            margin:0;
        }

        .btn{
            display:inline-block;
            padding:10px 15px;
            background:#7f5af0;
            color:#fff;
            text-decoration:none;
            border-radius:10px;
        }

        .btn:hover{
            background:#6842e3;
        }

        table{
            width:100%;
            border-collapse:collapse;
            background:#2b2b3d;
            border-radius:12px;
            overflow:hidden;
        }

        th, td{
            padding:14px;
            text-align:left;
            border-bottom:1px solid rgba(255,255,255,0.08);
            vertical-align:middle;
        }

        th{
            background:#34344a;
        }

        tr:hover{
            background:#323248;
        }

        .thumb{
            width:55px;
            height:55px;
            border-radius:10px;
            object-fit:cover;
            background:#ddd;
        }

        .type-badge{
            padding:6px 10px;
            border-radius:999px;
            font-size:12px;
            font-weight:bold;
            display:inline-block;
        }

        .fresher{
            background:#4caf50;
            color:#fff;
        }

        .experienced{
            background:#2196f3;
            color:#fff;
        }

        .view-btn,
        .delete-btn{
            display:inline-block;
            padding:8px 12px;
            border-radius:8px;
            text-decoration:none;
            color:#fff;
            font-size:14px;
            margin-right:6px;
        }

        .view-btn{
            background:#7f5af0;
        }

        .view-btn:hover{
            background:#6842e3;
        }

        .delete-btn{
            background:#ff4d4f;
        }

        .delete-btn:hover{
            background:#d9363e;
        }

        .muted{
            color:#bbb;
            font-size:13px;
        }
    </style>
</head>
<body>

<div class="wrapper">

    <div class="top-bar">
        <h1>Manage Portfolios</h1>
        <div>
            <a href="dashboard.php" class="btn">← Dashboard</a>
        </div>
    </div>

    <?php if (!empty($portfolios)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Preview</th>
                    <th>Portfolio</th>
                    <th>User</th>
                    <th>Type</th>
                    <th>Theme</th>
                    <th>Views</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($portfolios as $portfolio): ?>
                    <tr>
                        <td><?= (int)$portfolio['id'] ?></td>

                        <td>
                            <img 
                                class="thumb"
                                src="<?= !empty($portfolio['profile_image']) ? '../' . htmlspecialchars($portfolio['profile_image']) : '../assets/images/default.png' ?>" 
                                alt="Preview"
                            >
                        </td>

                        <td>
                            <strong><?= htmlspecialchars($portfolio['name']) ?></strong><br>
                            <span class="muted">/<?= htmlspecialchars($portfolio['slug']) ?></span>
                        </td>

                        <td>
                            <?= htmlspecialchars($portfolio['full_name']) ?><br>
                            <span class="muted"><?= htmlspecialchars($portfolio['email']) ?></span>
                        </td>

                        <td>
                            <span class="type-badge <?= $portfolio['type'] === 'experienced' ? 'experienced' : 'fresher' ?>">
                                <?= htmlspecialchars(ucfirst($portfolio['type'])) ?>
                            </span>
                        </td>

                        <td><?= htmlspecialchars($portfolio['theme_name'] ?? 'N/A') ?></td>
                        <td><?= (int)$portfolio['views'] ?></td>
                        <td class="muted"><?= htmlspecialchars(date('d M Y', strtotime($portfolio['created_at']))) ?></td>

                        <td>
                           
                            <a 
                                class="view-btn"
                                href="/portfolio-builder/<?= urlencode($portfolio['slug']) ?>" 
                                target="_blank"
                            >
                                View
                            </a>
                            
                            <a 
                                class="delete-btn" 
                                href="portfolios.php?delete=<?= (int)$portfolio['id'] ?>"
                                onclick="return confirm('Are you sure you want to delete this portfolio?')"
                            >
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No portfolios found.</p>
    <?php endif; ?>

</div>

</body>
</html>