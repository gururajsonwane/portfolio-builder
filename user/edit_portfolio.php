
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
$themesStmt = $pdo->query("
    SELECT id, theme_name, preview_image 
    FROM themes 
    ORDER BY id ASC
");
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
        $themeId = (int) ($_POST['theme_id'] ?? 0);

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
        } elseif ($githubUsername !== '' && !preg_match('/^[A-Za-z0-9-]+$/', $githubUsername)) {
            $error = 'GitHub username can contain only letters, numbers, and hyphens.';
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

                // skills
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

                // projects
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

                // education
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
                            $eduStmt->execute([
                                $portfolioId,
                                $degree ?: null,
                                $college ?: null,
                                $year ?: null,
                                $percentage ?: null
                            ]);
                        }
                    }
                }

                // experience
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
                            $expStmt->execute([
                                $portfolioId,
                                $company ?: null,
                                $role ?: null,
                                $start ?: null,
                                $end ?: null,
                                $desc ?: null
                            ]);
                        }
                    }
                }

                // social links
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

                header("Location: /portfolio-builder/" . urlencode($portfolio['slug']));
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

<?php include '../includes/user_navbar.php'; ?>

<div class="page-shell">
    <div class="builder-wrapper">

        <div class="form-panel">
            <h1>Edit Portfolio</h1>

            <?php if (!empty($error)): ?>
                <div class="error-box"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="portfolioForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div class="section">
                    <h3>Basic Details</h3>

                    <input 
                        class="field" 
                        type="text" 
                        name="name" 
                        id="nameInput"
                        value="<?= htmlspecialchars($portfolio['name']) ?>" 
                        placeholder="Your Name / Portfolio Name"
                        required
                    >

                    <div class="row">
                        <select class="field" name="type" id="typeInput" required>
                            <option value="fresher" <?= $portfolio['type'] === 'fresher' ? 'selected' : '' ?>>Fresher</option>
                            <option value="experienced" <?= $portfolio['type'] === 'experienced' ? 'selected' : '' ?>>Experienced</option>
                        </select>

                        <input 
                            class="field" 
                            type="text" 
                            name="github_username" 
                            value="<?= htmlspecialchars($portfolio['github_username'] ?? '') ?>" 
                            placeholder="GitHub Username"
                            pattern="[A-Za-z0-9\-]+"
                            title="Only letters, numbers and hyphens allowed"
                        >
                    </div>

                    <textarea name="bio" id="bioInput" placeholder="Write your bio..." required><?= htmlspecialchars($portfolio['bio']) ?></textarea>

                    <div class="ai-bio-row">
                        <select id="bioStyle" class="field ai-style-select">
                            <option value="professional">Professional</option>
                            <option value="concise">Concise</option>
                            <option value="creative">Creative</option>
                        </select>

                        <button type="button" class="btn" id="improveBioBtn" onclick="improveBioWithAI()">
                            Improve Bio with AI
                        </button>

                        <span id="aiBioStatus" class="ai-status"></span>
                    </div>

                    <label>Profile Image</label>
                    <input type="file" name="profile_image" id="imageInput" accept=".jpg,.jpeg,.png,.webp">
                </div>

                <div class="section">
                    <h3>Select Theme</h3>
                    <div class="theme-grid">
                        <?php foreach ($themes as $theme): ?>
                            <label class="theme-option">
                                <input 
                                    type="radio" 
                                    name="theme_id" 
                                    value="<?= (int)$theme['id'] ?>" 
                                    <?= (int)$portfolio['theme_id'] === (int)$theme['id'] ? 'checked' : '' ?> 
                                    required
                                >
                                <strong><?= htmlspecialchars($theme['theme_name']) ?></strong>
                                <?php if (!empty($theme['preview_image'])): ?>
                                    <img src="../<?= htmlspecialchars($theme['preview_image']) ?>" alt="<?= htmlspecialchars($theme['theme_name']) ?>">
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="section">
                    <h3>Skills</h3>
                    <div id="skillsContainer">
                        <?php if (!empty($skills)): ?>
                            <?php foreach ($skills as $skill): ?>
                                <div class="group removable-item">
                                    <div class="item-toolbar">
                                        <span class="drag-handle">☰ Drag</span>
                                        <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
                                    </div>
                                    <input class="field skill-input" type="text" name="skills[]" value="<?= htmlspecialchars($skill['skill_name']) ?>" placeholder="Skill">
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="group removable-item">
                                <div class="item-toolbar">
                                    <span class="drag-handle">☰ Drag</span>
                                    <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
                                </div>
                                <input class="field skill-input" type="text" name="skills[]" placeholder="Skill">
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addSkill()">+ Add Skill</button>
                </div>

                <div class="section">
                    <h3>Projects</h3>
                    <div id="projectsContainer">
                        <?php if (!empty($projects)): ?>
                            <?php foreach ($projects as $project): ?>
                                <div class="group removable-item project-group">
                                    <div class="item-toolbar">
                                        <span class="drag-handle">☰ Drag</span>
                                        <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
                                    </div>
                                    <input class="field project-title" type="text" name="project_title[]" value="<?= htmlspecialchars($project['title']) ?>" placeholder="Project Title">
                                    <textarea class="project-desc" name="project_desc[]" placeholder="Project Description"><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
                                    <input class="field" type="text" name="project_link[]" value="<?= htmlspecialchars($project['project_link'] ?? '') ?>" placeholder="Project Link">
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="group removable-item project-group">
                                <div class="item-toolbar">
                                    <span class="drag-handle">☰ Drag</span>
                                    <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
                                </div>
                                <input class="field project-title" type="text" name="project_title[]" placeholder="Project Title">
                                <textarea class="project-desc" name="project_desc[]" placeholder="Project Description"></textarea>
                                <input class="field" type="text" name="project_link[]" placeholder="Project Link">
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addProject()">+ Add Project</button>
                </div>

                <div class="section <?= $portfolio['type'] === 'experienced' ? 'hidden' : '' ?>" id="educationSection">
                    <h3>Education</h3>
                    <div id="educationContainer">
                        <?php if (!empty($education)): ?>
                            <?php foreach ($education as $edu): ?>
                                <div class="group removable-item edu-group">
                                    <div class="item-toolbar">
                                        <span class="drag-handle">☰ Drag</span>
                                        <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
                                    </div>
                                    <input class="field edu-degree" type="text" name="degree[]" value="<?= htmlspecialchars($edu['degree'] ?? '') ?>" placeholder="Degree">
                                    <input class="field edu-college" type="text" name="college[]" value="<?= htmlspecialchars($edu['college'] ?? '') ?>" placeholder="College">
                                    <input class="field edu-year" type="text" name="year[]" value="<?= htmlspecialchars($edu['year'] ?? '') ?>" placeholder="Year">
                                    <input class="field" type="text" name="percentage[]" value="<?= htmlspecialchars($edu['percentage'] ?? '') ?>" placeholder="Percentage / CGPA">
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="group removable-item edu-group">
                                <div class="item-toolbar">
                                    <span class="drag-handle">☰ Drag</span>
                                    <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
                                </div>
                                <input class="field edu-degree" type="text" name="degree[]" placeholder="Degree">
                                <input class="field edu-college" type="text" name="college[]" placeholder="College">
                                <input class="field edu-year" type="text" name="year[]" placeholder="Year">
                                <input class="field" type="text" name="percentage[]" placeholder="Percentage / CGPA">
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addEducation()">+ Add Education</button>
                </div>

                <div class="section <?= $portfolio['type'] === 'experienced' ? '' : 'hidden' ?>" id="experienceSection">
                    <h3>Experience</h3>
                    <div id="experienceContainer">
                        <?php if (!empty($experience)): ?>
                            <?php foreach ($experience as $exp): ?>
                                <div class="group removable-item exp-group">
                                    <div class="item-toolbar">
                                        <span class="drag-handle">☰ Drag</span>
                                        <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
                                    </div>
                                    <input class="field exp-company" type="text" name="company[]" value="<?= htmlspecialchars($exp['company'] ?? '') ?>" placeholder="Company">
                                    <input class="field exp-role" type="text" name="role[]" value="<?= htmlspecialchars($exp['role'] ?? '') ?>" placeholder="Role">
                                    <div class="row">
                                        <input class="field" type="date" name="start_date[]" value="<?= htmlspecialchars($exp['start_date'] ?? '') ?>">
                                        <input class="field" type="date" name="end_date[]" value="<?= htmlspecialchars($exp['end_date'] ?? '') ?>">
                                    </div>
                                    <textarea class="exp-desc" name="experience_desc[]" placeholder="Description"><?= htmlspecialchars($exp['description'] ?? '') ?></textarea>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="group removable-item exp-group">
                                <div class="item-toolbar">
                                    <span class="drag-handle">☰ Drag</span>
                                    <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
                                </div>
                                <input class="field exp-company" type="text" name="company[]" placeholder="Company">
                                <input class="field exp-role" type="text" name="role[]" placeholder="Role">
                                <div class="row">
                                    <input class="field" type="date" name="start_date[]">
                                    <input class="field" type="date" name="end_date[]">
                                </div>
                                <textarea class="exp-desc" name="experience_desc[]" placeholder="Description"></textarea>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addExperience()">+ Add Experience</button>
                </div>

                <div class="section">
                    <h3>Social Links</h3>
                    <div id="socialContainer">
                        <?php if (!empty($socialLinks)): ?>
                            <?php foreach ($socialLinks as $social): ?>
                                <div class="group removable-item social-group">
                                    <div class="item-toolbar">
                                        <span class="drag-handle">☰ Drag</span>
                                        <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
                                    </div>
                                    <div class="row">
                                        <input class="field" type="text" name="social_platform[]" value="<?= htmlspecialchars($social['platform']) ?>" placeholder="Platform (GitHub, LinkedIn)">
                                        <input class="field social-url" type="url" name="social_url[]" value="<?= htmlspecialchars($social['url']) ?>" placeholder="https://..." pattern="https?://.+" title="Please enter a valid URL starting with http:// or https://">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="group removable-item social-group">
                                <div class="item-toolbar">
                                    <span class="drag-handle">☰ Drag</span>
                                    <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
                                </div>
                                <div class="row">
                                    <input class="field" type="text" name="social_platform[]" placeholder="Platform (GitHub, LinkedIn)">
                                    <input class="field social-url" type="url" name="social_url[]" placeholder="https://..." pattern="https?://.+" title="Please enter a valid URL starting with http:// or https://">
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addSocial()">+ Add Social Link</button>
                </div>

                <button type="submit" class="btn" id="submitBtn">Update Portfolio</button>
            </form>
        </div>

        <div class="preview-panel">
            <h2>Live Preview</h2>

            <div class="preview-card">
                <div class="preview-header">
                    <img 
                        src="<?= !empty($portfolio['profile_image']) ? '../' . htmlspecialchars($portfolio['profile_image']) : '../assets/images/default.png' ?>" 
                        id="previewImage" 
                        class="preview-image" 
                        alt="Preview"
                    >
                    <h2 id="previewName"><?= htmlspecialchars($portfolio['name']) ?></h2>
                    <p id="previewType"><?= htmlspecialchars(ucfirst($portfolio['type'])) ?></p>
                </div>

                <div class="preview-body">

                    <div class="preview-section">
                        <h4>About Me</h4>
                        <p id="previewBio" class="muted"><?= nl2br(htmlspecialchars($portfolio['bio'])) ?></p>
                    </div>

                    <div class="preview-section">
                        <h4>Skills</h4>
                        <div id="previewSkills" class="skill-tags">
                            <?php if (!empty($skills)): ?>
                                <?php foreach ($skills as $skill): ?>
                                    <span class="skill-tag"><?= htmlspecialchars($skill['skill_name']) ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="muted">No skills added yet</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="preview-section">
                        <h4>Projects</h4>
                        <div id="previewProjects">
                            <?php if (!empty($projects)): ?>
                                <?php foreach ($projects as $project): ?>
                                    <div class="project-item">
                                        <strong><?= htmlspecialchars($project['title']) ?></strong>
                                        <div class="muted"><?= htmlspecialchars($project['description'] ?? '') ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="muted">No projects added yet</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="preview-section <?= $portfolio['type'] === 'experienced' ? 'hidden' : '' ?>" id="previewEducationSection">
                        <h4>Education</h4>
                        <div id="previewEducation">
                            <?php if (!empty($education)): ?>
                                <?php foreach ($education as $edu): ?>
                                    <div class="edu-item">
                                        <strong><?= htmlspecialchars($edu['degree'] ?? 'Degree') ?></strong>
                                        <div class="muted"><?= htmlspecialchars($edu['college'] ?? '') ?></div>
                                        <div class="muted"><?= htmlspecialchars($edu['year'] ?? '') ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="muted">No education details yet</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="preview-section <?= $portfolio['type'] === 'experienced' ? '' : 'hidden' ?>" id="previewExperienceSection">
                        <h4>Experience</h4>
                        <div id="previewExperience">
                            <?php if (!empty($experience)): ?>
                                <?php foreach ($experience as $exp): ?>
                                    <div class="exp-item">
                                        <strong><?= htmlspecialchars($exp['role'] ?? 'Role') ?></strong>
                                        <div class="muted"><?= htmlspecialchars($exp['company'] ?? '') ?></div>
                                        <div class="muted"><?= htmlspecialchars($exp['description'] ?? '') ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="muted">No experience details yet</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="preview-section">
                        <h4>Social Links</h4>
                        <div id="previewSocialLinks">
                            <?php if (!empty($socialLinks)): ?>
                                <?php foreach ($socialLinks as $social): ?>
                                    <div class="social-item">
                                        <strong><?= htmlspecialchars($social['platform']) ?></strong><br>
                                        <a href="<?= htmlspecialchars($social['url']) ?>" target="_blank"><?= htmlspecialchars($social['url']) ?></a>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="muted">No social links added yet</p>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="../assets/js/create_portfolio.js"></script>
</body>
</html>
