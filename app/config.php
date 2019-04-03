<?php

$ip = ini_get('include_path');

$curPath = dirname(__FILE__);
$addPath = "$curPath/code";
ini_set('include_path', "$ip:$addPath");

class Config {
    const CONFIG_FILE = '/app/config.ini';

    static private $data;
    static private $simple;

    static public function get($key) {
        if (!self::$data) {
            self::load();
        }
        if (isset(self::$simple[$key])) {
            return self::$simple[$key];
        }
        $parts = explode('/', $key);
        if (isset(self::$data[$parts[0]])) {
            if (isset(self::$data[$parts[0]][$parts[1]])) {
                return self::$data[$parts[0]][$parts[1]];
            }
        }
        return null;
    }

    static public function load() {
        self::$simple = parse_ini_file(self::CONFIG_FILE, false);
        self::$data = parse_ini_file(self::CONFIG_FILE, true);
    }
}

require_once('log.php');
require_once('db.php');
require_once('word.php');
require_once('player.php');
require_once('game.php');
require_once('request.php');
require_once('../vendor/autoload.php');

// session_start();
// Request::getInstance();
