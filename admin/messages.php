<?php
require_once '../includes/db.php';
session_start();

// admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// mark as read
if (isset($_GET['read'])) {
    $readId = (int) $_GET['read'];

    $stmt = $pdo->prepare("UPDATE contacts SET is_read = 1 WHERE id = ?");
    $stmt->execute([$readId]);

    header("Location: messages.php");
    exit;
}

// delete message
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->execute([$deleteId]);

    header("Location: messages.php");
    exit;
}

// fetch messages
$stmt = $pdo->query("
    SELECT 
        c.id,
        c.name,
        c.email,
        c.message,
        c.is_read,
        c.created_at,
        p.name AS portfolio_name,
        p.slug,
        u.full_name AS owner_name
    FROM contacts c
    JOIN portfolios p ON c.portfolio_id = p.id
    JOIN users u ON p.user_id = u.id
    ORDER BY c.id DESC
");
$messages = $stmt->fetchAll();

// unread count
$unreadCount = $pdo->query("SELECT COUNT(*) FROM contacts WHERE is_read = 0")->fetchColumn();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Contact Messages</title>
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

        .badge{
            display:inline-block;
            background:#ff4d4f;
            color:#fff;
            padding:6px 10px;
            border-radius:999px;
            font-size:12px;
            font-weight:bold;
            margin-left:10px;
        }

        .table-wrap{
            overflow:auto;
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
            vertical-align:top;
        }

        th{
            background:#34344a;
        }

        tr.unread{
            background:#2f324d;
        }

        .message-box{
            max-width:320px;
            white-space:pre-wrap;
            line-height:1.5;
            color:#eee;
        }

        .muted{
            color:#bbb;
            font-size:13px;
        }

        .status{
            display:inline-block;
            padding:6px 10px;
            border-radius:999px;
            font-size:12px;
            font-weight:bold;
        }

        .read{
            background:#4caf50;
            color:#fff;
        }

        .unread-status{
            background:#ff9800;
            color:#fff;
        }

        .action-btn{
            display:inline-block;
            padding:8px 12px;
            border-radius:8px;
            text-decoration:none;
            color:#fff;
            font-size:13px;
            margin:4px 4px 0 0;
        }

        .read-btn{
            background:#2196f3;
        }

        .view-btn{
            background:#7f5af0;
        }

        .delete-btn{
            background:#ff4d4f;
        }
    </style>
</head>
<body>
<?php include '../includes/admin_navbar.php'; ?>
<div class="wrapper">

    <div class="top-bar">
        <div>
            <h1>
                Contact Messages
                <?php if ($unreadCount > 0): ?>
                    <span class="badge"><?= (int)$unreadCount ?> unread</span>
                <?php endif; ?>
            </h1>
        </div>
        <a href="dashboard.php" class="btn">← Dashboard</a>
    </div>

    <?php if (!empty($messages)): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Sender</th>
                        <th>Portfolio</th>
                        <th>Owner</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Received</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg): ?>
                        <tr class="<?= (int)$msg['is_read'] === 0 ? 'unread' : '' ?>">
                            <td><?= (int)$msg['id'] ?></td>

                            <td>
                                <strong><?= htmlspecialchars($msg['name']) ?></strong><br>
                                <span class="muted"><?= htmlspecialchars($msg['email']) ?></span>
                            </td>

                            <td>
                                <strong><?= htmlspecialchars($msg['portfolio_name']) ?></strong><br>
                                <span class="muted">/<?= htmlspecialchars($msg['slug']) ?></span>
                            </td>

                            <td><?= htmlspecialchars($msg['owner_name']) ?></td>

                            <td>
                                <div class="message-box"><?= htmlspecialchars($msg['message']) ?></div>
                            </td>

                            <td>
                                <?php if ((int)$msg['is_read'] === 1): ?>
                                    <span class="status read">Read</span>
                                <?php else: ?>
                                    <span class="status unread-status">Unread</span>
                                <?php endif; ?>
                            </td>

                            <td class="muted"><?= htmlspecialchars(date('d M Y, h:i A', strtotime($msg['created_at']))) ?></td>

                            <td>
                                <?php if ((int)$msg['is_read'] === 0): ?>
                                    <a class="action-btn read-btn" href="messages.php?read=<?= (int)$msg['id'] ?>">Mark Read</a>
                                <?php endif; ?>

                                <a class="action-btn view-btn" href="../view.php?slug=<?= urlencode($msg['slug']) ?>" target="_blank">View Portfolio</a>

                                <a 
                                    class="action-btn delete-btn" 
                                    href="messages.php?delete=<?= (int)$msg['id'] ?>"
                                    onclick="return confirm('Delete this message?')"
                                >
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>No contact messages found.</p>
    <?php endif; ?>

</div>

</body>
</html>