<?php
require_once '../includes/db.php';
session_start();

// admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// delete user (optional action from URL)
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];

    // स्वतःचा admin account delete होऊ नये
    if ($deleteId !== (int)$_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$deleteId]);
    }

    header("Location: users.php");
    exit;
}

// fetch all users
$stmt = $pdo->query("
    SELECT id, full_name, email, role, created_at
    FROM users
    ORDER BY id DESC
");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <style>
        body{
            font-family:Arial, sans-serif;
            margin:0;
            background:#1f1f2e;
            color:#fff;
        }

        .wrapper{
            max-width:1200px;
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
        }

        th{
            background:#34344a;
        }

        tr:hover{
            background:#323248;
        }

        .role-badge{
            padding:6px 10px;
            border-radius:999px;
            font-size:12px;
            font-weight:bold;
            display:inline-block;
        }

        .admin{
            background:#ff9800;
            color:#fff;
        }

        .user{
            background:#4caf50;
            color:#fff;
        }

        .delete-btn{
            background:#ff4d4f;
            color:#fff;
            padding:8px 12px;
            border-radius:8px;
            text-decoration:none;
        }

        .delete-btn:hover{
            background:#d9363e;
        }

        .muted{
            color:#bbb;
        }
    </style>
</head>
<body>

<?php include '../includes/admin_navbar.php'; ?>
<div class="wrapper">

    <div class="top-bar">
        <h1>Manage Users</h1>
        <div>
            <a href="dashboard.php" class="btn">← Dashboard</a>
        </div>
    </div>

    <?php if (!empty($users)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= (int)$user['id'] ?></td>
                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <span class="role-badge <?= $user['role'] === 'admin' ? 'admin' : 'user' ?>">
                                <?= htmlspecialchars(ucfirst($user['role'])) ?>
                            </span>
                        </td>
                        <td class="muted"><?= htmlspecialchars(date('d M Y', strtotime($user['created_at']))) ?></td>
                        <td>
                            <?php if ((int)$user['id'] !== (int)$_SESSION['user_id']): ?>
                                <a 
                                    class="delete-btn" 
                                    href="users.php?delete=<?= (int)$user['id'] ?>"
                                    onclick="return confirm('Are you sure you want to delete this user?')"
                                >
                                    Delete
                                </a>
                            <?php else: ?>
                                <span class="muted">Current Admin</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No users found.</p>
    <?php endif; ?>

</div>

</body>
</html>