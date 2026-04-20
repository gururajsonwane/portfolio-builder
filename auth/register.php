<?php
session_start();
require_once '../includes/db.php';

// 🔐 CSRF TOKEN GENERATE
if(empty($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$success = "";

if(isset($_POST['register'])){

    // 🔐 CSRF CHECK
    if($_POST['csrf_token'] !== $_SESSION['csrf_token']){
        die("Invalid request!");
    }

    // 🔹 Get Data
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // 🔹 Validation
    if(empty($name) || empty($email) || empty($password)){
        $error = "All fields are required!";
    }
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = "Invalid email format!";
    }
    elseif(!preg_match("/^[a-zA-Z ]+$/", $name)){
        $error = "Name should contain only letters!";
    }
    elseif(strlen($password) < 6){
        $error = "Password must be at least 6 characters!";
    }
    elseif(!preg_match("/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/", $password)){
        $error = "Password must contain letters and numbers!";
    }
    elseif($password !== $confirm_password){
        $error = "Passwords do not match!";
    }
    else{

        // 🔹 Check Email Exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if($stmt->rowCount() > 0){
            $error = "Email already registered!";
        }
        else{

            // 🔐 Hash Password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // 🔹 Insert User
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");

            if($stmt->execute([$name, $email, $hashedPassword])){

                // 🔥 OPTION 1: AUTO LOGIN
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['full_name'] = $name;

                header("Location: ../user/dashboard.php");
                exit;

                // 🔥 OPTION 2 (if needed instead):
                // header("Location: register.php?success=1");
                // exit;
            } else {
                $error = "Something went wrong!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>

<div class="container">
    <h2>Create Account</h2>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <input type="text" name="full_name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>

        <input type="submit" name="register" value="Register">
    </form>

    <p>Already have account? <a href="login.php">Login</a></p>
</div>

<?php if($error): ?>
<script>
Swal.fire("Error", "<?= htmlspecialchars($error) ?>", "error");
</script>
<?php endif; ?>

</body>
</html>