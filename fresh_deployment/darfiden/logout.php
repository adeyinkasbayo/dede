<?php
require_once __DIR__ . '/src/init.php';

logout_user();
redirect('login.php');
?>