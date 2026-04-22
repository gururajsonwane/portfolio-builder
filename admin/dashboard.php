<?php
require_once '../includes/db.php';
session_start();

// admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPortfolios = $pdo->query("SELECT COUNT(*) FROM portfolios")->fetchColumn();
$totalViews = $pdo->query("SELECT SUM(views) FROM portfolios")->fetchColumn();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body{
            font-family:Arial;
            margin:0;
            background:#1f1f2e;
            color:#fff;
        }

        .wrapper{
            padding:30px;
        }

        h1{
            margin-bottom:20px;
        }

        .grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
            gap:20px;
        }

        .card{
            background:#2e2e42;
            padding:25px;
            border-radius:15px;
            text-align:center;
        }

        .card h2{
            margin:0;
            font-size:40px;
        }

        .card p{
            margin-top:10px;
            color:#bbb;
        }

        .nav{
            margin-bottom:20px;
        }

        .nav a{
            margin-right:10px;
            color:#fff;
            text-decoration:none;
            padding:10px 15px;
            background:#7f5af0;
            border-radius:10px;
        }
    </style>
</head>
<body>
<?php include '../includes/admin_navbar.php'; ?>
<div class="wrapper">

  
    

    <div class="grid">
        <div class="card">
            <h2><?= $totalUsers ?></h2>
            <p>Total Users</p>
        </div>

        <div class="card">
            <h2><?= $totalPortfolios ?></h2>
            <p>Total Portfolios</p>
        </div>

        <div class="card">
            <h2><?= $totalViews ?: 0 ?></h2>
            <p>Total Views</p>
        </div>
    </div>

</div>

</body>
</html>