<?php
// index.php
header("Content-Type: application/json");

echo json_encode([
    "message" => "Welcome to the Admin Management PHP Backend API",
    "status" => "OK",
    "available_routes" => [
        "POST /user.php?action=signup" => "Create a new admin (requires main admin password)",
        "GET /user.php?action=getAll" => "Fetch all admins",
        "GET /user.php?action=get&username={username}" => "Fetch a specific admin by username",
        "PUT /user.php?action=update&username={username}" => "Update admin details by username",
        "DELETE /user.php?action=delete&username={username}" => "Delete an admin by username"
    ]
]);
