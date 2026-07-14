<?php

namespace App\Modules\Auth\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Application;
use PDO;
use PDOException;

class AuthController extends Controller
{
    public function login()
    {
        if (Auth::check()) {
            header('Location: ' . \App\Core\Application::asset('dashboard'));
            return;
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            if (empty($username) || empty($password)) {
                $error = 'Please enter both username and password.';
            } else {
                try {
                    $db = Database::getSystemConnection();
                    $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
                    $stmt->execute([$username]);
                    $user = $stmt->fetch();

                    if ($user && password_verify($password, $user['password'])) {
                        Auth::login($user['id'], $user['username'], $user['role']);
                        \App\Core\Logger::log('Login', null, null, 'Successful login');
                        header('Location: ' . \App\Core\Application::asset('dashboard'));
                        return;
                    } else {
                        $error = 'Invalid username or password.';
                    }
                } catch (PDOException $e) {
                    // Check if table doesn't exist
                    if (strpos($e->getMessage(), "Table") !== false || strpos($e->getMessage(), "doesn't exist") !== false) {
                        $error = 'System database not setup yet. Please run initial setup.';
                    } else {
                        $error = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        }

        return $this->render('Auth.login', ['error' => $error]);
    }

    public function logout()
    {
        \App\Core\Logger::log('Logout', null, null, 'User logged out');
        Auth::logout();
        header('Location: ' . \App\Core\Application::asset('login'));
    }
}
