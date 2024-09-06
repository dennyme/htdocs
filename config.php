<?php
// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

// Database configuration
$db_host = 'localhost';
$db_name = 'minecraft_server_calculator';
$db_user = 'root';
$db_pass = 'DHf3ABTe7kPpjoYPWten';

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone setting
date_default_timezone_set('Asia/Bangkok');



// Other constants
define('SITE_URL', 'http://vm.otms.me');