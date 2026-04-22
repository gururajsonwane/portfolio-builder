<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /portfolio-builder/auth/login.php");
    exit;
}

require_once __DIR__ . '/db.php';

$adminId = $_SESSION['user_id'];
$adminName = $_SESSION['full_name'] ?? 'Admin';

$stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$adminId]);
$adminData = $stmt->fetch();

$adminImage = !empty($adminData['profile_image'])
    ? '/portfolio-builder/' . ltrim($adminData['profile_image'], '/')
    : '/portfolio-builder/assets/images/default.png';

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
.admin-navbar{
    position: sticky;
    top: 0;
    z-index: 999;
    width: 100%;
    background: rgba(22, 18, 38, 0.94);
    backdrop-filter: blur(14px);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    box-shadow: 0 10px 24px rgba(0,0,0,0.22);
}

.admin-navbar-inner{
    max-width: 1300px;
    margin: 0 auto;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.admin-navbar-left{
    display: flex;
    align-items: center;
    gap: 28px;
    min-width: 0;
}

.admin-logo{
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: #fff;
    flex-shrink: 0;
}

.admin-logo-mark{
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: linear-gradient(135deg, #ff9800, #7f5af0);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 700;
    box-shadow: 0 10px 18px rgba(255, 152, 0, 0.28);
}

.admin-logo-text{
    display: flex;
    flex-direction: column;
    line-height: 1.1;
}

.admin-logo-title{
    font-size: 17px;
    font-weight: 700;
    color: #fff;
}

.admin-logo-subtitle{
    font-size: 11px;
    color: #d8d1e8;
    letter-spacing: 0.4px;
}

.admin-nav-links{
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.admin-nav-links a{
    text-decoration: none;
    color: #ddd;
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.25s ease;
}

.admin-nav-links a:hover{
    background: rgba(255,255,255,0.08);
    color: #fff;
}

.admin-nav-links a.active{
    background: linear-gradient(135deg, #ff9800, #7f5af0);
    color: #fff;
    box-shadow: 0 8px 18px rgba(127, 90, 240, 0.22);
}

.admin-navbar-right{
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.admin-profile-pill{
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255,255,255,0.07);
    padding: 8px 12px 8px 8px;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,0.08);
}

.admin-profile-pill img{
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    background: #ddd;
    border: 2px solid rgba(255,255,255,0.18);
}

.admin-profile-info{
    display: flex;
    flex-direction: column;
    line-height: 1.1;
}

.admin-profile-name{
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    max-width: 150px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.admin-profile-role{
    color: #ffd699;
    font-size: 11px;
}

.admin-dashboard-link,
.admin-logout-btn{
    text-decoration: none;
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.25s ease;
}

.admin-dashboard-link{
    background: rgba(255,255,255,0.08);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.08);
}

.admin-dashboard-link:hover{
    background: rgba(255,255,255,0.14);
}

.admin-logout-btn{
    background: #ff6b6b;
    color: #fff;
}

.admin-logout-btn:hover{
    background: #e05252;
}

.admin-menu-toggle{
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

@media (max-width: 1080px){
    .admin-navbar-inner{
        flex-wrap: wrap;
    }

    .admin-menu-toggle{
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .admin-navbar-left{
        width: 100%;
        justify-content: space-between;
    }

    .admin-nav-links{
        display: none;
        width: 100%;
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
        padding-top: 8px;
    }

    .admin-nav-links.open{
        display: flex;
    }

    .admin-nav-links a{
        width: 100%;
        box-sizing: border-box;
    }

    .admin-navbar-right{
        width: 100%;
        justify-content: space-between;
        flex-wrap: wrap;
    }

    .admin-profile-pill{
        flex: 1;
        min-width: 220px;
    }
}

@media (max-width: 600px){
    .admin-logo-subtitle,
    .admin-profile-role{
        display: none;
    }

    .admin-profile-name{
        max-width: 100px;
    }

    .admin-navbar-inner{
        padding: 12px 14px;
    }
}
</style>

<nav class="admin-navbar">
    <div class="admin-navbar-inner">

        <div class="admin-navbar-left">
            <a href="/portfolio-builder/admin/dashboard.php" class="admin-logo">
                <div class="admin-logo-mark">AP</div>
                <div class="admin-logo-text">
                    <span class="admin-logo-title">Admin Panel</span>
                    <span class="admin-logo-subtitle">Manage Platform Efficiently</span>
                </div>
            </a>

            <button class="admin-menu-toggle" type="button" onclick="toggleAdminNav()">☰</button>

            <div class="admin-nav-links" id="adminNavLinks">
                <a href="/portfolio-builder/admin/dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                <a href="/portfolio-builder/admin/users.php" class="<?= $currentPage === 'users.php' ? 'active' : '' ?>">Users</a>
                <a href="/portfolio-builder/admin/portfolios.php" class="<?= $currentPage === 'portfolios.php' ? 'active' : '' ?>">Portfolios</a>
                <a href="/portfolio-builder/admin/themes.php" class="<?= $currentPage === 'themes.php' ? 'active' : '' ?>">Themes</a>
                <a href="/portfolio-builder/admin/messages.php" class="<?= $currentPage === 'messages.php' ? 'active' : '' ?>">Messages</a>
            </div>
        </div>

        <div class="admin-navbar-right">
            <div class="admin-profile-pill">
                <img src="<?= htmlspecialchars($adminImage) ?>" alt="Admin">
                <div class="admin-profile-info">
                    <span class="admin-profile-name"><?= htmlspecialchars($adminName) ?></span>
                    <span class="admin-profile-role">Administrator</span>
                </div>
            </div>

            <a href="/portfolio-builder/admin/dashboard.php" class="admin-dashboard-link">Admin Home</a>
            <a href="/portfolio-builder/auth/logout.php" class="admin-logout-btn">Logout</a>
        </div>

    </div>
</nav>

<script>
function toggleAdminNav() {
    const nav = document.getElementById('adminNavLinks');
    nav.classList.toggle('open');
}
</script>