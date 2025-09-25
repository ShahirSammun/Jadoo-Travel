<?php
session_start();
require 'db.php';

$message = "";
$showForm = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT id, email, token_expiry FROM users WHERE password_reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (strtotime($user['token_expiry']) >= time()) {
            $showForm = true;

            if ($_SERVER['REQUEST_METHOD'] === "POST") {
                $newPassword = trim($_POST['password']);
                $confirmPassword = trim($_POST['confirm_password']);

                if ($newPassword !== $confirmPassword) {
                    $message = "Passwords do not match.";
                } elseif (strlen($newPassword) < 6) {
                    $message = "Password must be at least 6 characters.";
                } else {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt2 = $conn->prepare("UPDATE users SET password = ?, password_reset_token = NULL, token_expiry = NULL WHERE id = ?");
                    $stmt2->bind_param("si", $hashedPassword, $user['id']);
                    if ($stmt2->execute()) {
                        $message = "Password reset successful! You can now <a href='login.php'>login</a>.";
                        $showForm = false;
                    } else {
                        $message = "Error updating password.";
                    }
                    $stmt2->close();
                }
            }
        } else {
            $message = "This reset link has expired. Please kindly request a new one.";
        }
    } else {
        $message = "Invalid reset token.";
    }

} else {
    $message = "No reset token provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-light">
<div class="card p-4 shadow" style="width: 400px;">
    <h3 class="mb-3 text-center">Reset Password</h3>
    <?php if($message) echo "<div class='alert alert-info text-center'>$message</div>"; ?>

    <?php if($showForm): ?>
    <form method="POST">
        <div class="mb-3">
            <input type="password" class="form-control" name="password" placeholder="New Password" required>
        </div>
        <div class="mb-3">
            <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required>
        </div>
        <button type="submit" class="btn btn-success w-100">Reset Password</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
