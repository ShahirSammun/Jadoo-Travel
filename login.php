<?php
session_start();
require "db.php";

if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true){
    header("Location: index.php");
    exit;
}

$error = "";
if($_SERVER['REQUEST_METHOD'] === "POST"){
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND is_verified=1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 1){
        $user = $result->fetch_assoc();
        if(password_verify($password, $user['password'])){
            $_SESSION['loggedin'] = true;
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_id'] = $user['id']; // store ID for profile etc.
            header("Location: index.php"); 
            exit;
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "Email not registered or not verified yet. Please Sign Up.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Jadoo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-light">
<div class="card p-4 shadow" style="width: 480px; min-height: 300px;">
    <h3 class="mb-3 text-center">Login</h3>

    <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <form method="POST" action="">
        <div class="mb-2">
            <input type="email" class="form-control" name="email" placeholder="Email" required>
        </div>
        <div class="mb-3">
            <input type="password" class="form-control" name="password" placeholder="Password" required>
        </div>
        <div class="mb-3 text-end">
            <a href="forget_pass.php">Forgot Password?</a>
        </div>

        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>

    <p class="mt-3 text-center">
        Don't have an account? <a href="signup.php">Sign Up</a>
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

