<?php
require_once '../includes/user_auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$userId = $_SESSION['user_id'];
$portfolioId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($portfolioId <= 0) {
    header("Location: my_portfolios.php");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

// fetch portfolio
$stmt = $pdo->prepare("
    SELECT * 
    FROM portfolios 
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$stmt->execute([$portfolioId, $userId]);
$portfolio = $stmt->fetch();

if (!$portfolio) {
    header("Location: my_portfolios.php");
    exit;
}

// fetch themes
$themesStmt = $pdo->query("SELECT id, theme_name, preview_image FROM themes ORDER BY id ASC");
$themes = $themesStmt->fetchAll();

// fetch related data
$skillsStmt = $pdo->prepare("SELECT * FROM skills WHERE portfolio_id = ? ORDER BY id ASC");
$skillsStmt->execute([$portfolioId]);
$skills = $skillsStmt->fetchAll();

$projectsStmt = $pdo->prepare("SELECT * FROM projects WHERE portfolio_id = ? ORDER BY id ASC");
$projectsStmt->execute([$portfolioId]);
$projects = $projectsStmt->fetchAll();

$educationStmt = $pdo->prepare("SELECT * FROM education WHERE portfolio_id = ? ORDER BY id ASC");
$educationStmt->execute([$portfolioId]);
$education = $educationStmt->fetchAll();

$experienceStmt = $pdo->prepare("SELECT * FROM experience WHERE portfolio_id = ? ORDER BY id ASC");
$experienceStmt->execute([$portfolioId]);
$experience = $experienceStmt->fetchAll();

$socialStmt = $pdo->prepare("SELECT * FROM social_links WHERE portfolio_id = ? ORDER BY id ASC");
$socialStmt->execute([$portfolioId]);
$socialLinks = $socialStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = 'Invalid request.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $githubUsername = trim($_POST['github_username'] ?? '');
        $themeId = (int)($_POST['theme_id'] ?? 0);

        $skillsInput = $_POST['skills'] ?? [];
        $projectTitles = $_POST['project_title'] ?? [];
        $projectDescriptions = $_POST['project_desc'] ?? [];
        $projectLinks = $_POST['project_link'] ?? [];

        $degrees = $_POST['degree'] ?? [];
        $colleges = $_POST['college'] ?? [];
        $years = $_POST['year'] ?? [];
        $percentages = $_POST['percentage'] ?? [];

        $companies = $_POST['company'] ?? [];
        $roles = $_POST['role'] ?? [];
        $startDates = $_POST['start_date'] ?? [];
        $endDates = $_POST['end_date'] ?? [];
        $expDescriptions = $_POST['experience_desc'] ?? [];

        $socialPlatforms = $_POST['social_platform'] ?? [];
        $socialUrls = $_POST['social_url'] ?? [];

        if ($name === '' || $type === '' || $bio === '' || $themeId <= 0) {
            $error = 'Please fill all required fields.';
        } elseif (!in_array($type, ['fresher', 'experienced'], true)) {
            $error = 'Invalid portfolio type.';
        } else {
            try {
                $profileImagePath = $portfolio['profile_image'];

                if (!empty($_FILES['profile_image']['name'])) {
                    $upload = uploadImage($_FILES['profile_image'], '../uploads/profiles/');
                    if (!$upload['success']) {
                        throw new Exception($upload['message']);
                    }
                    $profileImagePath = 'uploads/profiles/' . $upload['filename'];
                }

                $pdo->beginTransaction();

                $updateStmt = $pdo->prepare("
                    UPDATE portfolios
                    SET name = ?, type = ?, bio = ?, github_username = ?, profile_image = ?, theme_id = ?
                    WHERE id = ? AND user_id = ?
                ");
                $updateStmt->execute([
                    $name,
                    $type,
                    $bio,
                    $githubUsername ?: null,
                    $profileImagePath,
                    $themeId,
                    $portfolioId,
                    $userId
                ]);

                // clear old related data
                $pdo->prepare("DELETE FROM skills WHERE portfolio_id = ?")->execute([$portfolioId]);
                $pdo->prepare("DELETE FROM projects WHERE portfolio_id = ?")->execute([$portfolioId]);
                $pdo->prepare("DELETE FROM education WHERE portfolio_id = ?")->execute([$portfolioId]);
                $pdo->prepare("DELETE FROM experience WHERE portfolio_id = ?")->execute([$portfolioId]);
                $pdo->prepare("DELETE FROM social_links WHERE portfolio_id = ?")->execute([$portfolioId]);

                // re-insert skills
                $skillStmt = $pdo->prepare("
                    INSERT INTO skills (portfolio_id, skill_name, proficiency)
                    VALUES (?, ?, ?)
                ");
                foreach ($skillsInput as $skill) {
                    $skill = trim($skill);
                    if ($skill !== '') {
                        $skillStmt->execute([$portfolioId, $skill, 'intermediate']);
                    }
                }

                // re-insert projects
                $projectStmt = $pdo->prepare("
                    INSERT INTO projects (portfolio_id, title, description, project_link, source)
                    VALUES (?, ?, ?, ?, 'manual')
                ");
                for ($i = 0; $i < count($projectTitles); $i++) {
                    $title = trim($projectTitles[$i] ?? '');
                    $desc = trim($projectDescriptions[$i] ?? '');
                    $link = trim($projectLinks[$i] ?? '');

                    if ($title !== '') {
                        $projectStmt->execute([$portfolioId, $title, $desc ?: null, $link ?: null]);
                    }
                }

                // fresher
                if ($type === 'fresher') {
                    $eduStmt = $pdo->prepare("
                        INSERT INTO education (portfolio_id, degree, college, year, percentage)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    for ($i = 0; $i < count($degrees); $i++) {
                        $degree = trim($degrees[$i] ?? '');
                        $college = trim($colleges[$i] ?? '');
                        $year = trim($years[$i] ?? '');
                        $percentage = trim($percentages[$i] ?? '');

                        if ($degree !== '' || $college !== '' || $year !== '' || $percentage !== '') {
                            $eduStmt->execute([$portfolioId, $degree ?: null, $college ?: null, $year ?: null, $percentage ?: null]);
                        }
                    }
                }

                // experienced
                if ($type === 'experienced') {
                    $expStmt = $pdo->prepare("
                        INSERT INTO experience (portfolio_id, company, role, start_date, end_date, description)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    for ($i = 0; $i < count($companies); $i++) {
                        $company = trim($companies[$i] ?? '');
                        $role = trim($roles[$i] ?? '');
                        $start = trim($startDates[$i] ?? '');
                        $end = trim($endDates[$i] ?? '');
                        $desc = trim($expDescriptions[$i] ?? '');

                        if ($company !== '' || $role !== '' || $start !== '' || $end !== '' || $desc !== '') {
                            $expStmt->execute([$portfolioId, $company ?: null, $role ?: null, $start ?: null, $end ?: null, $desc ?: null]);
                        }
                    }
                }

                // re-insert social links
                $socialInsertStmt = $pdo->prepare("
                    INSERT INTO social_links (portfolio_id, platform, url)
                    VALUES (?, ?, ?)
                ");
                for ($i = 0; $i < count($socialPlatforms); $i++) {
                    $platform = trim($socialPlatforms[$i] ?? '');
                    $url = trim($socialUrls[$i] ?? '');

                    if ($platform !== '' && $url !== '') {
                        $socialInsertStmt->execute([$portfolioId, $platform, $url]);
                    }
                }

                $pdo->commit();

                header("Location: ../view.php?slug=" . urlencode($portfolio['slug']));
                exit;

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
    <title>Edit Portfolio</title>
    <link rel="stylesheet" href="../assets/css/create_portfolio.css">
</head>
<body>

<div class="builder-wrapper">
    <div class="form-panel">
        <h1>Edit Portfolio</h1>

        <?php if (!empty($error)): ?>
            <div class="error-box"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="section">
                <h3>Basic Details</h3>

                <input class="field" type="text" name="name" value="<?= htmlspecialchars($portfolio['name']) ?>" required>

                <div class="row">
                    <select class="field" name="type" required>
                        <option value="fresher" <?= $portfolio['type'] === 'fresher' ? 'selected' : '' ?>>Fresher</option>
                        <option value="experienced" <?= $portfolio['type'] === 'experienced' ? 'selected' : '' ?>>Experienced</option>
                    </select>

                    <input class="field" type="text" name="github_username" value="<?= htmlspecialchars($portfolio['github_username'] ?? '') ?>" placeholder="GitHub Username">
                </div>

                <textarea name="bio" required><?= htmlspecialchars($portfolio['bio']) ?></textarea>

                <label>Profile Image</label>
                <input type="file" name="profile_image" accept=".jpg,.jpeg,.png,.webp">
            </div>

            <div class="section">
                <h3>Select Theme</h3>
                <div class="theme-grid">
                    <?php foreach ($themes as $theme): ?>
                        <label class="theme-option">
                            <input type="radio" name="theme_id" value="<?= (int)$theme['id'] ?>" <?= (int)$portfolio['theme_id'] === (int)$theme['id'] ? 'checked' : '' ?> required>
                            <strong><?= htmlspecialchars($theme['theme_name']) ?></strong>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="section">
                <h3>Skills</h3>
                <?php if (!empty($skills)): ?>
                    <?php foreach ($skills as $skill): ?>
                        <input class="field" type="text" name="skills[]" value="<?= htmlspecialchars($skill['skill_name']) ?>">
                    <?php endforeach; ?>
                <?php else: ?>
                    <input class="field" type="text" name="skills[]" placeholder="Skill">
                <?php endif; ?>
            </div>

            <div class="section">
                <h3>Projects</h3>
                <?php if (!empty($projects)): ?>
                    <?php foreach ($projects as $project): ?>
                        <div class="group">
                            <input class="field" type="text" name="project_title[]" value="<?= htmlspecialchars($project['title']) ?>" placeholder="Project Title">
                            <textarea name="project_desc[]" placeholder="Project Description"><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
                            <input class="field" type="text" name="project_link[]" value="<?= htmlspecialchars($project['project_link'] ?? '') ?>" placeholder="Project Link">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="group">
                        <input class="field" type="text" name="project_title[]" placeholder="Project Title">
                        <textarea name="project_desc[]" placeholder="Project Description"></textarea>
                        <input class="field" type="text" name="project_link[]" placeholder="Project Link">
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($portfolio['type'] === 'fresher'): ?>
                <div class="section">
                    <h3>Education</h3>
                    <?php if (!empty($education)): ?>
                        <?php foreach ($education as $edu): ?>
                            <div class="group">
                                <input class="field" type="text" name="degree[]" value="<?= htmlspecialchars($edu['degree'] ?? '') ?>" placeholder="Degree">
                                <input class="field" type="text" name="college[]" value="<?= htmlspecialchars($edu['college'] ?? '') ?>" placeholder="College">
                                <input class="field" type="text" name="year[]" value="<?= htmlspecialchars($edu['year'] ?? '') ?>" placeholder="Year">
                                <input class="field" type="text" name="percentage[]" value="<?= htmlspecialchars($edu['percentage'] ?? '') ?>" placeholder="Percentage / CGPA">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($portfolio['type'] === 'experienced'): ?>
                <div class="section">
                    <h3>Experience</h3>
                    <?php if (!empty($experience)): ?>
                        <?php foreach ($experience as $exp): ?>
                            <div class="group">
                                <input class="field" type="text" name="company[]" value="<?= htmlspecialchars($exp['company'] ?? '') ?>" placeholder="Company">
                                <input class="field" type="text" name="role[]" value="<?= htmlspecialchars($exp['role'] ?? '') ?>" placeholder="Role">
                                <div class="row">
                                    <input class="field" type="date" name="start_date[]" value="<?= htmlspecialchars($exp['start_date'] ?? '') ?>">
                                    <input class="field" type="date" name="end_date[]" value="<?= htmlspecialchars($exp['end_date'] ?? '') ?>">
                                </div>
                                <textarea name="experience_desc[]" placeholder="Description"><?= htmlspecialchars($exp['description'] ?? '') ?></textarea>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="section">
                <h3>Social Links</h3>
                <?php if (!empty($socialLinks)): ?>
                    <?php foreach ($socialLinks as $social): ?>
                        <div class="group">
                            <input class="field" type="text" name="social_platform[]" value="<?= htmlspecialchars($social['platform']) ?>" placeholder="Platform">
                            <input class="field" type="url" name="social_url[]" value="<?= htmlspecialchars($social['url']) ?>" placeholder="URL">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="group">
                        <input class="field" type="text" name="social_platform[]" placeholder="Platform">
                        <input class="field" type="url" name="social_url[]" placeholder="URL">
                    </div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn">Update Portfolio</button>
        </form>
    </div>
</div>

</body>
</html>