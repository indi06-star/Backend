<?php
// index.php
header("Content-Type: application/json");

echo json_encode([
    "message" => "Welcome to the Attendance PHP Backend API",
    "status" => "OK",
    "available_routes" => [
        "POST /auth.php?action=register" => "Register a new user",
        "POST /auth.php?action=login" => "Login user",
        "POST /forgot.php" => "Request password reset",
        "GET /user.php" => "Fetch user profile (auth required)"
    ]
]);
