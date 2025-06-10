<?php
require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';

// Show all PHP errors (good for debugging in dev)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// ✅ Create new admin
function signUpAdmin($data) {
    global $conn;

    validateAdminSignup($data); // this should be defined in auth.php

    $username = $data['username'];
    $email = $data['email'];
    $phone_number = $data['phone_number'];
    $password = $data['password'];

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO admin (username, password_hash, email, phone_number) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $hashedPassword, $email, $phone_number);

    if ($stmt->execute()) {
        echo json_encode([
            'message' => 'Admin signed up successfully',
            'admin' => [
                'id' => $stmt->insert_id,
                'username' => $username,
                'email' => $email,
                'phone_number' => $phone_number,
                'role' => 'admin'
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to sign up admin']);
    }

    $stmt->close();
}

// ✅ Get all admins
function getAllAdmins() {
    global $conn;

    $result = $conn->query("SELECT id, username, email, phone_number FROM admin");

    $admins = [];
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }

    echo json_encode(['admins' => $admins]);
}

// ✅ Get one admin by username
function getAdminByUsername($username) {
    global $conn;

    $stmt = $conn->prepare("SELECT id, username, email, phone_number FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['message' => 'Admin not found.']);
    } else {
        echo json_encode(['admin' => $result->fetch_assoc()]);
    }

    $stmt->close();
}

// ✅ Update admin info
function updateAdminByUsername($username, $data) {
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing) {
        http_response_code(404);
        echo json_encode(['message' => 'Admin not found.']);
        return;
    }

    $newUsername = $data['new_username'] ?? $existing['username'];
    $email = $data['email'] ?? $existing['email'];
    $phone = $data['phone_number'] ?? $existing['phone_number'];

    $stmt = $conn->prepare("UPDATE admin SET username = ?, email = ?, phone_number = ? WHERE username = ?");
    $stmt->bind_param("ssss", $newUsername, $email, $phone, $username);
    $stmt->execute();

    echo json_encode(['message' => 'Admin updated successfully.']);
    $stmt->close();
}

// ✅ Delete admin
function deleteAdminByUsername($username) {
    global $conn;

    $stmt = $conn->prepare("DELETE FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(404);
        echo json_encode(['message' => 'Admin not found.']);
    } else {
        echo json_encode(['message' => 'Admin deleted successfully.']);
    }

    $stmt->close();
}

// ✅ Entry point logic
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $action = $_GET['action'] ?? '';
//     $input = json_decode(file_get_contents('php://input'), true);

//     if ($action === 'signup') {
//         signUpAdmin($input);
//     } else {
//         http_response_code(400);
//         echo json_encode(['message' => 'Invalid action']);
//     }
// } else {
//     http_response_code(405);
//     echo json_encode(['message' => 'Method Not Allowed']);
// }
// ✅ Entry point logic
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json');

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'signup') {
        signUpAdmin($input);
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid POST action']);
    }

} elseif ($method === 'GET') {

    if ($action === 'getAll') {
        getAllAdmins();
    } elseif ($action === 'get' && isset($_GET['username'])) {
        getAdminByUsername($_GET['username']);
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid GET action or missing username']);
    }

} elseif ($method === 'PUT') {

    $input = json_decode(file_get_contents('php://input'), true);
    if ($action === 'update' && isset($_GET['username'])) {
        updateAdminByUsername($_GET['username'], $input);
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid PUT action or missing username']);
    }

} elseif ($method === 'DELETE') {

    if ($action === 'delete' && isset($_GET['username'])) {
        deleteAdminByUsername($_GET['username']);
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid DELETE action or missing username']);
    }

} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
}

