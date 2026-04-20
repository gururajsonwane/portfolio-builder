<?php
require_once '../includes/user_auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

// themes fetch
$themesStmt = $pdo->query("SELECT id, theme_name, preview_image FROM themes ORDER BY id ASC");
$themes = $themesStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $userId = $_SESSION['user_id'];

        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $githubUsername = trim($_POST['github_username'] ?? '');
        $themeId = (int)($_POST['theme_id'] ?? 0);

        $skills = $_POST['skills'] ?? [];
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
        } elseif ($githubUsername !== '' && !preg_match('/^[a-zA-Z0-9-]+$/', $githubUsername)) {
            $error = 'GitHub username can contain only letters, numbers, and hyphens.';
        } else {
            try {
                $profileImagePath = null;

                if (!empty($_FILES['profile_image']['name'])) {
                    $upload = uploadImage($_FILES['profile_image'], '../uploads/profiles/');
                    if (!$upload['success']) {
                        throw new Exception($upload['message']);
                    }
                    $profileImagePath = 'uploads/profiles/' . $upload['filename'];
                }

                $baseSlug = generateSlug($name);
                $slug = uniqueSlug($pdo, $baseSlug);

                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    INSERT INTO portfolios 
                    (user_id, name, slug, type, bio, github_username, profile_image, theme_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $userId,
                    $name,
                    $slug,
                    $type,
                    $bio,
                    $githubUsername ?: null,
                    $profileImagePath,
                    $themeId
                ]);

                $portfolioId = $pdo->lastInsertId();

                $skillStmt = $pdo->prepare("
                    INSERT INTO skills (portfolio_id, skill_name, proficiency)
                    VALUES (?, ?, ?)
                ");
                foreach ($skills as $skill) {
                    $skill = trim($skill);
                    if ($skill !== '') {
                        $skillStmt->execute([$portfolioId, $skill, 'intermediate']);
                    }
                }

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

                $socialStmt = $pdo->prepare("
                    INSERT INTO social_links (portfolio_id, platform, url)
                    VALUES (?, ?, ?)
                ");
                for ($i = 0; $i < count($socialPlatforms); $i++) {
                    $platform = trim($socialPlatforms[$i] ?? '');
                    $url = trim($socialUrls[$i] ?? '');

                    if ($platform !== '' && $url !== '') {
                        $socialStmt->execute([$portfolioId, $platform, $url]);
                    }
                }

                $pdo->commit();

                unset($_SESSION['csrf_token']);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header("Location: ../view.php?slug=" . urlencode($slug));
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
    <title>Create Portfolio</title>
    <link rel="stylesheet" href="../assets/css/create_portfolio.css">
</head>
<body>

<div class="builder-wrapper">

    <div class="form-panel">
        <h1>Create Portfolio</h1>

        <?php if (!empty($error)): ?>
            <div class="error-box"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="portfolioForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="section">
                <h3>Basic Details</h3>

                <input class="field" type="text" name="name" id="nameInput" placeholder="Your Name / Portfolio Name" required>
<div class="row">
    <select class="field" name="type" id="typeInput" required>
        <option value="">Select Type</option>
        <option value="fresher">Fresher</option>
        <option value="experienced">Experienced</option>
    </select>

    <input
        class="field"
        type="text"
        name="github_username"
        placeholder="GitHub Username (Optional)"
        pattern="[A-Za-z0-9\-]+"
        title="Only letters, numbers and hyphens allowed"
    >
</div>

<button type="button" onclick="importGithub()" class="btn">
    Import from GitHub
</button>
          

                <textarea name="bio" id="bioInput" placeholder="Write your bio..." required></textarea>
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
                            <input type="radio" name="theme_id" value="<?= (int)$theme['id'] ?>" required>
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
                    <div class="group removable-item">
                        <input class="field skill-input" type="text" name="skills[]" placeholder="Skill">
                        <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" onclick="addSkill()">+ Add Skill</button>
            </div>

            <div class="section">
                <h3>Projects</h3>
                <div id="projectsContainer">
                    <div class="group removable-item project-group">
                        <input class="field project-title" type="text" name="project_title[]" placeholder="Project Title">
                        <textarea class="project-desc" name="project_desc[]" placeholder="Project Description"></textarea>
                        <input class="field" type="text" name="project_link[]" placeholder="Project Link">
                        <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" onclick="addProject()">+ Add Project</button>
            </div>

            <div class="section" id="educationSection">
                <h3>Education</h3>
                <div id="educationContainer">
                    <div class="group removable-item edu-group">
                        <input class="field edu-degree" type="text" name="degree[]" placeholder="Degree">
                        <input class="field edu-college" type="text" name="college[]" placeholder="College">
                        <input class="field edu-year" type="text" name="year[]" placeholder="Year">
                        <input class="field" type="text" name="percentage[]" placeholder="Percentage / CGPA">
                        <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" onclick="addEducation()">+ Add Education</button>
            </div>

            <div class="section hidden" id="experienceSection">
                <h3>Experience</h3>
                <div id="experienceContainer">
                    <div class="group removable-item exp-group">
                        <input class="field exp-company" type="text" name="company[]" placeholder="Company">
                        <input class="field exp-role" type="text" name="role[]" placeholder="Role">
                        <div class="row">
                            <input class="field" type="date" name="start_date[]">
                            <input class="field" type="date" name="end_date[]">
                        </div>
                        <textarea class="exp-desc" name="experience_desc[]" placeholder="Description"></textarea>
                        <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" onclick="addExperience()">+ Add Experience</button>
            </div>

            <div class="section">
                <h3>Social Links</h3>
                <div id="socialContainer">
                    <div class="group removable-item social-group">
                        <div class="row">
                            <input class="field" type="text" name="social_platform[]" placeholder="Platform (GitHub, LinkedIn)">
                            <input class="field social-url" type="url" name="social_url[]" placeholder="https://..." pattern="https?://.+" title="Please enter a valid URL starting with http:// or https://">
                        </div>
                        <button type="button" class="btn btn-secondary remove-btn" onclick="removeItem(this)">Remove</button>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" onclick="addSocial()">+ Add Social Link</button>
            </div>

            <button type="submit" class="btn" id="submitBtn">Save Portfolio</button>
        </form>
    </div>

    <div class="preview-panel">
        <h2>Live Preview</h2>

        <div class="preview-card">
            <div class="preview-header">
                <img src="../assets/images/default.png" id="previewImage" class="preview-image" alt="Preview">
                <h2 id="previewName">Your Name</h2>
                <p id="previewType">Your Role Type</p>
            </div>

            <div class="preview-body">

                <div class="preview-section">
                    <h4>About Me</h4>
                    <p id="previewBio" class="muted">Your bio will appear here...</p>
                </div>

                <div class="preview-section">
                    <h4>Skills</h4>
                    <div id="previewSkills" class="skill-tags">
                        <span class="muted">No skills added yet</span>
                    </div>
                </div>

                <div class="preview-section">
                    <h4>Projects</h4>
                    <div id="previewProjects">
                        <p class="muted">No projects added yet</p>
                    </div>
                </div>

                <div class="preview-section" id="previewEducationSection">
                    <h4>Education</h4>
                    <div id="previewEducation">
                        <p class="muted">No education details yet</p>
                    </div>
                </div>

                <div class="preview-section hidden" id="previewExperienceSection">
                    <h4>Experience</h4>
                    <div id="previewExperience">
                        <p class="muted">No experience details yet</p>
                    </div>
                </div>

                <div class="preview-section">
                    <h4>Social Links</h4>
                    <div id="previewSocialLinks">
                        <p class="muted">No social links added yet</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<script src="../assets/js/create_portfolio.js"></script>
</body>
</html>