<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /portfolio-builder/auth/login.php");
    exit;
}

require_once __DIR__ . '/db.php';

$userId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'User';

$stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$userData = $stmt->fetch();

$profileImage = !empty($userData['profile_image'])
    ? '/portfolio-builder/' . ltrim($userData['profile_image'], '/')
    : '/portfolio-builder/assets/images/default.png';

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
.user-navbar{
    position: sticky;
    top: 0;
    z-index: 999;
    width: 100%;
    background: rgba(31, 24, 54, 0.92);
    backdrop-filter: blur(14px);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    box-shadow: 0 10px 24px rgba(0,0,0,0.18);
}

.user-navbar-inner{
    max-width: 1250px;
    margin: 0 auto;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.user-navbar-left{
    display: flex;
    align-items: center;
    gap: 28px;
    min-width: 0;
}

.user-logo{
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: #fff;
    flex-shrink: 0;
}

.user-logo-mark{
    width: 42px;
    height: 42px;
    border-radius: 14px;
    background: linear-gradient(135deg, #7f5af0, #4b2e83);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 700;
    box-shadow: 0 10px 18px rgba(127, 90, 240, 0.35);
}

.user-logo-text{
    display: flex;
    flex-direction: column;
    line-height: 1.1;
}

.user-logo-title{
    font-size: 17px;
    font-weight: 700;
    color: #fff;
}

.user-logo-subtitle{
    font-size: 11px;
    color: #d8d1e8;
    letter-spacing: 0.4px;
}

.user-nav-links{
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.user-nav-links a{
    text-decoration: none;
    color: #ddd;
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.25s ease;
}

.user-nav-links a:hover{
    background: rgba(255,255,255,0.08);
    color: #fff;
}

.user-nav-links a.active{
    background: linear-gradient(135deg, #7f5af0, #5d3fe0);
    color: #fff;
    box-shadow: 0 8px 18px rgba(127, 90, 240, 0.25);
}

.user-navbar-right{
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.user-profile-pill{
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255,255,255,0.07);
    padding: 8px 12px 8px 8px;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,0.08);
}

.user-profile-pill img{
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    background: #ddd;
    border: 2px solid rgba(255,255,255,0.18);
}

.user-profile-info{
    display: flex;
    flex-direction: column;
    line-height: 1.1;
}

.user-profile-name{
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    max-width: 150px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-profile-role{
    color: #cfc3ff;
    font-size: 11px;
}

.user-profile-link,
.user-logout-btn{
    text-decoration: none;
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.25s ease;
}

.user-profile-link{
    background: rgba(255,255,255,0.08);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.08);
}

.user-profile-link:hover{
    background: rgba(255,255,255,0.14);
}

.user-logout-btn{
    background: #ff6b6b;
    color: #fff;
}

.user-logout-btn:hover{
    background: #e05252;
}

.user-menu-toggle{
    display: none;
    background: rgba(255,255,255,0.08);
    border: none;
    color: #fff;
    width: 42px;
    height: 42px;
    border-radius: 12px;
    font-size: 18px;
    cursor: pointer;
}

@media (max-width: 980px){
    .user-navbar-inner{
        flex-wrap: wrap;
    }

    .user-menu-toggle{
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .user-navbar-left{
        width: 100%;
        justify-content: space-between;
    }

    .user-nav-links{
        display: none;
        width: 100%;
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
        padding-top: 8px;
    }

    .user-nav-links.open{
        display: flex;
    }

    .user-nav-links a{
        width: 100%;
        box-sizing: border-box;
    }

    .user-navbar-right{
        width: 100%;
        justify-content: space-between;
        flex-wrap: wrap;
    }

    .user-profile-pill{
        flex: 1;
        min-width: 220px;
    }
}

@media (max-width: 600px){
    .user-logo-subtitle,
    .user-profile-role{
        display: none;
    }

    .user-profile-name{
        max-width: 100px;
    }

    .user-navbar-inner{
        padding: 12px 14px;
    }
}
</style>

<nav class="user-navbar">
    <div class="user-navbar-inner">

        <div class="user-navbar-left">
            <a href="/portfolio-builder/user/dashboard.php" class="user-logo">
                <div class="user-logo-mark">PB</div>
                <div class="user-logo-text">
                    <span class="user-logo-title">Portfolio Builder</span>
                    <span class="user-logo-subtitle">Build. Share. Impress.</span>
                </div>
            </a>

            <button class="user-menu-toggle" type="button" onclick="toggleUserNav()">☰</button>

            <div class="user-nav-links" id="userNavLinks">
                <a href="/portfolio-builder/user/dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                <a href="/portfolio-builder/user/my_portfolios.php" class="<?= $currentPage === 'my_portfolios.php' ? 'active' : '' ?>">My Portfolios</a>
                <a href="/portfolio-builder/user/create_portfolio.php" class="<?= $currentPage === 'create_portfolio.php' ? 'active' : '' ?>">Create Portfolio</a>
                <a href="/portfolio-builder/user/profile.php" class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>">My Profile</a>
            </div>
        </div>

        <div class="user-navbar-right">
            <div class="user-profile-pill">
                <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profile">
                <div class="user-profile-info">
                    <span class="user-profile-name"><?= htmlspecialchars($fullName) ?></span>
                    <span class="user-profile-role">User Panel</span>
                </div>
            </div>

            <a href="/portfolio-builder/user/profile.php" class="user-profile-link">Edit Profile</a>
            <a href="/portfolio-builder/auth/logout.php" class="user-logout-btn">Logout</a>
        </div>

    </div>
</nav>

<script>
function toggleUserNav() {
    const nav = document.getElementById('userNavLinks');
    nav.classList.toggle('open');
}
</script>