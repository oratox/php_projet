<?php
namespace wish\bd;

use Illuminate\Database\Capsule\Manager as DB;

class Connexion
{
    public static function start(String $file)
    {
        $db = new DB();

        $config = parse_ini_file(__DIR__. '/../conf/db.config.ini');

        if ($config) $db->addConnection($config);

        $db->setAsGlobal();
        $db->bootEloquent();
    }
}