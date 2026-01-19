<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
include '../includes/header.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        if (is_locked_out($user)) {
            $minutes = ceil((strtotime($user['lockout_until'])-time())/60);
            echo "<p class='text-muted'>Account locked. Try again in $minutes minutes.</p>";
        } elseif (password_verify($password, $user['password_hash'])) {
            $stmt2 = $conn->prepare("UPDATE users SET failed_logins = 0, lockout_until = NULL WHERE id=?");
            $stmt2->bind_param("i", $user['id']);
            $stmt2->execute();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            echo "<p class='text-muted'>Login successful! Redirecting...</p>";
            log_action($conn, $user['id'], "User logged in");
            header("Refresh:1; url=/SCP/index.php");
            exit();
        } else {
            $failed = $user['failed_logins'] + 1;
            if($failed >= 3){
                $lockout = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                $stmt3 = $conn->prepare("UPDATE users SET failed_logins=?, lockout_until=? WHERE id=?");
                $stmt3->bind_param("isi", $failed, $lockout, $user['id']);
                $stmt3->execute();
                echo "<p class='text-muted'>Account locked for 15 minutes due to 3 failed attempts.</p>";
            } else {
                $stmt3 = $conn->prepare("UPDATE users SET failed_logins=? WHERE id=?");
                $stmt3->bind_param("ii", $failed, $user['id']);
                $stmt3->execute();
                echo "<p class='text-muted'>Wrong credentials. Attempt $failed of 3.</p>";
            }
        }
    } else {
        echo "<p class='text-muted'>User not found.</p>";
    }
    $stmt->close();
}
?>

<div class="form-card mx-auto">
  <h3 class="mb-4">Login</h3>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">Username</label>
      <input type="text" class="form-control" name="username" required>
    </div>
    <div class="mb-4">
      <label class="form-label">Password</label>
      <div class="password-wrapper">
        <input type="password" class="form-control" name="password" id="authLoginPassword" required>
        <span class="password-toggle" onclick="togglePassword('authLoginPassword', this)"><i class="fa-regular fa-eye"></i></span>
      </div>
    </div>
    <button type="submit" class="btn btn-primary w-100">Login</button>
  </form>
  <p class="text-center mt-3 mb-0" style="color: #64748b;">Don't have an account? <a href="register.php">Register</a></p>
</div>

<?php include '../includes/footer.php'; ?>