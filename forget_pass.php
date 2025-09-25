<?php
session_start();
require 'db.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? AND is_verified = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $token = bin2hex(random_bytes(16));
        $expiry = date("Y-m-d H:i:s", strtotime("+30 minutes")); // token valid 30 min

        // Store token and expiry in DB
        $stmt2 = $conn->prepare("UPDATE users SET password_reset_token=?, token_expiry=? WHERE id=?");
        $stmt2->bind_param("ssi", $token, $expiry, $user['id']);
        $stmt2->execute();
        $stmt2->close();

        $resetLink = "http://localhost/web_frontend/reset_pass.php?token=$token";

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'shahirsammun00@gmail.com';
            $mail->Password   = 'mhdbcznoazyigsgy';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('shahirsammun00@gmail.com', 'Jadoo Travel');
            $mail->addAddress($email, $user['name']);

            $mail->isHTML(true);
            $mail->Subject = 'Reset Your Password';
            $mail->Body    = "<p>Hello {$user['name']},</p>
                              <p>Click the link below to reset your password:</p>
                              <a href='$resetLink'>$resetLink</a>
                              <p>If you didn’t request this, ignore this email.</p>";

            $mail->send();
            $message = "Reset link sent! Check your email.";
        } catch (Exception $e) {
            $message = "Mailer Error: {$mail->ErrorInfo}";
        }

    } else {
        $message = "Email not registered or not verified.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forget Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-light">
<div class="card p-4 shadow" style="width: 400px;">
    <h3 class="mb-3 text-center">Forget Password</h3>
    <?php if($message) echo "<div class='alert alert-info text-center'>$message</div>"; ?>
    <form method="POST">
        <div class="mb-3">
            <input type="email" class="form-control" name="email" placeholder="Enter your email" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
    </form>
    <p class="mt-3 text-center"><a href="login.php">Back to Login</a></p>
</div>
</body>
</html>

