<?php
require_once 'auth.php';

// ❌ user ला admin panel मध्ये जाऊ देऊ नको
if($_SESSION['role'] !== 'admin'){
    header("Location: /portfolio-builder/user/dashboard.php");
    exit;
}
?>