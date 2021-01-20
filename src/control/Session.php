<?php

namespace wish\control;

class Session
{
    static $instance;

    public function __construct()
    {
        session_start();
    }

    static function getInstance()
    {
        if(!self::$instance)
            self::$instance = new Session();

        return self::$instance;
    }

    public function read($key)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    public function delete($key)
    {
        if(isset($_SESSION[$key]))
         unset($_SESSION[$key]);
    }

    public function write($key, $value)
    {
        $_SESSION[$key] = $value;
    }
}
