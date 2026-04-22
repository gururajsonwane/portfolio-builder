<?php
require_once '../includes/user_auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$userId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'User';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

// existing profile fetch
$stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

// current user image fetch from users table
$userStmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ? LIMIT 1");
$userStmt->execute([$userId]);
$userData = $userStmt->fetch();
$currentProfileImage = $userData['profile_image'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = 'Invalid request.';
    } else {
        $phone = trim($_POST['phone'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $headline = trim($_POST['headline'] ?? '');
        $aboutMe = trim($_POST['about_me'] ?? '');
        $linkedinUrl = trim($_POST['linkedin_url'] ?? '');
        $githubUrl = trim($_POST['github_url'] ?? '');

        $resumeFilePath = $profile['resume_file'] ?? null;
        $profileImagePath = $currentProfileImage;

        // validate urls
        if ($linkedinUrl !== '' && !filter_var($linkedinUrl, FILTER_VALIDATE_URL)) {
            $error = 'Invalid LinkedIn URL.';
        } elseif ($githubUrl !== '' && !filter_var($githubUrl, FILTER_VALIDATE_URL)) {
            $error = 'Invalid GitHub URL.';
        } else {
            try {
                // profile image upload
                if (!empty($_FILES['profile_image']['name'])) {
                    $upload = uploadImage($_FILES['profile_image'], '../uploads/profiles/');
                    if (!$upload['success']) {
                        throw new Exception($upload['message']);
                    }
                    $profileImagePath = 'uploads/profiles/' . $upload['filename'];
                }

                // resume upload
                if (!empty($_FILES['resume_file']['name'])) {
                    $allowedResumeExt = ['pdf', 'doc', 'docx'];
                    $resumeName = $_FILES['resume_file']['name'];
                    $resumeTmp = $_FILES['resume_file']['tmp_name'];
                    $resumeSize = $_FILES['resume_file']['size'];
                    $resumeExt = strtolower(pathinfo($resumeName, PATHINFO_EXTENSION));

                    if (!in_array($resumeExt, $allowedResumeExt, true)) {
                        throw new Exception('Only PDF, DOC, DOCX resume files are allowed.');
                    }

                    if ($resumeSize > 5 * 1024 * 1024) {
                        throw new Exception('Resume file must be less than 5MB.');
                    }

                    if (!is_dir('../uploads/resumes/')) {
                        mkdir('../uploads/resumes/', 0777, true);
                    }

                    $newResumeName = uniqid('resume_', true) . '.' . $resumeExt;
                    $resumeTarget = '../uploads/resumes/' . $newResumeName;

                    if (!move_uploaded_file($resumeTmp, $resumeTarget)) {
                        throw new Exception('Failed to upload resume.');
                    }

                    $resumeFilePath = 'uploads/resumes/' . $newResumeName;
                }

                $pdo->beginTransaction();

                // update users table image
                $updateUserStmt = $pdo->prepare("
                    UPDATE users
                    SET profile_image = ?
                    WHERE id = ?
                ");
                $updateUserStmt->execute([$profileImagePath, $userId]);

                if ($profile) {
                    $stmt = $pdo->prepare("
                        UPDATE user_profiles
                        SET phone = ?, location = ?, headline = ?, about_me = ?, linkedin_url = ?, github_url = ?, resume_file = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([
                        $phone ?: null,
                        $location ?: null,
                        $headline ?: null,
                        $aboutMe ?: null,
                        $linkedinUrl ?: null,
                        $githubUrl ?: null,
                        $resumeFilePath,
                        $userId
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO user_profiles
                        (user_id, phone, location, headline, about_me, linkedin_url, github_url, resume_file)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $userId,
                        $phone ?: null,
                        $location ?: null,
                        $headline ?: null,
                        $aboutMe ?: null,
                        $linkedinUrl ?: null,
                        $githubUrl ?: null,
                        $resumeFilePath
                    ]);
                }

                $pdo->commit();
                $success = 'Profile updated successfully.';

                // reload profile
                $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ? LIMIT 1");
                $stmt->execute([$userId]);
                $profile = $stmt->fetch();

                $currentProfileImage = $profileImagePath;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <style>
        body{
            margin:0;
            font-family:'Poppins', sans-serif;
            background:#2e2647;
            color:#fff;
        }

        .profile-wrapper{
            max-width:1000px;
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

        .btn{
            display:inline-block;
            padding:12px 18px;
            border-radius:12px;
            text-decoration:none;
            font-weight:600;
            border:none;
            cursor:pointer;
            background:#7f5af0;
            color:#fff;
        }

        .btn:hover{
            background:#6842e3;
        }

        .profile-card{
            background:rgba(255,255,255,0.08);
            backdrop-filter:blur(10px);
            border-radius:22px;
            padding:25px;
            box-shadow:0 12px 24px rgba(0,0,0,0.18);
        }

        .profile-header{
            display:flex;
            align-items:center;
            gap:20px;
            margin-bottom:20px;
            flex-wrap:wrap;
        }

        .profile-header img{
            width:110px;
            height:110px;
            object-fit:cover;
            border-radius:50%;
            background:#ddd;
            border:4px solid rgba(255,255,255,0.25);
        }

        .field, textarea, input[type="file"]{
            width:100%;
            padding:12px 14px;
            border:none;
            border-radius:12px;
            margin:8px 0 14px;
            box-sizing:border-box;
            font-size:14px;
        }

        textarea{
            min-height:120px;
            resize:vertical;
        }

        .row{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:15px;
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

        .muted{
            color:#ddd;
            font-size:14px;
        }

        @media(max-width:768px){
            .row{
                grid-template-columns:1fr;
            }
        }
    </style>
</head>
<body>
<?php include '../includes/user_navbar.php'; ?>
<div class="profile-wrapper">

    <div class="top-bar">
        <h1>My Profile</h1>
        <a href="dashboard.php" class="btn">← Dashboard</a>
    </div>

    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="profile-card">
        <div class="profile-header">
            <img 
                src="<?= !empty($currentProfileImage) ? '../' . htmlspecialchars($currentProfileImage) : '../assets/images/default.png' ?>" 
                alt="Profile Image"
            >
            <div>
                <h2><?= htmlspecialchars($fullName) ?></h2>
                <p class="muted"><?= htmlspecialchars($profile['headline'] ?? 'Complete your profile to make your portfolio stronger.') ?></p>
            </div>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="row">
                <div>
                    <label>Phone</label>
                    <input class="field" type="text" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" placeholder="Phone Number">
                </div>

                <div>
                    <label>Location</label>
                    <input class="field" type="text" name="location" value="<?= htmlspecialchars($profile['location'] ?? '') ?>" placeholder="Your Location">
                </div>
            </div>

            <label>Headline</label>
            <input class="field" type="text" name="headline" value="<?= htmlspecialchars($profile['headline'] ?? '') ?>" placeholder="e.g. Full Stack Developer | BCA Student">

            <label>About Me</label>
            <textarea name="about_me" placeholder="Write a short introduction about yourself..."><?= htmlspecialchars($profile['about_me'] ?? '') ?></textarea>

            <div class="row">
                <div>
                    <label>LinkedIn URL</label>
                    <input class="field" type="url" name="linkedin_url" value="<?= htmlspecialchars($profile['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.com/in/...">
                </div>

                <div>
                    <label>GitHub URL</label>
                    <input class="field" type="url" name="github_url" value="<?= htmlspecialchars($profile['github_url'] ?? '') ?>" placeholder="https://github.com/...">
                </div>
            </div>

            <div class="row">
                <div>
                    <label>Profile Image</label>
                    <input class="field" type="file" name="profile_image" accept=".jpg,.jpeg,.png,.webp">
                </div>

                <div>
                    <label>Resume File</label>
                    <input class="field" type="file" name="resume_file" accept=".pdf,.doc,.docx">
                    <?php if (!empty($profile['resume_file'])): ?>
                        <p class="muted">
                            Current Resume:
                            <a href="../<?= htmlspecialchars($profile['resume_file']) ?>" target="_blank" style="color:#fff;">View Resume</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn">Save Profile</button>
        </form>
    </div>

</div>

</body>
</html>