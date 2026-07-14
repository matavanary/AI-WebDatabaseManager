<?php

namespace App\Core;

class Auth
{
    public static function check()
    {
        return Session::get('user_id') !== null;
    }

    public static function user()
    {
        if (self::check()) {
            return [
                'id' => Session::get('user_id'),
                'username' => Session::get('username'),
                'role' => Session::get('role')
            ];
        }
        return null;
    }

    public static function login($userId, $username, $role)
    {
        Session::set('user_id', $userId);
        Session::set('username', $username);
        Session::set('role', $role);
    }

    public static function logout()
    {
        Session::remove('user_id');
        Session::remove('username');
        Session::remove('role');
        Session::destroy();
    }
}
