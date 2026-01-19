<?php
session_start();
session_destroy();
header("Location: /SCP/index.php");
exit;
?>