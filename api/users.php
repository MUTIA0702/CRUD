<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../database/Database.php';

$pdo = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

// Helper function
function getJsonInput() {
    return json_decode(file_get_contents('php://input'), true);
}

switch ($method) {
    // ✅ GET: ambil semua user atau user by ID
    case 'GET':
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($data ?: ["message" => "User not found"]);
        } else {
            $stmt = $pdo->query("SELECT * FROM users");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($data);
        }
        break;

    // ✅ POST: tambah user baru
    case 'POST':
        $input = getJsonInput();
        if (!isset($input['username'], $input['email'], $input['password'])) {
            http_response_code(400);
            echo json_encode(["message" => "Incomplete data"]);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $hashed = password_hash($input['password'], PASSWORD_DEFAULT);
        $stmt->execute([$input['username'], $input['email'], $hashed]);

        echo json_encode(["message" => "User created successfully"]);
        break;

    // ✅ PUT: update data user
    case 'PUT':
        $input = getJsonInput();
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Missing user ID"]);
            exit;
        }

        $fields = [];
        $params = [];

        if (isset($input['username'])) {
            $fields[] = "username = ?";
            $params[] = $input['username'];
        }
        if (isset($input['email'])) {
            $fields[] = "email = ?";
            $params[] = $input['email'];
        }
        if (isset($input['password'])) {
            $fields[] = "password = ?";
            $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
        }

        if (count($fields) > 0) {
            $params[] = $_GET['id'];
            $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(["message" => "User updated successfully"]);
        } else {
            echo json_encode(["message" => "No fields to update"]);
        }
        break;

    // ✅ DELETE: hapus user
    case 'DELETE':
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(["message" => "Missing user ID"]);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        echo json_encode(["message" => "User deleted successfully"]);
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}
