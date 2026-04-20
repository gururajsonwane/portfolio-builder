<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($portfolio['name']) ?></title>
    <link rel="stylesheet" href="themes/theme1/style.css">
</head>
<body>

<div class="container">
    <div class="hero">
        <img 
            src="<?= !empty($portfolio['profile_image']) ? htmlspecialchars($portfolio['profile_image']) : 'assets/images/default.png' ?>" 
            alt="Profile" 
            class="profile-image"
        >
        <h1><?= htmlspecialchars($portfolio['name']) ?></h1>
        <p class="type"><?= htmlspecialchars(ucfirst($portfolio['type'])) ?></p>
        <p class="bio"><?= nl2br(htmlspecialchars($portfolio['bio'])) ?></p>

        <?php if (!empty($socialLinks)): ?>
            <div class="social-links">
                <?php foreach ($socialLinks as $social): ?>
                    <a href="<?= htmlspecialchars($social['url']) ?>" target="_blank">
                        <?= htmlspecialchars($social['platform']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <section>
        <h2>Skills</h2>
        <div class="skills">
            <?php if (!empty($skills)): ?>
                <?php foreach ($skills as $skill): ?>
                    <span class="skill-tag">
                        <?= htmlspecialchars($skill['skill_name']) ?>
                        (<?= htmlspecialchars($skill['proficiency']) ?>)
                    </span>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No skills added.</p>
            <?php endif; ?>
        </div>
    </section>

    <section>
        <h2>Projects</h2>
        <?php if (!empty($projects)): ?>
            <?php foreach ($projects as $project): ?>
                <div class="card">
                    <h3><?= htmlspecialchars($project['title']) ?></h3>
                    <p><?= nl2br(htmlspecialchars($project['description'] ?? '')) ?></p>
                    <?php if (!empty($project['project_link'])): ?>
                        <a href="<?= htmlspecialchars($project['project_link']) ?>" target="_blank">View Project</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No projects added.</p>
        <?php endif; ?>
    </section>

    <?php if ($portfolio['type'] === 'fresher'): ?>
        <section>
            <h2>Education</h2>
            <?php if (!empty($education)): ?>
                <?php foreach ($education as $edu): ?>
                    <div class="card">
                        <h3><?= htmlspecialchars($edu['degree'] ?? '') ?></h3>
                        <p><?= htmlspecialchars($edu['college'] ?? '') ?></p>
                        <p><?= htmlspecialchars($edu['year'] ?? '') ?></p>
                        <p><?= htmlspecialchars($edu['percentage'] ?? '') ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No education details added.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($portfolio['type'] === 'experienced'): ?>
        <section>
            <h2>Experience</h2>
            <?php if (!empty($experience)): ?>
                <?php foreach ($experience as $exp): ?>
                    <div class="card">
                        <h3><?= htmlspecialchars($exp['role'] ?? '') ?></h3>
                        <p><?= htmlspecialchars($exp['company'] ?? '') ?></p>
                        <p>
                            <?= htmlspecialchars($exp['start_date'] ?? '') ?>
                            -
                            <?= htmlspecialchars($exp['end_date'] ?? 'Present') ?>
                        </p>
                        <p><?= nl2br(htmlspecialchars($exp['description'] ?? '')) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No experience details added.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section>
        <h2>Portfolio Stats</h2>
        <p>Total Views: <?= (int)$portfolio['views'] + 1 ?></p>
    </section>
</div>

<section>
    <h2>Contact Me</h2>

    <?php if (isset($_GET['success'])): ?>
        <p style="color:green;">Message sent successfully!</p>
    <?php endif; ?>
<form method="POST" action="../portfolio-builder/send_message.php">
    
        <input type="hidden" name="portfolio_id" value="<?= (int)$portfolio['id'] ?>">

        <input type="text" name="name" placeholder="Your Name" required>
        <input type="email" name="email" placeholder="Your Email" required>

        <textarea name="message" placeholder="Your Message..." required></textarea>

        <button type="submit">Send Message</button>
    </form>
</section>

</body>
</html>