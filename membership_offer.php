<?php
include 'api/db.php';
require_once __DIR__ . '/api/csrf.php';

// If accessed directly with .php extension, redirect to clean URL
if (basename($_SERVER['PHP_SELF']) === 'membership_offer.php') {
    header("Location: " . $basePath . "/membership_offer", true, 301);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $basePath . "/login");
    exit;
}

$plan = $_GET['plan'] ?? '';
$valid_plans = ['basic', 'advanced', 'pro'];

if (!in_array($plan, $valid_plans)) {
    // Redirect to homepage or show an error if plan is invalid
    header("Location: " . $basePath . "/homepage");
    exit;
}

// Fetch plan details from translations or a database if available
// For now, let's use hardcoded details based on translations
$plan_details = [
    'basic' => [
        'title' => $t['basic'],
        'price' => '€20',
        'description' => $t['gym_only']
    ],
    'advanced' => [
        'title' => $t['advanced'],
        'price' => '€50',
        'description' => $t['gym_classes']
    ],
    'pro' => [
        'title' => $t['pro'],
        'price' => '€70',
        'description' => $t['trainer_included']
    ]
];

$selected_plan = $plan_details[$plan];
$csrf_token = generate_csrf();

// $basePath is already available from router.php

include 'views/membership_offer.html';
