<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}
?>

<div class="navbar">
    <div class="logo">Portfolio Builder</div>

    <div class="nav-links">
        <a href="index.php">Dashboard</a>
        <a href="create_portfolio.php">Create</a>
        <a href="logout.php">Logout</a>
    </div>
</div>