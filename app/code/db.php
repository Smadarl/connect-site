<?php

class ConnectDB
{
    const USER = 'webuser';
    const PASS = 'z*!EgMW08Iw1';

    private static $instance;

    private $pdo;

    private function __constructor() {
        $dsn = "mysql:host=db;dbname=connect";
        $this->pdo = new PDO($dsn, self::USER, self::PASS);
    }

    static public function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ConnectDB();
        }
        return self::$instance;
    }

    public function getPDOObject() {
        return $this->pdo;
    }

}
