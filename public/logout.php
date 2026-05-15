<?php
// Destroy session and redirects to login page 
// public/logout.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';

session_destroy();
redirect(BASE_URL . 'login.php');
