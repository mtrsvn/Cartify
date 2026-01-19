<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = isset($_SESSION['otp_user_id']) ? $_SESSION['otp_user_id'] : null;
    $code = isset($_POST['otp_code']) ? preg_replace('/\D/', '', $_POST['otp_code']) : '';
    if (strlen($code) !== 6) {
        echo json_encode(['success' => false, 'message' => 'OTP must be exactly 6 digits.']);
        exit();
    }

    if ($user_id) {
        $stmt = $conn->prepare("SELECT otp_code, otp_expires FROM users WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($db_otp, $otp_expiry);

        if($stmt->fetch()) {
            if ($db_otp && $otp_expiry) {
                if($db_otp == $code && strtotime($otp_expiry) > time()) {
                    $stmt->close();
                    $stmt2 = $conn->prepare("UPDATE users SET otp_code=NULL, otp_expires=NULL WHERE id=?");
                    $stmt2->bind_param("i", $user_id);
                    $stmt2->execute();
                    $stmt2->close();
                    
                    unset($_SESSION['otp_user_id']);
                    unset($_SESSION['otp_email']);
                    
                    echo json_encode(['success' => true, 'message' => 'Email verified successfully!', 'redirect' => '/SCP/index.php']);
                    exit();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP code.']);
                    exit();
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'No OTP to verify. Already verified?']);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit();
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Session error. Please register again.']);
        exit();
    }
}
?>
