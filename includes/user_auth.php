<?php
require_once 'auth.php';

// ❌ admin ला user pages access करू देऊ नको
if($_SESSION['role'] !== 'user'){
    header("Location: /portfolio-builder/admin/dashboard.php");
    exit;
}
?>