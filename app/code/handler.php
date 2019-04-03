<?php

use \Firebase\JWT\JWT;

function handleRequest($auth, Request $request) {
    $player = Player::load($auth->sub);
    $res = ['msg' => 'Unknown method'];
    switch($request->getAPIRoute()) {
        case 'gameList':
            $res = ['player' => $player, 'list' => $player->getGameList()];
            break;
        case 'me':
            $res = ['player' => $player];
            break;
        case 'gameSetup':
            $game = Game::getGame($request->getObjectID());
            // TODO: Verify game is with player
            $game->load();
            $res = gameSetup($player, $game);
            break;
        case 'saveClue':
            $game = Game::getGame($request->getObjectID());
            $game->load();
            $res = $game->saveClue($player, $request->get('clue'));
            break;
        case 'saveGuess':
            $game = Game::getGame($request->getObjectID());
            $game->load();
            $res = $game->saveGuess($player, $request->get('idx'));
            break;
        case 'endTurn':
            $game = Game::getGame($request->getObjectID());
            if (($game->turn != $player->id) || ($game->state != 'guess')) {
                throw new Exception("Invalid game state to end turn", -20);
            }
            $game->updateStatus('clue');
            $res = ['code' => 200, 'status' => 'clue'];
            break;
        case 'opponents':
            $res = ['code' => 200, 'list' => $player->getOpponents()];
            break;
        case 'newGame':
            $other = Player::load($request->get('oppId'));
            $game = Game::createGame($player);
            $game->updateStatus('clue', $player->id);
            $game->assign($other);
            return ['code' => 200, 'gameId' => $game->id];
            break;
    }
    echo json_encode($res, JSON_NUMERIC_CHECK);
    return true;
}

function login(Request $request) {
    $db = ConnectDB::getInstance();
    $query = "SELECT id, name FROM player WHERE email = ? AND password = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$request->get('email'), $request->get('pass')]);
    if ($stmt->rowCount() !== 1) {
        return unauthorized('Invalid login credentials');
    }
    $player = $stmt->fetchObject();

    $expTime = '+4 hours';
    if ($request->get('longlived') == 'ture') {
        $expTime = '+1 year';
    }
    $key = Config::get('auth/jwt_key');
    $token = array(
        "iss" => "https://connect.smada.com/",
        "aud" => "https://connect.smada.com/",
        "sub" => $player->id,
        'iat' => time(),
        "exp" => strtotime($expTime),
    );
    $jwt = JWT::encode($token, $key);
    echo json_encode(['code' => 200, 'token' => $jwt, 'player' => $player]);
}

function unauthorized($msg = '') {
    http_response_code(401);
    echo json_encode(['code' => 401, 'message' => $msg]);
    return true;
}

function register(Request $request) {
    $key = "avenge the fallen";
    echo json_encode(['req' => $_POST]);
}

function gameSetup(Player $player, Game $game) {
    $gameWords = $game->compileBoard($player);
    $other = 0;
    if (count($game->game_players) == 2) {
        $other = $game->getOtherID();
        $clues = [
            $game->game_players[0]->player_id => $game->game_players[0]->clues,
            $game->game_players[1]->player_id => $game->game_players[1]->clues
        ];
    } else {
        $clues = [$game->game_players[0]->player_id => $game->game_players[0]->clues];
    }
    return [
        'board' => $gameWords,
        'clues' => $clues,
        'turn' => $game->turn,
        'other' => $other,
        'state' => $game->state,
        'myId' => $player->id
    ];
}