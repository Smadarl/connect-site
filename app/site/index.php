<?php

// echo '<pre>'; print_r($_REQUEST); print_r($_SERVER); exit();

require_once('../config.php');
require_once('handler.php');

$request = Request::getInstance();

if ($request->getAPIRoute() == 'login') {
    login($request);
    exit();
}

if ($request->getAPIRoute() == 'register') {
    register($request);
    exit();
}

// if (!$request->get('action', false)) {
//     include('index.html');
//     exit();
// }

header('Content-Type: application/json');
if (!($auth = $request->checkAuth())) {
    unauthorized('Invalid token');
    // echo json_encode(['message' => 'Unauthorized']);
    exit();
}

handleRequest($auth, $request);
exit();

handle_ajax($request);

function handle_ajax($request) {
    $player = Player::getCurrent();
    $game = $player->getGame();
    $game->load();
    if ($request->get('action') == 'ajax') {
        try {
            if ($request->get('method') == 'setup') {
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
                echo json_encode([
                    'board' => $gameWords,
                    'clues' => $clues,
                    'turn' => $game->turn,
                    'other' => $other,
                    'state' => $game->state,
                    'myId' => $player->id
                ], JSON_NUMERIC_CHECK);
            } else if ($request->get('method') == 'saveClue') {
                $res = $game->saveClue($player, $request->get('clue'));
                echo json_encode($res);
            } else if ($request->get('method') == 'saveGuess') {
                $res = $game->saveGuess($player, $request->get('idx'));
                echo json_encode($res);
            } else if ($request->get('method') == 'endTurn') {
                if (($game->turn != $player->id) || ($game->state != 'guess')) {
                    throw new Exception("Invalid game state", -20);
                }
                $game->updateStatus('clue');
                echo json_encode(['code' => 200, 'status' => 'clue']);
            }
        } catch (Exception $e) {
            echo json_encode(['type' => 'error', 'status' => $e->getCode(), 'message' => $e->getMessage()]);
        }
    }
}
