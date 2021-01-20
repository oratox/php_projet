<?php

namespace wish\control;

class Auth
{
    private $session;

    public function __construct($session)
    {
        $this->session = $session;
    }

    public function restrict($redirection)
    {
        if(!$this->session->read('auth'))
        {
            header("Location: $redirection");
            exit();
        }
    }

    public function connect($user)
    {
        $this->session->write('auth', $user);
    }

    public function logout()
    {
        $this->session->delete('auth');
    }

    public function user()
    {
        if(!$this->session->read('auth'))
            return false;

        return $this->session->read('auth');
    }
}
