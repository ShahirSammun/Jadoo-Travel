<?php
session_start();
require 'db.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST["name"]);
    $email    = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } else {
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Check if email exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "Email already registered. Please login.";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, is_verified) VALUES (?, ?, ?, 0)");
            $stmt->bind_param("sss", $name, $email, $hashedPassword);

            if ($stmt->execute()) {
                // Generate OTP
                $otp = rand(100000, 999999);
                $_SESSION['otp'] = $otp;
                $_SESSION['email'] = $email;

                // Send OTP via PHPMailer
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
                    $mail->addAddress($email, $name);

                    $mail->isHTML(true);
                    $mail->Subject = 'Jadoo Travel - OTP Verification';
                    $mail->Body    = "<h3>Hello $name</h3><p>Your OTP is: <b>$otp</b></p>";

                    $mail->send();
                    header("Location: verify_otp.php");
                    exit;
                } catch (Exception $e) {
                    $message = "OTP sending failed: {$mail->ErrorInfo}";
                }
            } else {
                $message = "Signup failed. Please try again.";
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .signup-card {
            max-width: 450px;
            width: 120%;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="card signup-card p-4">
        <h3 class="text-center mb-4">Sign Up</h3>

        <?php if (!empty($message)): ?>
            <div class="alert alert-danger text-center"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <input type="text" name="name" class="form-control" placeholder="Full Name" required>
            </div>
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-success w-100">Sign Up</button>
        </form>

        <p class="text-center mt-3">
            Already have an account? <a href="login.php">Login</a>
        </p>
    </div>
</body>
</html>
