<?php
session_start();
require "db.php";

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_otp = trim($_POST["otp"]);

    // Check if OTP and email exist in session
    if (!isset($_SESSION['otp']) || !isset($_SESSION['email'])) {
        $message = "No OTP found. Please sign up first.";
    } elseif ($user_otp == $_SESSION['otp']) {
        $email = $_SESSION['email'];

        // Update user as verified in database
        $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE email = ?");
        $stmt->bind_param("s", $email);

        if ($stmt->execute()) {
            $success = true;
            $message = "Email verified! Redirecting to login...";
            // Clear session data
            unset($_SESSION['otp']);
            unset($_SESSION['email']);
            header("refresh:2;url=login.php");
        } else {
            $message = "Database error while verifying email.";
        }
        $stmt->close();
    } else {
        $message = "Invalid OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify OTP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        background: #f8f9fa;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }
    .verify-card {
        max-width: 420px;
        width: 100%;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        padding: 30px;
        background: #fff;
    }
</style>
</head>
<body>
<div class="verify-card">
    <h3 class="text-center mb-4">Verify Your Email</h3>

    <?php if (!empty($message)): ?>
        <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?> text-center">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST">
        <div class="mb-3">
            <input type="text" name="otp" class="form-control" placeholder="Enter OTP" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Verify</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>