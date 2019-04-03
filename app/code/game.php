<?php

class Game {
    public $id;
    public $words;
    public $turn;
    public $state;

    public $playerCount;

    /**
     * @var $game_players GamePlayer[]
     */
    public $game_players;
    public $other;

    static public function getGame($id) {
        $stmt = ConnectDB::getInstance()->query("SELECT * FROM game WHERE id = $id");
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Game');
        $game = $stmt->fetch();
        return $game;
    }

    static public function readByPlayer(Player $player, $limit = 1) {
        $stmt = ConnectDB::getInstance()->query("SELECT g.* FROM game g JOIN game_player gp ON gp.game_id = g.id WHERE gp.player_id = {$player->id}");
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Game');
        if ($limit == 1) {
            return $stmt->fetch();
        } else {
            $games = $stmt->fetchAll();
            foreach($games as $game) {
                // $query = "SELECT p.id, p.name, p.last_seen FROM player p JOIN game_player gp ON gp.player_id = p.id WHERE gp.game_id = {$game->id} AND p.id != {$player->id}";
                $query = "SELECT p.* FROM player p JOIN game_player gp ON gp.player_id = p.id WHERE gp.game_id = {$game->id} AND p.id != {$player->id}";
                $stmt2 = ConnectDB::getInstance()->query($query);
                $stmt2->setFetchMode(PDO::FETCH_CLASS, 'Player', [Player::CLEAN]);
                $game->other = $stmt2->fetch();
            }
        }
        return $games;
    }

    static public function readAllByPlayer(Player $player) {
        return self::readByPlayer($player, 0);
    }

    static public function startGame(Player $player) {
        $query = "SELECT g.*, count(gp.id) as playerCount
                  FROM game g
                    JOIN game_player gp ON gp.game_id = g.id
                  GROUP BY g.id
                  HAVING count(gp.id) = 1
                  LIMIT 1";
        $stmt = ConnectDB::getInstance()->query($query);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Game');
        $game = $stmt->fetch();
        if (!$game) {
            $game = self::createGame($player);
        } else {
            $game->assign($player);
            $game->updateStatus('clue');
        }
        return $game;
    }

    static public function createGame(Player $player) {
        $db = ConnectDB::getInstance();
        $query = "SELECT word FROM words ORDER BY RAND() LIMIT 25";
        $stmt = $db->query($query);
        $all = $stmt->fetchAll(PDO::FETCH_OBJ);
        $db->beginTransaction();
        foreach($all as $obj) {
            Word::increment($obj->word);
            $wordList[] = $obj->word;
        }
        $strList = implode(',', $wordList);
        $query = "INSERT INTO game (words, turn, state) VALUES ('$strList', $player->id, 'waiting')";
        $db->exec($query);
        $gameId = $db->lastInsertId();
        $game = self::getGame($gameId);
        $game->assign($player);
        $db->commit();
        return $game;
    }

    public function getPlayers() {
        $stmt = ConnectDB::getInstance()->query("SELECT * FROM game_player WHERE game_id = {$this->id}");
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'GamePlayer');
        return $stmt->fetchAll();
    }

    public function assign(Player $player) {
        $gplayers = $this->getPlayers();
        if (count($gplayers) === 2) {
            Log::write('Error', 'Game', 'Trying to assign player to full game.');
            return false;
        }
        $opponent = null; $opponentGood = []; $opponentBad = [];
        if (count($gplayers) === 1) {
            $opponent = $gplayers[0];
            $opponentGood = explode(',', $opponent->mywords);
            $opponentBad = explode(',', $opponent->mybad);
        }
        $db = ConnectDB::getInstance();
        $query = "INSERT INTO game_player (game_id, player_id, mywords, mybad) VALUES ({$this->id}, {$player->id}, :mywords, :mybad)";
        $stmt = $db->prepare($query);

        $list = explode(',', $this->words);
        shuffle($list);
        if ($opponent) {
            shuffle($opponentGood);
            $good = array_slice($opponentGood, 0, 3);
            shuffle($opponentBad);
            $good = array_merge($good, array_slice($opponentBad, 0, 1));
            $rest = array_diff($list, $opponentGood, $opponentBad);
            $good = array_merge($good, array_slice($rest, 0, 5));
            $bad = array_slice($rest, 5, 3);
        } else {
            $bad = array_slice($list, 0, 3);
            $good = array_slice($list, 3, 9);
        }
        $stmt->execute([':mywords' => implode(',', $good), ':mybad' => implode(',', $bad)]);
    }

    public function load() {
        if (isset($this->game_players) && count($this->game_players)) {
            return;
        }
        $this->game_players = $this->getPlayers();
        foreach($this->game_players as $gplayer) {
            $gplayer->load();
        }
    }

    /**
     * @param Player $player
     * @return Player
     */
    public function getOpponent(Player $player) {
        $opponent = null;
        foreach($this->game_players as $gplayer) {
            if ($gplayer->player_id != $player->id) {
                $opponent = $gplayer->player;
                break;
            }
        }
        return $opponent;
    }

    public function compileBoard(Player $player) {
        // TODO: Pass word status in every time, then adjust how it displays in mithril
        $wordObjs = [];
        $words = explode(',', $this->words);
        $opponent = $this->getOpponent($player);
        if (!$opponent) $this->turn = $player->id;
        if ($this->game_players[0]->player_id == $player->id)
            $thisPlayer = $this->game_players[0];
        else $thisPlayer = $this->game_players[1];
        $goodWords = explode(',', $thisPlayer->mywords);
        $badWords = explode(',', $thisPlayer->mybad);
        $results = $this->getResults();
        foreach($words as $idx => $word) {
            $wordObj = new \stdClass();
            $wordObj->id = $idx;
            $wordObj->word = $word;
            $wordObj->state = ['clue' => '', 'guess' => ''];

            if (in_array($word, $goodWords))
                $wordObj->state['clue'] = 'green';
            else if (in_array($word, $badWords))
                $wordObj->state['clue'] = 'red';

            if (isset($results[$idx])) {
                if (is_array($results[$idx])) {
                    if (isset($results[$idx][$opponent->id]))
                        $wordObj->state['guess'] = 'bystander';
                    if (isset($results[$idx][$player->id]))
                        $wordObj->state['clue'] = 'bystander';
                } else {
                    $wordObj->state['clue'] = $wordObj->state['guess'] = $results[$idx];
                }
            }
            $wordObjs[$idx] = $wordObj;
        }
        return $wordObjs;
    }

    public function getResults() {
        $results = [];
        foreach($this->game_players as $gplayer) {
            foreach($gplayer->clues as $clue) {
                foreach($clue->guesses as $guess) {
                    if ($guess->result == 'bystander') {
                        $results[$guess->card_number][$gplayer->player_id] = $guess->result;
                    } else {
                        $results[$guess->card_number] = $guess->result;
                    }
                }
            }
        }
        return $results;
    }

    public function getOtherID() {
        foreach($this->game_players as $gp) {
            if ($gp->player_id !== $this->turn) {
                return $gp->player_id;
            }
        }
    }

    /**
     * @param $player Player
     * @return GamePlayer
     */
    public function getGamePlayerFromPlayer(Player $player) {
        foreach($this->game_players as $gp) {
            if ($gp->player_id == $player->id) {
                return $gp;
            }
        }
        return null;
    }

    public function saveClue(Player $player, $clue) {
        if ($this->turn != $player->id) {
            return ['status' => 401, 'msg' => 'Not your turn'];
        }
        if ($this->state != 'clue') {
            return ['status' => 402, 'msg' => 'Waiting on guesses, not clue'];
        }
        // return ['status' => 201, 'clue' => $clue];
        $db = ConnectDB::getInstance();
        $gp = $this->getGamePlayerFromPlayer($player);
        $query = "INSERT INTO clue (game_player_id, word, number) VALUES ({$gp->id}, '{$clue['txtClue']}', {$clue['numClue']})";
        $db->exec($query);
        $clueId = $db->lastInsertId();
        $opponent = $this->getOpponent($player);
        $query = "UPDATE game SET state = 'guess', turn = {$opponent->id} WHERE id = {$this->id}";
        $db->exec($query);
        return ['status' => 200, 'clue' => ['id' => $clueId, 'word' => $clue['txtClue'], 'game_player_id' => $gp->id, 'number' => $clue['numClue'], 'guesses' => []]];
    }

    public function saveGuess(Player $player, $idx) {
        $origState = $this->state;
        $curClue = $this->getCurrentClue();
        if (!$curClue) throw new Exception("Not currently in guess mode", -10);
        $curGuessCount = $curClue->getGuessCount();
        if ($curGuessCount > $curClue->number + 1) {
            throw new Exception("Too many guesses", -11);
        }
        $wordList = explode(',', $this->words);
        $word = $wordList[$idx];
        $clueGP = $this->getGamePlayerFromPlayer($this->getOpponent($player));
        $db = ConnectDB::getInstance();
        $totalAgents = 0;
        if (in_array($word, explode(',', $clueGP->mywords))) {
            $res = 'agent';
            // check win condition
            $query = "SELECT count(*) FROM guess g JOIN clue c ON c.id = g.clue_id JOIN game_player gp ON gp.id =  c.game_player_id WHERE gp.game_id = {$this->id} AND g.result = 'agent'";
            $stmt = $db->query($query);
            $totalAgents = $stmt->fetchColumn(0) + 1;
            if ($totalAgents == 15) {
                $this->state = 'done';
            }
        } else if (in_array($word, explode(',', $clueGP->mybad))) {
            $res = 'assassin';
            $this->state = 'done';
        }
        else $res = 'bystander';
        $query = "INSERT INTO guess (clue_id, card_number, word, result) VALUES ({$curClue->id}, $idx, '$word', '$res')";
        $db->exec($query);
        $guessId = $db->lastInsertId();
        if (($res != 'agent') || ($curGuessCount == $curClue->number)) {
            $this->state = 'clue';
        }
        if ($this->state != $origState) {
            $db->exec("UPDATE game SET state = '{$this->state}' WHERE id = {$this->id}");
        }
        $res = ['guess' => ['id' => $guessId, 'clud_id' => $curClue->id, 'card_number' => $idx, 'word' => $word, 'result' => $res], 'state' => $this->state, 'totalAgents' => $totalAgents];
        return $res;
    }

    public function getCurrentClue() {
        if ($this->state != 'guess') {
            return null;
        }
        $clue = null;
        foreach($this->game_players as $gp) {
            if ($gp->player_id != $this->turn) {
                $clue = end($gp->clues);
                break;
            }
        }
        return $clue;
    }

    public function updateStatus($newStatus, $turn = null) {
        $db = ConnectDB::getInstance();
        $turnSQL = '';
        if ($turn) {
            $turnSQL = ", turn = '$turn' ";
        }
        $query = "UPDATE game SET state = '$newStatus' $turnSQL WHERE id = {$this->id}";
        $db->exec($query);
    }
}

class GamePlayer {
    public $id;
    public $game_id;
    public $player_id;
    public $mywords;
    public $mybad;

    public $player;

    /**
     * @var $clues Clue[]
     */
    public $clues;

    public function load() {
        $this->player = Player::load($this->player_id);
        $this->clues = $this->getClues();
        foreach($this->clues as $clue) {
            $clue->load();
        }
    }

    public function getClues() {
        $query = "SELECT c.* FROM clue c WHERE c.game_player_id = {$this->id}";
        $stmt = ConnectDB::getInstance()->query($query);
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Clue');
        return $stmt->fetchAll();
    }
}

class Clue {
    public $id;
    public $game_player_id;
    public $word;
    public $number;

    /**
     * @var $guesses Guess[]
     */
    public $guesses;

    public function load() {
        $this->guesses = $this->getGuesses();
    }

    /**
     * @return Guess[]
     */
    public function getGuesses() {
        $stmt = ConnectDB::getInstance()->query("SELECT * FROM guess where clue_id = {$this->id}");
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Guess');
        return $stmt->fetchAll();
    }

    public function getGuessCount() {
        return count($this->guesses);
    }
}

class Guess {
    public $id;
    public $clue_id;
    public $card_number;
    public $word;
    public $result;
}
