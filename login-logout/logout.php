<?php
session_start();
session_unset();
session_destroy();
header("Location: NutriDeqN-Login.php");
exit();
?>