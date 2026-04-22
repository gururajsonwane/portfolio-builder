<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
session_start();

// admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$error = '';
$success = '';

// add theme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_theme'])) {
    $themeName = trim($_POST['theme_name'] ?? '');
    $folderName = trim($_POST['folder_name'] ?? '');

    if ($themeName === '' || $folderName === '') {
        $error = 'Theme name and folder name are required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $folderName)) {
        $error = 'Folder name can contain only letters, numbers, underscores and hyphens.';
    } else {
        try {
            $previewImagePath = null;

            if (!empty($_FILES['preview_image']['name'])) {
                $upload = uploadImage($_FILES['preview_image'], '../uploads/themes/');
                if (!$upload['success']) {
                    throw new Exception($upload['message']);
                }
                $previewImagePath = 'uploads/themes/' . $upload['filename'];
            }

            $stmt = $pdo->prepare("
                INSERT INTO themes (theme_name, folder_name, preview_image)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$themeName, $folderName, $previewImagePath]);

            $success = 'Theme added successfully.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// delete theme
if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];

    // theme वापरला जातोय का ते check
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM portfolios WHERE theme_id = ?");
    $checkStmt->execute([$deleteId]);
    $usedCount = $checkStmt->fetchColumn();

    if ($usedCount > 0) {
        $error = 'This theme is already used in portfolios and cannot be deleted.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM themes WHERE id = ?");
        $stmt->execute([$deleteId]);
        header("Location: themes.php");
        exit;
    }
}

// fetch themes
$stmt = $pdo->query("
    SELECT id, theme_name, folder_name, preview_image, created_at
    FROM themes
    ORDER BY id DESC
");
$themes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Themes</title>
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

        h1,h2{
            margin:0 0 15px;
        }

        .btn{
            display:inline-block;
            padding:10px 15px;
            background:#7f5af0;
            color:#fff;
            text-decoration:none;
            border-radius:10px;
            border:none;
            cursor:pointer;
        }

        .btn:hover{
            background:#6842e3;
        }

        .layout{
            display:grid;
            grid-template-columns: 380px 1fr;
            gap:20px;
        }

        .card{
            background:#2b2b3d;
            border-radius:16px;
            padding:20px;
        }

        .field{
            width:100%;
            padding:12px;
            border:none;
            border-radius:10px;
            margin:8px 0 14px;
            box-sizing:border-box;
        }

        .alert-error{
            background:#4a1f28;
            color:#ffb3c1;
            padding:12px;
            border-radius:10px;
            margin-bottom:15px;
        }

        .alert-success{
            background:#1f4a32;
            color:#b8ffd4;
            padding:12px;
            border-radius:10px;
            margin-bottom:15px;
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

        .thumb{
            width:70px;
            height:50px;
            object-fit:cover;
            border-radius:8px;
            background:#ddd;
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
            font-size:13px;
        }

        @media(max-width:900px){
            .layout{
                grid-template-columns:1fr;
            }
        }
    </style>
</head>
<body>

<?php include '../includes/admin_navbar.php'; ?>
<div class="wrapper">

    <div class="top-bar">
        <h1>Manage Themes</h1>
        <a href="dashboard.php" class="btn">← Dashboard</a>
    </div>

    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="layout">

        <div class="card">
            <h2>Add Theme</h2>

            <form method="POST" enctype="multipart/form-data">
                <label>Theme Name</label>
                <input class="field" type="text" name="theme_name" placeholder="e.g. Minimal Dark" required>

                <label>Folder Name</label>
                <input class="field" type="text" name="folder_name" placeholder="e.g. theme4" required>

                <label>Preview Image</label>
                <input class="field" type="file" name="preview_image" accept=".jpg,.jpeg,.png,.webp">

                <button type="submit" name="add_theme" class="btn">Add Theme</button>
            </form>
        </div>

        <div>
            <?php if (!empty($themes)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Preview</th>
                            <th>Theme Name</th>
                            <th>Folder</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($themes as $theme): ?>
                            <tr>
                                <td><?= (int)$theme['id'] ?></td>
                                <td>
                                    <img 
                                        class="thumb"
                                        src="<?= !empty($theme['preview_image']) ? '../' . htmlspecialchars($theme['preview_image']) : '../assets/images/default.png' ?>"
                                        alt="Preview"
                                    >
                                </td>
                                <td><?= htmlspecialchars($theme['theme_name']) ?></td>
                                <td><span class="muted"><?= htmlspecialchars($theme['folder_name']) ?></span></td>
                                <td><?= htmlspecialchars(date('d M Y', strtotime($theme['created_at']))) ?></td>
                                <td>
                                    <a 
                                        class="delete-btn"
                                        href="themes.php?delete=<?= (int)$theme['id'] ?>"
                                        onclick="return confirm('Delete this theme?')"
                                    >
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="card">No themes found.</div>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>