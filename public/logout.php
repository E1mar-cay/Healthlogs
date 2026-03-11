<?php
session_start();

session_unset();
session_destroy();

header('Location: /HealthLogs/public/login.php');
exit;
