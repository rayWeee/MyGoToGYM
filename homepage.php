<?php
require_once 'api/db.php';

$is_logged_in = isset($_SESSION['user_id']);
$buy_url = $is_logged_in ? $basePath . '/membership_offer?plan=' : $basePath . '/login';
$register_or_home = $is_logged_in ? $basePath . '/index' : $basePath . '/login';

include 'views/homepage.html';