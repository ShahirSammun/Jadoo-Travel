<?php
session_start();
require 'db.php';

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['email'];
$message = "";

$stmt = $conn->prepare("SELECT id, name, email, created_at FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found.");
}
if (isset($_POST['update'])) {
    $newName = trim($_POST['name']);
    $newEmail = trim($_POST['email']);
    $newPassword = trim($_POST['password']);

    if (!empty($newName) && !empty($newEmail)) {
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $message = "Invalid email format.";
        } else {
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password=? WHERE id=?");
                $stmt->bind_param("sssi", $newName, $newEmail, $hashedPassword, $user['id']);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
                $stmt->bind_param("ssi", $newName, $newEmail, $user['id']);
            }
            if ($stmt->execute()) {
                $_SESSION['name'] = $newName;
                $_SESSION['email'] = $newEmail;
                $message = "Profile updated successfully.";
                // Refresh user data
                $user['name'] = $newName;
                $user['email'] = $newEmail;
            } else {
                $message = "Update failed.";
            }
            $stmt->close();
        }
    } else {
        $message = "Name and Email cannot be empty.";
    }
}

if (isset($_POST['delete'])) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $user['id']);
    if ($stmt->execute()) {
        session_destroy();
        header("Location: index.php");
        exit;
    } else {
        $message = "Failed to delete account.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile - Jadoo Travel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm rounded-4 p-4">
                <h3 class="mb-4 text-center">Profile</h3>

                <?php if($message): ?>
                    <div class="alert alert-info text-center"><?php echo $message; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>New Password <small>(leave blank to keep current)</small></label>
                        <input type="password" class="form-control" name="password">
                    </div>
                    <button type="submit" name="update" class="btn btn-primary w-100 mb-2">Update Profile</button>
                    <button type="submit" name="delete" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to delete your account?');">Delete Account</button>
                    <a href="index.php" class="btn btn-secondary w-100 mt-2">Back to Home</a>
                </form>

                <hr>
                <p class="text-center text-muted mb-0">Joined: <?php echo date("M d, Y", strtotime($user['created_at'])); ?></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
