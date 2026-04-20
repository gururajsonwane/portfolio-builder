<?php
session_start();
require_once '../includes/db.php';

// जर user already login असेल
if(isset($_SESSION['user_id'])){
    if($_SESSION['role'] === 'admin'){
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../user/dashboard.php");
    }
    exit;
}

$error = "";

if(isset($_POST['login'])){
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // ✅ Validation
    if(empty($email) || empty($password)){
        $error = "All fields are required!";
    }
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = "Invalid email format!";
    }
    else{
        // ✅ Fetch user
        $stmt = $pdo->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if($user){
            // ✅ Password verify
            if(password_verify($password, $user['password'])){
                
                // 🔥 SESSION SECURITY
                session_regenerate_id(true);

                // ✅ Store session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                // ✅ Role based redirect
                if($user['role'] === 'admin'){
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: ../user/dashboard.php");
                }
                exit;

            } else {
                $error = "Incorrect password!";
            }
        } else {
            $error = "Email not found!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>

<div class="container">
    <h2>Welcome Back 👋</h2>

    <form method="POST">
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        
        <input type="submit" name="login" value="Login">
    </form>

    <p>Don't have an account? <a href="register.php">Register</a></p>
</div>

<?php if($error): ?>
<script>
Swal.fire("Login Failed", "<?= $error ?>", "error");
</script>
<?php endif; ?>

</body>
</html>