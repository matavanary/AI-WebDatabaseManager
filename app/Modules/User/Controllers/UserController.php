<?php

namespace App\Modules\User\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use PDO;
use PDOException;

class UserController extends Controller
{
    public function index()
    {
        // Only administrator can access User Management
        if (!Auth::check() || Session::get('role') !== 'administrator') {
            header('Location: ' . \App\Core\Application::asset('dashboard'));
            return;
        }

        $pdo = Database::getSystemConnection();

        $stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('User.index', ['users' => $users]);
    }

    public function create()
    {
        if (!Auth::check() || Session::get('role') !== 'administrator') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $role = isset($_POST['role']) ? $_POST['role'] : 'viewer';

        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
            return;
        }

        try {
            $pdo = Database::getSystemConnection();

            // Check duplicate
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmtCheck->execute([$username]);
            if ($stmtCheck->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Username already exists.']);
                return;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hash, $role]);

            \App\Core\Logger::log('Create User', null, 'users', "Created user: $username");

            echo json_encode(['success' => true, 'message' => 'User created successfully.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function update()
    {
        if (!Auth::check() || Session::get('role') !== 'administrator') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $id = isset($_POST['id']) ? $_POST['id'] : null;
        $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $role = isset($_POST['role']) ? $_POST['role'] : 'viewer';

        if (empty($id) || empty($username)) {
            echo json_encode(['success' => false, 'message' => 'ID and Username are required.']);
            return;
        }

        try {
            $pdo = Database::getSystemConnection();

            // Check duplicate username for OTHER users
            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmtCheck->execute([$username, $id]);
            if ($stmtCheck->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Username already exists.']);
                return;
            }

            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $hash, $role, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
                $stmt->execute([$username, $role, $id]);
            }

            \App\Core\Logger::log('Update User', null, 'users', "Updated user: $username");

            echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function delete()
    {
        if (!Auth::check() || Session::get('role') !== 'administrator') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $id = isset($_POST['id']) ? $_POST['id'] : null;

        if ($id == Session::get('user_id')) {
            echo json_encode(['success' => false, 'message' => 'You cannot delete yourself.']);
            return;
        }

        try {
            $pdo = Database::getSystemConnection();

            // Get username for log
            $stmtGet = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmtGet->execute([$id]);
            $user = $stmtGet->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$id])) {
                \App\Core\Logger::log('Delete User', null, 'users', "Deleted user: $user");
                echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete user.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
}
