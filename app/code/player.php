<?php

trait fetch_object_clean {
    public function clean() {
        $classKeys = array_keys(get_class_vars(self::class));
        foreach(get_object_vars($this) as $objVar => $val) {
            if (!in_array($objVar, $classKeys)) {
                $this->$objVar = null;
            }
        }
    }
}

// Player class

class Player {
    use fetch_object_clean;

    const CLEAN = 'clean';
    public $id;
    // public $session_id;
    public $created;
    public $last_seen;
    public $name;

    public static $new = false;

    public function __construct($type = null) {
        if ($type == self::CLEAN) {
            $this->clean();
        }
    }

    /**
     * @return Player
     */
    static public function load($id) {
        $db = ConnectDB::getInstance();
        $stmt = $db->prepare("select * from player where id = ?");
        $stmt->execute([$id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Player');
        $player = $stmt->fetch();
        return $player;
    }

    /**
     * @return Player
     */
    public static function getCurrent() {
        $db = ConnectDB::getInstance();
        $cur = null;
        if (isset($_COOKIE['PHPSESSID'])) {
            $cur = $db->getPlayerBySession($_COOKIE['PHPSESSID']);
        }
        if (!$cur) {
            $cur = self::createPlayer(session_id());
        } else {
            $cur->updateLastSeen();
            $cur->last_seen = date('Y-m-d H:i:s');
        }
        return $cur;
    }

    /**
     * @return Player
     */
    public static function createPlayer($sessionId) {
        $query = "INSERT INTO player (session_id, created, last_seen)
                  VALUES ('$sessionId', NOW(), NOW()) ";
        $db = ConnectDB::getInstance();
        $db->beginTransaction();
        $db->exec($query);
        $newId = $db->lastInsertId();
        $db->commit();
        $player = self::load($newId);
        self::$new = true;
        return $player;
    }

    public function updateLastSeen() {
        if (self::$new) {
            return false;
        }
        $stmt = ConnectDB::getInstance()->prepare("UPDATE player SET last_seen = NOW() WHERE id = ?");
        $stmt->execute([$this->id]);
        self::$new = true;
    }

    /**
     * @return Game
     */
    public function getGame() {
        $game = Game::readByPlayer($this);
        if (!$game) {
            Log::write('Info', 'Game', "Creating new game");
            $game = Game::startGame($this);
        }
        return $game;
    }

    /**
     * @return Game[]
     */
    public function getGameList() {
        $games = Game::readAllByPlayer($this);
        return $games;
    }

    /**
     * @return Player[]
     */
    public function getOpponents() {
        $db = ConnectDB::getInstance();
        // TODO: limit to players who don't have an active game with this player
        $stmt = $db->prepare("select * from player where id != ?");
        $stmt->execute([$this->id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Player');
        return $stmt->fetchAll();
    }
}