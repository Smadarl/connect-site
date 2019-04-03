<?php

class ConnectDB
{
    private static $instance;

    /**
     * @var PDO
     */
    private $pdo;

    private function __construct() {
        $hostName = Config::get('db/host');
        if ($port = Config::get('db/port')) $hostName .= ":$port";
        $dbName = Config::get('db/database');
        $dsn = "mysql:host=$hostName;dbname=$dbName";
        $this->pdo = new PDO($dsn, Config::get('user'), Config::get('db/password'));
    }

    /**
     * @return PDO
     */
    static public function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ConnectDB();
        }
        return self::$instance;
    }

    /**
     * @return Player
     */
    public function getPlayerBySession($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM player WHERE session_id = ?");
        if (!$stmt->execute([$id]) || ($stmt->rowCount() == 0)) {
            return null;
        }
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Player');
        $obj = $stmt->fetch();
        return $obj;
    }

    public function __call($method, $args) {
        if (!method_exists($this->pdo, $method)) {
            Log::write('Error', 'PDO', $method, "Invalid method for pdo");
            return false;
        }
        Log::write('Info', 'PDO', $method, $args);
        return $this->pdo->$method(...$args);
    }

}
