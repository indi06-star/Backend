<?php
require_once 'config.php';
require_once 'database.php';
require_once 'auth.php';

// ✅ Show all PHP errors (for development only)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ✅ Add CORS headers
header("Access-Control-Allow-Origin: http://127.0.0.1:5501");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// ✅ Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ✅ Create new admin
function signUpAdmin($data) {
    global $conn;

    validateAdminSignup($data); // from auth.php

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

// ✅ Get admin by ID
function getAdminById($id) {
    global $conn;

    $stmt = $conn->prepare("SELECT id, username, email, phone_number FROM admin WHERE id = ?");
    $stmt->bind_param("i", $id);
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

// ✅ Get admin by username
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

// ✅ Update admin by username
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
    $stmt->close();

    echo json_encode(['message' => 'Admin updated successfully.']);
}

// ✅ Update admin by ID
function updateAdminById($id, $data) {
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM admin WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing) {
        http_response_code(404);
        echo json_encode(['message' => 'Admin not found.']);
        return;
    }

    $username = $data['username'] ?? $existing['username'];
    $email = $data['email'] ?? $existing['email'];
    $phone = $data['phone_number'] ?? $existing['phone_number'];

    $stmt = $conn->prepare("UPDATE admin SET username = ?, email = ?, phone_number = ? WHERE id = ?");
    $stmt->bind_param("sssi", $username, $email, $phone, $id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['message' => 'Admin updated successfully by ID.']);
}

// ✅ Delete admin by username
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

// ✅ Delete admin by ID
function deleteAdminById($id) {
    global $conn;

    $stmt = $conn->prepare("DELETE FROM admin WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(404);
        echo json_encode(['message' => 'Admin not found.']);
    } else {
        echo json_encode(['message' => 'Admin deleted successfully.']);
    }

    $stmt->close();
}

// ✅ Handle Routing Based on Method + Action
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

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
    } elseif ($action === 'get') {
        if (isset($_GET['username'])) {
            getAdminByUsername($_GET['username']);
        } elseif (isset($_GET['id'])) {
            getAdminById($_GET['id']);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Missing identifier: Provide either username or id']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid GET action']);
    }

} elseif ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'update') {
        if (isset($_GET['username'])) {
            updateAdminByUsername($_GET['username'], $input);
        } elseif (isset($_GET['id'])) {
            updateAdminById($_GET['id'], $input);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Missing identifier: Provide either username or id']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid PUT action']);
    }

} elseif ($method === 'DELETE') {
    if ($action === 'delete') {
        if (isset($_GET['username'])) {
            deleteAdminByUsername($_GET['username']);
        } elseif (isset($_GET['id'])) {
            deleteAdminById($_GET['id']);
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'Missing identifier: Provide either username or id']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid DELETE action']);
    }

} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
}
