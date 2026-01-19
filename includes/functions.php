<?php
function log_action($conn, $user_id, $action) {
    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $action);
    $stmt->execute();
    $stmt->close();
}

function is_locked_out($user) {
    if ($user['lockout_until'] && strtotime($user['lockout_until']) > time()) {
        return true;
    }
    return false;
}
?>